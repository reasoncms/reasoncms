<?php
/**
 * Reason Content Reminder (Class)
 * @author Nicholas Mischler'14, Beloit College
 * @package reason
 * @subpackage classes
 */

/**
 * Include Necessary Reason Elements
 * Extensions should include this class file in these calls.
 */
include_once( 'reason_header.php' );

/**
 * Class holding the functionality of the content reminder.
 *
 * The Content Reminder is intended to be a cronjob which
 * sends regular emails to Reason users to check and maintain
 * their content. In execution, it is flexible with paramaters
 * to limit who, what, and how much to show. It is also extendable
 * and cutomizable with your own language and settings.
 *
 * It is highly encouraged that developers extend this class 
 * and the script for their own use. 
 *
 * This class assumes that it and the script will be called in a 
 * command line environment and prints progress and log data for 
 * that environment. This script should not be run in a HTML context, 
 * as the execution can not be altered from default parameters.
 *
 * To use the Content Reminder, run the following script:
 *		- /reason_4.0/lib/core/scripts/content_reminder/content_reminder.php
 *
 * Default behavior is as follows:
 * 		- Sends an email...
 *		- to each and every user...
 *		- about all sites...
 *		- using a simple listing...
 *		- drawing attention to pages older than 6 months.
 *
 * For including or excluding user roles and site types, the script
 * will filter results by using both sets if given valid unique name(s).
 * If the same valid unique name is given in both include and exclude,
 * it will be initially included and finally excluded.
 *
 * Includes a notice logging feature which can include useful
 * information about your Reason instance, such as users without
 * sites or sites without pages.
 *
 * @author Nicholas Mischler'14, Beloit College
 */
class contentReminder {

	//Options
	public $options = array(	//General
								't:'	=> 'timelimit:',
								'v' 	=> 'verbose',
								//Users and User Roles
								'p' 	=> 'primary',
								'u:' 	=> 'includeusers:',
								'x:' 	=> 'excludeusers:',
								//Sites & Site Types
								'o'		=> 'outdated',
								'i:' 	=> 'includesites:',
								'e:' 	=> 'excludesites:',
								//Email
								'f:'	=> 'fromemail:',
								//Utility
								'h'		=> 'help',
								's'		=> 'simulate',
							);

	//General
	public $time_limit		= 	6;			//months from last_modified
	public $verbose			=	false;		//false: use simple listing, true: use verbose listing
	
	//Users and User Roles
	public $primary 		= 	false;		//false: send to all users, true: send only to primary
	public $user_include	=	array();	//valid user_roles to limit users to send to
	public $user_exclude	=	array();	//valid user_roles to limit users to not send to
	
	//Sites & Site Types
	public $outdated 		= 	false;		//false: send to all users, true: send only to primary
	public $site_include	=	array();	//valid site_types to limit sites to send about
	public $site_exclude	= 	array();	//valid site_types to limit sites to not send about
	
	//Email
	public $email_subject		=	'Reason Content Reminder';
	public $email_signature		=	'';		//If blank, attempts to use WEBMASTER_NAME.
	public $from_email_address	=	'';		//If blank, attempts to use WEBMASTER_EMAIL_ADDRESS.
	
	//Utility
	public $simulate		=	false;		//false: send emails as normal, true: print out emails
	public $notice_log		=	array();	//holds a log of notices to give the user
	
	/**
	 * Find valid users and sites given options and send emails.
	 */
	function run() 
	{
		//Prevent running outside of command line:
		if (PHP_SAPI != "cli")
			$this->stop_error("The Content Reminder must be run in a command line interface.");
		
		//Print log information:
		global $argv;
		echo "\n";
		echo ' * Reason Content Reminder Email Script'."\n";
		echo ' * Executed at '.date("g:i:s a \o\\n F j, Y")."\n";
		echo ' * ';
		foreach ($argv as $arg)
			echo $arg." ";
		echo "\n"."\n";
		
		//Setup instance with options, if given:
		$this->init();
		
		//Determine which users to email:
		echo 'Getting users...';
		$users = $this->get_valid_users();
		echo 'done.'."\n";
		
		//Determine which sites to email about:
		echo 'Getting sites...';
		$sites = $this->get_valid_sites();
		echo 'done.'."\n";
		
		//Send emails using the above pool of users and sites:
		echo 'Sending emails...'."\n";
		$this->send_emails($users, $sites);
		echo 'done.'."\n";
		
		//List any logged notices after mails have been sent:
		echo ($this->log_has_notice()) ? "\n".$this->get_log()."\n" : '';
	}
	
