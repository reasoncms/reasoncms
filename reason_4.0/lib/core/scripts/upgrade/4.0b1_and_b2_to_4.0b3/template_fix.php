<?php
/**
 * This script renames the templates in the database to go from the old model (tableless2, unbranded, etc)
 * to the new one (move the branded out of the default and into local).
 * @author Ben Cochran
 * @date 2006-10-18
 * @package reason
 * @subpackage scripts
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once( 'function_libraries/admin_actions.php' );
reason_include_once( 'function_libraries/user_functions.php' );

echo '<html><head><title>Fix Templates</title></head><body>'."\n";


force_secure_if_available();
$current_user = check_authentication();
$user_id = get_user_id ($current_user);
if (!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to fix templates.</p><p>Only Reason users who have upgrade privileges may do that.</p></body></html>');
}

echo '<h1>Fix Templates</h1>'."\n";
echo '<p>This script will alter the templates and themes in your Reason instance to match the new structure in the code</p>'."\n";

if(empty($_POST['run']))
{
	echo '<form method="post">'."\n";
	echo '<input type="submit" name="run" value="Do it" />'."\n";
	echo '</form>'."\n";
	echo '</body></html>'."\n";
	die();
}

$es = new entity_selector();
$es->add_type(id_of('minisite_template'));
//$es->set_num(4);
$entities = $es->run_one();
$old_templates = array();
foreach ($entities as $entity)
{
	$name = $entity->get_value('name');
	if (($name == 'tableless2') || ($name == 'tableless2_unbranded') || ($name == 'unbranded') || ($name == 'default'))
	{
		$old_templates[] = $entity;
	}
	//echo $name . '<hr />';
	//pray($entity);
}
if (count($old_templates) != 4)
{
	echo 'Template names already changed.<br />';
}
else
{
	foreach ($old_templates as $entity) {
		if ($entity->get_value('name') == 'tableless2') 
			$new_name = 'carleton_default';
		if ($entity->get_value('name') == 'tableless2_unbranded') 
			$new_name = 'default';
		if ($entity->get_value('name') == 'unbranded') 
			$new_name = 'tables';
		if ($entity->get_value('name') == 'default') 
			$new_name = 'carleton_tables';
		echo 'update_entity( '.$entity->get_value('id').', '.$user_id.', array( \'entity\' => array(\'name\' => \''.$new_name.'\')));';
		update_entity( $entity->get_value('id'), $user_id, array( 'entity' => array('name' => $new_name)));
	}
}
$es = new entity_selector();
$es->add_relation('entity.name = "external_css"');
$es->set_num(1);
$tables = $es->run_one(id_of('content_table'));
if(empty($tables))
{
	echo 'no "external_css" content table entity exists<br />';
	$table_id = create_reason_table('external_css', 'css', $current_user);
	if(!empty($table_id))
	{
		echo 'Created external_css table for the css type<br />';
		reason_include_once('classes/field_to_entity_table_class.php');
		reason_include_once('classes/amputee_fixer.php');
		$fields = array('css_relative_to_reason_http_base' => array('db_type' => "enum('true','false')"));
		$updater = new FieldToEntityTable('external_css', $fields);
		$updater->update_entity_table();
		$updater->report();
		$fixer = new AmputeeFixer();
		$fixer->fix_amputees(id_of('css'));
		$fixer->generate_report();
	}
}

$es = new entity_selector();
$es->add_relation('url = "/global_stock/css/tableless_layouts/three_column_1.css"');
$es->set_num(1);
$css_entities = $es->run_one(id_of('css'));
if(!empty($css_entities))
{
	$css_entity = current($css_entities);
	echo 'Updating '.$css_entity->get_value('name').'<br />';
	reason_update_entity($css_entity->id(), $user_id, array('url'=>'css/tableless_layouts/three_column_1.css','css_relative_to_reason_http_base'=>'true'));
}
$es = new entity_selector();
$es->add_relation('url = "/global_stock/css/opensource_reason/reason.css"');
$es->set_num(1);
$css_entities = $es->run_one(id_of('css'));
if(!empty($css_entities))
{
	$css_entity = current($css_entities);
	echo 'Updating '.$css_entity->get_value('name').' CSS file<br />';
	reason_update_entity($css_entity->id(), $user_id, array('url'=>'css/simplicity/blue.css','css_relative_to_reason_http_base'=>'true','name'=>'Simplicity Blue'));
}
$es = new entity_selector();
$es->add_relation('name = "Opensource Reason"');
$es->set_num(1);
$theme_entities = $es->run_one(id_of('theme_type'));
if(!empty($theme_entities))
{
	$theme_entity = current($theme_entities);
	echo 'Updating '.$theme_entity->get_value('name').' Theme<br />';
	reason_update_entity($theme_entity->id(), $user_id, array('name'=>'Simplicity Blue'));
}

$es = new entity_selector();
$es->add_relation('url = "css/simplicity/green.css"');
$es->set_num(1);
$css_entities = $es->run_one(id_of('css'));
if(empty($css_entities))
{
	$css_entity = current($css_entities);
	echo 'Adding simplicity green theme<br />',"\n";
	$css_id = reason_create_entity(id_of('master_admin'), id_of('css'), $user_id, 'Simplicity Green', array('url'=>'css/simplicity/green.css','css_relative_to_reason_http_base'=>'true'));
	$theme_id = reason_create_entity(id_of('master_admin'), id_of('theme_type'), $user_id, 'Simplicity Green', array('unique_name'=>'simplicity_green_theme'));
	create_relationship($theme_id,$css_id,relationship_id_of('theme_to_external_css_url'));
	$es = new entity_selector();
	$es->add_relation('name = "default"');
	$es->set_num(1);
	$def_temps = $es->run_one(id_of('minisite_template'));
	if(!empty($def_temps))
	{
		$def_temp = current($def_temps);
		create_relationship($theme_id,$def_temp->id(),relationship_id_of('theme_to_minisite_template'));
	}
}

// Add the css selector to exisiting generic themes (except for core themes)

$core_templates = array('tables','default');
$core_themes = array('unbranded_theme','tableless_2_unbranded_theme','unbranded_basic_blue_theme','opensource_reason_theme',
		     'reason_promo','reason_promo_subsite','simplicity_green_theme');

$css_es = new entity_selector();
$css_es->add_type(id_of('css'));
$css_es->add_relation('name = "cssSelector"');
$css_es->set_num(1);
$css = $css_es->run_one();
if(!empty($css))
{
	$css = current($css);
	foreach($core_templates as $template)
	{
		$es = new entity_selector();
		$es->add_type(id_of('minisite_template'));
		$es->add_relation('name = "'.$template.'"');
		$es->set_num(1);
		$templates = $es->run_one();
		if(!empty($templates))
		{
			$temp = current($templates);
			$es = new entity_selector();
			$es->add_type(id_of('theme_type'));
			$es->add_relation('entity.unique_name NOT IN ("'.implode('","',$core_themes).'")');
			$es->add_left_relationship($temp->id(),relationship_id_of('theme_to_template'));
			$themes = $es->run_one();
			if(!empty($themes))
			{
				$es->add_left_relationship($css->id(),relationship_id_of('theme_to_external_css_url'));
				$themes_with_css_selector = $es->run_one();
				foreach($themes_with_css_selector as $twcs)
				{
					unset($themes[$twcs->id()]);
				}
				if(!empty($themes))
				{
					foreach($themes as $theme)
					{
						create_relationship($theme->id(),$css->id(),relationship_id_of('theme_to_external_css_url'));
						echo '<p>Added cssSelector to theme: '.$theme->get_value('name').'</p>';
					}
				}
				else
				{
					echo '<p>All themes associated with the '.$template.' template are already using the cssSelector</p>';
				}
			}
			else
			{
				echo '<p>No themes associated with the '.$template.' template</p>';
			}
		}
		else
		{
			echo '<p>strong>Error:</strong> Couldn\'t find the '.$template.' template</p>';
		}
	}
}
else
{
	echo '<p><strong>Error:</strong> Couldn\'t find the cssSelector!</p>';
}

echo 'All Done!';

echo '</body></html>'."\n";
//pray($entities);
?>
