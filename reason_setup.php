<?php
include ('reason_header.php');
reason_include_once ('classes/entity_selector.php');
reason_include_once ('function_libraries/admin_actions.php');
include_once( CARL_UTIL_INC . 'tidy/tidy.php' );

?>
<html>
<head>
<title>Reason Setup</title>
</head>
<body>

<h2>Reason Setup</h2>
<?
if (isset($_GET['curl_test']))
{
	echo '</body></html>';
	die;
}

if (isset($_POST['do_it_pass']) == false)
{
	if (perform_checks() == false)
	{
		die_with_message('<p>Please address the identified problems and run this script again.</p>');
	}
	$login_site_id = id_of('site_login');
	$login_site_entity = new entity($login_site_id);
	$path = WEB_PATH.trim_slashes($login_site_entity->get_value('base_url'));
	echo '<h3>Checking for Login Site</h3>';
	if(!is_dir($path))
	{
		echo '<p>Setting up login site</p>';
		reason_include_once ('classes/url_manager.php');
		include_once(CARL_UTIL_INC.'basic/filesystem.php');
		mkdir_recursive($path, 0775);
		$um = new url_manager( $login_site_id, true );
		$um->update_rewrites();
	}
	else echo '<p>The login site appears to be setup</p>';
}

if (admin_user_exists() == false)
{
	if (isset($_POST['do_it_pass']))
	{
		$password = create_pass();
		$password_hash = sha1($password);
		$user_id = create_admin_user($password);
		if ($user_id > 0)
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_relation ('((entity.unique_name = "master_admin") OR (entity.unique_name = "site_login"))');
			$result = $es->run_one();
			foreach ($result as $result)
			{
				// check if current primary maintainer is invalid, if so, switch it to the just created admin user
				$current_username = $result->get_value('primary_maintainer');
				$current_userid = get_user_id($current_username);

				if (empty($current_userid))
				{ 
					reason_update_entity( $result->id(), $user_id, array('primary_maintainer' => 'admin'), $archive = false);
				}
			}
			created_admin_HTML($password);
		}
		else 
		{
			die_with_message('<p>Sorry to be the bearer of bad news, but the admin user does not exist and could not be created.</p>');
		}
	}	
	else
	{
		admin_user_HTML();
	}
}
else
{
	die_with_message('<p>This reason instance already has an admin user - you should consider moving this script out of the web tree or deleting it.</p>');
}
?>
</body>
</html>
<?
function admin_user_exists()
{
 	$admin_user_id = id_of('admin_user');
 	if ($admin_user_id > 0) return true;
 	else return false;
}

function create_admin_user($password)
{
	reason_include_once ('classes/user.php');
	$password_hash = sha1($password);
	$my_user = new User();
	$user = $my_user->create_user('admin');
	$user_id = $user->id();
	reason_update_entity( $user_id, $user_id, array('unique_name' => 'admin_user', 'user_email' => WEBMASTER_EMAIL_ADDRESS, 'user_password_hash' => $password_hash, 'user_authoritative_source' => 'reason'), false);
	$admin_id = id_of('admin_role');
	$rel_id1 = relationship_id_of('user_to_user_role');
	$rel_id2 = relationship_id_of('site_to_user');
	$ma_id = id_of('master_admin');
	create_relationship($user_id, $admin_id, $rel_id1, false, true);
	create_relationship($ma_id, $user_id, $rel_id2, false, true);
	return $user_id;
}

function created_admin_HTML($password)
{
	echo '<h3>Admin User Created</h3>';
	echo '<p>The reason user <strong>admin</strong> has been created with password <strong>'.$password.'</strong></p>';
	echo '<p>Write down the password! This script will not create another unless the original is deleted.</p>';
	echo '<p>You should now be able to login to the <a href="'.securest_available_protocol().'://'.REASON_WEB_ADMIN_PATH.'">reason administrative interface</a>.</p>';
}