	/**
	 * Get options, if any, varify, and apply them.
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
		
		//Only if $options were given:
		if (!empty($options)) {
			
			//Show help if requested:
			if (isset($options['h']) || isset($options['help']))
				$this->show_help();
			
			//Set the time limit of outdated content:
			if (isset($options['t'])) 
				$this->set_content_time_limit((int)$options['t']);
			else if (isset($options['timelimit'])) 
				$this->set_content_time_limit((int)$options['timelimit']);
			
			//Set to give users a verbose listing:
			if (isset($options['v']) || isset($options['verbose']))
				$this->set_verbose_list(true);
			
			//Set to send to primary maintainers only:
			if (isset($options['p']) || isset($options['primary']))
				$this->set_only_primary(true);
			
			//Set to limit to certain user roles:
			if (isset($options['u'])) 
				$this->set_include_user_roles((string)$options['u']);
			else if (isset($options['includeusers'])) 
				$this->set_include_user_roles((string)$options['includeusers']);
				
			if (isset($options['x'])) 
				$this->set_exclude_user_roles((string)$options['x']);
			else if (isset($options['excludeusers'])) 
				$this->set_exclude_user_roles((string)$options['excludeusers']);
				
			//Set to show only outdated sites/pages:
			if (isset($options['o']) || isset($options['outdated']))
				$this->set_only_outdated(true);
				
			//Set to limit to certain site types:
			if (isset($options['i'])) 
				$this->set_include_site_types((string)$options['i']);
			else if (isset($options['includesites'])) 
				$this->set_include_site_types((string)$options['includesites']);
				
			if (isset($options['e'])) 
				$this->set_exclude_site_types((string)$options['e']);
			else if (isset($options['excludesites'])) 
				$this->set_exclude_site_types((string)$options['excludesites']);
				
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
					$this->stop_error('No from/reply-to address is set. No emails sent.');
			}
				
			//Set to simulate execution, printing out messages:
			if (isset($options['s']) || isset($options['simulate']))
				$this->set_simulate(true);

		}
	}
	
	/**
	 * -h, --help 
	 * Print out how to use the script then exit.
	 */
	function show_help() 
	{
		//Echo out help text:
		echo "This class sends emails to Reason users reminding them to update content on sites which they have access to. The functionality of the execution can be modified with the following parameters. It is highly recommended that users also extend this class and modify it for their own instance. \n";
		echo "\n";
		echo "  General: \n";
		echo "	-t	--timelimit	Limit, in months, for outdated content. [Integer, Default: ".$this->time_limit."]\n";
		echo "	-v	--verbose	Send users a verbose listing. Else, defaults to a simple listing.\n";
		echo "\n";
		echo "  Users and User Roles: \n";
		echo "	-p	--primary	Limit users to only sites' primary maintainers.\n";
		echo "	-u	--includeusers	Limit users to specific user roles. Give as list of unique names separated with ','. [String]\n";
		echo "	-x	--excludeusers	Exclude users with specific user roles. Give as list of unique names separated with ','. [String]\n";
		echo "\n";
		echo "  Sites and Site Types: \n";
		echo "	-o	--outdated	Limit sites to those with outdated content (pages).\n";
		echo "	-i	--includesites	Limit sites to specific site types. Give as list of unique names separated with ','. [String]\n";
		echo "	-e	--excludesites	Exclude sites with specific site types. Give as list of unique names separated with ','. [String]\n";
		echo "\n";
		echo "  Email: \n";
		echo "	-f	--fromemail	Define a from/reply-to email; should rather be defined in an extension of this class. [String]\n";
		echo "\n";
		echo "  Utility: \n";
		echo "	-h	--help		This help explanation.\n";
		echo "	-s	--simulate	Execute script without sending emails. Prints out email messages and logs.\n";
		echo "\n";
	
		//End execution.
		exit(0);	
	}
	
