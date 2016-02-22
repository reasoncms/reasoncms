#!/usr/bin/php
<?php
/**
 * Entity Archiver (Class)
 * @author Nicholas Mischler'14, Beloit College
 * @package reason
 * @subpackage classes
 */

/**
 * Include Necessary Reason Elements
 * Extensions should include this class file in these calls.
 */
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/admin_actions.php' );

/**
 * Class holding the functionality of the enitity archiver.
 *
 * The Entity Archiver is intended to be used to check which entities of a given type are outdated 
 * (older than a given number of months), and list those entities out for review (including listing 
 * all of the entity's values for archiving). The script can also delete entities which explicitly 
 * expire (events, news, etc.) and can send an email with script output to a given email address.  
 *
 * TL;DR: list old things and remove the obvious ones.
 *
 * This script is limited in what type entities it can delete as to avoid deleting entities critical
 * to Reason. Entities can only be deleted with this script so that users have the option of recovering 
 * entities if needed. If you're interested in expunging entities, set up the Garbage Collector script 
 * (core/scripts/db_maintenance/garbage_collector.php) to regularly expunge entities which have been 
 * deleted for two or more weeks.
 *
 * This class assumes that it and the script will be called in a command line environment and prints 
 * progress and log data for that environment. This script should not be run in a HTML context, as the 
 * execution can not be altered from default parameters.
 *
 * To use the Entity Archiver, run one of the following commands:
 *		- "cd /path/to/your/reason_package && php reason_4.0/lib/core/scripts/archiver/archiver.php [options]"
 *	 			or
 *		- "cd /path/to/your/reason_package/reason_4.0/lib/core/scripts/archiver && php -d include_path=/path/to/your/reason_package/ archiver.php [options]"
 *
 * @author Nicholas Mischler'14, Beloit College
 */
class entityArchiver {

	//Options
	public $options = array(	
		//Entity Selection:
		't:'	=> 'type:',
		'i:' 	=> 'include:',
		'e:' 	=> 'exclude:',
		'n:'	=> 'number:',
		//Outdated Options:
		'l:' 	=> 'limit:',
		'c' 	=> 'created',
		'r'		=> 'datetime',	//"real time"
		'm' 	=> 'modified',
		//Actions:
		'a' 	=> 'archive',
		'd:' 	=> 'delete:',
		//Email
		'o:' 	=> 'email:',	//"to email"
		'f:'	=> 'fromemail:',
		//Utility
		'h'		=> 'help',
	);
	/**
	 * IMPORTANT - Types in this list can be deleted using this script. 
	 * The core list is limited to types which explicitly expire.
	 * Adding just about any other type could result in data loss or a broken Reason instance.
	 * Only add a type after careful consideration of which entities, including those
	 * initially created and absolutely required by Reason, could be removed with the script.
	 */
	public $can_remove_types = array(	
		'event_type',
		'news',
		'job',
		'classified_type',
	);
	/**
	 * Fields which always have ids as their values.
	 * Used to show entity name along with id to be more readable.
	 */
	public $fields_with_ids = array(
		//'id',
		'type',
		'created_by',
		'last_edited_by',
	);
	//Entity Selection:
	public $type			=	'';			//unique name of type of entities
	public $site_include	=	array();	//valid site_types to limit sites to send about
	public $site_exclude	= 	array();	//valid site_types to limit sites to not send about
	public $number_entities	=	0;			//number of entities to limit script to, 0 is all
	//Outdated Options:
	public $time_limit		= 	6;			//months from today before content is outdated, positive integer
	public $time_standard	=	'';			//'created', 'datetime', 'modified'
	//Actions:
	public $archive 		= 	false;		//false: only list name/time true: list entire entity
	public $delete	 		= 	'';			//id or username of user deleting the entities
	//Email
	public $email_subject		=	'Entity Archiver';	//is altered before use by alter_subject()
	public $email_addresses		= 	array();	//addresses to send copy of results to, send nothing if empty
	public $from_email_address	=	'';			//If blank, attempts to use WEBMASTER_EMAIL_ADDRESS.
	//Utility
	public $output			=	'';			//holds the output of the script for both CL and email
	public $notice_log		=	array();	//holds a log of notices to give the user
	
