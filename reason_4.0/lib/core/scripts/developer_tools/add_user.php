<?php
/**
 * Command-line interface for adding users, changing passwords, giving users access to sites, and assigning user roles
 *
 * php_cli /path/to/this/file/add_user.php -u username
 * Advanced options:
 * -p (pulls up password prompt; this only works on *nix with shell_exec(). If not available, you can simply enter the password in the command line, i.e. -p password
 * -s site_unique_name
 * -r role_unique_name
 * 
 * Advanced example:
 * php_cli /path/to/this/file/add_user.php -u username -p -s master_admin -r admin_role
 *
 * Advanced example if not *nix or shell_exec() and stty unavailable:
 * php_cli /path/to/this/file/add_user.php -u username -p password -s master_admin -r admin_role
 *
 * @author Matt Ryan
 */
 
 /**
 * Get a password from the shell.
 *
 * This function works on *nix systems only and requires system call access and stty.
 *
 * @return string
 */
function get_password()
{
	system('stty -echo');
	$password = trim(fgets(STDIN));
	system('stty echo');
	return $password;
}

if(PHP_SAPI != 'cli')
{
	echo '<p>This script is intended to be run from the command line.</p>';
	echo '<p>Run php_cli /path/to/this/file/add_user.php -u username</p>';
	echo '<p>Advanced options:</p>';
	echo '<p>-p (pulls up password prompt; this only works on *nix with shell_exec(). If not available, you can simply enter the password in the command line, i.e. -p password</p>';
	echo '<p>-s site_unique_name</p>';
	echo '<p>-r role_unique_name</p>';
	echo '<p>Advanced example:</p>';
	echo '<p>php_cli /path/to/this/file/add_user.php -u username -p password -s master_admin -r admin_role</p>';
}

include('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once ('classes/user.php');

$options = getopt('u:ps:r:');
$caller_username = trim(shell_exec('whoami'));

// -u: username (required)
// -p: password
// -s: site name to provide access to
// -r: role

echo "\n";

if(empty($options['u']))
{
	echo 'A username is required. Please provide a username via -u.'."\n\n";
	die();
}
$username = (string) $options['u'];

$user = new User();
if($caller_entity = $user->get_user($caller_username))
{
	$user->set_causal_agent($caller_username);
	echo 'Running as '.$caller_username."\n";
}
else
{
	$caller_entity = $user->create_user($caller_username);
	$user->set_causal_agent($caller_username);
	echo 'Set up to run as '.$caller_username."\n";
}

if($user_entity = $user->get_user($username))
{
	if($caller_entity->id() != $user_entity->id())
		echo 'Username '.$username.' already exists.'."\n";
}
else
{
	if($user_entity = $user->create_user($username))
	{
		echo 'Username '.$username.' created (Reason entity id '.$user_entity->id().')'."\n";
	}
	else
	{
		echo 'Failed to create user.'."\n\n";
		die();
	}
}
$password = '';
if(!empty($options['p']))
{
	$password = (string) $options['p'];
}
elseif(array_key_exists('p', $options))
{
	fwrite(STDOUT, "Enter a password for this user (Hit return to leave password as-is): ");
	$password = get_password();
}
if(!empty($password))
{
	$values = array(
		'user_password_hash' => sha1($password),
		'user_authoritative_source' => 'reason',
	);
	
	reason_update_entity($user_entity->id(), $caller_entity->id(), $values);
	echo 'Updated password for '.$username."\n";
}
if(!empty($options['s']))
{
	$site_uname = (string) $options['s'];
	if(!reason_unique_name_exists($site_uname))
	{
		echo 'Site '.$site_uname.' not found (Bad unique name). Unable to provide access.'."\n";
	}
	else
	{
		$site_id = id_of($site_uname);
		$site_entity = new entity($site_id);
		if($site_entity->get_value('type') != id_of('site'))
		{
			echo 'Site '.$site_uname.' not found (Unique name does not belong to a site). Unable to provide access.'."\n";
		}
		else
		{
			if($user->add_user_to_site($username, $site_id))
				echo $username . ' now has access to the site '.$site_uname."\n";
			else
				echo $username . ' already had access to the site '.$site_uname."\n";
		}
	}
}

if(!empty($options['r']))
{
	$role_uname = (string) $options['r'];
	if(!reason_unique_name_exists($role_uname))
	{
		echo 'Role '.$role_uname.' not found (Bad unique name). Unable to assign role.'."\n";
	}
	else
	{
		$role_id = id_of($role_uname);
		$role_entity = new entity($role_id);
		if($role_entity->get_value('type') != id_of('user_role'))
		{
			echo 'Role '.$role_uname.' not found (Unique name does not belong to a user role). Unable to assign role.'."\n";
		}
		else
		{
			if(create_relationship($user_entity->id(), $role_id, relationship_id_of('user_to_user_role'), false, true))
				echo $username . ' now has the role '.$role_uname."\n";
			else
				echo $username . ' already had the role '.$role_uname."\n";
		}
	}
}
echo 'Done.'."\n\n";