	/**
	 * Set function for -t (int), --timelimit (int) 
	 * Number of months until content is considered outdated.
	 */
	function set_content_time_limit($int) 
	{
		$this->time_limit = $int;
	}
	
	/**
	 * Set function for -v, --verbose,
	 * Show a verbose listing of sites/pages in email.
	 * Simple: create_list_simple()
	 * Verbose: create_list_verbose()
	 */
	function set_verbose_list($bool) 
	{
		
		//Nothing given, assume true.
		if (!isset($bool)) {
			$verbose = true;
			$this->log_notice('set_verbose_list('.$bool.') was not given a parameter. Assumed true.');
		}
		
		//Non-boolean given, typecast.
		else if (!is_bool($bool)) {
			$verbose = (bool)$bool;	
			$bool_string = ($verbose) ? 'true' : 'false';
			$this->log_notice('set_verbose_list('.$bool.') was given a '.gettype($bool).' rather than a boolean. Assumed '.$bool_string.'.');
		}
		
		//Given boolean, assign directly:
		else 
			$verbose = $bool;
			
		//Assign to instance:
		$this->verbose = $verbose;
		
	}
	
	/**
	 * Set function for -p, --primary
	 * Limit users to primary maintainers only.
	 */
	function set_only_primary($bool) 
	{
		//Nothing given, assume true.
		if (!isset($bool)) {
			$primary = true;
			$this->log_notice('set_only_primary('.$bool.') was not given a parameter. Assumed true.');
		}
		
		//Non-boolean given, typecast.
		else if (!is_bool($bool)) {
			$primary = (bool)$bool;	
			$bool_string = ($primary) ? 'true' : 'false';
			$this->log_notice('set_only_primary('.$bool.') was given a '.gettype($bool).' rather than a boolean. Assumed '.$bool_string.'.');
		}
		
		//Given boolean, assign directly:
		else 
			$primary = $bool;
			
		//Assign to instance:
		$this->primary = $primary;
	}
	
	/**
	 * Set function for -u (string), --includeusers (string)
	 * Takes string of user_role unique names.
	 * Valid unique names are used to create pool of valid users.
	 */
	function set_include_user_roles($string) 
	{
		$this->user_include = $this->_set_user_roles($string);
	}
	
	/**
	 * Set function for -x (string), --excludeusers (string)
	 * Takes string of user_role unique names.
	 * Valid unique names are used to remove users of those roles.
	 */
	function set_exclude_user_roles($string) 
	{
		$this->user_exclude = $this->_set_user_roles($string);
	}
	
	/**
	 * Utility for -x and -u.
	 * Parses string and returns array of valid unique names for user roles.
	 */
	function _set_user_roles($string) 
	{
		//Parse string:
		$given_roles = explode(",", $string);
		$valid_roles = array();
		
		//Check valid user_role.
		foreach($given_roles as $role) {
			
			//If a valid id, keep and get entity:
			if($id = id_of($role, true, false)) { 
				$entity = new entity($id);
				$entity->get_values();
				
				//If the entity's type is User Role
				if ($entity->get_value('type') === id_of('user_role')) {
					$valid_roles[] = $role;
				}
				else
					$this->log_notice('Given user role unique name ('.$role.') is not the user role type.');
			}
			else
				$this->log_notice('Given user role unique name ('.$role.') does not exist.');
		}
		
		//If no valid user roles were found, avoid emailing all users by giving error.
		if (empty($valid_roles)) {
			$this->stop_error('All given user role unique names are invalid. No emails sent.');
		} else
			return $valid_roles;
	}
	
	/**
	 * Set function for -o, --outdated
	 * Limit sites to those with outdated content (pages).
	 */
	function set_only_outdated($bool) 
	{
		//Nothing given, assume true.
		if (!isset($bool)) {
			$outdated = true;
			$this->log_notice('set_only_outdated('.$bool.') was not given a parameter. Assumed true.');
		}
		
		//Non-boolean given, typecast.
		else if (!is_bool($bool)) {
			$outdated = (bool)$bool;	
			$bool_string = ($outdated) ? 'true' : 'false';
			$this->log_notice('set_only_outdated('.$bool.') was given a '.gettype($bool).' rather than a boolean. Assumed '.$bool_string.'.');
		}
		
		//Given boolean, assign directly:
		else 
			$outdated = $bool;
			
		//Assign to instance:
		$this->outdated = $outdated;
		
	}
	
