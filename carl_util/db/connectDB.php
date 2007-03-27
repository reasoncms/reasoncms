<?php
include_once( 'paths.php' );
include_once( CARL_UTIL_INC . 'error_handler/error_handler.php' );
$GLOBALS['_current_db_connection_name'] = '';
require_once( 'XML/Unserializer.php'); // Requires PEAR XML_Serialize package

/**
 * Wraps up MySQL database connection code.
 * Uses get_db_credentials() to lookup authenticaton information from a central XML file.
 * All parameters except for dbName are deprecated.
 *
 * @param string $dbName A database connector name - this maps to an entry in the XML file
 * @param string $dbuser Deprecated - is now ignored
 * @param string $dbpasswd Deprecated - is now ignored
 * @param string $dbhost Deprecated - is now ignored
 * @return resource database connection resource
 */
function connectDB($dbName, $dbuser = '', $dbpasswd = '', $dbhost='')
{
	$db_info = get_db_credentials( $dbName );
	// try to connect to server
	// If a connection can not be made, sleep for 1 second and try again, up to a maximum of $max_tries times
	$max_tries = 5;
	$tries = 0;
	do
	{
		// is only true if the first connection could not be made
		if( $tries > 0 )
		{
			trigger_error('Unable to connect to database, sleeping and trying again (reconnect attempt #'.$tries.')', WARNING);
			sleep( 1 );
		}
		$db = @mysql_connect($db_info['host'], $db_info['user'], $db_info['password']);
		$tries++;
	} while(!$db AND $tries <= $max_tries);
	
	if( !$db )
	{
		$db_info['password'] = '*************'; // replace password so it will not be exposed onscreen - nwhite
		trigger_error('Unable to connect to database (Error #'.mysql_errno().':'.mysql_error().')', EMERGENCY);
	}
	elseif( $tries > 1 )
	{
		trigger_error('Successfully connected to database after an initial failure.  Reconnect attempts: '.($tries-1));
	}

	// select database
	if( !mysql_select_db($db_info[ 'db' ], $db) )
		trigger_error( 'Unable to select database ('.mysql_error().')', EMERGENCY );
	$GLOBALS['_current_db_connection_name'] = $dbName;
	return $db;
}

function get_current_db_connection_name()
{
	return $GLOBALS['_current_db_connection_name'];
}

/**
 * Return authentication credentials for the specified database connection.
 * Internally, parse the database connection definition XML file.
 * You can define DB_CREDENTIALS_FILEPATH if you want to overload the default path to the XML file containing the database
 * connector information.
 *
 * @param string $conn_name The name of the db connection you want to retrieve
 * @return array Array with all the db connection info defined for the specified named connection.
 */
function get_db_credentials( $conn_name )
{
	static $db_info = array();
	// if db_info is empty, this is the first time this function has been run.
	if( empty( $db_info ) )
	{
		if( defined( 'DB_CREDENTIALS_FILEPATH' ) )
			$db_file = DB_CREDENTIALS_FILEPATH;
		else
			$db_file = '/usr/local/etc/php3/dbs.xml';
		if( !is_file( $db_file ) )
		{
			trigger_error( 'Unable to get db connection info', FATAL );
		}

                $xml = file_get_contents($db_file);
                if(!empty($xml))
                {
                        $unserializer = &new XML_Unserializer();
                        $unserializer->unserialize($xml);
                        $data_array = $unserializer->getUnserializedData();

			//normalize format;
			if (isset($data_array['database']['connection_name'])) 
				$db_array['database'][0] = $data_array['database'];
			else $db_array = $data_array;

			foreach ($db_array['database'] as $my_db)
			{
				$db_info[$my_db['connection_name']] = $my_db;
			}
		}
		else
		{
			trigger_error('Unable to parse db credentials XML file', FATAL);
		}
	}

	// if this was the first time, the code above should have run successfully so db_info is populated.
	// if this is not the first time, then the code above should have been skipped since it was populated the first
	// run of the function.
	if( !empty( $db_info[ $conn_name ] ) )
	{
		return $db_info[ $conn_name ];
	}
	else
	{
		trigger_error('Unable to use database connection '.$conn_name.' - No credential information found in database credential file', FATAL);
	}
}
?>