function perform_checks()
{
	$check_passed = 0;
	$check_failed = 0;
	echo '<h3>Performing Basic Checks</h3>';
	// perform checks - each check should echo a success or failure string, and return true if successful
	// $check_passed and $check_failed increment accordingly. perform_checks returns true if all checks pass
	
	if (http_host_check()) $check_passed++;
	else $check_failed++;
	
	if (tidy_check()) $check_passed++;
	else $check_failed++;
	
	if (curl_check()) $check_passed++;
	else $check_failed++;
	
	echo '<h3>Performing Directory and File Checks</h3>';
	
	if (data_dir_writable(REASON_CSV_DIR, 'REASON_CSV_DIR')) $check_passed++;
	else $check_failed++;
	
	if (data_dir_writable(REASON_LOG_DIR, 'REASON_LOG_DIR')) $check_passed++;
	else $check_failed++;
	
	if (data_dir_writable(ASSET_PATH, 'ASSET_PATH')) $check_passed++;
	else $check_failed++;
	
	if (data_dir_writable(PHOTOSTOCK, 'PHOTOSTOCK')) $check_passed++;
	else $check_failed++;
	
	if (data_dir_writable(REASON_TEMP_DIR, 'REASON_TEMP_DIR')) $check_passed++;
	else $check_failed++;
	
	// In our default config if this path is not writable then uploads should fail. Probably it would be better to distribute the package with 
	// the /www/tmp directory being an alias to the file system location of REASON_TEMP_DIR. Until this is done, we are leaving it like this. 
	// We may want to do something to check the validity of those aliases, such as writing a file then trying to access it via curl. The same 
	// thing could be done for assets.
	if (data_dir_writable($_SERVER[ 'DOCUMENT_ROOT' ].WEB_TEMP, 'WEB_TEMP')) $check_passed++;
	else $check_failed++;
	
	if (check_file_readable(APACHE_MIME_TYPES, 'APACHE_MIME_TYPES')) $check_passed++;
	else $check_failed++;
	
	echo '<h3>Summary</h3>';
	echo '<ul>';
	echo '<li>'.$check_passed.' checks were successful</li>';
	echo '<li>'.$check_failed.' checks failed</li>';
	echo '</ul>';
	if ($check_failed == 0) return true;
	else return false;
}

function http_host_check()
{
	if ($_SERVER['HTTP_HOST'] == HTTP_HOST_NAME) return msg('http host check passed', true);
	else return msg('http host check failed - make sure the HTTP_HOST_NAME constant in paths.php is equivalent to the $_SERVER[\'HTTP_HOST\'] value', false);
}

function tidy_check()
{
	$html_string = '<h3>babababab</h3>';
	$string = tidy($html_string);
	if ($string == '') return msg('tidy check failed - make sure the constant TIDY_EXE in paths.php is set to the location of the tidy executable', false);
	else return msg('tidy check passed', true);
}

function curl_check()
{
	$content = get_reason_url_contents( carl_make_link(array('curl_test' => 'true')));
	if (empty($content)) return msg('curl check failed', false);
	else return msg('curl check passed', true);
}

function data_dir_writable($dir, $name)
{
	if (is_writable($dir)) return msg($name . ' directory is writable - check passed', true);
	else return msg ($name . ' directory not writable - failed. Make sure apache user has write access to ' . $dir, false);
}

function check_file_readable($file, $name)
{
	if (is_readable($file)) return msg($name . ' file is readable - check passed', true);
	else return msg ($name . ' file not readable - failed. Make sure ' .$file. ' exists and apache user has read access to it', false);
}

function msg($msg, $bool)
{
	echo '...' . $msg;
	echo '<br />';
	return $bool;
}

function admin_user_HTML()
{
	echo '<h3>Create Admin User</h3>';
	echo '<p>Your Reason instance does not have an administrative user. In order to login and create users, we need to setup the administrative 
user. Press submit to create the user and a random password - MAKE SURE TO WRITE DOWN THE PASSWORD!. You can change the password later but will
need it to login</p>';
echo '<form method="post"><input type="submit" name="do_it_pass" value="Do It!" /></form>';
}

function create_pass()
{
	$pass = '';
	$chars = "1234567890abcdefghijklmnopqrstuvwxyz";
	while (strlen($pass) < 6)
	{
		$my_char = $chars{rand(0,35)};
		if (!is_numeric($my_char))
		{
			if (rand(0,1) == 0) $my_char = strtoupper($my_char);
		}
		$pass .= $my_char;
	}
	return $pass;
}

function die_with_message($msg)
{
	echo $msg;
	die;
}
?>