	/**
	 * Set function for -i (string), --includesites (string)
	 * Takes string of site_type unique names.
	 * Valid unique names are used to create pool of valid sites.
	 */
	function set_include_site_types($string) 
	{
		$this->site_include = $this->_set_site_types($string);
	}
	
	/**
	 * Set function for -e (string), --excludesites (string)
	 * Takes string of site_type unique names.
	 * Valid unique names are used to remove sites of that type.
	 */
	function set_exclude_site_types($string) 
	{
		$this->site_exclude = $this->_set_site_types($string);
	}
	
	/**
	 * Utility for -i and -e.
	 * Parses string and returns array of valid unique names for site types.
	 */
	function _set_site_types($string) 
	{
		//Parse string:
		$given_types = explode(",", $string);
		$valid_types = array();
		
		//Check valid site_type.
		foreach($given_types as $type) {
			
			//If a valid id, keep and get entity:
			if($id = id_of($type, true, false)) { 
				$entity = new entity($id);
				$entity->get_values();
				
				//If the entity's type is Site Type
				if ($entity->get_value('type') === id_of('site_type_type')) {
					$valid_types[] = $type;
				}
				else
					$this->log_notice('Given site type unique name ('.$type.') is not the "site type" type.');
			}
			else
				$this->log_notice('Given site type unique name ('.$type.') does not exist.');
		}
		
		//If no valid site types were found, avoid emailing for all sites by giving error.
		if (empty($valid_types)) {
			$this->stop_error('All given site type unique names are invalid. No emails sent.');
		} else
			return $valid_types;
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
			$this->stop_error('The given from email address is invalid. No emails sent.');
	}
	
	/**
	 * Set function for -s, --simulate
	 * Print out messages rather than sending emails.
	 */
	function set_simulate($bool) 
	{
		//Nothing given, assume true.
		if (!isset($bool)) {
			$simulate = true;
			$this->log_notice('set_simulate('.$bool.') was not given a parameter. Assumed true.');
		}
		
		//Non-boolean given, typecast.
		else if (!is_bool($bool)) {
			$simulate = (bool)$bool;	
			$bool_string = ($simulate) ? 'true' : 'false';
			$this->log_notice('set_simulate('.$bool.') was given a '.gettype($bool).' rather than a boolean. Assumed '.$bool_string.'.');
		}
		
		//Given boolean, assign directly:
		else 
			$simulate = $bool;
			
		//Assign to instance:
		$this->simulate = $simulate;
		
	}
	
	/**
	 * Using given options, returns an array of users which
	 * match all given criteria.  Default behavior is to return
	 * all users who have a valid user_role.
	 */
	function get_valid_users() 
	{
		$users = array();
		
		//If only including sets of users with specific roles:
		if (!empty($this->user_include)) {
			
			foreach($this->user_include as $role) {
				$es = new entity_selector( id_of('master_admin') );
				$es->add_type( id_of('user') );
				$es->add_left_relationship( id_of($role), relationship_id_of('user_to_user_role') );
				//Avoid duplicates:
				$users = $this->array_merge_unique($users, $es->run_one());
			}
			
		} else 
		{
			//Obtain all users:
			$es = new entity_selector( id_of('master_admin') );
			$es->add_type( id_of('user') );
			$all_users = $es->run_one();	
			
			//Remove users who have no role and log:
			foreach ($all_users as $user) {
				$es = new entity_selector( );
				$es->add_type( id_of('user_role') );
				$es->add_right_relationship( $user->id(), relationship_id_of('user_to_user_role') );
				$user_role = $es->run_one();	
				
				if (!empty($user_role))
					$users[] = $user;
				else
					$this->log_notice('User ('.$user->get_value('name').') does not appear to have any assigned user role, not included.'); 
			}
		}
		
		//If excluding sets of users with specific roles:
		if (!empty($this->user_exclude)) {
		
			foreach($this->user_exclude as $role) {
				$es = new entity_selector( id_of('master_admin') );
				$es->add_type( id_of('user') );
				$es->add_left_relationship( id_of($role), relationship_id_of('user_to_user_role') );
				$users = array_udiff($users, $es->run_one(),
					//Remove users using id:
					function ($obj_a, $obj_b) {
						return $obj_a->id() - $obj_b->id();
					}
				);
			}
			
		}
		
		//If sending mail only to primary maintainers:
		if ($this->primary) {
			
			//Obtain all sites:
			$es = new entity_selector( id_of('master_admin') );
			$es->add_type( id_of('site') );
			$sites = $es->run_one();	
			
			if(!empty($sites)) {
				
				//Array to hold users who are primary maintainers:
				$primary_users = array();
				
				//Check each site:
				foreach($sites as $site) {
					$pm_name = $site->get_value('primary_maintainer');
					$exists = false;
					
					//First check against running list for duplicates:
					foreach($primary_users as $primary_user) {
						if ($pm_name === $primary_user->get_value('name')) {
							$exists = true;
							break; //Found, skip remaining users.
						}
					}
					
					//If not a duplicate, locate the user entity with that name:
					if (!$exists) {
						foreach($users as $user) {
							if($pm_name === $user->get_value('name')) {
								$primary_users[] = $user;
								break; //Found, skip remaining users.
							}
						}
					}
				}
			}
			
			//Return only the primary maintainers:
			return $primary_users;
		}
		
		//Else, just return the existing array:
		else {
			return $users;
		}
	}
	
