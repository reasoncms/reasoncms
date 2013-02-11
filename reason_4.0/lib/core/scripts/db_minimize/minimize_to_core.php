<?php
/**
 * Final step of the db minization process
 *
 * This script takes an already minimized Reason DB and strips it down to the Reason core
 *
 * This script is likely out of date; it should probably be updated before being run as it whitelists core types, etc and we add core types from time to time.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * This script may take a long time, so extend the time limit to infinity
 */
set_time_limit( 0 );

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('function_libraries/user_functions.php');

$effective = false;

// make sure user is authenticated, is a member of master admin, AND has the admin role.
force_secure_if_available();

$authenticated_user_netid = check_authentication();

auth_site_to_user( id_of('master_admin'), $authenticated_user_netid );

$user_id = get_user_id( $authenticated_user_netid );

if(!reason_user_has_privs( $user_id, 'minimize_db' ) )
{
	die('you must have minimize_db privileges to view this page. NOTE: For security reasons, admin users DO NOT have minimize_db privileges. If you are an admin user, you must add minimize_db privs to the admin role in this Reason instance, or set up a minimize-db-specific role and assume it.');
}

?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Create a core Reason instance</title>
</head>
<style type="text/css">
h2,h3 {
	display:inline;
}
</style>
<body>
<h1>Create a core Reason instance</h1>
<?php

$core_types = array('type','content_table','site','user','admin_link','news','image','view','view_type','field','project','minisite_page','css','minisite_template','text_blurb','av','av_file','user_role','asset','publication_type','category_type','comment_type','event_type','faq_type','form','group_type','issue_type','minutes_type','news_section_type','non_reason_site_type','office_department_type','policy_type','project_type_type','registration_slot_type','site_type_type','site_user_type','theme_type','bug','faculty_staff','job','audience_type','html_editor_type');

$core_user_roles = array('admin_role','contribute_only_role','power_user_role');

$core_themes = array('unbranded_theme','tableless_2_unbranded_theme','unbranded_basic_blue_theme','opensource_reason_theme','simplicity_green_theme');

$core_admin_links = array('alrel_manager_admin_link','delete_duplicate_relationships_admin_link','delete_headless_chickens_admin_link','delete_widowed_relationships_admin_link','fix_amputees_admin_link','import_photos_admin_link','view_page_type_info_admin_link','reason_stats_admin_link','remove_duplicate_entities_admin_link','get_type_listers_admin_link','sample_pages_for_each_module_admin_link','sample_pages_for_each_page_type_admin_link','find_across_sites_admin_link','update_urls_admin_link','move_entities_among_sites_admin_link');

$core_images = array('default_page_locations_image');

$core_view_types = array('tree_view','generic_lister');

