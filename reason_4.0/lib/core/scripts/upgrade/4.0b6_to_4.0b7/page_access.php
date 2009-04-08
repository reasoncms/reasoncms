<?php
/**
 * Add the page_to_access_group allowable relationship
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
<title>Upgrade Reason: Add page-to-access-group relationship</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

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
<h2>Reason: Add page-to-access-group relationship</h2>
<p>This update will add the page_to_access_group allowable relationship, which will enable easy placement of access restrictions on Reason pages.</p>
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
	
	$alrel_id = relationship_id_of('page_to_access_group', true, false);
	
	if($alrel_id)
	{
		echo '<p>Allowable relationship already exists. No need to run this script.</p>';
	}
	else
	{
		$a_side_type_id = id_of('minisite_page');
		$b_side_type_id = id_of('group_type');
		$name = 'page_to_access_group';
		$other_data = array('description'=>'Limits access to a group','connections'=>'one_to_many','directionality'=>'unidirectional','required'=>'no','is_sortable'=>'no','display_name'=>'Restrict Access','description_reverse_direction'=>'Pages that use this group for access restrictions');
		if($_POST['go'] == 'run')
		{
			$id = create_allowable_relationship($a_side_type_id,$b_side_type_id,$name,$other_data);
			if($id)
			{
				echo '<p>Successfully created new allowable relationship (ID '.$id.'). Relationship info:</p>';
			}
			else
			{
				echo '<p>Not able to add new allowable relationship. You should probably try to manually create this relationship, using the info below:</p>';
			}
		}
		else
		{
			echo '<p>Would have created a new allowable relationship. Relationship info:</p>';
		}
		echo '<p><strong>A side type id:</strong> '.$a_side_type_id.'</p>';
		echo '<p><strong>B side type id:</strong> '.$b_side_type_id.'</p>';
		echo '<p><strong>Name:</strong> '.$name.'</p>';
		echo '<p>Other data:</p>';
		pray($other_data);
	}
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