	/**
	 * Using given options, returns an array of sitess which
	 * match all given criteria.  Default behavior is to return
	 * all sites which have a valid site_type.
	 */
	function get_valid_sites() 
	{
		$sites = array();
		
		//If only including sets of sites with specific types:
		if (!empty($this->site_include)) {
			
			foreach($this->site_include as $type) {
				$es = new entity_selector( id_of('master_admin') );
				$es->add_type( id_of('site') );
				$es->add_left_relationship( id_of($type), relationship_id_of('site_to_site_type') );
				//Avoid duplicates:
				$sites = $this->array_merge_unique($sites, $es->run_one());
			}
			
		} else 
		{
			//Obtain all sites:
			$es = new entity_selector( id_of('master_admin') );
			$es->add_type( id_of('site') );
			$all_sites = $es->run_one();	
			
			//Remove sites who have no type and log:
			foreach ($all_sites as $site) {
				$es = new entity_selector( );
				$es->add_type( id_of('site_type_type') );
				$es->add_right_relationship( $site->id(), relationship_id_of('site_to_site_type') );
				$site_type = $es->run_one();	
				
				if (!empty($site_type))
					$sites[] = $site;
				else
					$this->log_notice('Site ('.$site->get_value('name').') does not appear to have an assigned site type, not included.'); 
			}
		}
		
		//If excluding sets of sites with specific types:
		if (!empty($this->site_exclude)) {
		
			foreach($this->site_exclude as $type) {
				$es = new entity_selector( id_of('master_admin') );
				$es->add_type( id_of('site') );
				$es->add_left_relationship( id_of($type), relationship_id_of('site_to_site_type') );
				$sites = array_udiff($sites, $es->run_one(),
					//Remove sites using id:
					function ($obj_a, $obj_b) {
						return $obj_a->id() - $obj_b->id();
					}
				);
			}
			
		}
		
		//If only focusing on sites with old content:
		if ($this->outdated) {
			
			//Array to hold sites which have outdated content:
			$outdated_sites = array();
			
			foreach($sites as $site) {
				//Get pages in order of last-modified:
				$es = new entity_selector( $site->id() );
				$es->add_type( id_of('minisite_page') );
				$es->set_order('last_modified ASC');
				$oldest_page = current($es->run_one());
				
				if(!empty($oldest_page)) {
					//Get timestamps to compare:
					$oldestdate = strtotime($oldest_page->get_value('last_modified'));
					$limit = strtotime('-'.$this->time_limit.' month');
					
					//Compare and add if outdated:
					if ($oldestdate < $limit)
						$outdated_sites[] = $site;
				}
				
				//Not sure why a site would have no pages, but now you know:
				else
					$this->log_notice('Site ('.$site->get_value('name').') does not appear to have any pages.');
			}
			
			//Return 
			return $outdated_sites;
		}
		
		//Else, just return the existing array:
		else {
			return $sites;
		}

	}
	
