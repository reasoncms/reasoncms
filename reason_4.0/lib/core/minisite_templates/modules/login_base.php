<?php
/**
 * @package reason
 * @subpackage minisite_modules
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
 
/**
 * Register the module
 */
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LoginBaseModule';

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/default.php' );
include_once( CARL_UTIL_INC . 'basic/browser.php' );
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

/**
 * This module that handles login to Reason authentication using installed directory services. 
 * It has been designed to be easily extended to use other authentication methods as your
 * site requires.
 *
 * April 2014: Refactored slightly to support two-factor authentication
 *
 * @todo Figure out if there is a more secure way to handle the msg_uname request value... right now if you know the unique name of a text blurb you can display its content. This makes the content of uniquely named text blurbs marely obscure rather than truly secure. (luckily there are likely not that many uniquely named text blurbs, but this should still be resolved in a better way somehow.)
 */
class LoginBaseModule extends DefaultMinisiteModule
{
	public $acceptable_params = array(
		// options are 'inline' or 'standalone'
		// inline: part of a page.  does no redirection
		// standalone: independent login page.  should check for referer and redirect there on success
		'login_mode' => 'standalone',
		// Array of directory services to try for authentication.  If empty, uses the default(s) defined in SETTINGS_INC/dir_service_config.php
		'auth_service' => array(),
		// Array of domains that this login is allowed to redirect to (defaults to current domain)
		'allowable_domains' => array(),
		'login_error_message' => 'It appears your login information is not valid.  Please try again.  If problems persist, contact the Web Services Group for assistance.',
	);
	public $cleanup_rules = array(
		'username' => array( 'function' => 'turn_into_string' ),
		'password' => array( 'function' => 'turn_into_string' ),
		'logout' => array( 'function' => 'turn_into_string' ),
		'dest_page' => array( 'function' => 'turn_into_string' ),
		'redir_link_text' => array( 'function' => 'turn_into_string' ),
		'noredirect' => array( 'function' => 'turn_into_string' ),
		'force_redirect' => array( 'function' => 'turn_into_string' ),
		'code' => array( 'function' => 'turn_into_int' ),
		'msg_uname' => array( 'function' => 'reason_unique_name_validate_string' ),
		'popup' => array('function' =>'check_against_array', 'extra_args' => array('true'))
	);
	
	protected $sess;
	protected $logged_in;
	protected $auth_username;
	protected $headline;
	protected $msg;
	protected $status_msg;
	protected $close_window = false;
	protected $dest_page = '';
	protected $redir_link_text = '';
	protected $on_secure_page_if_available = false;
	protected $current_url = '';
	protected $msg_extra = '';
	protected $verbose_logging = false; // Useful for debugging login issues
	protected $enable_two_factor_login = false;
	
	public function init( $args = array() )
	{
		$head_items =& $this->parent->head_items;
		$head_items->add_javascript(JQUERY_URL, true); // do we need to do this?
		$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'login/focus.js');
		// Search engines should not be indexing versions of the index page with specific destinations
		if( isset( $this->request[ 'dest_page' ] ) )
		{
			$head_items->add_head_item('meta', array('name'=>'robots','content'=>'none'));
		}
			
		$this->current_url = get_current_url();
		$this->on_secure_page_if_available = (!HTTPS_AVAILABLE || on_secure_page());
		$this->set_dest_page();
		
