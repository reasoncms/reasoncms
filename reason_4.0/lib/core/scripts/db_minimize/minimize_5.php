<?php
/**
 * Fifth step of the db minization process
 *
 * This script serves as a stopping point before continuing to minimize_to_core.php
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
<title>Minimize the Reason DB - Done</title>
</head>
<style type="text/css">
h2,h3 {
	display:inline;
}
</style>
<body>
<h1>Minimize this Reason Instance: Done</h1>
<p>You are now done minimizing this Reason instance.  It is now ready to be used as a base for a local copy.</p>
<p>It's probably a good idea to do a dump of this DB now so you have a record of this state</p>
<h3>Creating a core instance</h3>
<p>If you wish to create a core instance of Reason, there is <a href="minimize_to_core.php">one final step</a>.</p>
</body>
</html>