	/**
	 * Execute the script and it's components.
	 * Find outdated entities using given options, list them, and delete if requested/can.
	 */
	function run() 
	{
		//Prevent running outside of command line:
		if (PHP_SAPI != "cli")
			$this->stop_error("The Content Reminder must be run in a command line interface.");
		
		//Print log information:
		global $argv;
		$this->output .= "\n";
		$this->output .= ' * Entity Archiver Script'."\n";
		$this->output .= ' * Executed at '.date("g:i:s a (\G\M\TP) \o\\n F j, Y")."\n";
		$this->output .= ' * ';
		foreach ($argv as $arg)
			$this->output .= $arg." ";
		$this->output .= "\n"."\n";
		
		//Setup instance with options, if given:
		$this->init();
		
		//Print variables/options in readable list:
		$this->output .= $this->list_object_variables();
		
		//Determine which users to email:
		$this->output .= 'Getting outdated entities...';
		$entities = $this->get_type_entities();
		$this->output .= 'done.'."\n";
		
		//List information:
		$this->output .= 'Listing outdated entities...';
		if (!empty($entities))
			$this->output .= $this->describe_entities($entities);
		$this->output .= 'done.'."\n";
		
		//Delete:
		if (($this->delete) && (!empty($entities))) {
			$this->output .= 'Deleting entities...';
			$this->output .= $this->delete_entities($entities);
			$this->output .= 'done.'."\n";
		} else
			$this->output .= 'No entities were set to be deleted.'."\n";
		
		//Append any logged notices:
		$this->output .= ($this->log_has_notice()) ? "\n".$this->get_log()."\n" : "\n";
		
		//Print and email output as necessary:
		$this->release_output();
	}
	
	/**
	 * Get options, if any, varify and apply them.
	 */
	function init() 
	{			
		//Parse out short options from keys:
		$short_options = '';
		$short_keys = array_keys($this->options);
		foreach ($short_keys as $key)
			$short_options .= $key;
		
		//Attempt to get options from request:
		$options = getopt($short_options, $this->options);
		
		//If no options given or help requested, show help text:
		if ((empty($options)) || (isset($options['h']) || isset($options['help'])))
			$this->show_help();
		
		//Set the type to be considered:
		if (isset($options['t'])) 
			$this->set_type($options['t']);
		else if (isset($options['type'])) 
			$this->set_type($options['type']);
		else
			$this->stop_error('A type unique name was not given with the option -t or --type. A valid type unique name is required to continue. The script has stopped.');
		
		//Determine sites to consider:
		if (isset($options['i'])) 
			$this->set_include_sites($options['i']);
		else if (isset($options['include'])) 
			$this->set_include_sites($options['include']);
			
		if (isset($options['e'])) 
			$this->set_exclude_sites($options['e']);
		else if (isset($options['exclude'])) 
			$this->set_exclude_sites($options['exclude']);
			
		//Set limit of entities:
		if (isset($options['n'])) 
			$this->set_entity_number_limit($options['n']);
		else if (isset($options['number'])) 
			$this->set_entity_number_limit($options['number']);
		
		//Set time limit for outdated content:
		if (isset($options['l'])) 
			$this->set_content_time_limit($options['l']);
		else if (isset($options['limit'])) 
			$this->set_content_time_limit($options['limit']);
		
		//Set standard for judging outdated content:
		//Modified > Datetime > Created
		if (isset($options['m']) || isset($options['modified']))
			$this->set_time_standard('last_modified');
		else if (isset($options['r']) || isset($options['datetime'])) {
			//Get an example entity:
			$es = new entity_selector();
			$es->add_type( id_of($this->type) );
			$es->set_num(1);
			$entity = current($es->run_one());
			//Check it for the datetime field:
			if($entity->has_value('datetime'))
				$this->set_time_standard('datetime');
			else
				$this->stop_error('Given type ('.$this->type.') does not have the datetime (-r) field. The script has stopped.' );
							
		} else if (isset($options['c']) || isset($options['created']))
			$this->set_time_standard('creation_date');
		else
			$this->set_time_standard('last_modified');
		
		//Set archive:
		if (isset($options['a']) || isset($options['archive']))
			$this->set_archive(true);
		
		//Set delete (implies archive):
		if (isset($options['d']) || isset($options['delete'])) {
			//Check if type is removable:
			$removeable = (in_array($this->type, $this->can_remove_types)) ? true : false;
			if ($removeable) {
				
				//Set archive:
				$this->set_archive(true);
				
				//Delete:
				if (isset($options['d'])) 
					$this->set_delete($options['d']);
				else if (isset($options['delete'])) 
					$this->set_delete($options['delete']);
					
			//Is not removable:
			} else {
				$this->log_notice('Given type ('.$this->type.') is not removable. Entities will not be deleted.', 'Warning');
			}
		}
			
		//Set email to send copy to:
		if (isset($options['o'])) 
			$this->set_email_address($options['o']);
		else if (isset($options['email'])) 
			$this->set_email_address($options['email']);
		
		//Set the from/reply-to email for this email send:
		if (isset($options['f'])) 
			$this->set_from_email((string)$options['f']);
		else if (isset($options['fromemail'])) 
			$this->set_from_email((string)$options['fromemail']);
			
		//Check from/reply-to email value:
		if (empty($this->from_email_address)) {
			//Try to use Reason default:
			$default = WEBMASTER_EMAIL_ADDRESS;
			if (!empty($default))
				$this->from_email_address = $default;
			else
				$this->stop_error('No from/reply-to address (-f) is set. The script has stopped.');
		}
	}
	
