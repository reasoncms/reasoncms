<?php
/**
 * Reason 4 Beta 8 database cleanup and maintenance
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
<title>Upgrade Reason: Database Cleanup and Maintenance</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
include_once( CARL_UTIL_INC . 'db/sqler.php' );
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');


class databaseCleanup
{
	var $mode;
	var $reason_user_id;
		
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
		$this->change_relationship_terminology('page_to_publication', 'Places a blog / publication on a page', 'Place a blog / publication');
		$this->change_relationship_terminology('page_to_related_publication', 'Places a related publication on a page', 'Attach a related publication');
		$this->remove_fields_from_non_reason_site_type();
	}
	
	function change_relationship_terminology($rel_name, $term_search, $term_replace)
	{
	
		$rel_id = relationship_id_of($rel_name);
		if ($rel_id)
		{
			$res = mysql_fetch_assoc(db_query('SELECT * from allowable_relationship WHERE id='.$rel_id));
			foreach ($res as $k=>$v)
			{
				if ($v == $term_search) $update[$k] = $term_replace;
			}
			if (isset($update))
			{
				if ($this->mode == 'test') echo '<p>Would update the ' . $rel_name . ' allowable relationship terminology</p>';
				if ($this->mode == 'run')
				{
					$sqler = new sqler;
					$sqler->update_one( 'allowable_relationship', $update, $rel_id );
					echo '<p>Updated the ' . $rel_name . ' allowable relationship terminology</p>';
				}
			}
			else
			{
				echo '<p>The ' . $rel_name . ' allowable relationship terminology is up to date</p>';
			}
		}
	}
	
	function remove_fields_from_non_reason_site_type()
	{
		// $minisite_page_type_entity = new entity(id_of('minisite_page'));
// 		$finish_action = $minisite_page_type_entity->get_value('finish_actions');
// 		if ($finish_action != 'update_rewrites.php')
// 		{
// 			echo '<p>The minisite_page finish action is not update_rewrites.php - this script has probably been run</p>';
// 		}
// 		elseif ($this->mode == 'test') echo '<p>Would remove the update_rewrites.php finish action from the minisite_page type</p>';
// 		elseif ($this->mode == 'run')
// 		{
// 			$updates = array('finish_actions' => '');
// 			reason_update_entity(id_of('minisite_page'), $this->reason_user_id, $updates);
// 			echo '<p>Removed the update_rewrites.php finish action from the minisite_page type</p>';
// 		}
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
<h2>Reason: Database Cleanup and Maintenance</h2>
<p>This script addresses various small issues with wording, unnecessary fields in types, and more.</p>
<p><strong>What will this update do?</strong></p>
<ul>
<li>Changes "Places blog/publication on a page" to "Place a blog/publication" (addresses <a href="http://code.google.com/p/reason-cms/issues/detail?id=33">issue 33</a>)</li>
<li>Changes "Places a related publication on a page" to "Attach a related publication" (addresses <a href="http://code.google.com/p/reason-cms/issues/detail?id=33">issue 33</a>)</li>

<?
/* TODO
<li>Removes the finish action for sites.</li>
<li>Removes the finish action for assets.</li>*/
?>

</ul>

<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new databaseCleanup();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
