<?php
/**
 * Upgrade Site Types to use a custom content manager
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
<title>Upgrade content manager for site types</title>
</head>

<body>
<?php
include ('reason_header.php');
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

$stt = new entity(id_of('site_type_type'));

if($stt->get_value('custom_content_handler'))
{
	echo '<p>Site Types already have a content manager. There is no need to run this script!</p>';
}
else
{
	echo '<p>This script will add the site type content manager to this Reason instance. This content manager makes creating site types easier by adding useful comments.</p>';
	if(empty($_POST['run']))
	{
		echo '<form action="?" method="post"><input type="submit" name="run" value="Run It" /></form>';
	}
	else
	{
		$success = reason_update_entity( $stt->id(), $reason_user_id, array('custom_content_handler'=>'site_type.php') );
		if($success)
		{
			echo '<p>Successfully updated site types to use new content manager</p>';
		}
		else
		{
			echo '<p>Unable to update site type to use new content manager. Please see the errors triggered (or the Reason error log) to identify why it did not work. Alternately, you can manually update this Reason instance. Here\'s how:</p>';
			echo '<ol><li>Log in to Reason</li><li>Go into the Master Admin</li><li>Choose "Types"</li><li>Find the type "Site Type" and click Edit</li><li>Choose "Site Type" on the Content Manager field</li><li>Save and finish</li></ol>';
		}
	}
}

echo '<p><a href="index.php">Back to 4.0b4 to 4.0b5 upgrades</a></p>';

?>
</body>
</html>