if(empty($_POST['do_it']) && empty($_POST['test_it']))
{

?>
<form method="post">
<p>This script takes an already minimized Reason DB and strips it down to the Reason core</p>
<ul>
<li>Core types:
<ul>
<li><?php echo implode('</li><li>',$core_types); ?></li>
</ul>
</li>
<li>Core user roles:
<ul>
<li><?php echo implode('</li><li>',$core_user_roles); ?></li>
</ul>
</li>
<li>Core themes:
<ul>
<li><?php echo implode('</li><li>',$core_themes); ?></li>
</ul>
</li>
<li>Core admin links:
<ul>
<li><?php echo implode('</li><li>',$core_admin_links); ?></li>
</ul>
</li>
<li>Core images:
<ul>
<li><?php echo implode('</li><li>',$core_images); ?></li>
</ul>
</li>
<li>Core view types:
<ul>
<li><?php echo implode('</li><li>',$core_view_types); ?></li>
</ul>
</li>
<li>Tables used by core types</li>
<li>Fields used by above tables</li>
<li>Templates used by core themes</li>
<li>Views used by core types</li>
<li>View types used by above views</li>
<li>Images assigned to above themes</li>
<li>CSS assigned to above themes and templates</li>
</ul>

<p><strong>This script is highly destructive.</strong> You should only run this script on a copy of your main Reason instance.</p>
<p>This is so destructive there is a Reason setting that expressly prohibits it, and which is true by default: PREVENT_MINIMIZATION_OF_REASON_DB.</p>
<?php

if(PREVENT_MINIMIZATION_OF_REASON_DB)
{
	
	echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>true</strong>.  This means that this script will not do anything when run. You can, however, see what this script <strong>would</strong> do by clicking the button below.</p>';
	echo 'Process up to how many types? <input type="text" name="limit" value="500"/>';
	echo '<input type="submit" name="test_it" value="Test the script" />';
}
else
{
	echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>false</strong>.  This means that this instance has been set up in a way that allows this script to be run. Remember to <em>only run this script on a <strong>copy</strong> of your real Reason instance</em>.</p>';
	echo '<input type="submit" name="do_it" value="Run the script" />';
}
?>
</form>
<?php
}
else
{
	$out = array();
	$test_mode = true;
	if(!PREVENT_MINIMIZATION_OF_REASON_DB && !empty($_POST['do_it']))
	{
		$test_mode = false;
	}
	
	if(!empty($_POST['limit']))
	{
		$limit = turn_into_int($_POST['limit']);
	}
	else
	{
		$limit = 1;
	}
	
	echo '<p><a href="?">Return to form</a></p>';
	
	// types
	$out[] = '<h2>Entered type deletion phase</h2>';
	$es = new entity_selector();
	$es->set_num($limit);
	$all_types = $es->run_one(id_of('type'));
	foreach($all_types as $type_id=>$type)
	{
		if(!in_array($type->get_value('unique_name'), $core_types))
		{
			if($test_mode)
			{
				$out[] = 'Would have deleted: '.$type->get_value('name').' (id: '.$type_id.')';
				$effective = true;
			}
			else
			{
				delete_entity($type_id);
				$out[] = 'Deleted: '.$type->get_value('name').' (id: '.$type_id.')';
				$effective = true;
			}
			unset($all_types[$type_id]);
		}
	}
	
	// user roles
	$out[] = '<h2>Entered user role deletion phase</h2>';
	$es = new entity_selector();
	$es->set_num($limit);
	$all_user_roles = $es->run_one(id_of('user_role'));
	foreach($all_user_roles as $ur_id=>$ur)
	{
		if(!in_array($ur->get_value('unique_name'), $core_user_roles))
		{
			if($test_mode)
			{
				$out[] = 'Would have deleted: '.$ur->get_value('name').' (id: '.$type_id.')';
				$effective = true;
			}
			else
			{
				delete_entity($ur_id);
				$out[] = 'Deleted: '.$ur->get_value('name').' (id: '.$ur_id.')';
				$effective = true;
			}
			unset($all_user_roles[$ur_id]);
		}
	}
	
	// themes
	$out[] = '<h2>Entered theme deletion phase</h2>';
	/*$es = new entity_selector();
	$es->add_relation('external_css.css_relative_to_reason_http_base = "true"');
	$protected_css = $es->run_one(id_of('css'));
	$protected_themes = array();
	foreach($protected_css as $css)
	{
		$es = new entity_selector();
		$es->add_left_relationship($css->id(),relationship_id_of('theme_to_external_css_url'));
		$themes = $es->run_one(id_of('theme_type'));
		if(!empty($themes))
		{
			$protected_themes = array_merge($protected_themes,$themes);
		}
	}
	pray($protected_css);
	pray($protected_themes);*/
	
	$es = new entity_selector();
	$es->set_num($limit);
	$all_themes = $es->run_one(id_of('theme_type'));
	foreach($all_themes as $theme_id=>$theme)
	{
		if(!in_array($theme->get_value('unique_name'), $core_themes) /*&& !array_key_exists($theme_id,$protected_themes)*/)
		{
			if($test_mode)
			{
				$out[] = 'Would have deleted: '.$theme->get_value('name').' (id: '.$type_id.')';
				$effective = true;
			}
			else
			{
				delete_entity($theme_id);
				$out[] = 'Deleted: '.$theme->get_value('name').' (id: '.$theme_id.')';
				$effective = true;
			}
			unset($all_themes[$theme_id]);
		}
		else
		{
			$out[] = $theme->get_value('name').' (id: '.$type_id.') is protected.';
		}
	}
	
	// admin links
	$out[] = '<h2>Entered admin link deletion phase</h2>';
	$es = new entity_selector();
	$es->set_num($limit);
	$all_als = $es->run_one(id_of('admin_link'));
	foreach($all_als as $al_id=>$al)
	{
		if(!in_array($al->get_value('unique_name'), $core_admin_links))
		{
			if($test_mode)
			{
				$out[] = 'Would have deleted: '.$al->get_value('name').' (id: '.$type_id.')';
				$effective = true;

			}
			else
			{
				delete_entity($al_id);
				$out[] = 'Deleted: '.$al->get_value('name').' (id: '.$al_id.')';
				$effective = true;
			}
			unset($all_als[$al_id]);
		}
	}
	
	// delete tables not used by core types & their fields 
	$out[] = '<h2>Entered table/field deletion phase</h2>';
	$es = new entity_selector();
	$tables = $es->run_one(id_of('content_table'));
	foreach($tables as $table_id=>$table)
	{
		$es = new entity_selector();
		$es->add_left_relationship($table_id,relationship_id_of('type_to_table'));
		$es->add_relation('entity.unique_name IN ("'.implode('","',$core_types).'")');
		$es->set_num(1);
		$types = $es->run_one(id_of('type'));
		if(empty($types))
		{
			$es = new entity_selector();
			$es->add_left_relationship($table_id,relationship_id_of('field_to_entity_table'));
			$fields = $es->run_one(id_of('field'));
			
			foreach($fields as $field_id=>$field)
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted field: '.$table->get_value('name').'.'.$field->get_value('name').' (id: '.$field_id.')';
					$effective = true;
				}
				else
				{
					delete_entity($field_id);
					$out[] = 'Deleted field: '.$table->get_value('name').'.'.$field->get_value('name').' (id: '.$field_id.')';
					$effective = true;
				}
			}
			
			if($test_mode)
			{
				$out[] = 'Would have deleted table: '.$table->get_value('name').' (id: '.$table_id.')';
				$effective = true;
			}
			else
			{
				delete_entity($table_id);
				$out[] = 'Deleted table: '.$table->get_value('name').' (id: '.$table_id.')';
				$effective = true;
			}
			
		}
		
	}
	
	// delete templates not used by core themes
	$out[] = '<h2>Entered template deletion phase</h2>';
	$es = new entity_selector();
	$templates = $es->run_one(id_of('minisite_template'));
	foreach($templates as $template_id=>$template)
	{
		$es = new entity_selector();
		$es->add_left_relationship($template_id,relationship_id_of('theme_to_minisite_template'));
		$es->add_relation('entity.unique_name IN ("'.implode('","',$core_themes).'")');
		$es->set_num(1);
		$themes = $es->run_one(id_of('theme_type'));
		if(empty($themes))
		{
			if($test_mode)
			{
				$out[] = 'Would have deleted template: '.$template->get_value('name').' (id: '.$template_id.')';
				$effective = true;
			}
			else
			{
				delete_entity($template_id);
				$out[] = 'Deleted template: '.$template->get_value('name').' (id: '.$template_id.')';
				$effective = true;
			}
			unset($templates[$template_id]);
		}
	}
	
	// delete images not in core set & not used by a core theme
	
	$out[] = '<h2>Entered image deletion phase</h2>';
	$es = new entity_selector();
	$images = $es->run_one(id_of('image'));
	foreach($images as $image_id=>$image)
	{
		if(!in_array($image->get_value('unique_name'),$core_images))
		{
			$es = new entity_selector();
			$es->add_left_relationship($image_id,relationship_id_of('theme_to_primary_image'));	
			$es->add_relation('entity.unique_name IN ("'.implode('","',$core_themes).'")');
			$es->set_num(1);
			$themes = $es->run_one(id_of('theme_type'));
			if(empty($themes))
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted image: '.$image->get_value('name').' (id: '.$image_id.')';
					$effective = true;
				}
				else
				{
					delete_entity($image_id);
					$out[] = 'Deleted image: '.$image->get_value('name').' (id: '.$image_id.')';
					$effective = true;
				}
			}
		}
	}
	
	// delete views not used by core types
	
	$out[] = '<h2>Entered view deletion phase</h2>';
	$es = new entity_selector();
	$views = $es->run_one(id_of('view'));
	foreach($views as $view_id=>$view)
	{
		//$out[] = 'Testing '.$view->get_value('name');
		$es = new entity_selector();
		$es->add_right_relationship($view_id, relationship_id_of('view_to_site'));
		//$es->add_relation('entity.unique_name IN ("'.implode('","',$core_sites).'")');
		$es->set_num(1);
		$sites = $es->run_one(id_of('site'));
		if(empty($sites))
		{
			//$out[] = 'Failed test 1';
			$es = new entity_selector();
			$es->add_left_relationship($view_id, relationship_id_of('type_to_default_view'));
			$es->add_relation('entity.unique_name IN ("'.implode('","',$core_types).'")');
			$es->set_num(1);
			$types = $es->run_one(id_of('type'));
			if(empty($types))
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted: '.$view->get_value('name').' (id: '.$view_id.')';
					$effective = true;
				}
				else
				{
					delete_entity($view_id);
					$out[] = 'Deleted: '.$view->get_value('name').' (id: '.$view_id.')';
					$effective = true;
				}
				unset($views[$view_id]);
			}
			
		}
	}
	
	// delete view types not used by remaining views
	
	$out[] = '<h2>Entered view type deletion phase</h2>';
	$es = new entity_selector();
	$vts = $es->run_one(id_of('view_type'));
	foreach($vts as $vt_id=>$vt)
	{
		//$out[] = 'Checking '.$vt->get_value('name');
		if(!in_array($vt->get_value('unique_name'),$core_view_types))
		{
			$es = new entity_selector();
			$es->add_relation('entity.id IN ("'.implode('","',array_keys($views)).'")');
			$es->add_left_relationship($vt_id, relationship_id_of('view_to_view_type'));
			$es->set_num(1);
			$vs = $es->run_one(id_of('view'));
			if(empty($vs))
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted view type: '.$vt->get_value('name').' (id: '.$vt_id.')';
					$effective = true;
				}
				else
				{
					delete_entity($vt_id);
					$out[] = 'Deleted view type: '.$vt->get_value('name').' (id: '.$vt_id.')';
					$effective = true;
				}
			}
		}
	}
	
	// delete CSS
	
	$out[] = '<h2>Entered CSS deletion phase</h2>';
	$es = new entity_selector();
	$css_array = $es->run_one(id_of('css'));
	foreach($css_array as $css_id=>$css)
	{
		//$out[] = 'Testing '.$view->get_value('name');
		$es = new entity_selector();
		$es->add_left_relationship($css_id, relationship_id_of('theme_to_external_css_url'));
		$es->add_relation('entity.unique_name IN ("'.implode('","',$core_themes).'")');
		$es->set_num(1);
		$themes = $es->run_one(id_of('theme_type'));
		if(empty($themes))
		{
			//$out[] = 'Failed test 1';
			$es = new entity_selector();
			$es->add_left_relationship($view_id, relationship_id_of('minisite_template_to_external_css'));
			$es->add_relation('entity.unique_name IN ("'.implode('","',array_keys($templates)).'")');
			$es->set_num(1);
			$tmplts = $es->run_one(id_of('minisite_template'));
			if(empty($tmplts))
			{
				if($test_mode)
				{
					$out[] = 'Would have deleted: '.$css->get_value('name').' (id: '.$css_id.')';
					$effective = true;
				}
				else
				{
					delete_entity($css_id);
					$out[] = 'Deleted: '.$css->get_value('name').' (id: '.$css_id.')';
					$effective = true;
				}
				unset($css_array[$css_id]);
			}
			
		}
	}
	
	if ($effective == true)
	{
		pray ($out);
		echo '<p>The script was successful, but more processing is needed</p>';
		if(PREVENT_MINIMIZATION_OF_REASON_DB)
		{	
			echo '<form method="post">';
			echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>true</strong>.  This means that this script will not do anything when run. You can, however, see what this script <strong>would</strong> do by clicking the button below.</p>';
			echo '<input type="submit" name="test_it" value="Test the script" />';
		}
		else
		{
			echo '<p>PREVENT_MINIMIZATION_OF_REASON_DB is currently set to <strong>false</strong>.  This means that this instance has been set up in a way that allows this script to be run. Remember to <em>only run this script on a <strong>copy</strong> of your real Reason instance</em>.</p>';
			echo 'Process up to how many types? <input type="text" name="limit" value="500"/>';
			echo '<input type="submit" name="test_it" value="Test the script" />';
			echo '<input type="submit" name="do_it" value="Run the script" />';
			echo '</form>';
		}
	}
	else
	{
		$type_str = '';
		$es = new entity_selector();
		$all_types = $es->run_one(id_of('type'));
		foreach($all_types as $type_id=>$type)
		{
			$type_str .= $type_id . ',';
		}
		if (!empty($type_str))
		{
			$type_str = substr($type_str, 0, -1); // strip trailing ,
		}
		else
		{
			echo '<p>The reason instance does not appear to have any types - the script cannot continue</p>';
			die;
		}
		if(PREVENT_MINIMIZATION_OF_REASON_DB)
		{
			echo '<h2>Testing deletion all entities which do not correspond to an existant type</h2>';
			$q = 'SELECT id, type from entity WHERE type NOT IN (' . $type_str . ')';
			$result = db_query($q, 'could not select entities');
			while ($item = mysql_fetch_assoc($result))
			{
				echo 'would delete entity ' . $item['id'] . '<br />';
			}
		}
		else
		{
			echo '<h2>Deleting all entities which do not correspond to an existant type</h2>';
			$q = 'SELECT id, type from entity WHERE type NOT IN (' . $type_str . ')';
			$result = db_query($q, 'could not select entities');
			while ($item = mysql_fetch_assoc($result))
			{
				delete_entity($item['id']);
				echo 'deleted entity ' . $item['id'] . '<br />';
			}
		}
		echo '<p>The script is complete</p>';
		echo '<p><a href="?">Return to form</a></p>';	
		echo '<h3>Next steps</h3>';
		echo '<p><a href="minimize_3.php">Run steps 3 and 4 of the minimization process again</a></p>';

	}
}
?>
</body>
</html>