	/**
	 * Takes each user, obtains and filters the sites they have
	 * access to, then composes and sends the email to that user.
	 * Users without access to any sites will recieve no emails.
	 */
	function send_emails($users, $sites) 
	{
		//Run through each user:
		foreach($users as $user) {
			
			//Get the sites associated with the user:
			$es = new entity_selector( id_of('master_admin') );
			$es->add_type( id_of('site') );
			$es->add_left_relationship( $user->id(), relationship_id_of('site_to_user') );
			$es->set_order('name ASC');
			$user_sites = $es->run_one();
			
			//If the user has sites:
			if(!empty($user_sites)) {
				
				//Filter the user's sites to those they are primary maintainers of:
				if ($this->primary) {
					$user_sites = $this->filter_to_primary($user_sites, $user);
					
					if(empty($user_sites)) {
						$this->log_notice('User ('.$user->get_value('name').') may be designated as the primary maintainer of site(s) they do not have access to.  No email will be sent for these site(s).');
						continue; //No valid sites, skip user.
					}
				}
				
				//Filter the user's sites to valid sites:
				if ((!empty($this->site_include)) ||
					(!empty($this->site_exclude)) ||
					($this->outdated === true) ) {
					
					$user_sites = $this->filter_user_sites($user_sites, $sites);
					
					if(empty($user_sites))
						//No notice, as this situation is reasonably expected.
						continue; //No valid sites, skip user.
				}
				
				//Compose and send email for user:
				$this->compose_email($user, $user_sites);
				
			} 
			//Return nothing so user recieves no email:
			else
				$this->log_notice('User ('.$user->get_value('name').') does not have access to any sites, email not sent.');
		
		}
	}
	
	/**
	 * Filter the sites a user has access to down to
	 * only those they are designated primary maintainers of.
	 */
	function filter_to_primary($user_sites, $user) 
	{
		$filtered_sites = array();
		foreach ($user_sites as $user_site) {
			if ($user->get_value('name') === $user_site->get_value('primary_maintainer')) {
				$filtered_sites[] = $user_site;
			}
		}
		return $filtered_sites;
	}
	
	/**
	 * Filter the sites a user has access to down to
	 * only sites which emails are to be sent about.
	 * 
	 * More literally, this function returns an array of
	 * elements which are in both parameter arrays.
	 */
	function filter_user_sites($user_sites, $sites) 
	{
		$filtered_sites = array();
		foreach ($user_sites as $user_site) {
			if (in_array($user_site, $sites, false)) {
				$filtered_sites[] = $user_site;
			}
		}
		return $filtered_sites;
	}
	
	/**
	 * Compose and send an email to a single user.
	 */
	function compose_email($user, $user_sites) 
	{
		//Attempt to create message:
		$message = $this->create_message($user, $user_sites);
		
		//If there is a message:
		if (!empty($message)) {
		
			//Get the user's email:
			$email = $this->get_user_email($user);
		
			//Check for if we're in -s / --simulate:
			if ($this->simulate) {
				echo '* '.$user->get_value('name').' ('.$email.') *'."\n";
				echo $message."\n";
			}
			//Otherwise, send email
			else {
				
				//Check for email string and assign:
				if (empty($email)) {
					$this->log_notice('Email address for user ('.$user->get_value('name').') could not be obtained. Email not sent.');
					return;
				}
				else
					$to = $email;
				
				//Assign subject and check:
				$subject = $this->get_email_subject();
				if (empty($subject))
					$this->stop_error('No email subject is set. No emails sent.');
					
				//From/Reply-To Email should already have been checked in init();
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= 'From: '.$this->from_email_address . "\r\n";
				$headers .= 'Reply-To: '.$this->from_email_address . "\r\n";
				//$headers .= 'To: ' . "\r\n";
				//$headers .= 'Cc: ' . "\r\n";
				//$headers .= 'Bcc: ' . "\r\n";
				
				//Send email to the user:
				mail($to, $subject, $message, $headers);
				
			}
		}
	}
	
