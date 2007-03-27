<?php
/*
 * db functions
 */

//-----------------------------------------------
// CONNECTION SCRIPT
//-----------------------------------------------
if(!defined("_DBPHP3" ))
{
	define("_DBPHP3", 1);
	include_once( 'paths.php' );
	include_once( CARL_UTIL_INC . 'basic/misc.php' ); // for get_microtime
	include_once( CARL_UTIL_INC . 'error_handler/error_handler.php' );
	include_once( 'connectDB.php' );
	include_once( 'db_query.php' );

}
?>