	/**
	 * -h, --help 
	 * Print out how to use the script then exit.
	 */
	function show_help() 
	{
		//Print what exists in $output (namely, execution information):
		$this->release_output();
		
		//Echo out help text:
		echo "This class determines which entities of a given type are outdated, returns a list of said entities to command line and email, and can potentially delete said entities. It may be used simply to notify which entities need attention, or else to remove entities which are no longer required. Only types which explicitly expire (such as events, news posts, etc.) can be deleted with the core script. In order to remove entities of other types, this script must be extended and the 'can_remove_types' list appended. If doing so, you should note the possibility of removing critical entities which could result in data loss or a broken Reason instance.\n";
		echo "\n";
		echo "If you intend to use this script to regularly delete entities, it is highly encouraged that you also use the Garbage Collector script (core/scripts/db_maintenance/garbage_collector.php) to regularly expunge deleted entities from your instance of Reason. You should also log the script output and/or have the output emailed to someone so that the entity information is not forever lost.\n";
		echo "\n";
		echo "Types which can be deleted are: ";
		$num = 0;
		foreach ($this->can_remove_types as $type)
			echo ($num++ === 0) ? $type : ", ".$type;
		echo ".\n";
		echo "\n";
		echo "  Entity Selection: \n";
		echo "	-t	--type		Unique name of type to review/archive/delete. \033[1mThis is required.\033[0m [String]\n";
		echo "	-i	--include	Limit to specific sites, else all. Give as list of unique names separated with ','. [String]\n";
		echo "	-e	--exclude	Exclude specific sites. Give as list of unique names separated with ','. [String]\n";
		echo "	-n	--number	Maximum number of entities to use. [Integer, Non-negative, Default: ".$this->number_entities." (All)]\n";
		echo "  Outdated Options: \n";
		echo "	-l	--limit		Limit, in months, for outdated content. [Integer, Positive, Default: ".$this->time_limit."]\n";
		echo "	-c	--created	Determine outdated with date_created field.\n";
		echo "	-r	--datetime	Determine outdated with datetime field. Overrides -c.\n";
		echo "	-m	--modified	Determine outdated with last_modified field. Default. Overrides -c and -r.\n";
		echo "  Actions: \n";
		echo "	-a	--archive	Print out entire entities. Else, just gives name and date.\n";
		echo "	-d	--delete	Define user_id to delete live, outdated entities. Implies -a. [Integer, Positive]\n";
		echo "  Email: \n";
		echo "	-o	--email		Define an email to send a copy of output to. [String]\n";
		echo "	-f	--fromemail	Define a from/reply-to email; else use Reason default if defined. [String]\n";
		echo "  Utility: \n";
		echo "	-h	--help		This help explanation.\n";
		echo "\n";
	
		//End execution.
		exit(0);	
	}
	
	/*
	 * Set function for -t (string), --type (string)
	 * Set the type of entity to look at. Required option.
	 */
	function set_type($type_unique) 
	{
		//If a valid id, keep and get entity:
		if($id = id_of($type_unique, true, false)) { 
			$entity = new entity($id);
			//If the entity's type is Type
			if (reason_is_entity($entity, 'type'))
				$this->type = $type_unique;
			else
				$this->stop_error('Given type unique name ('.$type_unique.') is not the "type" type. A valid type unique name is required to continue. The script has stopped.');
		}
		else
			$this->stop_error('Given type unique name ('.$type_unique.') does not exist. A valid type unique name is required to continue. The script has stopped.');
	}
	
	/**
	 * Set function for -i (string), --include (string)
	 * Takes string of site unique names.
	 */
	function set_include_sites($string) 
	{
		$this->site_include = $this->_set_sites($string);
	}
	
