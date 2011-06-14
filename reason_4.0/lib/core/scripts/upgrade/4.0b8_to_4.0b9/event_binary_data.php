<?php
/**
 * Add location-related fields to Reason's event type.
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
<title>Upgrade Reason: Add Event Binary Data</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('scripts/upgrade/4.0b8_to_4.0b9/update_types_lib.php');
reason_include_once('function_libraries/user_functions.php');

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

<h2>Reason: Add MySQL spatial data support</h2>
<p>Upgrade the Reason event type to support MySQL spatial data.</p>
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
<p><strong>What will this update do?</strong></p>
<p>This optional update will setup your database to store spatial data in binary format for optimal geolocation support. While not required, 
future geolocation features may only work if MySQL spatial data support is enabled. After you successfully modify your database such that this script reports 
the existence of the geopoint field, you should make sure to also change REASON_MYSQL_SPATIAL_DATA_AVAILABLE in reason_settings.php to true.</p>
<p>There are a number of requirements that could make this update script fail. While we will try to determine common points of failure and alert 
you before running anything that alters your database, you should make sure you test this script thoroughly and back up your database before attempting 
to run this script. You also may have to run the commands to create database triggers manually as the database user used by Reason may not have the correct 
privileges, especially if you are using a version of MySQL 5 prior to 5.1.6.</p>
<ul>
<li>Adds event.geopoint field (point)</li>
<li>Populates event.geopoint for all event entities</li>
<li>Adds a spatial index on event.geopoint</li>
<li>Creates databse triggers on insert / update to keep geopoint updated</li>
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
	if ($mode)
	{
		$update = new updateTypes($mode, $reason_user_id);
		$update->add_event_binary_data($mode, $reason_user_id);
	}
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
