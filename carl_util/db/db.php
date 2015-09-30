<?php
/**
 * Includes connectDB.php and db_query.php, along with a few useful libraries
 *
 * @package carl_util
 * @subpackage db
 *
 * @todo remove old method of enforcing require_once
 */

/**
 * Old php3-style method of enforcing require_once
 */
if(!defined("_DBPHP3" ))
{
	define("_DBPHP3", 1);
	include_once( 'paths.php' );
	include_once( CARL_UTIL_INC . 'basic/misc.php' ); // for get_microtime
	include_once( CARL_UTIL_INC . 'error_handler/error_handler.php' );
	include_once( 'connectDB.php' );
	include_once( 'db_query.php' );
	include_once( 'sql_string_escape.php' );

}
?>