	/**
	 * Set function for -e (string), --exclude (string)
	 * Takes string of site unique names.
	 */
	function set_exclude_sites($string) 
	{
		$this->site_exclude = $this->_set_sites($string);
	}
	
	/**
	 * Utility for -i and -e.
	 * Parses string and returns array of valid unique names for sites.
	 */
	function _set_sites($string) 
	{
		//Parse string:
		$given_sites = explode(",", $string);
		$valid_sites = array();
		
		//Check valid site_type.
		foreach($given_sites as $site) {
			
			//If a valid id, keep and get entity:
			if($id = id_of($site, true, false)) {
				$entity = new entity($id);
				
				//If the entity's type is Site Type
				if (reason_is_entity($entity, 'site'))
					$valid_sites[] = $site;
				else
					$this->log_notice('Given site unique name ('.$site.') is not the "site" type.');
			}
			else
				$this->log_notice('Given site unique name ('.$site.') does not exist.');
		}
		
		//If no valid sites were found, avoid continuing with all sites by giving error.
		if (empty($valid_sites)) {
			$this->stop_error('Given site unique names for include (-i) or exclude (-e) are all invalid. The script has stopped.');
		} else
			return $valid_sites;
	}
	
	/**
	 * Set function for -n (int), --number (int) 
	 * Number of entities to limit the script to.
	 */
	function set_entity_number_limit($int) 
	{
		//Check for numeric:
		if (!is_numeric($int))
			$this->stop_error('Limit for number of entities (-n) must be a positive integer or 0 (unlimited) for all entities. Given value ('.$int.') is invalid.  The script has stopped.');
		//Cast to integer (if not already):	
		$int = (int)$int;
		//Check for non-negative:
		if ($int < 0)
			$this->stop_error('Limit for number of entities (-n) must be a positive integer or 0 (unlimited) for all entities. Given value ('.$int.', cast to integer) is invalid.  The script has stopped.');
		//Set:
		else
			$this->number_entities = $int;
	}
	
	/**
	 * Set function for -l (int), --limit (int) 
	 * Number of months until content is considered outdated.
	 */
	function set_content_time_limit($int) 
	{
		//Check for numeric:
		if (!is_numeric($int))
			$this->stop_error('Limit for outdated content (-l) must be a positive integer. Given value ('.$int.') is invalid.  The script has stopped.');
		//Cast to integer (if not already):	
		$int = (int)$int;
		//Check for non-negative:
		if ($int <= 0)
			$this->stop_error('Limit for outdated content (-l) must be a positive integer. Given value ('.$int.', cast to integer) is invalid.  The script has stopped.');
		//Set:
		else
			$this->time_limit = $int;
	}
	
	/**
	 * Set function for -c/r/m
	 * Which date of the entity to use at the standard.
	 */
	function set_time_standard($string) 
	{
		$this->time_standard = $string;
	}
	
	/**
	 * Set function for -a, --archive
	 * Set boolean to show all of the entities' information.
	 */
	function set_archive($bool) 
	{
		//Nothing given, assume true.
		if (!isset($bool)) {
			$archive = true;
			$this->log_notice('set_archive('.$bool.') was not given a parameter. Assumed true.');
		}
		
		//Non-boolean given, typecast.
		else if (!is_bool($bool)) {
			$archive = (bool)$bool;	
			$bool_string = ($archive) ? 'true' : 'false';
			$this->log_notice('set_archive('.$bool.') was given a '.gettype($bool).' rather than a boolean. Assumed '.$bool_string.'.');
		}
		
		//Given boolean, assign directly:
		else 
			$archive = $bool;
			
		//Assign to instance:
		$this->archive = $archive;
	}
	
	/**
	 * Set function for -d, --delete
	 * Set id of user to delete entities; required to do so.
	 */
	function set_delete($user_id) 
	{
		$this->delete = $this->_confirm_user_id($user_id, 'delete');
	}
	
