<?php
/**
 * Fourth step of the db minization process: file removal
 *
 * This stage of the script removes the following items from this Reason instance:
 * - All image files that do not correspond to an image
 * - All asset files that do not correspond to an asset
 * - All cache files
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
<title>Minimize the Reason DB - Step 4</title>
</head>
<style type="text/css">
h2,h3 {
	display:inline;
}
</style>
<body>
<h1>Minimize this Reason Instance: Step 4</h1>
<?php

if(empty($_POST['do_it']) && empty($_POST['test_it']))
{
?>
<form method="post">
<p>This stage of the script removes the following items from this Reason instance:</p>
<ul>
<li>All image files that do not correspond to an image</li>
<li>All asset files that do not correspond to an asset</li>
<li>All cache files</li>
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
$limit_sel .= '<option value="100000000" selected="selected">All</option>';
$limit_sel .= '</select>';

if(PREVENT_MINIMIZATION_OF_REASON_DB)
{
	
	echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>true</strong>.  This means that this script will not do anything when run. You can, however, see what this script <strong>would</strong> do by clicking the button below.</p>';
	echo '<p><label for="limit">Test delete: </label>'.$limit_sel.' items per type</p>';
	echo '<input type="submit" name="test_it" value="Test the script" />';
}
else
{
	echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>false</strong>.  This means that this instance has been set up in a way that allows this script to be run. Remember to <em>only run this script on a <strong>copy</strong> of your real Reason instance</em>.</p>';
echo '<p><label for="limit">Delete (or test delete): </label>'.$limit_sel.' items per type</p>';
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
		$limit = 100000000;
	}
	
	echo '<p><a href="?">Return to form</a></p>';
	
	$es = new entity_selector();
	$all_images = $es->run_one(id_of('image'));
	$all_assets = $es->run_one(id_of('asset'));
	$ignore = array('.','..','.htaccess');
	
	// All entities that do not belong to a site
	
	$out[] = '<h2>Entered image deletion phase</h2>';
	
	$i = 1;
	$d = dir(PHOTOSTOCK);
	while (false !== ($entry = $d->read()))
	{
		if($i > $limit) break;
		if(!in_array($entry,$ignore))
		{
			$parts1 = explode('.',$entry);
			$parts2 = explode('_',$parts1[0]);
			$id = turn_into_int($parts2[0]);
			$es = new entity_selector();
			$es->add_relation('entity.id = '.$id);
			$es->set_num(1);
			$images = $es->run_one(id_of('image'));
			if(empty($images))
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted '.$entry.' (id: '.$id.')';
				}
				else
				{
					unlink(PHOTOSTOCK.$entry);
					$out[] = 'Deleted '.$entry.' (id: '.$id.')';
				}
				$i++;
			}
		}
	}
	$d->close();
	
	$out[] = '<h2>Entered asset deletion phase</h2>';
	
	$i = 1;
	$d = dir(ASSET_PATH);
	while (false !== ($entry = $d->read()))
	{
		if($i > $limit) break;
		if(!in_array($entry,$ignore))
		{
			$parts = explode('.',$entry);
			$id = turn_into_int($parts[0]);
			$es = new entity_selector();
			$es->add_relation('entity.id = '.$id);
			$es->set_num(1);
			$assets = $es->run_one(id_of('asset'));
			if(empty($assets))
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted '.$entry.' (id: '.$id.')';
				}
				else
				{
					unlink(ASSET_PATH.$entry);
					$out[] = 'Deleted '.$entry.' (id: '.$id.')';
				}
				$i++;
			}
		}
	}
	$d->close();
	
	$out[] = '<h2>Entered cache deletion phase</h2>';
	$i = 1;
	$d = dir(REASON_CACHE_DIR);
	while (false !== ($entry = $d->read()))
	{
		if($i > $limit) break;
		if(!in_array($entry,$ignore))
		{
			
			if($test_mode)
			{
				$out[] = 'Would have deleted '.$entry;
			}
			else
			{
				if(unlink('/'.trim_slashes(REASON_CACHE_DIR).'/'.$entry))
					$out[] = 'Deleted '.$entry;
				else
					$out[] = 'Unable to delete '.$entry;
			}
			$i++;
		}
	}
	$d->close();
	
	
	pray($out);
	
	echo '<p><a href="?">Return to form</a></p>';
	
	echo '<p><a href="minimize_5.php">Next step</a></p>';
}
?>
</body>
</html>
