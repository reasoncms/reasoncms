<?php
/**
 * Removes rewrite finish actions for types that now have rewrites handled in the content manager
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
<title>Upgrade Reason: Remove Unnecessary Finish Actions from Types</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class removeRewriteFinishActions
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
		$this->remove_minisite_page_rewrite_finish_action();
	}
	
	function remove_minisite_page_rewrite_finish_action()
	{
		$minisite_page_type_entity = new entity(id_of('minisite_page'));
		$finish_action = $minisite_page_type_entity->get_value('finish_actions');
		if ($finish_action != 'update_rewrites.php')
		{
			echo '<p>The minisite_page finish action is not update_rewrites.php - this script has probably been run</p>';
		}
		elseif ($this->mode == 'test') echo '<p>Would remove the update_rewrites.php finish action from the minisite_page type</p>';
		elseif ($this->mode == 'run')
		{
			$updates = array('finish_actions' => '');
			reason_update_entity(id_of('minisite_page'), $this->reason_user_id, $updates);
			echo '<p>Removed the update_rewrites.php finish action from the minisite_page type</p>';
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
<h2>Reason: Remove Rewrite Finish Actions</h2>
<p>We have been using finish actions to update the rewrite rules, instead of performing these actions when an entity is saved. This creates some
problems. Previously, when a page would have its url_fragment changed, the page would not actually be accessible until the entity was "finished." Now,
the rewrites are updated anytime a url_fragment change or parent change takes place.</p>
<p><strong>What will this update do?</strong></p>
<ul>
<li>Removes the finish action for pages.</li>

<?php
/* TODO
<li>Removes the finish action for sites.</li>
<li>Removes the finish action for assets.</li>*/
?>

</ul>

<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new removeRewriteFinishActions();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
