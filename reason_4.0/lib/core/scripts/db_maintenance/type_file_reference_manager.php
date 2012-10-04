<?php
/**
 * Script that helps find references to nonexistent (or non-local) files in the Reason db
 * and assists in fixing them.
 * @package reason
 * @subpackage scripts
 */

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/file_finders.php');
reason_include_once('function_libraries/url_utils.php');

$fields = array (	
				'custom_content_handler' => 'content_managers', 
				'custom_content_lister' => 'content_listers', 
				'custom_deleter' => 'content_deleters',
				'display_name_handler' => 'display_name_handlers',
				'custom_previewer' => 'content_previewers', 
				'custom_sorter' => 'content_sorters', 
				'custom_post_deleter' => 'content_post_deleters',
				'custom_feed' => 'feeds',
				'finish_actions' => 'finish_actions'	
				);
				
// make sure user is authenticated, is a member of master admin, AND has the admin role.
force_secure_if_available();
$authenticated_user_netid = check_authentication();
auth_site_to_user( id_of('master_admin'), $authenticated_user_netid );
$user_id = get_user_id( $authenticated_user_netid );

if (!reason_user_has_privs( $user_id, 'db_maintenance' ) )
{
	die('<html><head><title>Reason: Delete Duplicate Relationships</title></head><body><h1>Sorry.</h1><p>You do not have permission to delete duplicate relationships.</p><p>Only Reason users who have database maintenance privileges may do that.</p></body></html>');
}

if (!isset($_GET['fields_referencing'])) $_GET['fields_referencing'] = 'local';
if (!isset($_GET['allow_new_from'])) $_GET['allow_new_from'] = 'core';
if (!isset($_GET['limit_to_field'])) $_GET['limit_to_field'] = '';

show_config($fields);

echo '<h1>"Type" Type File Reference Manager</h1>';
echo '<p>This script examines the types in your reason instance, and discovers references to files on the "local" and/or "core" side of the ';
echo ' file system split. The script allows you to easily change these references, or resolve references to missing files. By default, missing ';
echo ' files and references to "local" files are displayed, and "core" files are shown as available options for new values.</p>';

// we loop through POST and make sure it contains requested changes to type entities and that those are limited to changeable fields.
// once we do this, we actually make the changes.
if (!empty($_POST))
{
	unset($_POST['process']);
	$count = 0;
	foreach ($_POST as $k => $v)
	{
		$change_values = array();
		// each $k should be an integer that corresponds to a type entity. If not, ignore it.
		if (is_int($k))
		{
			$e = new entity($k);
			if (reason_is_entity($e, 'type'))
			{
				foreach ($v as $field => $value)
				{
					if (isset($fields[$field]))
					{
						if (reason_file_exists($fields[$field].'/'.$value))
						{
							$change_values[$field] = $value;
						}
					}
				}
				if (!empty($change_values))
				{
					if ($update_attempt = reason_update_entity( $k, $user_id, $change_values ))
					{
						$count++;
					}
				}	
			}
		}
	}
	echo '<h3>'.$count.' entity updates were saved.</h3>';
}

if ($_GET['fields_referencing'] === 'all') $fields_referencing = array('core', 'local', '');
if ($_GET['fields_referencing'] === 'core') $fields_referencing = array('core', '');
if ($_GET['fields_referencing'] === 'local') $fields_referencing = array('local', '');
if ($_GET['fields_referencing'] === 'missing') $fields_referencing = array(''); 

if ($_GET['allow_new_from'] === 'all') $allow_new_from = array('core', 'local');
if ($_GET['allow_new_from'] === 'core') $allow_new_from = array('core');
if ($_GET['allow_new_from'] === 'local') $allow_new_from = array('local');

if (!empty($_GET['limit_to_field'])) $fields = array($_GET['limit_to_field'] => $fields[$_GET['limit_to_field']]);

$status = find_type_localization($fields);
$new_options = find_options($fields, $allow_new_from);
build_form($status, $new_options, $fields_referencing);

/**
 * find type localization
 * 
 * iterate through types and find .php and .php3 file locations and values for content managers, custom deleters,
 * custom post deleters, custom previewers, custom sorters, and finish actions.
 * 
 */
function find_type_localization($fields)
{
	$type_status = array();
	
	$es = new entity_selector(id_of('master_admin'));
	$es->add_type(id_of('type'));
	$es->set_order('entity.name ASC');
	$types = $es->run_one();
	foreach ($types as $k=>$v)
	{
		$item = '';
		$typename = $v->get_value('name');
		$typestring = '<h3>'. $v->get_value('name') . ' Type</h3>';
		foreach ($fields as $k2=>$v2)
		{	
			$filename = $v->get_value($k2);
			if (!empty($filename))
			{
				$loc = reason_file_location($v2 . '/' . $filename, 'lib', 'true');
				$type_status[$k]['fields'][$k2]['current'] = $filename;
				$type_status[$k]['fields'][$k2]['location'] = $loc;
			}
		}
		if (!empty($type_status[$k]))
		{
				$type_status[$k]['name'] = $typename;
		}
	}
	return $type_status;
}