	/**
	 * Utility for -d.
	 * Parses integer and returns id of user if valid.
	 */
	function _confirm_user_id($user_id, $action) 
	{
		//Must have value:
		if (empty($user_id)) {
			$this->log_notice('A user id is required to '.$action.' entities. No entities will be '.$action.'d.', 'Warning');
			return NULL;
		}
		//Must be numeric:
		if (!is_numeric($user_id)) {
			$this->log_notice('Given user id ('.$user_id.') must be numeric. No entities will be '.$action.'d.', 'Warning');
			return NULL;
		}
		//Must be positive:
		if ($user_id < 1) {
			$this->log_notice('Given user id ('.$user_id.') must be positive. No entities will be '.$action.'d.', 'Warning');
			return NULL;
		}
		//Must be a valid entity:
		$entity = new entity($user_id);
		if (!$entity->get_values()) {
			$this->log_notice('Given user id ('.$user_id.') does not corespond to a valid entity. No entities will be '.$action.'d.', 'Warning');
			return NULL;
		}
		//Must be a entity of user type:
		if ($entity->get_value('type') !== id_of('user')) {
			$this->log_notice('Given user id ('.$user_id.') is for an entity which is not of the user type. No entities will be '.$action.'d.', 'Warning');
			return NULL;
		}
		//User must be able to delete:
		if(!reason_user_has_privs($user_id, 'delete'))
		{
			$this->log_notice('Given user id ('.$user_id.') does not have the ability to '.$action.' entities. No entities will be '.$action.'d.', 'Warning');
			return NULL;
		}
		
		//Its all good!
		return $user_id;
	}
	
	/**
	 * Set function for -o (string), --email (string) 
	 * Email address to send a copy of the log.
	 */
	function set_email_address($email_string) 
	{
		//Parse string:
		$given_emails = explode(",", $email_string);
		$valid_emails = array();
		
		//Check valid email:
		foreach($given_emails as $email) {
			//If not empty and is an email address:
			if ((!empty($email)) && (filter_var($email, FILTER_VALIDATE_EMAIL)))
				$valid_emails[] = $email;
			else
				$this->log_notice('The given email address ('.$email.') is invalid.');
		}
		
		//If no valid emails were given, avoid continuing with all sites by giving error.
		if (empty($valid_emails)) {
			$this->stop_error('Given emails to send to (-o) are all invalid. The script has stopped.');
		} else
			$this->email_addresses = $valid_emails;
	}
	
	/**
	 * Set function for -f (string), --fromemail (string) 
	 * Email address the emails should appear to be sent from.
	 */
	function set_from_email($email_string) 
	{
		if ((!empty($email_string)) && (filter_var($email_string, FILTER_VALIDATE_EMAIL)))
			//If not empty and is an email address:
			$this->from_email_address = $email_string;
		else
			//Prevent sending emails from an unitended source:
			$this->stop_error('The given from email address (-f) is invalid. The script has stopped.');
	}
	
	/**
	 * Print out the status of all option variables.
	 */
	function list_object_variables() 
	{
		$list = "--------------------------------------------------------------\n";
		$list .= "Type:		".$this->type.' ('.id_of($this->type).')'."\n";
		$list .= "Include:	";
		if (empty($this->site_include))
			$list .= "All\n";
		else {
			$num = 0;
			foreach ($this->site_include as $site)
				$list .= ($num++ === 0) ? $site : ", ".$site;
			$list .= "\n";
		}
		$list .= "Exclude:	";
		if (empty($this->site_exclude))
			$list .= "None\n";
		else {
			$num = 0;
			foreach ($this->site_exclude as $site)
				$list .= ($num++ === 0) ? $site : ", ".$site;
			$list .= "\n";
		}
		$list .= "Number:		";
		$list .= ($this->number_entities) ? $this->number_entities.' (entities, max)'."\n" : 'No Limit'."\n";
		$list .= "Limit:		".$this->time_limit.' (months)'."\n";
		$list .= "Standard:	".$this->time_standard."\n";
		$list .= "Archive:	";
		$list .= ($this->archive) ? "True\n" : "False\n";
		$list .= "Delete:		";
		$list .= ($this->delete) ? 
			$this->delete.' ('.$this->get_name_from_id($this->delete).')'."\n" : 
			"No\n";
		$list .= "Email:		";
		if (empty($this->email_addresses))
			$list .= "None\n";
		else {
			$num = 0;
			foreach ($this->email_addresses as $email)
				$list .= ($num++ === 0) ? $email : ", ".$email;
			$list .= "\n";
		}
		$list .= "From Email:	".$this->from_email_address."\n";
		$list .= "--------------------------------------------------------------\n";
		//Return:
		return $list."\n";
	}
	
	/**
	 * Given a valid id, return the name of the id's entity.
	 */
	 function get_name_from_id($id) 
	 {
		$entity = new entity($id);
		return $entity->get_value('name');
	 }
	
