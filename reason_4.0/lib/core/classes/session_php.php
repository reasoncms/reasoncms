<?php
	/**
	 *	@package reason
	 *	@subpackage classes
	 */
	
	/**
	 * Include dependencies
	 */
	include_once( 'reason_header.php' );
	reason_include_once( 'classes/session.php' );
	reason_include_once( 'classes/entity_selector.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	
	/**
	 *	Implementation of the session interface using PHP sessions
	 *
	 *	THIS A SECURE IMPLEMENTATION.  Uses a second cookie for a number of reasons to be specified later.
	 *
	 *	@author Dave Hendler
	 */
	class Session_PHP extends Session
	{
		/**
		 * boolean to determine if we are on a secure page or not
		 * @var boolean
		 */
		var $secure_if_available = false;
		/**
		 * flag passed to session to choose secure (1) / insecure (0) sessions
		 * @var integer (1 or 0)
		 */
		var $secure_session_flag = 1;
		/**
		 * A debug tool.
		 *
		 * When a session is started, this is pointed at the $_SESSION array.  This basically
		 * allows us to see if the session has loaded properly when prp()ing the session object.
		 *
		 * DO NOT USE THIS.
		 *
		 * Again, DO NOT USE THIS FOR ANYTHING.
		 *
		 * @access private
		 * @deprecated
		 */
		var $__session_ref;
		
		/**
		 * Keep track of whether the session has been started.
		 * @var boolean
		 */
		var $_started = false;
		
		var $_sid;
		
		var $error_num;
		
		/**
		 * The length of the session, in seconds.
		 *
		 * The default value is overridden with REASON_SESSION_TIMEOUT * 60 if
		 * REASON_SESSION_TIMEOUT is set.
		 *
		 * @var integer
		 */
		var $expires = 3600;
		
		/**
		 * @var array
		 */
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

			$this->secure_session_flag = (HTTPS_AVAILABLE) ? 1 : 0;
			$this->secure_if_available = (!HTTPS_AVAILABLE || on_secure_page());
		} // }}}
		function has_started() // {{{
		{
			return $this->_started;
		} // }}}
		function start($sid_override=null) // {{{
		{
			if( !$this->secure_if_available )
			{
				trigger_error( 'Unable to start session on an insecure page when https is available' );
				$this->error_num = ERR_SESS_INSECURE;
				return false;
			}
			else
			{
				if( $this->_started )
				{
					trigger_error( 'Session PHP trying to start new session when a session is already started. Possible programmer error.', WARNING );
					$this->error_num = ERR_SESS_DUPLICATE;
					return false;
				}
				else
				{
					if( !$this->exists() )
					{
						setcookie( $this->sess_name.'_EXISTS', 'true', 0, '/', $this->_transform_domain($_SERVER['SERVER_NAME']), 0 );
					}
					session_name( $this->sess_name );
					session_set_cookie_params(0, '/', $this->_transform_domain($_SERVER['SERVER_NAME']), $this->secure_session_flag );
					
					if ($sid_override) {
						session_id($sid_override);
						$started = session_start();
					} else if (!session_id()) {
						$started = session_start();
					}
					
					if (!$started) 
					{
						error_log('Failed to start session '.$this->sess_name.'; sid_override='.$sid_override);
						return false;
					}
					
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
						// If the session has expired, but is still active, destroy it and start over
						if( $this->has_expired() && $this->exists() )
						{
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
		
		/**
		 * Returns the opaque ID of this session.
		 * @return string the session ID
		 */
		function get_id()
		{
			return $this->_sid;
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
			if( $this->secure_if_available )
			{
				$domain = $this->_transform_domain($_SERVER['HTTP_HOST']);
				setcookie( $this->sess_name.'_EXISTS', '', 0, '/', $domain, 0 );
				$_SESSION = array();
				session_destroy();
				setcookie( $this->sess_name, '', 0, '/', $domain, $this->secure_session_flag );
			}
			else
			{
				trigger_error( 'Unable to destroy a session on an insecure page when https is available.', WARNING );
			}
		} // }}}
		function exists() // {{{
		{
			return !empty($_COOKIE[$this->sess_name.'_EXISTS']);
		} // }}}
		function _store( $var, $val ) // {{{
		{
			if( $this->secure_if_available )
			{
				$_SESSION[ $var ] = $val;
			}
			else
			{
				trigger_error( 'Trying to store a session variable on an insecure page when https is available.  Variable NOT stored', WARNING );
			}
		} // }}}
		function _retrieve( $var ) // {{{
		{
			if( $this->secure_if_available )
			{
				if( !empty( $_SESSION[ $var ] ) )
					return $_SESSION[ $var ];
				else
					return '';
			}
			else
			{
				trigger_error( 'Trying to get a session variable on an insecure page when https is available.  Unable to retrieve', WARNING );
			}
		} // }}}
		
		/**
		 * Browser cookies that include a port number or localhost are problematic and will cause the browser to be unable to access the cookie.
		 * This function strips any port number from the domain, and will return an empty string if the domain is localhost (or any single word without a period character)
		 * @author Nathan White
		 */
		 function _transform_domain($domain)
		 {
		 	$port_check = strpos($domain, ':');
		 	if ($port_check !== false) $domain = substr($domain, 0, $port_check);
		 	return (strpos($domain,'.') !== false) ? $domain : '';
		 }
	}
?>
