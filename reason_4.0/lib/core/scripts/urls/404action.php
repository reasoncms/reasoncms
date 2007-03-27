<?php
/*
    Checks to see if the current URL is in our history database. 
*/

$reason_session = false;
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/URL_History.php' );

check_URL_history( get_current_url() );
?>
