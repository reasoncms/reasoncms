<?php
	/**
	 * This library is for factory functions that load the appropriate classes for a given system.
	 *
	 * @author Dave Hendler
	 * @package reason
	 * @subpackage function_libraries
	 */

	/**
	 * Get a new session object of the given session class
	 * @param string $class
	 * @return object
	 */
	function get_session_factory( $class )
	{
		switch( $class )
		{
			case 'Session_Cookie':
				reason_include_once( 'classes/session_cookie.php' );
				break;
			case 'Session_PHP':
				reason_include_once( 'classes/session_php.php' );
				break;
			default:
				trigger_error( 'The session class requested does not exist', WARNING );
				break;
		}

		return new $class();
	}
?>
