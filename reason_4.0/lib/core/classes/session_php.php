<?php
	/*
	 *	session_php.php
	 *	Dave Hendler
	 *	9/9/04
	 *
	 *	Implementation of the session interface using PHP sesssions
	 *
	 *	THIS IS ACTUALLY A SECURE IMPLEMENTATION.  Uses a second cookie for a number of reasons to be specified later.
	 */

	include_once( 'reason_header.php' );
	reason_include_once( 'classes/session.php' );
	reason_include_once( 'classes/entity_selector.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	
	class Session_PHP extends Session
	{
		// boolean to determine if we are on a secure page or not
		var $secure = false;
		// this is a debug tool.  when a session is started, this is pointed at the $_SESSION array.  This basically
		// allows me to see if the session has loaded properly when prp()ing the session object.  DO NOT USE THIS.
		// again, DO NOT USE THIS FOR ANYTHING.
		var $__session_ref;
		
		// keep track of whether the session has been started.
		var $_started = false;
		
		var $_sid;
		
		var $error_num;
		
		var $expires = 3600;
		
		var $errors = array(
			1 => array(
				'name' => 'ERR_SESS_EXPIRED',
				'msg' => 'Your session has expired.',
			),
			array(
				'name' => 'ERR_SESS_DUPLICATE',
				'msg' => 'Session has already been started.',
			),
			array(
				'name' => 'ERR_SESS_INSECURE',
				'msg' => 'You must be on a secure page to log in.',
			),
		);
		
		function Session_PHP() // {{{
		{
			if (defined('REASON_SESSION_TIMEOUT'))
			{
				$this->expires = (REASON_SESSION_TIMEOUT * 60);
			}
			ini_set( 'session.use_cookies', 1 ) OR trigger_error('Unable to set use_cookies ini');
			ini_set( 'session.gc_maxlifetime', 86400 );
			
			foreach( $this->errors AS $err_num => $err )
			{
				if( !defined( $err['name'] ) )
					define( $err['name'], $err_num );
			}
		} // }}}
		function has_started() // {{{
		{
			return $this->_started;
		} // }}}
		function start() // {{{
		{
			$this->secure = on_secure_page();
			if( !$this->secure )
			{
				trigger_error( 'Unable to start session on an insecure page' );
				$this->error_num = ERR_SESS_INSECURE;
				return false;
			}
			else
			{
				if( session_id() OR $this->_started )
				{
					trigger_error( 'Session PHP trying to start new session when a session is already started. Possible programmer error.', WARNING );
					$this->error_num = ERR_SESS_DUPLICATE;
					return false;
				}
				else
				{
					if( empty( $_COOKIE[ $this->sess_name.'_EXISTS' ] ) )
					{
						setcookie( $this->sess_name.'_EXISTS', 'true', 0, '/', $_SERVER['HTTP_HOST'], 0 );
					}
					session_name( $this->sess_name );
					session_set_cookie_params(0, '/', $_SERVER['HTTP_HOST'], 1 );
					session_start();
					
					$this->__session_ref =& $_SESSION;
					$this->_sid = session_id();
					$this->_started = true;
					
					if (!$this->get( '_user_popup_alert_pref' ) )
					{
						$this->set_user_prefs();
					}
					// check expiration action
					if( !$this->get( '_sess_expire_time' ) )
					{
						// no expiration time set.  we are probably starting the session for the first time.
						$this->update_expiration_time();
					}
					else
					{
						if( $this->has_expired() )
						{
							// Super common -- no need to trigger an error here -- mr
							//trigger_error( 'Session has expired' );
							$this->destroy();
							$this->error_num = ERR_SESS_EXPIRED;
							return false;
						}
						else
						{
							$this->update_expiration_time();
						}
					}
					return true;
				}
			}
		} // }}}
		
		function set_user_prefs()
		{
			$myname = reason_check_authentication();
			if (!empty($myname))
			{
				$popup_alert = 'no';
				$es = new entity_selector();
				$es->add_type(id_of('user'));
				$es->add_relation('entity.name = "'.$myname.'"');
				$es->set_num(1);
				$users = $es->run_one();
				if(!empty($users))
				{
					$user = current($es->run_one());
					$popup_alert = $user->get_value('user_popup_alert_pref');
				}
				
				$this->set('_user_popup_alert_pref' , $popup_alert);
			}
		}
		
		function error() // {{{
		{
			return $this->error_num;
		} // }}}
		function get_error_msg( $error_num ) // {{{
		{
			return $this->errors[ $error_num ][ 'msg' ];
		} // }}}
		function has_expired() // {{{
		{
			return (time() > $this->get( '_sess_expire_time' ));
		} // }}}
		function update_expiration_time() // {{{
		{
			$this->set( '_sess_expire_time', time() + $this->expires );
		} // }}}
		function destroy() // {{{
		{
			if( $this->secure )
			{
				setcookie( $this->sess_name.'_EXISTS', '', 0, '/', $_SERVER['HTTP_HOST'], 0 );
				$_SESSION = array();
				session_destroy();
				setcookie( $this->sess_name, '', 0, '/', $_SERVER['HTTP_HOST'], 1);
			}
			else
			{
				trigger_error( 'Unable to destroy a session on an insecure page.', WARNING );
			}
		} // }}}
		function exists() // {{{
		{
			$ret = ( !empty( $_COOKIE[ $this->sess_name.'_EXISTS' ] ) );
			return $ret;
		} // }}}
		function _store( $var, $val ) // {{{
		{
			if( $this->secure )
			{
				$_SESSION[ $var ] = $val;
			}
			else
			{
				trigger_error( 'Trying to store a session variable on an insecure page.  Variable NOT stored', WARNING );
			}
		} // }}}
		function _retrieve( $var ) // {{{
		{
			if( $this->secure )
			{
				if( !empty( $_SESSION[ $var ] ) )
					return $_SESSION[ $var ];
				else
					return '';
			}
			else
			{
				trigger_error( 'Trying to get a session variable on an insecure page.  Unable to retrieve', WARNING );
			}
		} // }}}
	}
?>
