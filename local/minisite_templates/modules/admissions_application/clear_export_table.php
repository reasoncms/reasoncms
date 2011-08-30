<?php
/**
 * @author Steve Smith
 * Description:
 * Clears the files out of /var/reason_admissions_app_exports/application_exports
 * Run from the application export page which is currently at www.luther.edu/apply/export
 *
 */
include_once('reason_header.php');
require_once( '/usr/local/webapps/reason/reason_package/carl_util/db/db.php' );
reason_include_once('function_libraries/user_functions.php');

force_secure_if_available();

$username = check_authentication();
$group = id_of('application_export_group');
$gh = new group_helper();
$gh->set_group_by_id($group);
$has_access = $gh->has_authorization($username);



?>
