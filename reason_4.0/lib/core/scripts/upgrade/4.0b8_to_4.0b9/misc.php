<?php
/**
 * Add miscellaneous updates to Reason
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
<title>Upgrade Reason: Make MIscellaneous Updates</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('scripts/upgrade/4.0b8_to_4.0b9/update_types_lib.php');
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

<h2>Reason: Miscellaneous Updates</h2>
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
<li>Update the External CSS type to include media directives</li>
<li>Add the entity_locks table</li>
<li>Add a user called 'anonymous'</li>
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
	
	// Update css table
	if ($mode == 'run')
	{
		$update = new updateTypes($mode, $reason_user_id);
		$update->add_field_to_entity_table('external_css', 'css_media', 'tinytext');
	}
	else
	{
		echo '<p>Would have made sure the external_css table had the field css_media</p>';
	}
	
	// Add locks table
	if ($mode == 'run')
	{
		reason_include_once('classes/locks.php');
		$e = new entity(id_of('type'));
		$locks = new ReasonEntityLocks($e);
		if($locks->enable_locks())
			echo '<p>entity_lock table now exists.</p>';
		else
			echo '<p>entity_lock table not created.</p>';
	}
	else
	{
		echo '<p>Would have made sure the locks table exists.</p>';
	}
	
	if ($mode == 'run')
	{
		if (make_sure_username_is_user('anonymous', id_of('master_admin'))) {
			echo '<p>anonymous user exists.</p>';
		} else {
			echo '<p>anonymous user not created.</p>';
		}
	}
	else
	{
		echo '<p>Would have added a user called "anonymous".</p>';
	}
	
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
