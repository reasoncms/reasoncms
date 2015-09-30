<?php
/**
 * @package reason
 * @subpackage classes
 *
 *  @todo remove the code that allows users to directly access this file
 *        and see their cookie information
 */

	/**
	 * Include Reason libraries & parent class
	 */
	include_once( 'reason_header.php' );
	reason_include_once( 'classes/session.php' );
	/**
	 *	Implementation of the session interface using cookies.
	 *	a.k.a. client-side sessions.  no data is stored on the server.
	 *
	 *  The cookie implementation requires an array of variables
	 *	that exist in the session.  This is obviously different from PHP
	 *	sessions.  The variables are stored in the cookie as one string with
	 *	each value separated by some glue determined by $cookie_glue.  In
	 *	addition to the values defined by the user, the class automatically
	 *	appends some extra, internal variables.
	 *	TODO: decide what these are.
	 *
	 *  @todo Figure out what this session class would be useful for, as it seems
	 *        like a possibly insecure implementation?
	 *
	 *	@author Dave Hendler, 7/28/04
	 *
	 * @deprecated use php session instead this has never been fully implemented
	 */
	class Session_Cookie extends Session  /* interface */
	{
		var $cookie_glue = '|';
		function start()
		{
			// make sure some elements are defined
			if( empty( $this->sess_vars ) )
			{
				trigger_error('Session must have variables set ',WARNING );
				return false;
			}
			// if a cookie exists...
			if( !empty( $_COOKIE[ $this->sess_name ] ) )
			{
				// retrieve info from the cookie
				$this->_unpack_cookie( $_COOKIE[ $this->sess_name ] );

				// if expired, return false
			}
			return true;
		}
		function destroy()
		{
			$this->_set_cookie( '' );
		}
		function is_idle() {}
		function _unpack_cookie( $cookie_val )
		{
			$parts = explode( $this->cookie_glue, $cookie_val );
			if( count( $parts ) != count( $this->sess_vars ) )
			{
				trigger_error( 'Session cookie does not have the same number of variables as it should', WARNING );
			}
			else
			{
				$i = 0;
				foreach( $this->sess_vars AS $key )
				{
					$this->sess_values[ $key ] = $parts[ $i ];
					$i++;
				}
			}
		}
		function _store( $var, $val )
		{
			$this->_pack_cookie();
		}
		function _pack_cookie()
		{
			$cookie_val = implode( $this->cookie_glue, $this->sess_values );
			echo 'cookie_val = '.$cookie_val.'<br/>';
			$this->_set_cookie( $cookie_val );
		}
		function _set_cookie( $cookie_val )
		{
			$success = setcookie( $this->sess_name, $cookie_val, 0, '/', REASON_COOKIE_DOMAIN, false );
			if( !$success )
			{
				trigger_error( 'Unable to set cookie', WARNING );
			}
		}
	}

	/**
	 * @deprecated
	 */
	if( $_SERVER['SCRIPT_FILENAME'] == __FILE__ )
	{
		trigger_error('Using the session cookie file directly to test the contents of the session is deprecated. This aspect of session_cookie will be removed in a later release.');
		echo 'testing session_cookie<br/>';
		$s = new Session_Cookie();
		$s->define_vars( array( 'user_id','username','first_name','foo' ) );
		$s->start();
		if( $s->get('user_id') )
		{
			echo 'session is running<br/>';
			$s->destroy();
		}
		else
		{
			echo 'there is no session.  only xool<br/>';
			$s->set( 'user_id', 15 );
		}
		pray( $_COOKIE );
	}
?>
