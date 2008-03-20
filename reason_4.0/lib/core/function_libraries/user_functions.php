<?php
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/admin_actions.php' );

function make_sure_username_is_user($username, $creator_id)
{
	$master_admin_id = id_of('master_admin');
	if(empty($creator_id))
	{
		trigger_error('Creator ID is needed by make_sure_username_is_user (second argument)');
		$creator_id = $master_admin_id;
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
 */
function user_has_access_to_site($site_id, $force_refresh = false)
{
	static $user;
	static $has_access_to_site;
	
 	if (!isset($has_access_to_site[$site_id]) || $force_refresh)
 	{
 		$netid = reason_check_authentication();
 		if ($netid)
 		{
			reason_include_once('classes/user.php');
			if (!isset($user)) $user = new user();
			$has_access_to_site[$site_id] = $user->is_site_user($netid, $site_id, $force_refresh);
		}
		else $has_access_to_site[$site_id] = false;
	}
	return $has_access_to_site[$site_id];
}

/**
* check_authentication returns a username from http authentication or the session and forces login if not found
* @param string $msg_uname unique name of text blurb to show on the login page
* @deprecated since reason 4 beta 4 - use reason_check_authentication or reason_required_authentication
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
 * checks whether the user is authenticated - returns username or forces user to login
 * @param string $msg_uname unique name of text blurb to show on the login page
 * @param string $method optional - can specify whether to check server variables or session - both are checked by default
 * @return string $username
 */
function reason_require_authentication($msg_uname = '', $method = '')
{
	if ($method == 'server') $username = get_authentication_from_server();
	elseif ($method == 'session') $username = get_authentication_from_session();
	else $username = (get_authentication_from_server()) ? get_authentication_from_server() : get_authentication_from_session();
	if (empty($username)) force_login($msg_uname);
	else return $username;
}

/**
 * checks whether the user is authenticated - returns username or boolean false
 * @param string $method optional - can specify whether to check server variables or session - both are checked by default
 * @return mixed $username or false
 */
function reason_check_authentication($method  = '')
{
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
 * redirects the current url to force a secure session
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
 */
function force_secure_if_available()
{
	if(HTTPS_AVAILABLE)
		force_secure();
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
