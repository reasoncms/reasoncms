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
 *	Query tracking is memory expensive (4-5K per call), so if you want to use it, you need
 *	to set $GLOBALS['_db_query_enable_tracking'] to true in your script.
 *
 *	@param	$query	the query to run
 *	@param	$error_message	the custom error message
 *	@param	$die_on_error	boolean variable that determines whether to die on an error
 *	@return	query result if query succeeds or false if query fails.
 */

function db_query( $query, $error_message = '', $die_on_error = true )
{
	// keep track of all queries
	static $queries = array();
	static $first_run = true;
	
	if ($first_run)
	{
		if (isset($GLOBALS['_db_query_first_run_connection_name']) && !get_current_db_connection_name())
		{
			connectDB($GLOBALS['_db_query_first_run_connection_name']);
		}
		$first_run = false;
	}
	
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
			$distinct_queries = array();
			foreach ($queries as $query)
			{
				if (isset($distinct_queries[$query['q']]))
					$distinct_queries[$query['q']]++;
				else
					$distinct_queries[$query['q']] = 0;
			}
			arsort( $distinct_queries );
			pray( $distinct_queries );
			break;
		case 'REPORT DISTINCT ERRORS':
			$distinct_errors = array();
			foreach ($queries as $query)
			{
				if (isset($distinct_errors[$query['error']]))
					$distinct_errors[$query['error']]++;
				else
					$distinct_errors[$query['error']] = 0;
			}
			arsort( $distinct_errors );
			pray( $distinct_errors );
			break;
		case 'REPORT':
			echo '<br /><br />';
			echo '<strong>Queries run through db_query():</strong> '.count( $queries ).'<br />';
			echo '<strong>Queries:</strong><br />';
			$last = 0;
			// Sweeten the display with some timing information
			foreach ($queries as $key => $query)
			{
				$queries[$key]['length'] = round(($query['endtime'] - $query['starttime'])*1000,4).'ms';
				$queries[$key]['time_since_last_q'] = ($last) ? round(($query['starttime'] - $last)*1000,4).'ms' : 0;
				$last = $query['endtime'];
				// Flatten debug data to take up less space
				foreach ($query['context'] as $dkey => $call)
				{
					$queries[$key]['context'][$dkey] = '...'.substr($call['file'], -40).':'.$call['line'].'  '.$call['function'].'()';
				}
			}
			pray( $queries );
			echo '<strong>Total Time: </strong>'.round((microtime(true) - $queries[0]['starttime'])*1000,4).'ms<br />';
			break;
		// query case
		default:
			if (!empty($GLOBALS['_db_query_enable_tracking'])) $start = microtime(true);
			// run the query
			if( $r = mysql_query( $query ) )
			{
				if (!empty($GLOBALS['_db_query_enable_tracking']))
				{
					$queries[] = array('q' => $query, 
										'error' => $error_message, 
										'starttime' => $start, 
										'endtime' => microtime(true),
										'context' => debug_backtrace(0));
				}
				return $r;
			}
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
