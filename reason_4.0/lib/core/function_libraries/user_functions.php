<?php
/**
 * Functions for user creation, authentication, and privilege checking
 * @package reason
 * @subpackage function_libraries
 */

/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/group_helper.php' );
reason_include_once( 'function_libraries/admin_actions.php' );
reason_include_once( 'function_libraries/util.php' );

/**
 * Get the Reason ID for a given username; otherwise create a new user and return created Reason ID
 * @param string $username
 * @param integer $creator_id the ID of the user that is creating the user (if needed)
 * @return integer $user_id
 */
function make_sure_username_is_user($username, $creator_id)
{
	$master_admin_id = id_of('master_admin');
	if(empty($creator_id))
	{
		trigger_error('Creator ID is needed by make_sure_username_is_user (second argument)');
		$creator_id = $master_admin_id;
	}
	if(empty($username))
	{
		trigger_error('Username is needed by make_sure_username_is_user (first argument)', E_USER_ERROR);
		die();
	}
	$es = new entity_selector($master_admin_id);
	$es->add_type(id_of('user'));
	$es->add_relation('entity.name = "'.$username.'"');
	$es->set_num(1);
	$users = $es->run_one();
	if(empty($users))
	{
		$new_user_id = create_entity( 
										$master_admin_id, 
										id_of('user'), 
										$creator_id,
										$username
									);
		return $new_user_id;
	}
	else
	{
		$user = current($users);
		return $user->id();
	}
}

/**
 * check if the currently logged in user has access to the site - do not force login
 * @deprecated use reason_check_access_to_site
 */
function user_has_access_to_site($site_id, $force_refresh = false)
{
	return reason_check_access_to_site($site_id, $force_refresh);
}

/**
 * Find out if a given username has access to edit a given site
 *
 * Note that an additional check against reason_user_has_privs() should be done before granting privilegesm, 
 * as the user may not have the specific privileges needed despite having access.
 *
 * @param string $username
 * @param integer $site_id
 * @param boolean $force_refresh Set this to false if the same script has previously changed site privs (this should be a very rare case)
 * @return boolean true if the user has access to edit the site; false if the user does not
 */
function reason_username_has_access_to_site($username, $site_id, $force_refresh = false)
{
	static $has_access_to_site;
	
	if (empty($username)) return false;
 	if (!isset($has_access_to_site[$username][$site_id]) || $force_refresh)
 	{
 		$id = get_user_id($username);
 		$has_access_to_site[$username][$site_id] = (!empty($id)) ? user_can_edit_site($id, $site_id, $force_refresh) : false;
	}
	return $has_access_to_site[$username][$site_id];
}

/**
 * Combines reason_check_authentication with reason_username_has_access_to_site
 *
 * Checks if current user has admin access to given site
 *
 * @param integer $site_id
 * @param boolean $force_refresh
 * @return boolean
 */
function reason_check_access_to_site($site_id, $force_refresh = false)
{
	$netid = reason_check_authentication();
	return reason_username_has_access_to_site($netid, $site_id, $force_refresh);
}

/**
 * Checks whether the given user is a member of the given group.
 * @param string $username the username of the user whose group membership is
 *        in question
 * @param int|object $group a group entity or the ID of a group entity
 * @return boolean true if the user was determined to be a member of the group;
 *         false if otherwise
 * @since Reason 4.0 beta 8
 */
function reason_user_is_in_group($username, $group)
{	
	static $error_prefix = "cannot check if user is a member of a group:";	
	if (!$username)
	{
		return false;
		trigger_error("$error_prefix a username was not provided");
	}
	elseif (!$group || (!is_object($group) && !is_numeric($group)))	
	{
		trigger_error("$error_prefix an integer (group id) or entity was not provided");
		return false;
	}
	else
	{
		$group_entity = is_object($group) ? $group : new entity($group);
		if (reason_is_entity($group_entity, 'group_type'))
		{
			$helper = new group_helper();
			$helper->set_group_by_entity($group_entity);
			return $helper->is_username_member_of_group($username);
		}
		else
		{
			trigger_error("$error_prefix a valid group entity was not provided");
			return false;
		}
	}
}

/**
 * Get the Reason entity that represents the current user, if one exists
 *
 * @return mixed Reason entity or false (if no user logged in or if logged-in user does not have Reason entity)
 */
function reason_get_current_user_entity()
{
	static $user;
	if(!isset($user))
	{
		if($username = reason_check_authentication())
		{
			$es = new entity_selector();
			$es->add_type(id_of('user'));
			$es->add_relation('entity.name = "'.addslashes($username).'"');
			$es->set_num(1);
			$result = $es->run_one();
			if(!empty($result))
			{
				$user = current($result);
			}
		}
		if(empty($user))
		{
			$user = false;
		}
	}
	return $user;
}

/**
 * Combines reason_check_authentication with reason_user_has_privs
 * @param string $privilege
 * @return boolean true if current user have privs; false if no logged-in user or if logged-in user does not have privs
 */
function reason_check_privs($privilege)
{
	$netid = reason_check_authentication();
	$user_id = get_user_id($netid);
	return reason_user_has_privs($user_id, $privilege);
}

