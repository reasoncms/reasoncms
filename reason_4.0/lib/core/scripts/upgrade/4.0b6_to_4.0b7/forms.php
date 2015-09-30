<?php
/**
 * Modify the form type with extra fields for expanded thor capabilities
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start script
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Form Upgrader</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/field_to_entity_table_class.php');

class formUpgrader
{
	var $mode;
	var $reason_user_id;
	
	var $fields_to_add =
		array('is_editable' =>
				array('db_type' => 'enum("yes","no")'),
			  'thor_view' =>
			    array('db_type' => 'tinytext'),
			  'allow_multiple' =>
			    array('db_type' => 'enum("yes","no")'),
			  'email_submitter' => 
			    array('db_type' => 'enum("yes","no")'),
			  'email_link' => 
			    array('db_type' => 'enum("yes","no")'),
			  'email_data' =>
			    array('db_type' => 'enum("yes","no") DEFAULT "yes"'),
			  'email_empty_fields' =>
			    array('db_type' => 'enum("yes","no") DEFAULT "yes"')); // lets preserve old behavior
		
	//type_to_default_view
	
	function do_updates($mode, $reason_user_id)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		
		settype($reason_user_id, 'integer');
		if(empty($reason_user_id))
		{
			trigger_error('$reason_user_id must be a nonempty integer');
			return;
		}
		$this->reason_user_id = $reason_user_id;
		
		// The updates
		$this->create_authenticated_group_if_it_does_not_exist();
		$this->add_fields_to_form_entity_table($this->fields_to_add);
		$this->modify_form_force_login_pages();
		$this->add_last_modified_field();
		$this->show_local_link();
	}
	
	function add_fields_to_form_entity_table($fields)
	{
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "form"');
		$es->set_num(1);
		$table = $es->run_one();
	
		if(empty($table))
		{
			echo '<p>Unable to find form entity table - the script cannot run.</p>';
		}
		else
		{
			$field_string = '"'.implode('","', array_keys($fields)).'"';
			$form_table = current($table);
			$es = new entity_selector();
			$es->add_type(id_of('field'));
			$es->add_relation('entity.name IN ('.$field_string.')');
			$es->add_left_relationship($form_table->id(),relationship_id_of('field_to_entity_table'));
			$existing_fields = $es->run_one();
			
			foreach($existing_fields as $field)
			{
				unset($fields[$field->get_value('name')]);
			}
		
			if(empty($fields))
			{
				echo '<p>All new form fields exist - the form type has already been updated.</p>';
			}
			else
			{
				if($this->mode != 'run')
				{
					echo '<p>Would have created these fields:</p>';
					pray($fields);
				}
				else
				{
					$updater = new FieldToEntityTable($form_table->get_value('name'), $fields);
					$updater->update_entity_table();
					$updater->report();
				}
			}
		}
	}
	
	function modify_form_force_login_pages()
	{
		$es = new entity_selector();
		$es->add_type(id_of('minisite_page'));
		$es->limit_tables('page_node');
		$es->limit_fields('custom_page');
		$es->add_relation('page_node.custom_page = "form_force_login"');
		$result = $es->run_one();
		if(empty($result))
		{
			echo '<p>These are no pages of type form_force_login in this reason instance.</p>';
		}
		else
		{
			if ($this->mode != 'run')
			{
				echo '<p>Would modify ' . count($result) . ' page(s) with page type form_force_login</p>';
			}
			else
			{
				foreach ($result as $page_id => $page)
				{
					$site = $page->get_owner();
					$this->ensure_group_type_is_on_site($site);
					$this->restrict_page_to_group_and_change_page_type($site, $page);
				}
			}
		}
	}
	
	function add_last_modified_field()
	{
		connectDB(THOR_FORM_DB_CONN);
		
		$updates_to_make = false;
		$prefix = 'form_';
		$result = db_query("show tables");
		if (mysql_num_rows($result) > 0)
		{
			while ($row = mysql_fetch_row($result))
			if (substr($row[0], 0, strlen($prefix)) == $prefix) $return[] = $row[0];
		}
		if (!empty($return))
		{
			foreach ($return as $key => $table)
			{
				$result = db_query("select * from " . $table . " LIMIT 1");
				if (mysql_num_rows($result) > 0) // a thor table by definition has records - so don't do anything if not
				{
					// first lets make sure this needs to be updated
					$has_date_created = $has_last_modified = $has_submitter_ip = false;
					while ($colinfo = mysql_fetch_field($result))
					{
						if ($colinfo->name == 'date_created') $has_date_created = true;
						elseif ($colinfo->name == 'date_modified') $has_last_modified = true;
						elseif ($colinfo->name == 'submitter_ip') $has_submitter_ip = true;
					}
					if ( ($has_date_created === true) && ($has_last_modified === false) && ($has_submitter_ip === true) )
					{
						$updates_to_make = true;
						//lets alter the table and update ALL the fields!
						if ($this->mode == 'test')
						{
							echo '<p>Would update thor table with name ' . $table . '</p>';
						}
						else
						{
							$qry1 = 'ALTER TABLE `'.$table.'` CHANGE `date_created` `date_modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
							$qry2 = 'ALTER TABLE `'.$table.'` ADD `date_created` TIMESTAMP NOT NULL DEFAULT 0 AFTER `submitter_ip`';
							$qry3 = 'UPDATE `'.$table.'` SET `date_created` = `date_modified`, `date_modified` = `date_modified`';
							db_query($qry1);
							db_query($qry2);
							db_query($qry3);
							echo '<p>Updated thor table with name ' . $table . '</p>';
						}
					}
				}
			}
		}
		if (!$updates_to_make) echo '<p>No thor tables need updating - the script has probably been run</p>';
		connectDB(REASON_DB);
	}
	
	//$q .= '`date_created` timestamp default 0 NOT NULL , ';
	//$q .= '`date_modified` timestamp default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP , ';
	
	function ensure_group_type_is_on_site($site)
	{
		
		$site_id = $site->id();
		$es = new entity_selector();
		$es->add_type(id_of('group_type'));
		$es->add_right_relationship($site_id,relationship_id_of('site_to_type'));
		$es->add_relation('entity.id = "'.id_of('group_type').'"');
		$es->set_num(1);
		$type = $es->run_one();
		if(empty($type))
		{
			create_relationship( $site_id, id_of('group_type'), relationship_id_of('site_to_type'));
		}
	}
	
	function restrict_page_to_group_and_change_page_type($site, $page)
	{
		$site_id = $site->id();
		$page_id = $page->id();
		// first lets see if there is a group related to the page over the page_to_access_group relationship
		$es = new entity_selector();
		$es->add_type(id_of('group_type'));
		$es->add_right_relationship($page_id, relationship_id_of('page_to_access_group'));
		$es->set_num(1);
		$group = $es->run_one();
		if(empty($group))
		{
			if(!site_borrows_entity( $site_id, id_of('authenticated_group', false)))
			{
				create_relationship( $site_id, id_of('authenticated_group', false), get_borrow_relationship_id(id_of('group_type')));
			}
			create_relationship( $page_id, id_of('authenticated_group', false), relationship_id_of('page_to_access_group'));
		}
		reason_update_entity($page_id, $this->reason_user_id, array('custom_page' => 'form'));
		echo '<p>Page ' . $page->get_value('name') . ' on site ' . $site->get_value('name') . ' is now of the form page type and properly restricted.</p>';
	}
	
	function create_authenticated_group_if_it_does_not_exist()
	{
		if (!reason_unique_name_exists('authenticated_group', false))
		{
			if ($this->mode != 'run') echo '<p>Would create authenticated group</p>';
			else
			{
				$group['name'] = 'Authenticated Group';
				$group['require_authentication'] = "true";
				$group['limit_authorization'] = "false";
				$group['group_has_members'] = "true";
				$group['new'] = 0;
				$group['unique_name'] = 'authenticated_group';
				$group_id = reason_create_entity(id_of('master_admin'), id_of('group_type'), $this->reason_user_id, $group['name'], $group);
				echo '<p>Created authenticated group</p>';
			}
		}
		else echo '<p>Authenticated group already exists</p>';
	}
	
	/**
	 * A little hook to link to a local update file if it exists
	 */
	function show_local_link()
	{
		if (reason_file_exists('scripts/upgrade/4.0b6_to_4.0b7/forms_local.php'))
		{
			$link = carl_construct_link(array(), array(), '/reason_package/reason_4.0/lib/local/scripts/upgrade/4.0b6_to_4.0b7/forms_local.php');
			echo '<a href="'.$link.'">Proceed to local updates</a>';
		}
	}
	
	function add_type_to_site($type_id)
	{
		$rel_id = create_relationship( $this->get_value('site_id'), $type_id, relationship_id_of('site_to_type'));
		return $rel_id;
	}
}

