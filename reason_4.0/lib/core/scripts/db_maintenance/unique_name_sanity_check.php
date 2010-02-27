<?php
/**
 * Script that helps find conflicting and/or badly formatted unique names
 * @todo add to master admin links
 * @package reason
 * @subpackage scripts
 */
	include_once( 'reason_header.php' );
	include_once(CARL_UTIL_INC . 'db/db.php' );
	include_once(CARL_UTIL_INC . 'dev/pray.php' );
	
	reason_include_once( 'function_libraries/user_functions.php' );
	force_secure_if_available();
	$current_user = check_authentication();
	if (!reason_user_has_privs( get_user_id ( $current_user ), 'db_maintenance' ) )
	{
		die('<html><head><title>Reason: Check Unique Names for Sanity</title></head><body><h1>Sorry.</h1><p>You do not have permission to check unique names.</p><p>Only Reason users who have database maintenance privileges may do that.</p></body></html>');
	}
	
	?>
	<html>
	<head>
	<title>Reason: Check Unique Names for Sanity</title>
	</head>
	<body>
	<h1>Check Unique Names for Sanity</h1>
	<?php
	if(empty($_POST['do_it']))
	{
	?>
	<form method="post">
	<p>Unique names are human-readable keys for entities in Reason. For a given entity, the unique_name field may be empty, but if there is any content in the unique_name field, it must be:</p>
	<ol>
	<li>unique, and</li>
	<li>contain only low-ascii letters, numbers, and underscores.</li>
	</ol>
	<p>This script finds any unique names that do not fit those criteria, and reports on them.  Fixing bad unique names is currently a manual process.</p>
	<input type="submit" name="do_it" value="Run the script" />
	</form>
	<?php
	}
	else
	{

		reason_include_once('classes/entity_selector.php');
		
		$dbs = new DBSelector();
		$dbs->add_table('entity');
		$dbs->add_field('entity','id','id');
		$dbs->add_field('entity','unique_name','unique_name');
		$dbs->add_field('entity','name','name');
		$dbs->add_relation('`unique_name` != ""');
		$dbs->add_relation('unique_name IS NOT NULL');
		$dbs->add_relation('`state` IN ("Live","pending")');
		$results = $dbs->run('Error getting unique names');
		
		$uniques = array();
		$names = array();
		foreach($results as $result)
		{
			if(empty($uniques[$result['unique_name']]))
			{
				$uniques[$result['unique_name']] = array();
			}
			$uniques[$result['unique_name']][$result['id']] = $result['name'];
		}
		foreach($uniques as $uname=>$items)
		{
			$bad_string = false;
			$multiples = false;
			if(!preg_match( "|^[0-9a-z_]*$|i" , $uname ))
			{
				$bad_string = true;
			}
			if(count($items) > 1)
			{
				$multiples = true;
			}
			if($bad_string || $multiples)
			{
				echo '<h3>"'.$uname.'" :: ';
				if($bad_string)
				{
					echo 'Bad string ';
				}
				if($multiples)
				{
					echo 'Multiple Items';
				}
				echo '</h3>';
				if($multiples) echo '<p>Items:</p>';
				else echo '<p>Item:</p>';
				echo '<ul>';
				foreach($items as $id=>$name)
				{
					$ent = new entity($id);
					$type = new entity($ent->get_value('type'));
					$owner = $ent->get_owner();
					echo '<li>';
					echo '<strong>'.$name.'</strong>';
					echo '<ul>';
					echo '<li>ID: '.$id.'</li>';
					echo '<li>State: '.$ent->get_value('state').'</li>';
					echo '<li>Type: '.$type->get_value('name').'</li>';
					echo '<li>Site: '.$owner->get_value('name').'</li>';
					echo '</ul>';
					echo '</li>';
				}
				echo '</ul>';
			}
			else
			{
				unset($uniques[$uname]);
			}
		}
		if(empty($uniques))
		{
			echo '<h3>Yay! There are no bad unique names in the Reason db.</h3>';
		}
	}

?>
