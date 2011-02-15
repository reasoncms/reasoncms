<?php
	/**
	 * @package reason
	 * @subpackage minisite_modules
	 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
	 */
	 
	/**
	 * Register the module
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LoginModule';
	
	/**
	 * Include dependencies
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	include_once( CARL_UTIL_INC . 'basic/browser.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	
	/**
	 * The module that handles login to Reason authentication
	 *
	 * @todo Figure out if there is a more secure way to handle the msg_uname request value... right now if you know the unique name of a text blurb you can display its content. This makes the content of uniquely named text blurbs marely obscure rather than truly secure. (luckily there are likely not that many uniquely named text blurbs, but this should still be resolved in a better way somehow.)
	 */
	class LoginModule extends DefaultMinisiteModule
	{
		var $sess;
		var $logged_in;
		var $msg;
		var $acceptable_params = array(
			// options are 'inline' or 'standalone'
			// inline: part of a page.  does no redirection
			// standalone: independent login page.  should check for referer and redirect there on success
			'login_mode' => 'standalone',
			// Array of directory services to try for authentication.  If empty, uses the default(s) defined in SETTINGS_INC/dir_service_config.php
			'auth_service' => array(),
			'login_error_message' => 'It appears your login information is not valid.  Please try again.  If problems persist, contact the Web Services Group for assistance.',
		);
		var $cleanup_rules = array(
			'username' => array( 'function' => 'turn_into_string' ),
			'password' => array( 'function' => 'turn_into_string' ),
			'logout' => array( 'function' => 'turn_into_string' ),
			'dest_page' => array( 'function' => 'turn_into_string' ),
			'noredirect' => array( 'function' => 'turn_into_string' ),
			'code' => array( 'function' => 'turn_into_int' ),
			'msg_uname' => array( 'function' => 'reason_unique_name_validate_string' ),
			'redir_link_text' => array( 'function' => 'turn_into_string' ),
			'popup' => array('function' =>'check_against_array', 'extra_args' => array('true'))
		);
		
		var $close_window = false;
		var $dest_page = '';
		//var $redir_link_text = '';
		var $on_secure_page_if_available = false;
		var $current_url = '';
		var $msg_extra = '';
		
		function set_test_cookie()
		{
			setcookie('cookie_test','');
			setcookie('cookie_test','test',0);
		}
		function test_cookie_exists()
		{
			$cookie_exists = !empty( $_COOKIE['cookie_test'] );
			if( $cookie_exists )
			{
				setcookie( 'cookie_test', false );
				return true;
			}
			else
			{
				return false;
			}
		}
		function init( $args = array() )
		{
			$head_items =& $this->parent->head_items;
            $head_items->add_javascript(JQUERY_URL, true);
            $head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'login/focus.js');
			$this->current_url = get_current_url();
			$this->on_secure_page_if_available = (!HTTPS_AVAILABLE || on_secure_page());
			
			if( empty( $this->request[ 'dest_page' ] ) )
			{
				// in standalone mode, once the user has successfully logged in, they will be bounced back to the page
				// they came from if there was one.  otherwise, they will see a successful login message
				if( $this->params['login_mode'] == 'standalone' )
				{
					if (empty($this->request['popup']))
					{
						// we have a referer.  remember for later.
						if( isset( $_SERVER['HTTP_REFERER'] ) && !empty( $_SERVER['HTTP_REFERER'] ) )
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
			// we received a URL from the form.  decode and store.
			else
			{
				// Search engines should not be indexing versions of the index page with specific destinations
				$head_items->add_head_item('meta', array('name'=>'robots','content'=>'none'));
				$this->dest_page = $this->request['dest_page'];
			}
			if ( !empty($this->request ['redir_link_text']))
			{
				$this->redir_link_text = $this->request ['redir_link_text'];
			}
			$this->dest_page = $this->localize_destination_page();
			$this->sess =& get_reason_session();
			$this->logged_in = false;
			// A session exists
			if( $this->sess->exists( ) )
			{
				if( !$this->sess->has_started() )
					$this->sess->start();
				// user is logging out
				if( !empty( $this->request[ 'logout' ] ) )
				{
					$username = $this->sess->get( 'username' );
					$this->sess->destroy();
					$this->msg = 'You are now logged out';
					$this->log_authentication_event('logout succeeded', $username);
					if( empty( $this->request[ 'noredirect' ] ) )
					{
						$parts = parse_url( $this->dest_page );
						$port = (isset($parts['port']) && !empty($parts['port'])) ? ":".$parts['port'] : '';
						$query = (isset($parts['query']) && !empty($parts['query'])) ? '?'.$parts['query'] : '';
						$fragment = (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
						$loc = 'http://'.$parts['host'].$port.$parts['path'].$query.$fragment;
						header( 'Location: '.$loc);
						exit;
					}
				}
				elseif( !$this->sess->get( 'username' ) )
				{
					$this->sess->destroy();
					header( 'Location: '.get_current_url() );
					exit;
				}
				// user is logged in
				else
				{
					$this->logged_in = true;
					$this->msg = 'You are logged in as '.$this->sess->get('username').'.';
					if( !empty( $this->dest_page ) )
					{
						if( $this->dest_page != get_current_url() )
						{
							$dest_txt = $this->_get_dest_page_text();
							$cleaned_dest_page = htmlspecialchars($this->dest_page);
							$this->msg_extra ='<p>Proceed to <a href="'.$cleaned_dest_page.'" title="'.$cleaned_dest_page.'">'.htmlspecialchars($dest_txt).'</a></p>';
						}
					}
				}
			}
			// no session, not logged in
			else
			{
				// trying to login
				if( !empty( $this->request[ 'username' ] ) AND !empty( $this->request[ 'password' ] ) )
				{
					if( $this->test_cookie_exists() )
					{
						$auth = new directory_service($this->params[ 'auth_service' ]);
						
						// succesful login
						if( $auth->authenticate( $this->request['username'], $this->request['password'] ) )
						{
							$this->sess->start();
							$this->logged_in = true;
							$this->sess->set( 'username', trim($this->request['username']) );
							$this->log_authentication_event('login succeeded', $this->request['username']);
							
							// pop user back to the top of the page.  this makes sure that the session
							// info is available to all modules
							if( !empty( $this->dest_page ) )
							{
								$parts = parse_url( $this->dest_page );
								$port = (isset($parts['port']) && !empty($parts['port'])) ? ":".$parts['port'] : '';
								$query = (isset($parts['query']) && !empty($parts['query'])) ? '?'.$parts['query'] : '';
								$fragment = (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
								$loc = securest_available_protocol() . '://'.$parts['host'].$port.$parts['path'].$query.$fragment;
								header( 'Location: '.$loc);
								exit;
							}
							if (!empty($this->request['popup']))
							{
								$this->close_window = true;
								$this->msg = 'You are now logged in. Please close this window.';
							}
						}
						// failed login
						else
						{
							$this->log_authentication_event('login failed', $this->request['username']);
							$this->msg = 'The username and password you provided do not match.  Please try again.';
						}
					}
					else
					{
						$this->msg = 'It appears that you do not have cookies enabled.  Please enable cookies and try logging in again';
					}
				}
				else
				{
					$this->set_test_cookie();
					if( !empty( $this->request[ 'code' ] ) )
					{
						$s =& get_reason_session();
						$this->msg = $s->get_error_msg( $this->request[ 'code' ] );
					}
					if( !empty( $this->request[ 'msg_uname' ] ) )
					{
						$msg_id = id_of($this->request[ 'msg_uname' ], true, false);
						if(!empty($msg_id))
						{
							$msg_ent = new entity($msg_id);
							if( $msg_ent->get_value( 'type' ) == id_of('text_blurb') )
								$this->msg .= $msg_ent->get_value('content');
						}
					}
				}
			}
		}
		function run()
		{
			if (DISABLE_REASON_LOGIN)
			{
				echo '<div id="login">'."\n";
				echo '<h4 class="msg">Reason login is currently disabled</h4>'."\n";
				echo '<div class="msg_extra">Please try again later.</div>'."\n";
				echo '</div>'."\n";
				return false;
			}
			if ($this->close_window)
			{
				?>
				<script language="JavaScript" type="text/javascript">
				window.close();
				</script>
				<?php
			}
			echo '<div id="login">'."\n";
			if( !empty( $this->msg ) )
			{
				echo '<h4 class="msg">'.$this->msg.'</h4>'."\n";
			}
			if(!empty( $this->msg_extra ) )
			{
				echo '<div class="msg_extra">'.$this->msg_extra.'</div>'."\n";
			}
			if( !$this->logged_in )
			{
				
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
				else
				{
					$this->set_test_cookie();
					$uname = '';
					if(!empty($this->request['username']))
					{
						$uname = $this->request['username'];
					}
					$current_url = (!empty($this->dest_page)) ? carl_make_link(array('dest_page' => $this->dest_page)) : get_current_url(); // carl_make_link also runs htmlspecialchars
					?>
					<form action="<?php echo $current_url; ?>" method="post">
						<table cellpadding="4" cellspacing="2" summary="Login Form">
							<tr><td style="text-align:right;">Username:</td><td><input type="text" name="username" value="<?php echo htmlspecialchars($uname); ?>" /></td></tr>
							<tr><td style="text-align:right;">Password:</td><td><input type="password" name="password" /></td></tr>
							<tr><td></td><td><input type="submit" value="Log In" /></td></tr>
						</table>
					</form>
					<?php
					show_cookie_capability('<p class="smallText">You must have cookies enabled to login.  You do not have cookies enabled.</p>');
					if( !empty( $this->dest_page ) )
					{
						if( $this->dest_page != get_current_url() )
						{
							$dest_txt = $this->_get_dest_page_text();
							$cleaned_dest_page = htmlspecialchars($this->dest_page);
							echo '<p class="smallText">You will be redirected to <a href="'.$cleaned_dest_page.'" title="'.$cleaned_dest_page.'">'.htmlspecialchars($dest_txt).'</a> once you login.</p>';
						}
					}
				}
			}
			else
			{
				echo '<a href="?logout=1" class="logoutLink">Logout</a>';
			}
			echo '</div>'."\n";
		}
		
		function _get_dest_page_text($max_chars = 50)
		{
			if( !empty( $this->dest_page ) )
			{
				if( $this->dest_page != get_current_url() )
				{
					if(empty($this->redir_link_text))
					{
						if(strlen($this->dest_page) > $max_chars)
						{
							$piece_length = floor($max_chars/2);
							$dest_txt_1 = substr($this->dest_page,0,$piece_length);
							$dest_txt_2 = substr($this->dest_page,strlen($this->dest_page)-$piece_length);
							$dest_txt = $dest_txt_1.'...'.$dest_txt_2;
						}
						else
						{
							$dest_txt = $this->dest_page;
						}
					}
					else
					{
						$dest_txt = $this->redir_link_text;
					}
					return $dest_txt;
				}
			}
		}
		
		/**
		 * The destination page should only be on the same server as the login page ... this function makes sure that is the case
		 * @author Nathan White
		 */
		function localize_destination_page()
		{
			if ($this->dest_page)
			{
				$current_parts = parse_url( get_current_url() );
				$parts = parse_url( $this->dest_page );
				$port = (isset($parts['port']) && !empty($parts['port'])) ? ":".$parts['port'] : '';
				$query = (isset($parts['query']) && !empty($parts['query'])) ? '?'.$parts['query'] : '';
				$fragment = (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
				return securest_available_protocol() . '://'.$current_parts['host'].$port.$parts['path'].$query.$fragment;
			}
		}
		
		function log_authentication_event($event, $username)
		{
			if (defined('REASON_LOG_LOGINS') && REASON_LOG_LOGINS)
			{
				$refer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '-';
				$logtext = sprintf('%s - %s [%s] "%s" - - "%s" "%s"', 
					$_SERVER['REMOTE_ADDR'], 
					trim($username), 
					date('d/M/Y:H:i:s O'), 
					$event,
					$refer,
					$_SERVER['HTTP_USER_AGENT']
					);
				dlog($logtext, 	REASON_LOG_DIR.'reason_login.log');
			}
		}
	}
?>
