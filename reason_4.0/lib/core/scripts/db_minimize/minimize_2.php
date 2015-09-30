<?php
/**
 * Second step of the db minization process
 *
 * This stage of the script removes the following items from this Reason instance:
 * - All Non-Reason Sites
 * - All Site Types not currently in use
 * - All text blurbs, except a few core ones
 * - All users, except a few core ones
 * - All Deleted, Pending, and Archived entities
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * This script may take a long time, so extend the time limit to infinity
 */
set_time_limit( 0 );

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/user_functions.php');

// make sure user is authenticated, is a member of master admin, AND has the admin role.
force_secure_if_available();

$authenticated_user_netid = check_authentication();

auth_site_to_user( id_of('master_admin'), $authenticated_user_netid );

$user_id = get_user_id( $authenticated_user_netid );

if(!reason_user_has_privs( $user_id, 'minimize_db' ) )
{
	die('you must have minimize_db privileges to view this page. NOTE: For security reasons, admin users DO NOT have minimize_db privileges. If you are an admin user, you must add minimize_db privs to the admin role in this Reason instance, or set up a minimize-db-specific role and assume it.');
}

?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Minimize the Reason DB - Step 2</title>
</head>
<style type="text/css">
h2,h3 {
	display:inline;
}
</style>
<body>
<h1>Minimize this Reason Instance: Step 2</h1>
<?php

$minimal_blurbs = array('login_to_access_file', 'form_login_msg','admin_login', 'expired_login');
$minimal_users = array('causal_agent', 'root', $authenticated_user_netid);