	/**
	 * Get and return entities of given type constrained
	 * to outdated status and given site includes/excludes.
	 */
	function get_type_entities() 
	{
		//Variable to return:
		$entities = array();
		
		//If limiting to a certain set of sites:
		if (!empty($this->site_include)) {
			
			//Exclude sites (not sure who would include then exclude, but for completeness...)
			if (!empty($this->site_exclude))
				$sites = array_diff($this->site_include, $this->site_exclude);
			else
				$sites = $this->site_include;
			
			//May exclude all sites:
			if (!empty($sites)) {
				
				//If limiting, need to find number for all sites:
				if ($this->number_entities > 0) {
					$num_entities = $this->number_entities;
					$num_sites = count($sites);
					if ($num_entities < $num_sites) {
						//Show at least one entity per site:
						$num_ent_per_site = 1;
						$this->log_notice('Number of sites (-i/-e) was less than limit of number of entities (-n). Using one entity per site.');
					} else 
						//Drop remainder (e%s) and divide evenly:
						$num_ent_per_site = (($num_entities - ($num_entities % $num_sites)) / $num_sites);
				}
				
				//For each site, get entities of the given type and add to array:
				foreach($sites as $unique) {
					$es = new entity_selector(id_of($unique));
					$es->add_type(id_of($this->type));
					//Limit entities:
					if ($this->number_entities > 0)
						$es->set_num($num_ent_per_site);
					//Append and avoid duplicates (not that there should be any):
					$entities = $this->array_merge_unique($entities, $es->run_one('','All'));
				}
				
				//Sort entities from multiple sites into time order:
				$this->mergesort_field($entities, $this->time_standard, function($a,$b){
					return strtotime($a)-strtotime($b);
				});
				
			} else
				$this->log_notice("Are you sure you wanted to exclude all of your included sites? <_<'");
			
		//Else if using all sites (or excluding some):
		} else {
			//Get every entity of the given type:
			$es = new entity_selector();
			$es->add_type(id_of($this->type));
			$es->set_order(''.$this->time_standard.' ASC');
			$entities = $es->run_one('','All');
			
			//Exclude entities on excluded sites:
			if (!empty($this->site_exclude)) {
				foreach($this->site_exclude as $unique) {
					$es = new entity_selector( id_of($unique) );
					$es->add_type( id_of($this->type) );
					$entities = array_udiff($entities, $es->run_one('','All'),
						//Remove entities using id:
						function ($obj_a, $obj_b) {
							return $obj_a->id() - $obj_b->id();
						}
					);
				}
			}
			
			//Limit number of entities:
			if ($this->number_entities > 0)
				$entities = array_slice($entities, 0, $this->number_entities);
			
		}
		
		//If no entities were found:
		if (empty($entities))
			$this->stop_error('Given options did not yield any entities of the given type ('.$this->type.'). Either there are no entities of this type, or you have excluded them all with -i and -e. The script has stopped.');
		else {
			//Trim entities down to outdated only:
			$limit = strtotime('-'.$this->time_limit.' month');
			$num_outdated = 0;
			
			//Run through sorted array until outdated division is found:
			foreach($entities as $entity) {
				$date = strtotime($entity->get_value($this->time_standard));
				if ($date >= $limit)
					break;	
				else
					$num_outdated++;
			}
			
			//Set entities to outdated section:
			$entities = array_slice($entities, 0, $num_outdated);
			
			//If there are no outdated entities:
			if (empty($entities)) {
				$this->log_notice('There are no outdated entities of the given type ('.$this->type.') with the given options! :D', 'Hurray');
				return NULL;
			}
			else 
				return $entities;
		}
	}
	
	/**
	 * Add to $output either a simple list of outdated entities
	 * or a complete listing if using -a / --archive.
	 */
	function describe_entities($entities) 
	{
		//Variable to return:
		$listing = "\n\n";
		
		//Start with simple list:
		foreach($entities as $entity) {
			//Get date to use:
			$date = strtotime($entity->get_value($this->time_standard));
			//List basic entity information:
			$listing .= ($this->archive) ? "\033[1m" : ''; //Bold start
			$listing .= (!empty($date)) ? '['.date("Y M d", $date).'] ' : '[No Date Set] ';
			$listing .= '('.$entity->id().') ';
			$listing .= ''.$entity->get_value('name');
			$listing .= ($this->archive) ? "\033[0m" : ''; //Bold end
			
			//Append all values for archival:
			if ($this->archive) {
				$listing .= "\n";
				foreach($entity->get_values() as $key => $value) {
					$listing .= '     '; //indent, 5 spaces
					$listing .= '['.$key.'] => '.$value;
					//Try to include names to ids:
					if (!empty($value)) {
						//Limit to only fields that use ids:
						if (in_array($key, $this->fields_with_ids)) {
							$listing .= ' ('.$this->get_name_from_id($value).')';
						}
					}
					$listing .= "\n";
				}
			}
			
			$listing .= "\n";
		}
				
		//Return listing:
		return $listing."\n";
	}
	
