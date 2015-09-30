<?php
/**
 * Checks to see if the current URL is in the URL history table. 
 *
 * This script can be included in any php-based 404 page to automatically redirect requests to
 * pages that have moved in some way: from one place to another in the hierarchy of their site; 
 * due to url snippet renaming; from one site to another.
 *
 * @package reason
 * @subpackage scripts
 */

$reason_session = false;
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/URL_History.php' );

check_URL_history( get_current_url() );
?>