/**
 * checks whether the user is authenticated - returns username or forces user to login
 * @param string $msg_uname unique name of text blurb to show on the login page
 * @param string $method optional - can specify whether to check server variables or session - both are checked by default
 * @return string $username
 */
function reason_require_authentication($msg_uname = '', $method = '')
{
	force_secure_if_available();
	if ($method == 'server') $username = get_authentication_from_server();
	elseif ($method == 'session') $username = get_authentication_from_session();
	else $username = (get_authentication_from_server()) ? get_authentication_from_server() : get_authentication_from_session();
	if (empty($username)) force_login($msg_uname);
	else return $username;
}

/**
 * checks whether the user is authenticated - returns username or boolean false
 * @param string $method optional - can specify whether to check server variables or session - both are checked by default
 * @param boolean $disable_redirect - defaults to false - if true, disables redirect to secure version of page for logged in users
 * @return mixed $username or false
 */
function reason_check_authentication($method  = '', $disable_redirect = false)
{
	if (!$disable_redirect && ($method == '' || $method == 'session'))
	{
		$session =& get_reason_session();
		if ($session->exists() && !$session->has_started() && !$session->secure_if_available) force_secure_if_available();
	}
	if ($method == 'server') $username = get_authentication_from_server();
	elseif ($method == 'session') $username = get_authentication_from_session();
	else $username = (get_authentication_from_server()) ? get_authentication_from_server() : get_authentication_from_session();
	return $username;
}

/**
 * redirects to the login page with the appropriate return url
 * @param string $msg_uname unique name of text blurb to show on the login page
 */
function force_login($msg_uname = '')
{
	$url = get_current_url();
	$url = urlencode($url);
	if (!empty($msg_uname))
	{
		header('Location: '.REASON_LOGIN_URL.'?dest_page='.$url.'&msg_uname='.$msg_uname);
	}
	else
	{
		header('Location: '.REASON_LOGIN_URL.'?dest_page='.$url);
	}
	exit();
}
/**
 * Require authentication via http basic auth
 *
 * Note 1: If the user already has a session-based login, or the script is otherwise behind an
 * apache-rule-based http auth, this function will return the username without forcing a second
 * login.
 *
 * Note 2: This function currently only works properly when php is running as an Apache module. If
 * Apache is running under CGI/Fast CGI, it currently simply denies access.
 *
 * @todo Add CGI/FastCGI support
 *
 * @param string $realm
 * @param string $cancel_message
 * @return string username
 *
 */
function reason_require_http_authentication($realm = FULL_ORGANIZATION_NAME, $cancel_message = '')
{
	if($username = reason_check_authentication())
		return $username;
	
	force_secure_if_available();
	
	if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW']))
	{
    	require_once(CARL_UTIL_INC.'dir_service/directory.php');
    	$dir = new directory_service();
    	if($dir->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
    		return $_SERVER['PHP_AUTH_USER'];
    }
    $cgi_mode = (substr(php_sapi_name(), 0, 3) == 'cgi');
    if(!$cgi_mode)
    {
		header('WWW-Authenticate: Basic realm="'.str_replace('"',"'",$realm).'"');
	}
	http_response_code(401);
	if(empty($cancel_message))
	{
		$msg_str = 'This resource requires login.';
		$cancel_message = '<!doctype HTML><html><title>'.$msg_str.'</title></head><body><h3>'.$msg_str.'</h3>';
		if($cgi_mode && function_exists('is_developer') && is_developer())
			$cancel_message .= '<p>HTTP authentication is not currently supported when PHP is running under CGI/Fast CGI.</p>';
		$cancel_message .= '</body></html>';
	}
	echo $cancel_message;
    exit;
}

/**
 * redirects the current url to force a secure session
 *
 *If the current page is insecure, this function will header to the secure version of the page and die
 *
 * @return void
 */
function force_secure()
{
	if (!on_secure_page())
	{
		$url = get_current_url( 'https' );
		header('Location: '.$url);
		exit();
	}
}
/**
 * redirects the current url to force a secure session -- but only if the server supports https
 * @return void
 */
function force_secure_if_available()
{
	if(HTTPS_AVAILABLE)
		force_secure();
}

/**
* check_authentication returns a username from http authentication or the session and forces login if not found
* @param string $msg_uname unique name of text blurb to show on the login page
* @deprecated since reason 4 beta 4 - use reason_check_authentication or reason_require_authentication
* @return string $username
*/
function check_authentication($msg_uname = '')
{
		if($username = get_authentication_from_server())
		{
				return $username;
		}
		else
		{
				if($username = get_authentication_from_session())
				{
						return $username;
				}
				else
				{
						force_login($msg_uname);
				}
		}
}

/**
 * Returns the current user's netID, or false if the user does not have an active reason session.
 * @return string user's netID.
 */
function get_authentication_from_session()
{
	$session =& get_reason_session();
	if($session->exists())
	{
		if( !$session->has_started() )
		{
			$session->start();
		}
		$username = $session->get( 'username' );
		return $username;
	}
	else
	{
		return false;
	}
}

/**
 * Returns the current user's netID from $_SERVER['REMOTE_USER'], or false if the value is not present.
 * @return string user's netID.
 */
function get_authentication_from_server()
{
	if(!empty($_SERVER['REMOTE_USER']))
	{
		return $_SERVER['REMOTE_USER'];		
	}
	else return false;
}

?>