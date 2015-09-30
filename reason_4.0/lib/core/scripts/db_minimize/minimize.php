<?php
/**
 * First step of the db minization process
 *
 * This script allows you to package up a minimal Reason DB for creating a new Reason instance.
 *
 * Note: This is not the best way to go about DB minimization. It would be a lot easier 
 * to have a script which exported <em>only</em> those parts of the DB that were needed, 
 * rather than deleting everything unnecessary.  This method's problems are, primarly, time -- 
 * it is super slow, you have to babysit it as you crunch through the sites in small batches, etc. 
 * But at the moment we have no good way of exporting just pieces of Reason data -- just the 
 * whole shebang as a SQL dump. So at least for the moment this is how we're doing it.
 *
 * This stage of the script will destroy all Reason sites except for a few core ones.
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
<title>Minimize the Reason DB - Step 1</title>
</head>
<style type="text/css">
h2,h3 {
	display:inline;
}
</style>
<body>
<h1>Minimize this Reason Instance: Step 1</h1>
<?php

$minimal_sites = array('master_admin','site_login');

if(empty($_POST['do_it']) && empty($_POST['test_it']))
{
?>
<form method="post">
<p>This script allows you to package up a minimal Reason DB for creating a new Reason instance.</p>
<p><strong>This script is highly destructive.</strong> You should only run this script on a copy of your main Reason instance. <strong>It will destroy all of your Reason sites except for these: <?php echo implode(', ',$minimal_sites); ?>.</strong></p>
<p>This is so destructive there is a Reason setting that expressly prohibits it, and which is true by default: PREVENT_MINIMIZATION_OF_REASON_DB.</p>
<?php
$select = '<select size="1" name="num_sites" id="num_sites">';
$select .= '<option value="1">1</option>';
$select .= '<option value="2">2</option>';
$select .= '<option value="5">5</option>';
$select .= '<option value="10">10</option>';
$select .= '<option value="25">25</option>';
$select .= '<option value="50">50</option>';
$select .= '<option value="100">100</option>';
$select .= '<option value="-1">All (this may take a ridiculous amount of time)</option>';
$select .= '</select>';

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
	echo '<p><label for="num_sites">Number of sites to test deletion on: </label>';
	echo $select;
	echo '</p>';
	echo '<p><label for="limit">Test delete: </label>'.$limit_sel.' items per type per site</p>';
	echo '<input type="submit" name="test_it" value="Test the script" />';
}
else
{
	echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>false</strong>.  This means that this instance has been set up in a way that allows this script to be run. Remember to <em>only run this script on a <strong>copy</strong> of your real Reason instance</em>.</p>';
	echo '<p><label for="num_sites">Number of sites to delete (or test deletion on): </label>';
	echo $select;
	echo '</p>';
	echo '<p><label for="limit">Delete: </label>'.$limit_sel.' items per type per site.<br />(Selections other than "All" will not delete the site itself. Choose a value other than "All" if you have a site which is too big to be resident in memory, and which needs to be dismantled in chunks.)</p>';
	echo '<input type="submit" name="test_it" value="Test the script" />';
	echo '<input type="submit" name="do_it" value="Run the script" />';
}
?>
<p>Note: This is not the best way to go about DB minimization. It would be a lot easier to have a script which exported <em>only</em> those parts of the DB that were needed, rather than deleting everything unnecessary.  This method's problems are, primarly, time -- it is super slow, you have to babysit it as you crunch through the sites in small batches, etc. But at the moment we have no good way of exporting just pieces of Reason data -- just the whole shebang as a SQL dump. So at least for the moment this is how we're doing it.</p>
</form>
<?php
}
else
{
	echo '<p><a href="?">Return to form</a></p>';
	if(!empty($_POST['num_sites']))
	{
		$num_sites = turn_into_int($_POST['num_sites']);
	}
	else
	{
		die('num_sites must be in post');
	}
	if(!empty($_POST['limit']))
	{
		$limit = turn_into_int($_POST['limit']);
	}
	else
	{
		$limit = -1;
	}
	$sites_es = new entity_selector();
	$sites_es->add_type(id_of('site'));
	$sites_es->add_relation('entity.unique_name NOT IN ("'.implode('","',$minimal_sites).'")');
	$sites_es->set_num($num_sites);
	$sites_es->set_order('entity.last_modified DESC');
	$sites = $sites_es->run_one();
	
	$test_mode = true;
	if(!PREVENT_MINIMIZATION_OF_REASON_DB && !empty($_POST['do_it']))
	{
		$test_mode = false;
	}
	
	if(!empty($sites))
	{
		foreach($sites as $site_id=>$site)
		{
			if($test_mode)
			{
				pray(delete_site($site_id, false, array(), $limit));
			}
			else
			{
				pray(delete_site($site_id, true, array(), $limit));
			}
		}
	}
	else
	{
		echo '<p>It appears that all of the sites have been deleted. You are now ready to start <a href="minimize_2.php">step 2</a>.</p>';
	}
	echo '<p><a href="?">Return to form</a></p>';
}

function delete_site($site_id, $do_it = false, $types = array(), $limit_dels = -1)
{
	static $all_types = array();
	if(empty($all_types))
	{
		$es = new entity_selector();
		$es->add_type(id_of('type'));
		$all_types = $es->run_one();
	}
	$out = array();
	$site = new entity($site_id);
	if($site->get_value('type') == id_of('site'))
	{
		$out[] = '<h2>Started deletion process for '.$site->get_value('name').' (id: '.$site_id.')</h2>';
	}
	else
	{
		trigger_error('id given not the id of a site');
		return false;
	}
	/* $es = new entity_selector();
	$es->add_type(id_of('type'));
	$es->add_right_relationship($site_id, relationship_id_of('site_to_type'));
	$types = $es->run_one(); */
	
	$es = new entity_selector($site_id);
	$es->set_sharing( 'owns' );
	$es->set_num($limit_dels);
	/* foreach($types as $type_id=>$type)
	{
		$es->add_type($type_id);
	} */
	if(!empty($types))
	{
		foreach($types as $type_id)
		{
			if(!empty($all_types[$type_id]))
				$types_to_delete[$type_id] = $all_types[$type_id];
		}
	}
	else
	{
		$types_to_delete = $all_types;
	}
	foreach($types_to_delete as $type_id=>$type)
	{
		$out[] = '<h3>Entered '.$type->get_value('name').'</h3>';
		$entities = $es->run_one($type_id);
		$pendings = $es->run_one($type_id, 'Pending');
		$deleteds = $es->run($type_id,'Deleted');
		if(!empty($pendings))
			$entities += $pendings;
		if(!empty($deleteds))
			$entities += $deleteds;
		foreach($entities as $entity_id=>$entity)
		{
			if($do_it)
			{
			 delete_entity( $entity_id );
			 $out[] = 'Deleted '.$entity->get_value('name').' (id: '.$entity_id.')';
			}
			else
			{
			 $out[] = 'Would have deleted '.$entity->get_value('name').' (id: '.$entity_id.')';
			}
		}
	}
	if($do_it && empty($types) && $limit_dels == -1)
	{
		delete_entity( $site_id );
		$out[] = '<h3>Deleted Site: '.$site->get_value('name').'</h3>';
	}
	else
	{
		$out[] = '<h3>Would have deleted site: '.$site->get_value('name').'</h3>';
	}
	// should probably delete .htaccess file here
	$htaccess = '/'.trim_slashes(WEB_PATH).$site->get_value('base_url').'.htaccess';
	if(file_exists($htaccess))
	{
		if($do_it && empty($types) && $limit_dels == -1)
		{
			unlink($htaccess);
			$out[] = '<h3>Deleted '.$htaccess.'</h3>';
		}
		else
		{
			$out[] = '<h3>Would have deleted '.$htaccess.'</h3>';
		}
	}
	return $out;
}
?>
</body>
</html>
