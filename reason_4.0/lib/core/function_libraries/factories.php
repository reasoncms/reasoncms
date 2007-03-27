<?php
	/*
	 *	factories.php
	 *	Dave Hendler
	 *	9.9.04
	 *
	 *	This library is for factory functions that load the appropriate classes for a given system.
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