	/**
	 * Delete entities. (PRINT status.)
	 */
	function delete_entities($entities) 
	{		
		//Variable to return:
		$listing = '';
		
		//Get post-deleter for type if exists:
		$type = new entity( id_of($this->type) );
		$post_deleter_filename = $type->get_value( 'custom_post_deleter' );
		if(!(empty($post_deleter_filename))) {
			reason_include_once ( 'content_post_deleters/' . $post_deleter_filename );
			$post_deleter_class_name = $GLOBALS['_content_post_deleter_classes'][$post_deleter_filename];
		}
		
		//Go through each entity:
		foreach($entities as $entity) {
			//Add lead text:
			$listing .= 'Entity ('.$entity->id().') '.$entity->get_value('name').' ';
			//If the entity is deletable, go for deletion.
			$deleteable = $this->entity_is_deletable($entity->id(), $this->delete);
			if ($deleteable === true) {
				$result = reason_update_entity( $entity->id(), $this->delete, array('state' => 'Deleted'), true );
				$listing .= ($result) ? 'was successfuly deleted.'."\n" : 'was not deleted for some reason.'."\n";
				//Run post-deleter if need be:
				if(!(empty($post_deleter_filename))) {
					$pd = new $post_deleter_class_name();
					$vars = array( 'site_id'=>$entity->get_owner()->id(),
								   'type_id'=>id_of($this->type),
								   'id'=>$entity->id(),
								   'user_id'=>$this->delete );
					$pd->init($vars, $entity);
					$pd->run();
				}
			} else
				//List reason for not being deleteable:
				$listing .= '[!] '.$deleteable."\n";
		}
		
		//Return listing:
		return "\n\n".$listing."\n";
	}
	
	/**
	 * Determine if the entity can be deleted.
	 * Returns true if can be deleted, else string with reason.
	 * Borrows heavy from the AdminPage (admin_page.php) and DeleteModule (delete.php) classes.
	 */
	function entity_is_deletable($entity_id, $user_id)
	{
		//id is required
		if((empty($entity_id)) || (empty($user_id)))
			return 'entity_is_deleteable() requires an entity id and user id.';
		//get entity
		$entity = new entity($entity_id);
		//check to make sure it's not already deleted
		if($entity->get_value('state') === 'Deleted')
			return 'Entity is already deleted.';
		//pending state
		if($entity->get_value('state') === 'Pending')
			return 'Entity is set to pending and will not be deleted.';
		//check all one-to-many required relationships and borrowing (dependencies)
		$dbq = new DBSelector;
		$dbq->add_table( 'ar' , 'allowable_relationship' );
		$dbq->add_table( 'r' , 'relationship' );
		$dbq->add_table( 'entity' );
		$dbq->add_field( 'ar' , '*' );
		$dbq->add_field( 'r' , 'entity_a' );
		$dbq->add_field( 'r' , 'entity_b' );
		$dbq->add_field( 'entity' , 'id' , 'e_id' );
		$dbq->add_field( 'entity' , 'name' , 'e_name' );
		$dbq->add_relation( 'ar.connections = "one_to_many"' );
		$dbq->add_relation( 'ar.required = "yes"' );
		$dbq->add_relation( 'r.entity_b = ' . $entity_id );
		$dbq->add_relation( 'r.type = ar.id' );
		$dbq->add_relation( 'entity.id = r.entity_a' );
		$dbq->add_relation( 'entity.state = "Live"' );
		$dbq->add_relation( 'r.entity_b != r.entity_a' );
		if(!empty($dbq) && ($dbq->run()))
			return 'Entity has required relationships, preventing deletion.';
		//check for entity being borrowed
		$sites = get_sites_that_are_borrowing_entity($entity_id);
		if(!empty($sites))
			return 'Entity is currently borrowed, preventing deletion.';
		//User must be able to alter the state field:
		$user = new entity($user_id);
		if(!$entity->user_can_edit_field('state', $user))
			return 'Entity\'s state field can not be altered by the given user.';
		//make sure state is live and give true
		if($entity->get_value('state') === 'Live')
			return true;
		else
			return 'Entity must be Live to be deleted.';
	}
	
