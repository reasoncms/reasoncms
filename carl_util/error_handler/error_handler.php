<?php

	// make sure all errors hit the error handler
	error_reporting( E_ALL );
	
	$host = !empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : 'cli';
	
	require_once( 'paths.php');
	// This is where the $GLOBALS['_DEVELOPER_INFO'] array gets set up
	require_once(SETTINGS_INC.'error_handler_settings.php');
	include_once( CARL_UTIL_INC . 'basic/misc.php' );
	include_once( CARL_UTIL_INC . 'dev/pray.php' );
	
	// quick boolean function to determine if the visitor in a particular transaction is a developer or not.  uses the
	// global array of IPs that is actually defined later on.
	function is_developer()
	{
		if( !empty( $_SERVER[ 'REMOTE_ADDR' ] ) )
		{
			return in_array( $_SERVER['REMOTE_ADDR'], $GLOBALS['_DEVELOPER_IPS'] );
		}
		else
		{
			// no IP address - almost definitely running from the command line
			return true;
		}
	}
	
	/*
		
		Usage of error handling system
		
		If you want to use the error notification system coded up here, all you
		really have to do is use the trigger_error() or user_error() functions.
		They are both the same function, so you can use whichever you please.
		The basic usage is trigger_error( error_message, error_level ), so, for
		example, if I want to trigger an emergency message for some action, I
		would use trigger_error( "Something really bad happened", EMERGENCY ).
		The constants defined below can be used in place of EMERGENCY for
		different levels of notification.  
		
	*/
	
	
	// Constants for programmer level error triggering
	
	// If a machine with one of the listed IPs gets ones of these errors,
	// they behave like normal PHP errors and just get printed out.
	// If the error is bad enough, the current script exit()s.
	// The notification only happens if a non-developer (i.e. most of the
	// world) triggers an error.
	
	// EMERGENCY is for really bad errors.  If an EMERGENCY is triggered,
	// cell phones should be alerted.  Alias: FATAL
	define( 'EMERGENCY', E_USER_ERROR );
	define( 'FATAL', E_USER_ERROR );

	// HIGH is for pretty bad errors.  These are errors that don't
	// takes sites down, but result in bad pages.  These usually go
	// to email.  Alias: ERROR
	define( 'HIGH', E_USER_WARNING );
	define( 'ERROR', E_USER_WARNING );

	// MEDIUM errors just get logged.  Check out the error handler code to 
	// see where they go.  Alias: WARNING
	define( 'MEDIUM', E_USER_NOTICE );
	define( 'WARNING', E_USER_NOTICE );

	
	

	// error email from address
	// NOTE: by looking at the email address that an error comes from you can determine
	// where the error occurred.  errors@webdev is webdev, errors@webapps is webapps, and
	// apache@Detroit is a command line script error
	if( !empty( $_SERVER['HTTP_HOST'] ) )
	{
		$err_email_from = 'errors@'.$_SERVER['HTTP_HOST'];
	}
	else
	{
		// get hostname from command line program 'hostname' since HTTP_HOST and SERVER_NAME are null on the command
		// line
		$err_email_from = 'cli_error@'.strtolower(trim(`hostname`));
	}
	define( 'ERROR_EMAIL_FROM', $err_email_from );

	$GLOBALS[ 'ERRNO_TO_ERROR' ] = array(
		FATAL => 'FATAL',
		ERROR => 'ERROR',
		WARNING => 'WARNING',
		E_WARNING => 'WARNING',
		E_NOTICE => 'NOTICE',
	);

	
	
	// make a flat array of IP addresses to easily check
	$GLOBALS[ '_DEVELOPER_IPS' ] = array();
	foreach( $GLOBALS[ '_DEVELOPER_INFO' ] AS $name => $dev )
		if( !empty( $dev[ 'ip' ] ) )
			if( is_array( $dev['ip'] ) )
				$GLOBALS[ '_DEVELOPER_IPS' ] = array_merge( $GLOBALS[ '_DEVELOPER_IPS' ], $dev['ip'] );
			else
				$GLOBALS[ '_DEVELOPER_IPS' ][] = $dev[ 'ip' ];
	
	
	// Here is the code that actually looks to see if maintenance mode is on and to check if the current user is one of
	// the developers.
	// make double sure about the state of maintenance mode
	if( defined( 'MAINTENANCE_MODE_ON' ) AND MAINTENANCE_MODE_ON === true )
	{
		if( !is_developer() )
		{
			header( 'Location: '.MAINTENTANCE_MODE_URL.'?estimate='.$GLOBALS['_maintenance_estimate'] );
			die();
		}
		else
		{
			echo '<h1>MAINTENANCE MODE IS ON</h1>';
		}
	}
	
	// controls whether errors ore logged to error logging file
	$GLOBALS['_carl_util_error_logging'] = true;
	
	function turn_carl_util_error_logging_off()
	{
		$GLOBALS['_carl_util_error_logging'] = false;
	}
	function turn_carl_util_error_logging_on()
	{
		$GLOBALS['_carl_util_error_logging'] = true;
	}
	function carl_util_log_errors()
	{
		return $GLOBALS['_carl_util_error_logging'];
	}
	
	// controls whether errors ore written to the output (only affects admin IP addresses)
	$GLOBALS['_carl_util_error_output'] = true;
	
	function turn_carl_util_error_output_off()
	{
		$GLOBALS['_carl_util_error_output'] = false;
	}
	function turn_carl_util_error_output_on()
	{
		$GLOBALS['_carl_util_error_output'] = true;
	}
	function carl_util_output_errors()
	{
		return $GLOBALS['_carl_util_error_output'];
	}
	
	// the actual error handler function.  this is put into place below the function declaration
	function carlUtilErrorHandler( $errno, $errstr, $errfile, $errline, $context )
	{
		// developer actions
		if( is_developer() AND empty($_REQUEST['nodebug']) AND carl_util_output_errors())
		{
			// handle error_reporting the correct way.  If this type of error
			// was not set in error_reporting(), do not report an error.
			if( !($errno & error_reporting())) return;
			$err = '<div style="border: 1px #f00 dashed; background-color: #ddd; padding: 8px">';
			switch ($errno)
			{
				case FATAL:
					$err .= "<strong>FATAL:</strong> $errstr on line $errline of file $errfile\n";
					$err .= '<pre>Error Context:'."\n\n";
					$err .= sprint_r( $context );
					$err .= '</pre>';
					$err .= '<br /><br /><strong>Script Execution Terminated.</strong>';
					break;
				case ERROR:
					$err .= "<strong>ERROR:</strong> $errstr on line $errline of file $errfile\n";
					$err .= '<pre>Error Context:'."\n\n";
					$err .= sprint_r( $context );
					$err .= '</pre>';
					$err .= '<br /><br /><strong>Script Execution Terminated.</strong>';
					break;
				case E_WARNING:
				case WARNING:
					$err .=  "<strong>WARNING:</strong> $errstr on line $errline of file $errfile\n";
					break;
				case E_NOTICE:
					$err .=  "<strong>NOTICE:</strong> $errstr on line $errline of file $errfile\n";
					break;
				default:
					$err .=  "Unknown error type: [$errno] $errstr on line $errline of file $errfile\n";
					break;
			}
			$err .= '</div>';
			
			if( php_sapi_name() == 'cli' )
			{
				$err = strip_tags( preg_replace('=<br */?>=i', "\n", $err) );
			}
			
			echo $err;
			
			if( $errno == FATAL OR $errno == ERROR )
			{
				exit;
			}
		}
		// rest of the world actions
		else
		{
			// log the error
			$err_parts = array(
				'type' => $GLOBALS['ERRNO_TO_ERROR'][$errno],
				'time' => date('r'),
				'msg' => $errstr,
				'line' => $errline,
				'file' => $errfile,
				'uri' => $_SERVER['REQUEST_URI'],
				'ip' => $_SERVER['REMOTE_ADDR'],
				'ua' => $_SERVER['HTTP_USER_AGENT'],
				'errno' => $errno,
				'referer' => (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
			);
			array_walk( $err_parts, 'quote_walk' );
			$err = join( $err_parts, ',' );

			if(carl_util_log_errors())
			{
				dlog( $err, PHP_ERROR_LOG_FILE );
			}

			// emergency contact
			if( $errno == EMERGENCY )
			{
				// attempt to contact people directly through cell phones
				$to = array();
				foreach( $GLOBALS[ '_DEVELOPER_INFO' ] AS $name => $dev )
					if( !empty( $dev[ 'pager' ] ) )
						$to[] = $name.' <'.$dev['pager'].'>';
			}

			// email devs if high or emergency
			if( in_array( $errno, array( EMERGENCY, HIGH ) ) )
			{
				//------- email all involved
				// get email addresses
				$to = array();
				foreach( $GLOBALS[ '_DEVELOPER_INFO' ] AS $name => $dev )
					if( !empty( $dev[ 'email' ] ) )
						$to[] = $name.' <'.$dev['email'].'>';
				
				$subject = SHORT_ORGANIZATION_NAME.' Web: '.($errno == EMERGENCY ? 'Emergency' : 'High' ).' Level Error';

				// set up the body of the message
				$body_arr = array(
					'--- Error/Script Info ---',
					'Error: '.strip_tags($errstr),
					'File: '.$errfile,
					'Line: '.$errline,
				);

				if( empty( $_SERVER[ '_' ] ) )
				{
					$body_arr[] = 'URL: '.(!empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '' );
					$body_arr[] = 'PHP Self: '.$_SERVER['PHP_SELF'];
				}
				else
					$body_arr[] = 'Script being run from the command line';

				$body_arr = array_merge($body_arr,array(
					'--- User Info ---',
					'Remote User: '.(!empty($_SERVER[ 'REMOTE_USER' ]) ? $_SERVER[ 'REMOTE_USER' ] : '' ),
					'PHP Auth User: '.(!empty($_SERVER[ 'PHP_AUTH_USER' ]) ? $_SERVER[ 'PHP_AUTH_USER' ] : '' ),
					'Remote IP: '.(!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER[ 'REMOTE_ADDR' ] : '' ),
					'User Agent: '.(!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '' ),
				) );
				$body = join( "\n", $body_arr );

				// loop through a few of the Super Global Arrays (SGAs)
				/*
				$sgas = array('_GET','_POST','_COOKIE');
				foreach( $sgas AS $sga )
				{
					$body .= "--- $sga ---\n";
					if( !empty( $sga ) AND is_array( $sga ) )
						foreach( $$sga AS $key => $val )
							$body .= "'$key' => '$val'\n";
				}

				$body .= "\n\nContext:\n\n";
				//$body .= sprint_r( $context );
				*/
				//mail( join(',',$to), $subject, $body, 'From: '.ERROR_EMAIL_FROM ) ;

				header( 'Location: '.OHSHI_SCRIPT );
				die();
			}
		}
	}

	$default_err_handler = set_error_handler( 'carlUtilErrorHandler' );

	if( !empty( $_SERVER[ 'REMOTE_ADDR' ] ) && empty($GLOBALS['_DEVELOPER_IPS'] ) )
	{
		echo '<p style="background-color:#ddd;color:#333;font-size:80%;margin:0;padding:.35em;border-bottom:1px solid #333;"><strong>Note:</strong> The error handler is not yet set up. Administrators must look in the php error logs ('.PHP_ERROR_LOG_FILE.') to see any errors that occur in the execution of this script. To turn this notice off, edit '.SETTINGS_INC.'error_handler_settings.php</p>'."\n";
	}
?>
