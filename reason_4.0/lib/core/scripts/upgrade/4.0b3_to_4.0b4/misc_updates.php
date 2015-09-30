<?php
/**
 * Run a variety of updates for the beta 3 to beta 4 upgrade
 *
 * Includes:
 * - Adding a content manager for themes
 * - A bunch of new themes
 * - Adds new Editor user role
 * - Enables flash video as a media type
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include ('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/user.php');
reason_include_once('classes/theme.php');
reason_include_once('function_libraries/file_finders.php');

// try to increase limits in case user chooses a really big chunk
set_time_limit(1800);
ini_set('max_execution_time', 1800);
ini_set('mysql_connect_timeout', 1200);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Reason Upgrade: Miscellaneous 4.0b3 to 4.0b4 Updates</title>
</head>

<body>
<?php

force_secure_if_available();

$GLOBALS['__cur_username__hack__'] = reason_require_authentication();
$usr = new user();
$user = $usr->get_user($GLOBALS['__cur_username__hack__']);
if($user)
{
	$GLOBALS['__cur_user_id_hack__'] = $user->id();
}
else
{
	echo 'You must be a valid reason user to run this script';
	die();
}

if(!reason_user_has_privs( $GLOBALS['__cur_user_id_hack__'], 'upgrade' ) )
{
	die('You must have upgrade privileges to run this script');
}

echo '<h2>Reason: Miscellaneous 4.0b3 to 4.0b4 Updates</h2>';
if ( !isset ($_POST['verify']))
{
        echo '<p>This script does a variety of minor updates to your Reason instance, including:</p>';
		echo '<ul>';
		echo '<li>Setting up a new content manager for themes</li>';
		echo '<li>Adding new themes:';
		echo '<ul>';
		foreach(get_themes_to_add_b3_to_b4() as $theme_uname=>$theme_info)
		{
			if(!empty($theme_info['name']))
				$name = $theme_info['name'];
			else
				$name = prettify_string($theme_uname);
			echo '<li>'.$name.'</li>';
		}
		echo '</ul>';
		echo '</li>';
		echo '<li>Changes the event.last_occurence field to be a date field rather than a text field</li>';
		echo '<li>Adds indexes to the following fields:';
		echo '<ul>';
		foreach(get_indexes_to_add_b3_to_b4() as $table=>$fields)
		{
			foreach($fields as $field)
			{
				echo '<li>'.$table.'.'.$field.'</li>';
			}
		}
		echo '</ul>';
		echo '</li>';
		echo '<li>Adds a Editor user role and assoiates every user who does not have a role to the new role</li>';
		echo '<li>Adds Flash Video as an option to av.media_format</li>';
		echo '</ul>';
		echo_form();
}

if (isset ($_POST['verify']))
{
	$test_mode = true;
	if(!empty($_POST['run']) && $_POST['run'] == 'Run')
		$test_mode = false;
	run_updates($test_mode);
}

function echo_form()
{
	echo '<form name="doit" method="post" action="'.get_current_url().'" />';
	echo '<input type="submit" name="run" value="Run" />';
	echo '<input type="submit" name="test" value="Test" />';
	echo '<input type="hidden" name="verify" value="true" />';
	echo '</form>';
}

function run_updates($test_mode = true)
{
	$updates = array('update_theme_content_manager','add_new_themes','change_event_last_occurence_to_date','add_indexes_b3_to_b4','add_editor_user_role','add_flash_video_media_type');
	if($test_mode)
	{
		echo '<h2>Testing</h2>';
	}
	foreach($updates as $update)
	{
		$update($test_mode);
	}
}

function get_themes_to_add_b3_to_b4()
{
	return array(
				'simplicity_tan_theme'=>array(
					'name'=>'Simplicity Tan',
					'css'=>array('Simplicity Tan'=>array('url'=>'css/simplicity/tan.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'default'
				),
				'simplicity_grey_theme'=>array(
					'name'=>'Simplicity Grey',
					'css'=>array('Simplicity Grey'=>array('url'=>'css/simplicity/grey.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'default'
				),
				'black_box_theme'=>array(
					'name'=>'Black Box',
					'css'=>array('Black Box'=>array('url'=>'css/themes/black_box/black_box.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables'
				),
				'pedagogue_plum_theme'=>array(
					'name'=>'Pedagogue Plum',
					'css'=>array('Pedagogue Plum'=>array('url'=>'css/themes/pedagogue/plum.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables'
				),
				'gemstone_hematite_theme'=>array(
					'name'=>'Gemstone Hematite',
					'css'=>array('Gemstone Base'=>array('url'=>'css/themes/gemstone/gemstone.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables'
				),
				'gemstone_ruby_theme'=>array(
					'name'=>'Gemstone Ruby',
					'css'=>array('Gemstone Ruby'=>array('url'=>'css/themes/gemstone/ruby/ruby.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables'
				),
				'gemstone_emerald_theme'=>array(
					'name'=>'Gemstone Emerald',
					'css'=>array('Gemstone Emerald'=>array('url'=>'css/themes/gemstone/emerald/emerald.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables',
					'image'=>'css/themes/gemstone/emerald/example.png', // this is not yet working, but the idea is that we would import the example image at the same time as we import the theme
				),
				'starbaby_theme'=>array(
					'name'=>'Starbaby',
					'css'=>array('Starbaby'=>array('url'=>'css/themes/starbaby/starbaby.css','css_relative_to_reason_http_base'=>'true')),
					'template'=>'tables',
				),
	);
}

function update_theme_content_manager($test_mode = true)
{
	echo '<h3>Theme Content Manager Update</h3>';
	$theme_type = new entity(id_of('theme_type'));
	if($theme_type->get_values())
	{
		if(!$theme_type->get_value('custom_content_handler'))
		{
			if($test_mode)
			{
				echo '<p>Would have updated the theme type to use the new content manager</p>';
			}
			else
			{
				if(reason_update_entity( id_of('theme_type'), $GLOBALS['__cur_user_id_hack__'], array('custom_content_handler'=>'theme.php')))
				{
					echo '<p>Updated the theme type to use the new content manager</p>';
				}
				else
				{
					echo '<p>Some sort of problem has occurred with updating the theme type to use the new content manager</p>';
				}
			}
		}
		else
		{
			echo '<p>Theme type appears to be using the file '.$theme_type->get_value('custom_content_handler').' as a content manager. No database changes are needed.</p>';
		}
	}
	else
	{
		echo '<p>Theme type not found; unable to update</p>';
	}
}

function add_new_themes($test_mode = true)
{
	echo '<h3>Adding new themes</h3>';
	$themes_to_add = get_themes_to_add_b3_to_b4();
	//pray($themes_to_add);
	foreach($themes_to_add as $unique_name=>$theme_info)
	{
		$rt = new reasonTheme();
		$rt->set_test_mode($test_mode);
		$css = array();
		if(!empty($theme_info['css']))
			$css = $theme_info['css'];
		$results = $rt->add_complete($unique_name,$theme_info['name'],$css,$theme_info['template'],$GLOBALS['__cur_user_id_hack__']);
		echo $results['report'];
	}
}

function change_event_last_occurence_to_date($test_mode = true)
{
	echo '<h3>Changing event.last_occurence to DATE</h3>';
	$handle = db_query('DESC `event` `last_occurence`');
	$results = array();
	while($row = mysql_fetch_assoc($handle))
	{
		$results = $row;
	}
	if(strtolower($results['Type']) == 'date')
	{
		echo '<p>event.last_occurence is already set to be a date field. No db changes are necessary.</p>';
	}
	else
	{
		if($test_mode)
		{
			echo '<p>Would have updated event.last_occurence to be a true date field</p>';
		}
		else
		{
			if(db_query('ALTER TABLE `event` CHANGE `last_occurence` `last_occurence` DATE NULL DEFAULT NULL'))
			{
				echo '<p>Successfully updated event.last_occurence to be a date field</p>';
			}
			else
			{
				echo '<p>Failed to update event.last_occurence to be a date field. You might try to manually update the column definition for event.last_occurence from "tinytext" to "date."</p>';
			}
		}
	}
}

function get_indexes_to_add_b3_to_b4()
{
	return array('dated'=>array('datetime'),'event'=>array('last_occurence'));
}

function add_indexes_b3_to_b4($test_mode = true)
{
	echo '<h3>Adding indexes</h3>';
	echo '<ul>';
	foreach(get_indexes_to_add_b3_to_b4() as $table=>$fields)
	{
		$handle = db_query('SHOW INDEX FROM `'.reason_sql_string_escape($table).'`');
		$results = array();
		while($row = mysql_fetch_assoc($handle))
		{
			$results[] = $row['Column_name'];
		}
		foreach($fields as $field)
		{
			if(in_array($field, $results))
			{
				echo '<li>'.$table.'.'.$field.' is already indexed. No need to do anything.</li>';
			}
			else
			{
				if($test_mode)
				{
					echo '<li>Would have added index on '.$table.'.'.$field.'.</li>';
				}
				else
				{
					if(db_query('ALTER TABLE `'.reason_sql_string_escape($table).'` ADD INDEX ( `'.reason_sql_string_escape($field).'` )'))
					{
						echo '<li>Successfully added index on '.$table.'.'.$field.'.</li>';
					}
					else
					{
						echo '<li>Attempted to add index on '.$table.'.'.$field.', but failed.</li>';
					}
				}
			}
		}
	}
	echo '</ul>';
}

function add_editor_user_role($test_mode = true)
{
	echo '<h3>Adding editor user role</h3>';
	$editor_user_role_id = id_of('editor_user_role');
	echo '<ul>';
	if(!empty($editor_user_role_id))
	{
		echo '<li>Editor user role already exists; no need to create it</li>';
	}
	else
	{
		if($test_mode)
		{
			echo '<li>Would have created editor user role</li>';
		}
		else
		{
			$editor_user_role_id = reason_create_entity(id_of('master_admin'), id_of('user_role'), $GLOBALS['__cur_user_id_hack__'], 'Editor', array('new'=>'0','unique_name'=>'editor_user_role'));
			if($editor_user_role_id)
				echo '<li>Created editor user role (ID: '.$editor_user_role_id.')</li>';
			else
			{
				echo '<li>Unable to create editor user role! Aborting this step.</li></ul>';
				return false;
			}
		}
	}
	// get users with their user roles
	$es = new entity_selector();
	$es->add_type(id_of('user'));
	$es->add_left_relationship_field( 'user_to_user_role' , 'entity' , 'id' , 'user_role_id', false );
	$users = $es->run_one();
	if($test_mode)
		echo '<li>Would have added editor user role to these users currently without a role:';
	else
		echo '<li>Adding editor user role to users currently without a role:';
	echo '<ol>';
	$count = 0;
	foreach($users as $user)
	{
		if(!$user->get_value('user_role_id'))
		{
			$count++;
			if($test_mode)
			{
				echo '<li>'.$user->get_value('name').'</li>';
			}
			else
			{
				if( create_relationship( $user->id(), $editor_user_role_id, relationship_id_of('user_to_user_role') ) )
					echo '<li>'.$user->get_value('name').'</li>';
				else
					echo '<li><strong>Eeep!</strong> Problem assigning editor user role to '.$user->get_value('name').'</li>';
			}
		}
	}
	echo '</ol>';
	if(!$count)
		echo '<strong>No users needed to be updated; all users have roles.</strong>';
	echo '</li>';
	echo '</ul>';
}

function add_flash_video_media_type($test_mode = true)
{
	echo '<h3>Changing av.media_format to include "Flash Video"</h3>';
	$handle = db_query('DESC `av` `media_format`');
	$results = array();
	while($row = mysql_fetch_assoc($handle))
	{
		$results = $row;
	}
	if(strpos($results['Type'],'Flash Video'))
	{
		echo '<p>av.media_format already includes Flash Video. No db changes are necessary.</p>';
	}
	else
	{
		if($test_mode)
		{
			echo '<p>Would have updated av.media_format to include the Flash Video enum option</p>';
		}
		else
		{
			if(db_query('ALTER TABLE `av` CHANGE `media_format` `media_format` enum(\'Quicktime\',\'Windows Media\',\'Real\',\'Flash\',\'MP3\',\'AIFF\',\'Flash Video\')'))
			{
				echo '<p>Successfully updated av.media_format to include the Flash Video enum option</p>';
			}
			else
			{
				echo '<p>Failed to update av.media_format to include the Flash Video enum option. You might try to manually update the column definition for av.media_format to "enum(\'Quicktime\',\'Windows Media\',\'Real\',\'Flash\',\'MP3\',\'AIFF\',\'Flash Video\')"</p>';
			}
		}
	}
}

?>
</body>
</html>