	/**
	 * Echo output to the command line.
	 * Also emails output if email exists.
	 */
	function release_output() 
	{
		//Give to command line:
		echo $this->output;
		
		//Send email if address is given:
		if (!empty($this->email_addresses)) {
			
			//Email to send to:
			$to = implode(', ',$this->email_addresses);
			
			//Subject:
			$subject = $this->alter_subject($this->email_subject);
			
			//Message substitutions:
			$search = 	array(	"\n",		"\033[1m", 	"\033[0m",);
			$replace = 	array(	"<br>\r\n",	"<strong>",	"</strong>",);
			
			//Message:
			$message =	'<html>'."\r\n";
			$message .=	'<head>'."\r\n";
			$message .=	'<title>Entity Archiver</title>'."\r\n";
			$message .=	'</head>'."\r\n";
			$message .=	'<body>'."\r\n";
				$message .=	'<div id="entity_archiver" style="font-family:monospace,monospace">'."\r\n";
					$message .= '<p>'.str_replace($search,$replace,$this->output).'</p>'."\r\n";
				$message .=	'</div>'."\r\n";
			$message .=	'</body>'."\r\n";
			$message .=	'</html>'."\r\n";
			
			//Headers:
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: '.$this->from_email_address . "\r\n";
			$headers .= 'Reply-To: '.$this->from_email_address . "\r\n";
			//$headers .= 'To: ' . "\r\n";
			$headers .= 'Cc: ' .$this->from_email_address. "\r\n";
			//$headers .= 'Bcc: ' . "\r\n";
			
			//Send email to the user:
			mail($to, $subject, $message, $headers);
		}
	}
	
	/**
	 * Alter the subject of the email.
	 */
	function alter_subject($subject) 
	{
		return $subject.' ('.$this->type.') ['.date("g:ia m/d/y").']';
	}
	
	
	//Utility --------------------------------------------------------//
	
	/**
	 * Extension of array_merge to account for avoiding unique objects.
	 */
	function array_merge_unique($a1, $a2) 
	{
		$merged = array_merge($a1, $a2);
		$final  = array();
		foreach ($merged as $current) {
			if (!in_array($current, $final))
				$final[] = $current;
		}
		return $final;
	}
	
	/** 
	 * Merge sort utility function able to sort ASC on a given field in a Reason entity.
	 * NOTE: Array is modified by reference.
	 */
	function mergesort_field(&$array, $value = 'name', $cmp_function = 'strcasecmp') 
	{
		// Arrays of size < 2 require no action.
		if (count($array) < 2) return;
		// Split the array in half
		$halfway = count($array) / 2;
		$array1 = array_slice($array, 0, $halfway);
		$array2 = array_slice($array, $halfway);
		// Recurse to sort the two halves
		$this->mergesort_field($array1, $value, $cmp_function);
		$this->mergesort_field($array2, $value, $cmp_function);
		// If all of $array1 is <= all of $array2, just append them.
		if (call_user_func($cmp_function, end($array1)->get_value( $value ), $array2[0]->get_value( $value )) < 1) {
			$array = array_merge($array1, $array2);
			return;
		}
		// Merge the two sorted arrays into a single sorted array
		$array = array();
		$ptr1 = $ptr2 = 0;
		while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
			if (call_user_func($cmp_function, $array1[$ptr1]->get_value( $value ), $array2[$ptr2]->get_value( $value )) < 1) {
				$array[] = $array1[$ptr1++];
			}
			else {
				$array[] = $array2[$ptr2++];
			}
		}
		// Merge the remainder
		while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
		while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];
		return;
	}
	
	/**
	 * Add a notice string to the log listing.
	 */
	function log_notice($string, $header = "Notice") 
	{
		//Make message:
		$message = $header.": ".$string;
		//Add to log, avoiding duplicates:
		if (!in_array($message, $this->notice_log))
			$this->notice_log[] = $message;
	}
	
	/**
	 * Determine if logs exist.
	 */
	function log_has_notice() 
	{
		return (!empty($this->notice_log)) ? true : false;
	}
	
	/**
	 * Obtain the log as a string to be printed.
	 */
	function get_log() 
	{
		$return = '*** Logs ***'."\n";
		foreach ($this->notice_log as $log)
			$return .= $log."\n";
		$return .= "\n";
		return $return;
	}
	
	/**
	 * Report an error, print logs, and end execution.
	 */
	function stop_error($string) 
	{
		trigger_error($string);
		$this->log_notice($string, "ERROR");
		$this->output .= ($this->log_has_notice()) ? "\n"."\n".$this->get_log() : "\n";
		$this->release_output();
		exit(1);
	}
	
} //entityArchiver class end