<?php
/**
 * Upgrade the site parent relationship to be many-to-many
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
<title>Upgrade Reason: make site parent relationship many-to-many</title>
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
<h2>Reason: update the site parent relationship to be many-to-many</h2>
<p>This update will make the parent site relationship any-to-many (allowing sites to have multiple parent sites)</p>
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
	
	if($alrel_id = relationship_id_of('parent_site'))
	{
		if($_POST['go'] == 'run')
		{
			if($GLOBALS['sqler']->update_one('allowable_relationship', array('connections'=>'many_to_many'), $alrel_id))
			{
				echo '<p>Successfully updated the parent_site allowable relationship to be many-to-many</p>';
			}
			else
			{
				echo '<p>Unable to update the parent_site allowable relationship to be many-to-many. You many want to use the <a href="'.REASON_WEB_ADMIN_PATH.'?cur_module=AllowableRelationshipManager">allowable relationship manager</a> to do this update (Change the "Connections" field to "many_to_many.")</p>';
			}
		}
		else
		{
			echo '<p>Would have updated the parent_site allowable relationship to be many-to-many</p>';
		}
	}
	else
	{
		echo '<p>The allowable relationship "parent_site" does not appear to exist in your Reason instance. This upgrade script does not need to be run.</p>';
	}
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