	/**
	 * Construct the message to be sent to the user.
	 * Messages are composed of five editable parts:
	 * 
	 * Header - Addressing the user, "Dear User,".
	 * Intro - Text before list; what email is, why important.
	 * List - List of sites/pages to look at.
	 * Outro -  Text after list; links to contact and resources.
	 * Footer - Signing the email, "Sincerly, Reason".
	 * 
	 * Returns a empty string if email is not to be sent.
	 */
	function create_message($user, $user_sites) 
	{		
		$message =	'<html>'."\r\n";
		
			$message .=	'<head>'."\r\n";
			$message .=	'<title>Reason Content Reminder</title>'."\r\n";
			$message .=	'</head>'."\r\n";
			$message .=	'<body>'."\r\n";
			
				$message .=	'<div id="content_reminder">'."\r\n";
				
					//Get header and intro and append:
					$message .=	$this->create_message_header($user);		
					$message .= $this->create_message_intro();
					
					//Get list of sites and append if exists:
					$list = $this->create_message_list($user, $user_sites);
					if (!empty($list))
						$message .= $list;
					else
						return '';	
						
					//Get outro and footer and append:
					$message .= $this->create_message_outro();
					$message .= $this->create_message_footer();
				$message .=	'</div>'."\r\n";
				
			$message .=	'</body>'."\r\n";
		$message .=	'</html>'."\r\n";
		
		return $message;
	}
	
	/**
	 * The introduction to the email.
	 * Default behavior attempt to use given and surname,
	 * uses a default message if both not available.
	 */
	function create_message_header($user) 
	{
		$given_name = $user->get_value('user_given_name');
		$surname = $user->get_value('user_surname');
		return 	((!empty($given_name)) && (!empty($surname))) ?
				'<p>Dear '.$given_name.' '.$surname.', </p>'."\r\n" :
				'<p>Dear Reason User '.$user->get_value('name').', </p>'."\r\n" ;
	}
	
	/**
	 * Text which appears before the list.
	 * Intended to explain the purpose of the email.
	 * Default behavior differentiates between primary
	 * maintainers and general users.
	 */
	function create_message_intro() 
	{
		//Start intro and give purpose of email:
		$intro = '<p>';
		$intro .= 'You are receiving this email as a reminder to review and update the content found on the website. ';
		
		//Change language for primary maintainers:
		$intro .= ($this->primary)
			? 'As the primary maintainer of the following Reason sites, it is your responsibility to regularly ensure that the content on these sites is relevant and up to date. '
			: 'As a user with access to the following Reason sites, please regularly ensure that the content on these sites is relevant and up to date. ';
			
		//Change language if showing simple or verbose:
		$content = ($this->verbose)	? "sites and pages" : "sites";
		
		//Change language if showing only outdated content:
		$intro .= ($this->outdated)
			? 'Please review and update the content of the '.$content.' listed below which have not been updated in over '.$this->time_limit.' months. '
			: 'Please review and update the content of the '.$content.' listed below, especially pages which have not been updated in over '.$this->time_limit.' months (given in bold). ';
			
		//End intro and return:
		$intro .= '</p>'."\r\n";
		return $intro;
	}
	
	/**
	 * Create the list of sites/pages for the user to review.
	 * Allows for simple and verbose listings based on settings.
	 * Returns a empty string if email is not to be sent.
	 */
	function create_message_list($user, $user_sites) 
	{
		foreach ($user_sites as $site) {
			
				//Get all pages, sorted for last_modified:
				$es = new entity_selector( $site->id() );
				$es->add_type( id_of('minisite_page') );
				$es->set_order('last_modified ASC');
				$pages = $es->run_one();
				
				if(!empty($pages)) {
					//Determine to give simple or verbose listing:
					if ($this->verbose)
						$return .= $this->create_list_verbose($site, $pages);
					else
						$return .= $this->create_list_simple($site, $pages);
				}
				
				//Not sure why a site would have no pages, but now you know:
				else
					$this->log_notice('Site ('.$site->get_value('name').') does not appear to have any pages.'); 
			
		}
		
		//If the user doesn't have anything to return:
		if ((empty($return))) {
			//It's because of empty sites:	
			$this->log_notice('User ('.$user->get_value('name').') does not have sites to list because accessable sites have no pages, email not sent.'); 
			//Return empty string so no email is sent:
			return '';
		}
		//If there is a list, return it with surrounding <ul> tags:
		else
			return '<ul>'."\r\n".$return.'</ul>'."\r\n";
		
	}
	
