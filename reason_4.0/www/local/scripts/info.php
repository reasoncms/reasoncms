<?php
include_once('reason_header.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/user_functions.php');

if (!reason_user_has_privs( id_of( reason_check_authentication() ), 'db_maintenance' ) )
{
    die('<html><head><title>Reason: PHP info</title></head><body><h1>Sorry.</h1><p>You do not have permission to view this page.</p><p>Only Reason users who have database maintenance privileges may do that.</p></body></html>');
} else {
    phpinfo();
}
?>