function find_options($fields, $areas = array('core'))
{
	foreach ($fields as $k=>$v)
	{
		$options[$k] = reason_get_fileset($v, 'lib', $areas);
	}
	return $options;
}

function build_form($type_info_array, $new_options, $show = array('core', 'local', ''))
{
	$has_content = false;
	$html[] = '<form style="clear: both;" action = '.get_current_url().' method="post">';
	$html[] = '<table border="1px" cellpadding="4px" cellspacing="0px">';
	$html[] = '<tr><th>Type</th><th>Field</th><th>Current Value</th><th>Location</th><th>New Value</th></tr>';
	foreach ($type_info_array as $k => $v)
	{
		foreach ($v['fields'] as $k2 => $v2)
		{
			if (in_array($v2['location'], $show))
			{
				$html[] = '<tr>';
				$html[] = '<td>'.$v['name'].'</td>';
				$html[] = '<td>'.$k2.'</td>';
				$html[] = '<td>'.$v2['current'].'</td>';
				$html[] = ($v2['location']) ? '<td>'.$v2['location'].'</td>' : '<td><strong>missing</strong></td>';
				if ($v2['location']) $html[] = '<td>'.build_select_list($k.'['.$k2.']', $new_options[$k2], array($v2['current'] => $v2['current'] . ' ('.$v2['location'].')', '0' => 'change to null')).'</td>';
				else $html[] = '<td>'.build_select_list($k.'['.$k2.']', $new_options[$k2], array('0' => 'change to null')).'</td>';
				$html[] = '</tr>';
				$has_content = true;
			}
		}
	}
	$html[] = '</table>';
	$html[] = '<p><input name="process" value = "Update" type="submit"></p>';
	$html[] = '</form>';
	
	if ($has_content)
	{
		foreach ($html as $line)
		{
			echo $line . "\n";
		}
	}
	else
	{
		echo '<p>No rows to show.</p>';
	}
}

function build_select_list($name, $options_array, $default)
{
	$default_key = key($default);
	$options_array = $default + $options_array;
	$html = '<select name="'.$name.'">';
	//pray ($options_array);
	foreach ($options_array as $k => $v)
	{
		$selected = ($k === $default_key) ? ' selected' : '';
		if ($k == "0")
		{
			$html .= '<option value=""'.$selected.'>'.$v.'</option>';
		}
		else
		{
			$html .= '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
		}
	}
	$html .= '</select>';
	return $html;
}

function reason_get_fileset($dir_path, $section = 'lib', $areas = array('core'))
{
        $files = array();
        foreach($areas as $area)
        {
                $directory = REASON_INC.$section.'/'.$area.'/'.trim_slashes($dir_path).'/';
                if(is_dir( $directory ) )
                {
                        $handle = opendir( $directory );
                        while( $entry = readdir( $handle ) )
                        {
                                if( is_file( $directory.$entry ) )
                                {
                                        $files[$entry] = $entry .' ('.$area.')';
                                }
                        }
                }
        }
        ksort($files);
        return $files;
}

function reason_file_location($path, $section = 'lib', $return_area = false)
{
        $areas = array('core','local');
        foreach($areas as $area)
        {
                if(file_exists(REASON_INC.$section.'/'.$area.'/'.$path))
                {
                        if ($return_area) return $area;
                        return true;
                }
        }
        return false;
}

function show_config($fields)
{
	echo '<div style="float: right; width: 300px; border: 1px solid #000; padding: 5px; margin-left: 25px; margin-bottom: 25px;">';
	echo '<h2 style="padding: 0px; margin: 0px;">Configuration</h2>';
	echo '<form style="margin-top: 10px;" action = "'.get_current_url().'" method="get">';
	echo 'Show fields referencing ';
	echo '<select name="fields_referencing">';
	echo '<option value="all"'.check_me('fields_referencing', 'all').'>All</option>';
	echo '<option value="core"'.check_me('fields_referencing', 'core').'>Core</option>';
	echo '<option value="local"'.check_me('fields_referencing', 'local').'>Local</option>';
	echo '<option value="missing"'.check_me('fields_referencing', 'missing').'>Missing</option>';
	echo '</select> files.';
	echo '<br />';
	echo 'Allow new values from ';
	echo '<select name="allow_new_from">';
	echo '<option value="all"'.check_me('allow_new_from', 'all').'>All</option>';
	echo '<option value="core"'.check_me('allow_new_from', 'core').'>Core</option>';
	echo '<option value="local"'.check_me('allow_new_from', 'local').'>Local</option>';
	echo '</select> files.';
	echo '<br />';
	echo 'Limit to field <select name="limit_to_field">';
	echo '<option value=""'.check_me('limit_to_field', '').'>None</option>';
	foreach ($fields as $k=>$v)
	{
		echo '<option value="'.$k.'"'.check_me('limit_to_field', $k).'>'.$k.'</option>';
	}
	echo '</select>';
	echo '<br /><input style="margin-top: 10px" type="submit" value="Update Configuration" />';
	echo '</form>';
	echo '</div>';
}
function check_me($field, $value, $default = false)
{
	if (isset($_GET[$field]))
	{
		if ($_GET[$field] === $value) return " SELECTED";	
	}
	if ($default) return " SELECTED";
	return '';
}
?>