	/**
	 * Creates a simple listing of for each of the user's sites.
	 * Default behavior is to list oldest and newest modified pages, 
	 * their last modified date, and designate if those pages have 
	 * not updated within a limit.
	 */
	function create_list_simple($site, $pages) 
	{
		$oldpage = current($pages)->get_value('name');
		$olddate = strtotime(current($pages)->get_value('last_modified'));
		$newpage = end($pages)->get_value('name');
		$newdate = strtotime(end($pages)->get_value('last_modified'));
		$limit = strtotime('-'.$this->time_limit.' month');
		
		//It is assumed that the calling function will add the surrounding <ul> tags.
		$return .= '<li><h3>'.$site->get_value('name').'</h3>'."\r\n";
		$return .= '<ul>'."\r\n";
			$return .= ($olddate < $limit) ? "<strong>" : "";
			$return .= '<li>'.'Oldest Update: '.$oldpage.' ('.date('F jS, Y', $olddate).')';
			$return .= ($olddate < $limit) ? "</strong>"."\r\n" : "\r\n";
			$return .= ($newdate < $limit) ? "<strong>" : "";
			$return .= '<li>'.'Newest Update: '.$newpage.' ('.date('F jS, Y', $newdate).')';
			$return .= ($newdate < $limit) ? "</strong>"."\r\n" : "\r\n";
		$return .= '</ul>'."\r\n";
		
		return $return;
	}
	
	/**
	 * Creates a verbose listing of for each of the user's sites.
	 * Default behavior is to list all pages, their last modified
	 * date, and designate pages not updated in a limit.
	 */
	function create_list_verbose($site, $pages) 
	{
		//List site name and start page list:
		$return .= '<li><h3>'.$site->get_value('name').'</h3>'."\r\n";
		$return .= '<ul>'."\r\n";
		
		//Determine limit to compare against:
		$limit = strtotime('-'.$this->time_limit.' month');
		
		foreach ($pages as $page) {
			
			//Get name and last_modified:
			$name = $page->get_value('name');
			$date = strtotime($page->get_value('last_modified'));
			
			//Don't show up to date pages for the site if outdated only:
			//Assumes array of pages are in last_modified ASC order.
			if (($this->outdated) && (($date >= $limit)))
				break;
			
			//It is assumed that the calling function will add the surrounding <ul> tags.
			$return .= ($date < $limit) ? "<strong>" : "";
			$return .= '<li>'.$name.' ('.date('F jS, Y', $date).')';
			$return .= ($date < $limit) ? "</strong>"."\r\n" : "\r\n";
		}
		
		//End page list:
		$return .= '</ul>'."\r\n";
		
		//Return site listing:
		return $return;
	}
	
	/**
	 * Text which appears after the list.
	 * Intended to hold reference/contact information.
	 */
	function create_message_outro() 
	{
		return '<p>If you require any assistance with making changes to content within Reason, please review the Reason resources which are available on the website or contact the Web Services team.</p>'."\r\n";
	}
	
	/**
	 * The signature of the email.
	 */
	function create_message_footer() 
	{
		//Try to use given value:
		if (!empty($this->email_signature))
			return '<p>Thank you,<br/>'.$this->email_signature.'</p>'."\r\n";
		else {
			//Try to use package value:
			$signature = WEBMASTER_NAME;
			if (!empty($signature))
				return '<p>Thank you,<br/>'.$signature.'</p>'."\r\n";
			else {
				//Print without name:
				$this->log_notice('No email signature is set. No name given in footer of emails.');
				return '<p>Thank you.</p>'."\r\n";
			}
		}
	}
	
	/**
	 * Return the email of the user.
	 */
	function get_user_email($user) 
	{
		return $user->get_value('user_email');
	}
	
	/**
	 * Return the title of emails to be sent.
	 */
	function get_email_subject() 
	{
		if (empty($this->email_subject))
			return '';
		else
			//Affix date:
			return $this->email_subject.', '.date('F jS, Y');
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
			if ( ! in_array($current, $final)) {
				$final[] = $current;
			}
		}

		return $final;
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
		echo ($this->log_has_notice()) ? "\n"."\n".$this->get_log() : '';
		exit(1);
	}
	
} //contentReminder class end
