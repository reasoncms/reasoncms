<?php
/**
 * Add athe page-to-feed-diplay-url relationship
 *
 * This script is part of the 4.0 beta 3 to beta 4 upgrade
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('classes/amputee_fixer.php');

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Reason Upgrade: Add Page to Feed URL Relationship</title>
</head>

<body>
<?php

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

echo '<h2>Reason: Add "Page to Feed URL" Relationship</h2>';
if ( (!isset ($_POST['verify'])) && (!isset ($_POST['verify2'])))
{
        echo '<p>This script will add the page_to_feed_url relationship that allows pages to present feeds. It will also delete the page_to_external_url relationship, which is not used anywhere in the code and it just confusing to users.</p>';
		echo_form();
}

if (isset ($_POST['verify']))
{
	$test_mode = true;
	if($_POST['verify'] == 'Run')
		$test_mode = false;
	do_action($test_mode);
}

function echo_form()
{
	echo '<form name="doit" method="post" action="'.get_current_url().'" />';
	echo '<input type="submit" name="verify" value="Run" />';
	echo '<input type="submit" name="verify" value="Test" />';
	echo '</form>';
}

function do_action($test_mode = true)
{
	$rel_id = relationship_id_of('page_to_feed_url');
	$ext_url_id = id_of('external_url');
	if ($ext_url_id == 0)
	{
		$external_url_fields  	  = array('external_url' => array(
							  		  'url' => array('db_type' => 'tinytext')));
							  		  
		$ext_url_id = create_external_url_type($ext_url_id, 'External Url', 'external_url', 'External URLs', $external_url_fields);
	}
	if(empty($rel_id))
	{
		echo '<p>page_to_feed_url does not yet exist.</p>';
		if($test_mode)
			echo '<p>Would have created the allowable relationship page_to_feed_url</p>';
		else
		{
			$alrel_id = create_allowable_relationship(id_of('minisite_page'),$ext_url_id,'page_to_feed_url',array('connections'=>'one_to_many','display_name'=>'Set up feed to display','description_reverse_direction'=>'Page(s) using this URL as the feed they display','description'=>'This relationship allows an external URL to be used as the feed source of an RSS parser/displayer'));
			if(!empty($alrel_id))
			{
				echo '<p>created allowable relationship, id '.$alrel_id.'</p>';
			}
		}
	}
	else
	{
		echo '<p>page_to_feed_url already exists, so it does not need to be created.</p>';
	}
	$rel_id = relationship_id_of('page_to_external_url');
	if(!empty($rel_id))
	{
		echo '<p>page_to_external_url still exists.</p>';
		if($test_mode)
			echo '<p>Would have deleted the allowable relationship page_to_external_url</p>';
		else
		{
			if($GLOBALS['sqler']->delete_one('allowable_relationship',$rel_id))
			{
				echo '<p>deleted allowable relationship, id '.$rel_id.'</p>';
			}
		}
	}
	else
	{
		echo '<p>page_to_external_url no longer exists, so it does not need to be deleted.</p>';
	}
}

function create_external_url_type($type_id, $type_name, $type_unique_name, $type_plural_name, $entity_table_fields)
{
	$user_id = get_user_id(reason_require_authentication());
	
	if ($type_id == 0) // type needs creation
	{
		$type_id = reason_create_entity(id_of('master_admin'), id_of('type'), $user_id, $type_name, array(		'unique_name' => $type_unique_name, 
																												'plural_name' => $type_plural_name,
																												'new' => 0));
		if ($type_id != 0)
		{
			report( $type_name . ' type created');
			if (@create_default_rels_for_new_type($type_id, $type_unique_name)) report( $type_name . ' default rels created');
		}
		else
		{
			warn( $type_name . ' not created - this is unexpected');
			return false;
		}
	}
	if (!empty($entity_table_fields))
	{
		foreach ($entity_table_fields as $table_name=>$table_fields)
		{
			$table_id = @create_reason_table($table_name, $type_id, $user_id);
			if ($table_id) report('created entity table ' . $table_name . ' with id ' . $table_id);
			else 
			{
				report('did not create entity table ' . $table_name . ' - probably already exists - will try to add to type');
				add_entity_table_to_type($table_name, $type_id);
			}
			$updater = new FieldToEntityTable($table_name, $table_fields);
			$updater->update_entity_table();
			if ($updater->fields > 0)
			{
				ob_start();
				$updater->report();
				$updater_report = '<h3>Amputee Report - entity table '.$table_name.'</h3><hr />'.ob_get_contents().'<hr />';
				ob_end_clean();
				report($updater_report);
			}
		}
		$fixer = new AmputeeFixer();
		$fixer->fix_amputees($type_id);
		report('fixed amputees for type ' . $type_name);
	}
	else report( $type_name . ' requires no entity table(s) because it has no fields other than what is in the entity table');	
	return $type_id;
}

function add_entity_table_to_type($table_name, $type_id)
{
	$es = new entity_selector();
	$es->add_type(id_of('content_table'));
	$es->add_relation('entity.name = "'.$table_name.'"');
	$results = current($es->run_one());
	if(!empty($results))
	{
		$try = create_relationship( $type_id, $results->id(), relationship_id_of('type_to_table'));
		if ($try) 
		{
			report('added ' . $table_name . ' to type id ' . $type_id);
			return true;
		}
	}
	return false;
}

function report($msg, $mode = '')
{
	static $report = array();
	if (empty($mode)) $report[] = $msg;
	else
	{
		echo '<h3>Report</h3>';
		if (!empty($report)) 
		{
			pray ($report);
		}
		else
		{
			echo 'Nothing to report';
		}
	}
}

function warn($msg, $mode = '')
{
	static $warn = array();
	if (empty($mode)) $warn[] = $msg;
	else
	{
		echo '<h3>Warnings</h3>';
		if (!empty($warn)) 
		{
			pray ($warn);
		}
		else
		{
			echo 'No warnings';
		}
	}
}

warn('', 'output');
report('', 'output');

?>
</body>
</html>
