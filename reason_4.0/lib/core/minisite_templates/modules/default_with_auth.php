<?php
	/**
	 * @package reason
	 * @subpackage minisite_modules
	 */
	
	/**
	 * Include the base module and register this module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DefaultMinisiteWithAuthModule';

	/**
	 * Default Minisite Module with Authentication
	 *
	 * This class should be extended by modules that are only for use by logged in users
	 * Classes which extend this module and call its init function via parent::init() will
	 * be assured of having a populated $user_netID class variable. The login box will be
	 * brought up when necessary, or the active users netID will be retrieved from the session
	 * or $_SERVER['REMOTE_USER'].
	 *
	 * Classes which extend this module can define a $msg_uname to provide a unique_name of a
	 * text blurb which contains the login box text, and also define a $redir_link_text to provide
	 * custom text for the redirect link instead of just the URL.
	 *
	 * @author Nate White
	 */
	 
	class DefaultMinisiteWithAuthModule extends DefaultMinisiteModule
	{
		/**
		 * The netID of the logged in user - gets set after a successful login or populated if the user is already logged in
		 * @var string
		 */	
		var $user_netID = '';
		
		/**
		 * An optional unique name of the text blurb that contains the message at the top of the login box
		 * @var string
		 */	
		var $msg_uname = '';
		
		/**
		 * An optional string to replace the linked text that displays as a URL by default underneath the login box in this context:
		 * "You will be redirected to http://www.redireccturl.edu once you login"
		 * @var string
		 */	
		var $redir_link_text = '';

		function init( $args = array() )
		{
 			parent::init($args);
			
			if (!$this->get_authentication())
			{
				$extra_params = '';
				if (!empty($this->msg_uname)) $extra_params .= '&msg_uname='.$this->msg_uname;
				if (!empty($this->redir_link_text)) $extra_params .= '&redir_link_text='.$this->redir_link_text;
				$dest_page = urlencode(get_current_url());
				header('Location: '.REASON_LOGIN_URL. '?dest_page=' . $dest_page. $extra_params);
				exit();
			}
		}
		
		/**	
		* Returns the current user's netID, or false if the user is not logged in.
		* @return string user_netID
		*/	
		function get_authentication()
		{
			if(empty($this->user_netID))
			{
				if(!empty($_SERVER['REMOTE_USER']))
				{
					$this->user_netID = $_SERVER['REMOTE_USER'];
					return $this->user_netID;
				}
				else
				{
					return $this->get_authentication_from_session();
				}
			}
			else
			{
				return $this->user_netID;
			}
		}
		
		/**	
		* Returns the current user's netID from the session, or false if the user is not logged in.
		* @return string user_netID
		* @access private
		*/	
		function get_authentication_from_session()
		{
			$this->session =& get_reason_session();
			if($this->session->exists())
			{
				force_secure_if_available();
				if( !$this->session->has_started() )
				{
					$this->session->start();
				}
				$this->user_netID = $this->session->get( 'username' );
				return $this->user_netID;
			}
			else
			{
				return false;
			}
		}	
	}

?>