if(empty($_POST['do_it']) && empty($_POST['test_it']))
{
?>
<form method="post">
<p>This stage of the script removes the following items from this Reason instance:</p>
<ul>
<li>All Non-Reason Sites</li>
<li>All Site Types not currently in use</li>
<li>All text blurbs, except the following: <?php echo implode(', ',$minimal_blurbs)?></li>
<li>All users, except the following: <?php echo implode(', ',$minimal_users)?></li>
<li>All Deleted, Pending, and Archived entities</li>
</ul>
<p><strong>This script is highly destructive.</strong> You should only run this script on a copy of your main Reason instance.</p>
<p>This is so destructive there is a Reason setting that expressly prohibits it, and which is true by default: PREVENT_MINIMIZATION_OF_REASON_DB.</p>
<?php
$limit_sel = '<select size="1" name="limit" id="limit">';
$limit_sel .= '<option value="10">10</option>';
$limit_sel .= '<option value="20">20</option>';
$limit_sel .= '<option value="40">40</option>';
$limit_sel .= '<option value="80">80</option>';
$limit_sel .= '<option value="160">160</option>';
$limit_sel .= '<option value="320">320</option>';
$limit_sel .= '<option value="640">640</option>';
$limit_sel .= '<option value="1280">1280</option>';
$limit_sel .= '<option value="-1" selected="selected">All</option>';
$limit_sel .= '</select>';

if(PREVENT_MINIMIZATION_OF_REASON_DB)
{
	echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>true</strong>.  This means that this script will not do anything when run. You can, however, see what this script <strong>would</strong> do by clicking the button below.</p>';
	echo '<p><label for="limit">Test delete: </label>'.$limit_sel.' items per phase</p>';
	echo '<input type="submit" name="test_it" value="Test the script" />';
}
else
{
	echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>false</strong>.  This means that this instance has been set up in a way that allows this script to be run. Remember to <em>only run this script on a <strong>copy</strong> of your real Reason instance</em>.</p>';
	echo '<p><label for="limit">Delete (or test delete): </label>'.$limit_sel.' items per phase</p>';
	echo '<input type="submit" name="test_it" value="Test the script" />';
	echo '<input type="submit" name="do_it" value="Run the script" />';
}
?>
</form>
<?php
}
else
{
	$out = array();
	$test_mode = true;
	if(!PREVENT_MINIMIZATION_OF_REASON_DB && !empty($_POST['do_it']))
	{
		$test_mode = false;
	}
	if(!empty($_POST['limit']))
	{
		$limit = turn_into_int($_POST['limit']);
	}
	else
	{
		$limit = -1;
	}
	
	echo '<p><a href="?">Return to form</a></p>';
	
	// Delete Non_reason sites
	$out[] = '<h2>Started Non-Reason Sites</h2>';
	$es = new entity_selector();
	$es->set_num($limit);
	$non_reason_sites = $es->run_one(id_of('non_reason_site_type'));
	$pending_non_reason_sites = $es->run_one(id_of('non_reason_site_type'), 'Pending');
	if(!empty($pending_non_reason_sites))
		$non_reason_sites += $pending_non_reason_sites;
	$deleted_non_reason_sites = $es->run_one(id_of('non_reason_site_type'), 'Deleted');
	if(!empty($deleted_non_reason_sites))
		$non_reason_sites += $deleted_non_reason_sites;
	foreach($non_reason_sites as $nrs_id=>$nrs)
	{
		if($test_mode)
		{
			$out[] = 'Would have deleted: '.$nrs->get_value('name').' (id: '.$nrs_id.')';
		}
		else
		{
			delete_entity($nrs_id);
			$out[] = 'Deleted: '.$nrs->get_value('name').' (id: '.$nrs_id.')';
		}
	}
	
	$non_reason_sites = array();
	$pending_non_reason_sites = array();
	$deleted_non_reason_sites = array();
	
	// Delete site types not in use
	$out[] = '<h2>Started Unused Site Types</h2>';
	$es = new entity_selector();
	$es->set_num($limit);
	$site_types = $es->run_one(id_of('site_type_type'));
	$pending_site_types = $es->run_one(id_of('site_type_type'), 'Pending');
	if(!empty($pending_site_types))
		$site_types += $pending_site_types;
	$deleted_site_types = $es->run_one(id_of('site_type_type'), 'Deleted');
	if(!empty($deleted_site_types))
		$site_types += $deleted_site_types;
	foreach($site_types as $st_id=>$st)
	{
		if(!$st->has_right_relation_of_type( 'site_to_site_type' ))
		{
			if($test_mode)
			{
				$out[] = 'Would have deleted: '.$st->get_value('name').' (id: '.$st_id.')';
			}
			else
			{
				delete_entity($st_id);
				$out[] = 'Deleted: '.$st->get_value('name').' (id: '.$st_id.')';
			}
		}
	}
	$site_types = array();
	$pending_site_types = array();
	$deleted_site_types = array();
	
	// Delete most blurbs
	$out[] = '<h2>Started Blurbs</h2>';
	$es = new entity_selector();
	$es->set_num($limit);
	$es->add_relation('entity.unique_name NOT IN ("'.implode('","',$minimal_blurbs).'")');
	$blurbs = $es->run_one(id_of('text_blurb'));
	$pending_blurbs = $es->run_one(id_of('text_blurb'), 'Pending');
	if(!empty($pending_blurbs))
		$blurbs += $pending_blurbs;
	$deleted_blurbs = $es->run_one(id_of('text_blurb'), 'Deleted');
	if(!empty($deleted_blurbs))
		$blurbs += $deleted_blurbs;
	foreach($blurbs as $bl_id=>$bl)
	{
		if($test_mode)
		{
			$out[] = 'Would have deleted: '.$bl->get_value('name').' (id: '.$bl_id.')';
		}
		else
		{
			delete_entity($bl_id);
			$out[] = 'Deleted: '.$bl->get_value('name').' (id: '.$bl_id.')';
		}
	}
	$blurbs = array();
	$pending_blurbs = array();
	$deleted_blurbs = array();
	
	// Delete most users
	$out[] = '<h2>Started Users</h2>';
	$es = new entity_selector();
	$es->set_num($limit);
	$es->add_relation('entity.name NOT IN ("'.implode('","',$minimal_users).'")');
	$users = $es->run_one(id_of('user'));
	$pending_users = $es->run_one(id_of('user'), 'Pending');
	if(!empty($pending_users))
		$users += $pending_users;
	$deleted_users = $es->run_one(id_of('user'), 'Deleted');
	if(!empty($deleted_users))
		$users += $deleted_users;
	foreach($users as $usr_id=>$usr)
	{
		if($test_mode)
		{
			$out[] = 'Would have deleted: '.$usr->get_value('name').' (id: '.$usr_id.')';
		}
		else
		{
			delete_entity($usr_id);
			$out[] = 'Deleted: '.$usr->get_value('name').' (id: '.$usr_id.')';
		}
	}
	
	$users = array();
	$pending_users = array();
	$deleted_users = array();
	
	// remove all pending, deleted, and archived everythings
	$out[] = '<h2>Entered nonlive entity deletion phase</h2>';
	$q = 'SELECT `id`,`name`,`state` FROM `entity` WHERE `state` != "Live"';
	if($limit > 0)
	{
		$q .= ' LIMIT 0,'.$limit;
	}
	$r = db_query($q);
	while($row = mysql_fetch_array($r, MYSQL_ASSOC))
	{
		if(!empty($row['id']))
		{
			if($test_mode)
			{
				$out[] = 'Would have deleted: '.strip_tags($row['name']).' (id: '.$row['id'].'; state: '.$row['state'].')';
			}
			else
			{
				delete_entity($row['id']);
				$out[] = 'Deleted: '.strip_tags($row['name']).' (id: '.$row['id'].'; state: '.$row['state'].')';
			}
		}
	}
	
	pray($out);
	
	echo '<p><a href="?">Return to form</a></p>';
	
	if($limit == -1)
	{
		echo '<p><a href="minimize_3.php">Go to step 3</a></p>';
	}
}
?>
</body>
</html>
