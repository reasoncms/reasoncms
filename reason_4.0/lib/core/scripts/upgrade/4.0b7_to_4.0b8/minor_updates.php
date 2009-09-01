<?php
/**
 * Make minor updates
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
<title>Upgrade Reason: Make Minor Updates</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class addIssuesSectionsViewUpdater
{
	function do_updates($mode, $reason_user_id)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$messages = array();
		
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('view_type'));
		$es->add_relation('url = "sections_and_issues.php"');
		$es->set_num(1);
		$view_types = $es->run_one();
		if(empty($view_types))
		{
			if('test' == $mode)
			{
				echo '<p>Would have added the view type sections_and_issues.php and the Sections and Issues view</p>'."\n";
				return;
			}
			else
			{
				$view_type_id = reason_create_entity( id_of('master_admin'), id_of('view_type'), $reason_user_id, 'News Sections and Issues', array('url'=>'sections_and_issues.php'));
				$view_type = new entity($view_type_id);
				echo '<p>Added the view type sections_and_issues.php</p>'."\n";
			}
		}
		else
		{
			echo '<p>sections_and_issues.php view type already added</p>'."\n";
			$view_type = current($view_types);
		}
		
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('view'));
		$es->add_left_relationship($view_type->id(), relationship_id_of('view_to_view_type'));
		$es->set_num(1);
		$views = $es->run_one();
		if(empty($views))
		{
			if('test' == $mode)
			{
				echo '<p>Would have added the Sections and Issues view</p>'."\n";
			}
			else
			{
				$es = new entity_selector(id_of('master_admin'));
				$es->add_type(id_of('field'));
				$es->add_relation('entity.name = "status"');
				$es->set_num(1);
				$fields = $es->run_one();
				
				$view_id = reason_create_entity( id_of('master_admin'), id_of('view'), $reason_user_id, 'News Sections and Issues', array('display_name'=>'Sections and Issues'));
				create_relationship( $view_id, $view_type->id(), relationship_id_of('view_to_view_type'));
				create_relationship( $view_id, id_of('news'), relationship_id_of('view_to_type'));
				
				if(!empty($fields))
				{
					$field = current($fields);
					create_relationship( $view_id, $field->id(), relationship_id_of('view_columns'));
					create_relationship( $view_id, $field->id(), relationship_id_of('view_searchable_fields'));
				}
				
				echo '<p>Added sections and issue view</p>';
			}
		}
		else
		{
			echo '<p>sections and issues view already added.</p>'."\n";
		}
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
<h2>Reason: Minor Updates for 4.0 Beta 7 to Beta 8</h2>
<p>What's included in this update script:</p>

</ul>
<h3>Add sections/issues lister for news posts</h3>
<p>This update will add a new view for news posts that will provide the option of viewing issues and sections in the Reason backend</p>

<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
//	$updater = new removeRewriteFinishActions();
//	$updater->do_updates($_POST['go'], $reason_user_id);
	$updater2 = new addIssuesSectionsViewUpdater();
	$updater2->do_updates($_POST['go'], $reason_user_id);
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
