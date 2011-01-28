<?php
/**
 * Wrapper function for querying a database
 * @package carl_util
 * @subpackage db
 */

/**
 * include the paths and error handler
 */
include_once( 'paths.php' );
include_once( CARL_UTIL_INC . 'error_handler/error_handler.php' );
include_once( CARL_UTIL_INC . 'db/connectDB.php' );

/** db_query( $query, $error_message = '', $die_on_error = true ) {{{
 *	Wrapper function for querying the database
 *
 *	Wraps up extra functionality for handling queries.
 *	- easy handling of errors.
 *	- query tracking
 *	- query reporting
 *
 *	@param	$query	the query to run
 *	@param	$error_message	the custom error message
 *	@param	$die_on_error	boolean variable that determines whether to die on an error
 *	@return	query result if query succeeds or false if query fails.
 */

function db_query( $query, $error_message = '', $die_on_error = true )
{
	// keep track of all queries
	static $queries;
	static $distinct_queries;
	static $distinct_errors;
	static $first_run = true;
	
	if ($first_run)
	{
		if (isset($GLOBALS['_db_query_first_run_connection_name']) && !get_current_db_connection_name())
		{
			connectDB($GLOBALS['_db_query_first_run_connection_name']);
		}
		$first_run = false;
	}
	
	if( !isset( $queries ) OR empty( $queries ) )
		$queries = array();
	if( !isset( $distinct_errors ) OR empty( $distinct_errors ) )
		$distinct_errors = array();
	if( !isset( $distinct_queries ) OR empty( $distinct_queries ) )
		$distinct_queries = array();

	$queries[] = array('q' => $query, 'error' => $error_message );

	if( !isset( $distinct_queries[ $query ] ) )
		$distinct_queries[ $query ] = 0;
	$distinct_queries[ $query ]++;

	if( !isset( $distinct_errors[ $error_message ] ) )
		$distinct_errors[ $error_message ] = 0;
	$distinct_errors[ $error_message ]++;

	switch( $query )
	{
		// profiling and reporting cases
		case 'GET NUM QUERIES':
			return count( $queries );
			break;
		case 'GET QUERIES':
			return $queries;
			break;
		case 'REPORT DISTINCT QUERIES':
			arsort( $distinct_queries );
			pray( $distinct_queries );
			break;
		case 'REPORT DISTINCT ERRORS':
			arsort( $distinct_errors );
			pray( $distinct_errors );
			break;
		case 'REPORT':
			echo '<br /><br />';
			echo '<strong>Queries run through db_query():</strong> '.count( $queries ).'<br />';
			echo '<strong>Queries:</strong><br />';
			pray( $queries );
			break;
		// query case
		default:
			// run the query
			if( $r = mysql_query( $query ) )
				return $r;
			// if an error, run through the error fun.
			else
			{
				global $PHP_SELF;
				if( empty( $error_message ) )
					$error_message = 'Bad db query.';
				$body = $error_message.'<br />';
				$body .= 'Query: "'.str_replace("\n",' ',$query).'"<br />';
				$body .= 'Error: "'.mysql_error().'" (errno: "'.mysql_errno().'")';
				$errorlevel = MEDIUM;
				if( $die_on_error )
				{
					$errorlevel = EMERGENCY;
				}
				trigger_error(str_replace("\n",'',nl2br($body)), $errorlevel);
				if( $die_on_error ) // trigger_error should have died appropriately and forwarded to OSHI page, but it can't hurt to make sure...
				{
					die();
				}
				return false;
			}
			break;
	}
} // }}}

?>
