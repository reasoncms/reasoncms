<?php
/**
 * This file includes a number of core libraries and functions needed by reason
 *
 * It is included by reason_header.php and should not be included by anything else directly
 *
 * @package reason
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
	ob_start();

	// setup the REASON_LOGIN_URL constant dynamically based upon value of HTTPS_AVAILABLE
	if (HTTPS_AVAILABLE)
	{
		define( 'REASON_LOGIN_URL', 'https://'.HTTP_HOST_NAME.'/'.REASON_LOGIN_PATH );
	}
	else define( 'REASON_LOGIN_URL', 'http://'.HTTP_HOST_NAME.'/'.REASON_LOGIN_PATH );

	if (function_exists('date_default_timezone_set')) // for php5, set default timezone if the constant is defined
	{
		if (defined('REASON_DEFAULT_TIMEZONE'))
		{
			date_default_timezone_set(REASON_DEFAULT_TIMEZONE);
		}
	}
	if(!defined('REASON_DEFAULT_ALLOWED_TAGS'))
	{
		define('REASON_DEFAULT_ALLOWED_TAGS','<a><abbrev><acronym><address><area><au><author><b><big><blockquote><bq><br><caption><center><cite><code><col><colgroup><credit><dfn><dir><div><dl><dt><dd><em><fn><form><h1><h2><h3><h4><h5><h6><hr><i><img><input><lang><lh><li><link><listing><map><math><menu><multicol><nobr><note><ol><option><p><param><person><plaintext><pre><samp><select><small><strike><strong><sub><sup><table><tbody><td><textarea><tfoot><th><thead><tr><tt><u><ul><var><wbr>');
	}
?>