force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );
if(empty($reason_user_id))
{
	die('valid Reason user required');
}
if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have Reason upgrade rights to run this script');
}

?>
<h2>Reason: Form Upgrader</h2>
<p>The thor form module has been completely reworked in Reason 4 Beta 7. Existing forms should continue to function as usual.
The content manager has been updated a bit, but the new features are for the moment only exposed to instance administrators. 
Reason 4 Beta 8 will expose more of the new functionality to site administrators, and make the interface prettier.</p>
<p><strong>Changes include:</strong></p>
<ul>
<li>Forms can be marked as "editable," allowing users to fill out a form, and edit the submission at a later time.</li>
<li>A form can allow multiple editable submissions from a single user</li>
<li>Forms can be set to e-mail the submitter</li>
<li>Forms can be set to e-mail data, a link to the form data (great for more secure forms), or both</li>
<li>Forms can be set to not e-mail empty fields</li>
<li>Administrators can develop and choose custom views for a particular thor form.</li>
<li>Thor tables now have both a last_modified and date_created field - date_created used to function like a last_modified</li>
</ul>

<p><strong>What will this update do?</strong></p>
<ul>
<li>Adds is_editable to the form entity table.</li>
<li>Adds allow_multiple to the form entity table.</li>
<li>Adds email_submitter to the form entity table.</li>
<li>Adds email_link to the form entity table.</li>
<li>Adds email_data to the form entity table.</li>
<li>Adds email_empty_fields to the form entity table.</li>
<li>Adds thor_view to the form entity table.</li>
<li>Create the authenticated group - a group comprised of people with netids, it it does not exist</li>
<li>Modifies pages using form_force_login page type to be restricted to users with a netid.</li>
<li>Modifies pages using form_force_login page type to use the standard form page type.</li>
<li>Updates all thor tables to add a last_modified field, and changes the definition of the date_created field</li>
</ul>
<?php
if(!defined('REASON_FORMS_THOR_DEFAULT_VIEW') || !defined('REASON_FORMS_THOR_DEFAULT_CONTROLLER') || !defined('REASON_FORMS_THOR_DEFAULT_VIEW'))
{
	echo '<h3>Step 1: Add setting</h3>';
	if(!empty($_GET['setting']))
	{
		echo '<h4 style="color:#c00;">ALERT: It does not appear that all needed constants are defined. You need to add the following constants to reason_settings.php before proceeding.</h4>'."\n";
		echo '<ul>';
		if(!defined('REASON_FORMS_THOR_DEFAULT_VIEW')) echo '<li>REASON_FORMS_THOR_DEFAULT_VIEW</li>';
		if(!defined('REASON_FORMS_THOR_DEFAULT_CONTROLLER')) echo '<li>REASON_FORMS_THOR_DEFAULT_CONTROLLER</li>';
		if(!defined('REASON_FORMS_THOR_DEFAULT_MODEL')) echo '<li>REASON_FORMS_THOR_DEFAULT_MODEL</li>';
		echo '</ul>';
	}	
?>
<p>Copy and paste the missing constant definitions below into your reason_settings.php file.</p>
<textarea rows="24" cols="80" style="width:100%;background-color:#ddd;">
/**
 * REASON_FORMS_THOR_DEFAULT_MODEL
 *
 * Indicates the filename that should be used as the default model for thor forms within Reason.
 * 
 * You can provide the path in one of three ways:
 *
 * 1. Fully qualified path
 * 2. Pathname from the core/local split
 * 3. Relative path from within minisite_templates/modules/form/models/ directory
 */
define('REASON_FORMS_THOR_DEFAULT_MODEL', 'thor.php');

/**
 * REASON_FORMS_THOR_DEFAULT_CONTROLLER
 *
 * Indicates the filename that should be used as the default controller for thor forms within Reason.
 * 
 * You can provide the path in one of three ways:
 *
 * 1. Fully qualified path
 * 2. Pathname from the core/local split
 * 3. Relative path from within minisite_templates/modules/form/controllers/ directory
 */
define('REASON_FORMS_THOR_DEFAULT_CONTROLLER', 'thor.php');

/**
 * REASON_FORMS_THOR_DEFAULT_VIEW
 *
 * Indicates the filename that should be used as the default thor view for thor forms within Reason.
 * 
 * You can provide the path in one of three ways:
 *
 * 1. Fully qualified path
 * 2. Pathname from the core/local split
 * 3. filename within minisite_templates/modules/form/views/thor/ directory
 */
define('REASON_FORMS_THOR_DEFAULT_VIEW', 'default.php');
</textarea>
<p>Once you have done that, <a href="?setting=1">Proceed to the database upgrade</a>.</p>
<?php
}
else
{
?>
<p>REASON_FORMS_THOR_DEFAULT_VIEW setting OK.</p>
<p>Next step: update the database.</p>	
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php
}
if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new formUpgrader();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

$link = carl_construct_link(array(),array(), REASON_HTTP_BASE_PATH . 'scripts/upgrade/4.0b6_to_4.0b7/index.php');
echo '<p><a href="'.$link.'">Return to Index</a></p>';
?>
</body>
</html>
