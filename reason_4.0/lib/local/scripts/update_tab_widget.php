<?php
/**
 * Upgrader that adds tab widget module to Reason database.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Add Tab Widget Type</title>
</head>

<body>

<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
include_once( CARL_UTIL_INC . 'db/sqler.php' );
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('classes/amputee_fixer.php');

force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}
?>

<h2>Reason: Create Tab Widget type</h2>
<p>Adds the Luther College Tab Widget type to Reason.</p>
<?php
if (!defined("DISABLE_REASON_ADMINISTRATIVE_INTERFACE"))
{
	echo '<p><strong>You have not defined DISABLE_REASON_ADMINISTRATIVE_INTERFACE in your reason_settings.php file. It is strongly suggested that you do so AND
	      set it to true before running this script. This will help ensure the integrity of your database.</strong></p>';
}
elseif (DISABLE_REASON_ADMINISTRATIVE_INTERFACE == false)
{
	echo '<p><strong>This script modifies types. You should set DISABLE_REASON_ADMINISTRATIVE_INTERFACE to true before you run this script. 
		  This will help ensure the integrity of your database.</strong></p>';
}
elseif (DISABLE_REASON_ADMINISTRATIVE_INTERFACE == true)
{
	echo '<p><strong>DISABLE_REASON_ADMINISTRATIVE_INTERFACE is set to true - make sure to set it to false after the update 
	      so your users can administer reason sites.</strong></p>';
}
?>
<p>What will this update do?</p>
<ul>
<li>Add the Luther College Tab Widget type to Reason</li>
</ul>

<form method="post">
<input type="submit" name="go" value="test" />
<input type="submit" name="go" value="run" />
</form>

<?php
if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
	{
		echo '<p>Running updater...</p>'."\n";
		$mode = 'run';
	}
	else
	{
		echo '<p>Testing updates...</p>'."\n";
		$mode = 'test';
	}
	if ($mode)
	{
		$update = new updateTypes($mode, $reason_user_id);
		$update->create_tab_widget_type($mode, $reason_user_id);	
	}
}

?>
</body>
</html>

<?php
class updateTypes
{
	var $mode;
	var $reason_id;
	var $tab_widget_type_details = array (
		'new' => 0,
		'unique_name' => 'tab_widget_type',
		'custom_content_handler' => 'tab_widget.php',
		'plural_name' => 'Tab Widgets'
	);	
	var $tab_widget_table_fields = array(
		'tab_widget_description' => 'text',
		'tab_widget_title_1' => 'tinytext',
		'tab_widget_content_1' => 'text',
		'tab_widget_title_2' => 'tinytext',
		'tab_widget_content_2' => 'text',
		'tab_widget_title_3' => 'tinytext',
		'tab_widget_content_3' => 'text',
		'tab_widget_title_4' => 'tinytext',
		'tab_widget_content_4' => 'text',
		'tab_widget_title_5' => 'tinytext',
		'tab_widget_content_5' => 'text',
	);	
	var $page_to_tab_widget_details = array (
		'description'=>'Page to Tab Widget',
		'directionality'=>'unidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'yes',
		'display_name'=>'Place Tab Widget on this page',
		'display_name_reverse_direction'=>'Pages that use this Tab Widget',
		'description_reverse_direction'=>'Pages that use this Tab Widget'
	);	
	
	function create_tab_widget_type($mode, $reason_user_id = NULL)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
	
		$this->mode = $mode;
		$this->reason_id = $reason_user_id;
	
		//create tab widget type entity
		$this->create_tab_widget_type_entity();
	
		// create tab widget entity table
		$table = 'tab_widget';
		$type_unique_name = $this->tab_widget_type_details['unique_name'];
		$this->add_new_entity_table_to_type($table, $type_unique_name);
	
		// add fields to the tab widget entity table
		foreach($this->tab_widget_table_fields as $fname=>$ftype)
		{
			$this->add_field_to_entity_table($table, $fname, $ftype);
		}
	
		// create all the necessary relationships for the tab widget type
			
		// page_to_tab_widget
		$this->add_allowable_relationship('minisite_page', 'tab_widget_type', 'page_to_tab_widget', $this->page_to_tab_widget_details);
				
	}
	
	/**
	 * Add Tab Widget to Type table
	 */
	function create_tab_widget_type_entity()
	{
		if (reason_unique_name_exists('tab_widget_type'))
		{
			echo '<p>This script has probably already been run</p>';
		}
		elseif ($this->mode == 'test')
		{
			echo '<p>Would create the tab widget type</p>';
		}
		elseif ($this->mode == 'run')
		{
			// do it
			echo "Created tab widget type";
			$tab_widget_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->reason_id, 'Tab Widget', $this->tab_widget_type_details);
			create_default_rels_for_new_type($tab_widget_id, $this->tab_widget_type_details ['unique_name']);
			//reason_expunge_entity( $tab_widget_id, $this->reason_id );
		}
	}
	
	function add_allowable_relationship($a_side, $b_side, $name, $other_data = array())
	{
		if (reason_relationship_name_exists($name, false))
		{
			echo '<p>'.$name.' already exists. No need to update.</p>'."\n";
			return false;
		}
		if ($this->mode == 'run')
		{
			$r_id = create_allowable_relationship(id_of($a_side), id_of($b_side), $name, $other_data);
			if($r_id)
			{
				echo '<p>'.$name.' allowable relationship successfully created</p>'."\n";
			}
			else
			{
				echo '<p>Unable to create '.$name.' allowable relationship</p>';
				echo '<p>You might try creating the relationship '.$name.' yourself in the reason administrative interface';
				echo '- it should include the following characteristics:</p>';
				pray ($other_data);
			}
		}
		else
		{
			echo '<p>Would have created '.$name.' allowable relationship.</p>'."\n";
		}
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////
	/*
	 The following methods are generic, and so are not specific to creating a tab widget type.
	*/
	////////////////////////////////////////////////////////////////////////////////////////////////
	
	function add_new_entity_table_to_type($table, $type_unique_name)
	{
		$type_id = reason_unique_name_exists($type_unique_name) ? id_of($type_unique_name) : false;
		if ($type_id)
		{
			$tables = get_entity_tables_by_type($type_id, false);
				
			if (!in_array($table, $tables))
			{
				if ($this->mode == 'test') echo '<p>Would create table ' . $table . ' for type ' . $type_unique_name . '</p>';
				else
				{
					create_reason_table($table, $type_unique_name, $this->reason_id);
					echo '<p>Created table ' . $table . ' for type ' . $type_unique_name . '</p>';
				}
			}
			else
			{
				echo '<p>The table ' . $table . ' for type ' . $type_unique_name . ' already exists.</p>';
			}
		}
	}
	
	/**
	 * Creates entity tables if necessary
	 */
	function add_field_to_entity_table($table, $field_name, $field_db_type)
	{
		// lets make sure the table exists first
		$es = new entity_selector();
		$es->add_type(id_of('content_table'));
		$es->add_relation('entity.name = "'.$table.'"');
		$results = $es->run_one();
		if ($results)
		{
			if (in_array($field_name, get_fields_by_content_table($table)))
			{
				echo '<p>The '.$table.' entity table already has the field '.$field_name.' - the script has probably been run.</p>';
				return false;
			}
			else
			{
				$updater = new FieldToEntityTable($table, array($field_name => array('db_type' => $field_db_type)));
				if ($this->mode == 'test') $updater->test_mode = true;
				$updater->update_entity_table();
				$updater->report();
			}
		}
	}

}

?>