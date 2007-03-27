<?php
/**
 * This file includes a number of core libraries and functions needed by reason
 * It is included by reason_header.php ans should not be included by anything else directly
 *
 */
	include_once( CARL_UTIL_INC.'error_handler/error_handler.php' );
	
	// the pray() function
	include_once( CARL_UTIL_INC.'dev/pray.php' );
	
	// misc stuff: prettify_string() and unhtmlentities()
	include_once( CARL_UTIL_INC.'basic/misc.php' );
	
	// the prp function
	include_once( CARL_UTIL_INC.'dev/prp.php' );
	
	// load core db functions - connectDB() and db_query()
	include_once( CARL_UTIL_INC.'db/db.php' );

	//load file finder functions - reason_get_merged_fileset() and reason_file_exists()
	include_once ( REASON_INC .'lib/core/function_libraries/file_finders.php' );
	
	include_once( REASON_INC . 'lib/core/function_libraries/reason_includers.php');

	// include the settings for reason.
	//reason_include_once( 'settings.php' );
	include_once( SETTINGS_INC.'reason_settings.php');

	// load all the factory functions
	reason_include_once( 'function_libraries/factories.php' );

	// load the session library
	reason_include_once( 'function_libraries/reason_session.php' );

	// load utility functions
	reason_include_once( 'function_libraries/util.php' );
	

	// make sure this variable is set to something by default
	if( !isset( $reason_session ) )
		$reason_session = false;

	// start a session if this is a web page and if the session variable is set.
	if( empty( $_SERVER[ '_' ] ) AND !empty( $reason_session ) )
	{
		ini_set( 'session.use_cookies', 1 );
		ini_set( 'session.use_only_cookies', 1 );
		ini_set( 'session.use_trans_sid', 0 );
		session_start();
	}

	// start output buffering
	// if the request variable _ob_off is non-empty, output buffering will not be used
	if( empty( $_REQUEST[ '_ob_off' ] ) )
		ob_start();

	error_reporting( E_ALL );
// }
?>