		if ( isset($this->request ['redir_link_text']))
		{
			$this->redir_link_text = $this->request ['redir_link_text'];
		}
		$this->dest_page = $this->localize_destination_page();
		$this->sess =& get_reason_session();
		$this->logged_in = false;
		// A session exists
		if( $this->sess->exists( ) )
		{
			if ($this->verbose_logging) error_log('LOGIN: Session exists');
			if( !$this->sess->has_started() )
			{
				$this->sess->start();
				if ($this->verbose_logging) error_log('LOGIN: Session started');
			}
			
			// user is logging out
			if( isset( $this->request[ 'logout' ] ) )
			{
				if ($this->verbose_logging) error_log('LOGIN: do_logout');
				// Set the test cookie here, so they can log back in again
				$this->set_test_cookie();
				$this->do_logout();
			}
			// session exists, but no identity is set; bad state, start over
			elseif( !$this->sess->get( 'username' ) )
			{
				if ($this->verbose_logging) error_log('LOGIN: Destroying bad session');
				$this->sess->destroy();
				header( 'Location: '.get_current_url() );
				exit;
			}
			// user is logged in
			else
			{
				if ($this->verbose_logging) error_log('LOGIN: do_logged_in');
				$this->do_logged_in();
			}
		}
		// no session, not logged in
		else
		{
			if ($this->verbose_logging) error_log('LOGIN: No Session');
			// In the process of logging in
			if( $this->login_in_progress() )
			{
				if ($this->verbose_logging) error_log('LOGIN: Login in progress');
				if( $this->test_cookie_exists() )
				{
					if ($this->verbose_logging) error_log('LOGIN: Test cookie exists');
					if ($this->check_authentication() && !$this->should_check_secondary_auth())
					{
						if ($this->do_login())
							$this->do_logged_in();
					}
				}
				else
				{
					if ($this->verbose_logging) error_log('LOGIN: NO test cookie');
					$this->status_msg = 'It appears that you do not have cookies enabled.  Please enable cookies and try logging in again';
				}
			}
			else if ($this->secondary_auth_in_progress() )
			{
				if ($this->check_secondary_authentication())
				{
					if ($this->do_login())
						$this->do_logged_in();
				}
			}
			// (Apparent) first visit to login page
			else
			{
				if ($this->verbose_logging) error_log('LOGIN: No login in progress');
				$this->set_test_cookie();
				if( isset( $this->request[ 'code' ] ) )
				{
					$s =& get_reason_session();
					$this->msg = $s->get_error_msg( $this->request[ 'code' ] );
				}
				
				if( isset( $this->request[ 'msg_uname' ] ) )
					$this->set_message_from_unique_name($this->request[ 'msg_uname' ]);
			}
		}
	}
	
	public function run()
	{
		if ($this->verbose_logging) error_log('LOGIN: Run phase');
		if (DISABLE_REASON_LOGIN)
		{
			echo '<div id="login">'."\n";
			echo '<h4 class="msg">Reason login is currently disabled</h4>'."\n";
			echo '<div class="msg_extra">Please try again later.</div>'."\n";
			echo '</div>'."\n";
			return false;
		}
		if( !$this->logged_in )
		{
			// If secure login is available and they're not using it, either
			// bounce them to a secure page (standalone mode) or offer a link
			// to a secure login (inline mode)
			if( !$this->on_secure_page_if_available )
			{
				$url = get_current_url( securest_available_protocol() );
				if( $this->params['login_mode'] == 'standalone' )
				{
					header('Location: '.$url);
					exit();
				}
				else
					echo '<a href="'.$url.'">Use Secure Login</a>';
			}
			else if ($this->should_check_secondary_auth() && $this->login_in_progress() && $this->auth_username)
			{
				$this->display_secondary_auth();
			}
			else
			{
				if(isset($this->request['username']))
					$uname = $this->request['username'];
				else
					$uname = '';
					
				$this->display_login_form($uname);
			}
		}
		else
		{
			if (isset($this->request['popup']))
			{
				echo '<script language="JavaScript" type="text/javascript">window.close();</script>';
				return false;
			}
			if ($this->headline) echo '<h2>'.$this->headline.'</h2>'."\n";
			if ($this->status_msg)	echo '<p class="statusInfo">'.$this->status_msg.'</p>'."\n";
			$this->display_close_window_link();
			$this->display_logout_message();
		}
	}
	
	protected function set_test_cookie()
	{
		$this->clear_test_cookie();
		setcookie('cookie_test','test',time()+60*20);
	}
	
	protected function clear_test_cookie()
	{
		setcookie('cookie_test','');		
	}
	
	protected function test_cookie_exists()
	{
		$cookie_exists = isset( $_COOKIE['cookie_test'] );
		if( $cookie_exists )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	protected function display_login_message($position = 'pre')
	{
			
		if( !empty( $this->dest_page ) )
		{
			if( $this->dest_page != get_current_url() )
			{
				$dest_txt = $this->get_dest_page_text();
			}
		}


		if ($position == 'pre')
		{
			if (empty($this->headline)) $this->headline = 'Please Sign In.';

			if( empty( $this->msg ) )
			{
				echo '<h2>'.$this->headline.'</h2>'."\n";
				if (isset($dest_txt))
					echo '<p class="destinationInfo">Signing in for: <a href="'.htmlspecialchars($this->dest_page, ENT_QUOTES).'" title="'.htmlspecialchars($this->dest_page, ENT_QUOTES).'">'.htmlspecialchars($dest_txt).'</a></p>'."\n";

			}
			else
			{
				echo '<div class="customMessage">'."\n";
				echo $this->msg."\n";
				echo '</div>'."\n";
			}
			
			if ($this->status_msg)
			{
				echo '<p class="statusInfo">'.$this->status_msg.'</p>'."\n";	
			}
		} 
		else 
		{ 
			if (!empty( $this->msg ) && isset($dest_txt))
				echo '<p class="destinationInfo below">Signing in for: <a href="'.htmlspecialchars($this->dest_page, ENT_QUOTES).'" title="'.htmlspecialchars($this->dest_page, ENT_QUOTES).'">'.htmlspecialchars($dest_txt).'</a></p>'."\n";
		}

		
		// Used after login for "Proceed to..."
		if(!empty( $this->msg_extra ) )
		{
			echo '<p>'.$this->msg_extra.'</p>'."\n";
		}		
	}
	
	protected function display_login_form($uname=null, $class=null)
	{
		echo '<div id="loginModule"><div class="LoginWrap"><div class="loginForm">'."\n";
		$this->display_login_message('pre');
		show_cookie_capability('<p class="smallText">You must have cookies enabled to login.  You do not have cookies enabled.</p>');
		
		$current_url = carl_make_link(array('dest_page' => $this->dest_page, 'redir_link_text' => $this->redir_link_text, 'logout'=>''));
		$username_value = ($uname) ? 'value="'.htmlspecialchars($uname).'"' : '';
		$form_class = ($class) ? 'class="'.htmlspecialchars($class).'"' : '';
		?>
		<form action="<?php echo $current_url; ?>" method="post" <?php echo $form_class; ?>>
			<div class="loginElement" id="usernameLoginElement">
				<label for="usernameLoginInput">Username</label>
				<input type="text" name="username" id="usernameLoginInput" <?php echo $username_value; ?>/>
			</div>
			<div class="loginElement" id="passwordLoginElement">
				<label for="passwordLoginInput">Password</label>
				<input type="password" name="password" id="passwordLoginInput" />
			</div>
			<div class="formActions">
				<div id="loginSubmitElement">
					<input type="submit" value="Sign In" id="loginSubmit" />
				</div>
				<div id="loginHelpElement">
					<a href="/login/help/">Username/<br />Password Help</a>
				</div>
			</div>
		</form>
		<?php
		$this->display_login_message('post');
		echo '</div></div></div>'."\n";
	}
	
	protected function display_logout_message()
	{
			
		echo '<a href="?logout=1" class="logoutLink">Logout</a>';	
	}
	
	protected function display_close_window_link()
	{
		if (isset($this->request['popup']))
		{
			$this->close_window = true;
			echo '<p class="closeLink"><a href="#" onclick="window.close();">Close this window.</a></p>';
		}
	}
	
	protected function display_secondary_auth()
	{
		// Stub for inserting your own second-factor interface
	}
	
	protected function login_in_progress()
	{
		return (isset( $this->request[ 'username' ] ) AND isset( $this->request[ 'password' ] ));
	}
	
	/**
	 * Whether or not to enable two-factor auth for this login. Default is whatever is
	 * in the enable_two_factor_login class var, but you could extend this to have more
	 * complicated logic (e.g. check a directory flag)
	 */
	protected function should_check_secondary_auth()
	{
		return $this->enable_two_factor_login;
	}
	
	protected function secondary_auth_in_progress()
	{
		// Stub for detecting your own second-factor login
	}

	protected function check_authentication()
	{
		$auth = new directory_service($this->params[ 'auth_service' ]);
		
		// succesful login
		if( $auth->authenticate( $this->request['username'], $this->request['password'] ) )
		{
			if ($this->verbose_logging) error_log('LOGIN: check_authentication succeeded');
			$this->auth_username = $this->request['username'];
			return $this->auth_username;
		}
		// failed login
		else
		{
			if ($this->verbose_logging) error_log('LOGIN: check_authentication failed');
			$this->log_authentication_event('login failed', $this->request['username']);
			$this->status_msg = 'The username and password you provided do not match.  Please try again.';
			return false;
		}
	}
	
	protected function check_secondary_authentication()
	{
		// Stub for checking the status of secondary auth attempt
		// If successful, set $this->auth_username and return true.
	}

	protected function do_login()
	{		
		if ($this->sess->start())
		{
			if ($this->verbose_logging) error_log('LOGIN: do_login storing '.$this->auth_username.' in session.');
			$this->sess->set( 'username', strtolower(trim($this->auth_username)) );
			$this->log_authentication_event('login succeeded', $this->auth_username);
			return true;
		} else {
			if ($this->sess->get( '_sess_expire_time' ) && $this->verbose_logging)
				error_log('LOGIN: Session exists; expires: '.$this->sess->get( '_sess_expire_time' ));
			if ($this->verbose_logging) error_log('LOGIN: do_login cannot start session: '.$this->sess->get_error_msg($this->sess->error()));
			trigger_error('Could not start session in do_login()');	
			return false;
		}
	}

	protected function do_logged_in()
	{
		$this->logged_in = true;
		$this->clear_test_cookie();
		if( !empty( $this->dest_page ) )
		{
			if( $this->dest_page != get_current_url() )
			{
				if (isset($this->request['force_redirect']) || !isset($this->request['noredirect']) ) 
				{
					if ($this->verbose_logging) error_log('LOGIN: Redirecting to '.$this->get_dest_page_link(true));
					header( 'Location: '.$this->get_dest_page_link(true));
					exit;	
				} else {				
					$dest_txt = $this->get_dest_page_text();
					$cleaned_dest_page = htmlspecialchars($this->dest_page, ENT_QUOTES);
					$this->status_msg .= 'Proceed to <a href="'.$cleaned_dest_page.'" title="'.$cleaned_dest_page.'">'.htmlspecialchars($dest_txt).'</a>';
				}
			}
		}
		$this->headline = 'You\'re logged in!';
		$this->status_msg = 'You are logged in as <strong>'.$this->get_authenticated_identity().'</strong>. ';
	}
	
	protected function do_logout()
	{
		$username = $this->sess->get( 'username' );
		$this->sess->destroy();
		$this->logged_in = false;
		$this->status_msg = 'You are now logged out.';
		$this->log_authentication_event('logout succeeded', $username);
		if( !isset( $this->request[ 'noredirect' ] ) && $this->dest_page )
		{
			$this->clear_test_cookie();
			if ($this->verbose_logging) error_log('LOGIN: do_logout redirecting to '.$this->get_dest_page_link(false));
			header( 'Location: '.$this->get_dest_page_link(false));
			exit;
		}
	}
	
	protected function set_dest_page()
	{
		// If we've been passed a destination, just set it and return
		if (isset($this->request[ 'dest_page' ]))
		{
			$this->dest_page = $this->request[ 'dest_page' ];
			return;
		}
		
		// Otherwise, we have to figure out the appropriate destination:
		// In standalone mode, once the user has successfully logged in, they will be bounced back to the page
		// they came from if there was one.  Otherwise, they will see a successful login message
		if( $this->params['login_mode'] == 'standalone' )
		{
			if (!isset($this->request['popup']) && !$this->login_in_progress())
			{
				if( !empty( $_SERVER['HTTP_REFERER'] ) )
				{
					$this->dest_page = $_SERVER['HTTP_REFERER'];
				}
				else
				{
					// we have no valid information on where to go back to.  this will happen if a user goes
					// directly to the login page without clicking on a link.  in this case, there will be no
					// jumping and a message saying you are logged in will appear along side the logout link.
				}
			}
		}
		// in "inline" mode, the page bounces back to itself.  the reason it use the redirect is that since this
		// is a module, other modules may need to know that the user has been logged in.  if this module appears
		// later, the information that a user has logged in won't be available.  another loop to jump back to
		// the page fixes this situation.
		else
		{
			$this->dest_page = $this->current_url;
		}
	}
	
	protected function get_dest_page_text($max_chars = 50)
	{
		if( !empty( $this->dest_page ) )
		{
			if( $this->dest_page != get_current_url() )
			{
				if(empty($this->redir_link_text))
				{
					$dest_txt = $this->truncate_link_text($this->dest_page, $max_chars);
				}
				else
				{
					$dest_txt = $this->redir_link_text;
				}
				return $dest_txt;
			}
		}
	}
	
	protected function get_dest_page_link($secure = null)
	{
		$parts = parse_url( $this->dest_page );
		$fragment = (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
		if ($secure === true)
			$link = alter_protocol($this->dest_page, 'http', securest_available_protocol()).$fragment;
		else if ($secure === false)
			$link = alter_protocol($this->dest_page, 'https', 'http').$fragment;
		else
			$link = $this->dest_page;
		return $link;
	}
	
	public static function truncate_link_text($text, $max_chars = 50)
	{
		if(strlen($text) > $max_chars)
		{
			$piece_length = floor($max_chars/2);
			$dest_txt_1 = substr($text,0,$piece_length);
			$dest_txt_2 = substr($text,strlen($text)-$piece_length);
			$dest_txt = $dest_txt_1.'...'.$dest_txt_2;
		}
		else
		{
			$dest_txt = $text;
		}
		return $dest_txt;
	}
	
	protected function set_message_from_unique_name($name)
	{
		$msg_id = id_of($name, true, false);
		if(!empty($msg_id))
		{
			$msg_ent = new entity($msg_id);
			if( $msg_ent->get_value( 'type' ) == id_of('text_blurb') )
				$this->msg .= $msg_ent->get_value('content');
		}		
	}
	
	/**
	 * Get the identity of the person who is currently logged in. (Extending classes might want
	 * to expand to full name, etc.).
	 * @return string display identity
	 *
	 */
	protected function get_authenticated_identity()
	{
		return $this->sess->get('username');
	}
	
	/**
	 * The destination page should only be on a host that this login page is allowed to serve
	 * (defaults to local host).
	 *
	 */
	protected function localize_destination_page()
	{
		if ($this->dest_page)
		{
			$current_parts = parse_url( get_current_url() );
			$parts = parse_url( $this->dest_page );
			
			if (isset($parts['host']) && isset($current_parts['host']) && $parts['host'] != $current_parts['host'] && !in_array($parts['host'], $this->params['allowable_domains']))
				return '';
				
			$host = (isset($parts['port'])) ? $parts['host'] : $_SERVER['HTTP_HOST'];
			$port = (isset($parts['port']) && !empty($parts['port'])) ? ":".$parts['port'] : '';
			$query = (isset($parts['query']) && !empty($parts['query'])) ? '?'.$parts['query'] : '';
			$fragment = (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
			return securest_available_protocol() . '://'.$host.$port.$parts['path'].$query.$fragment;
		}
	}
	
	protected function log_authentication_event($event, $username)
	{
		if (defined('REASON_LOG_LOGINS') && REASON_LOG_LOGINS)
		{
			$logtext = sprintf('%s - %s [%s] "%s" - - "%s" "%s"', 
				$_SERVER['REMOTE_ADDR'], 
				trim($username), 
				date('d/M/Y:H:i:s O'), 
				$event,
				((isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')),
				$_SERVER['HTTP_USER_AGENT']
				);
			dlog($logtext, 	REASON_LOG_DIR.'reason_login.log');
		}
	}
}
?>
