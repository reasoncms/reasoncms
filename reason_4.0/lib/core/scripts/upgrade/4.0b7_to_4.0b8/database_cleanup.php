<?php
/**
 * Reason 4 Beta 8 database cleanup and maintenance
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start script
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Database Cleanup and Maintenance</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
include_once( CARL_UTIL_INC . 'db/sqler.php' );
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');


class databaseCleanup
{
	var $mode;
	var $reason_user_id;
		
	function do_updates($mode, $reason_user_id)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		
		settype($reason_user_id, 'integer');
		if(empty($reason_user_id))
		{
			trigger_error('$reason_user_id must be a nonempty integer');
			return;
		}
		$this->reason_user_id = $reason_user_id;
		
		// The updates
		$this->change_relationship_terminology('page_to_publication', 'Places a blog / publication on a page', 'Place a blog / publication');
		$this->change_relationship_terminology('page_to_related_publication', 'Places a related publication on a page', 'Attach a related publication');
		$this->_check_custom_deleters();
		$this->_update_theme_previewer();
		$this->_fix_unbranded_basic_blue_theme();
		
		$this->provide_entity_info_admin_link();
		
		$this->provide_version_check_admin_link();
		
		$this->provide_review_changes_admin_link();
		
		$this->provide_orphan_manager_admin_link();
	}
	
	function change_relationship_terminology($rel_name, $term_search, $term_replace)
	{
	
		$rel_id = relationship_id_of($rel_name);
		if ($rel_id)
		{
			$res = mysql_fetch_assoc(db_query('SELECT * from allowable_relationship WHERE id='.$rel_id));
			foreach ($res as $k=>$v)
			{
				if ($v == $term_search) $update[$k] = $term_replace;
			}
			if (isset($update))
			{
				if ($this->mode == 'test') echo '<p>Would update the ' . $rel_name . ' allowable relationship terminology</p>';
				if ($this->mode == 'run')
				{
					$sqler = new sqler;
					$sqler->update_one( 'allowable_relationship', $update, $rel_id );
					echo '<p>Updated the ' . $rel_name . ' allowable relationship terminology</p>';
				}
			}
			else
			{
				echo '<p>The ' . $rel_name . ' allowable relationship terminology is up to date</p>';
			}
		}
	}
	
	function _update_theme_previewer()
	{
		$theme_type = new entity(id_of('theme_type'));
		if(!$theme_type->get_value('custom_previewer'))
		{
			if($this->mode == 'run')
			{
				if(reason_update_entity($theme_type->id(), $this->reason_user_id, array('custom_previewer'=>'theme.php')))
				{
					echo '<p>Updated the theme type to use the new theme previewer</p>'."\n";
				}
				else
				{
					echo '<p>Unable to update the theme type to use the new theme previewer.</p>'."\n";
				}
			}
			else
			{
				echo '<p>Would add the theme previewer to the theme type</p>'."\n";
			}
		}
		else
		{
			echo '<p>Theme type is up to date</p>'."\n";
		}
	}
	
	function _fix_unbranded_basic_blue_theme()
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('css'));
		$es->add_relation('url.url = "/global_stock/css/default_styles.css"');
		$es->add_relation('entity.name = "New Default Minisite CSS"');
		$es->set_num(1);
		$result = $es->run_one();
		if (!empty($result))
		{
			$entity = reset($result);
			if ($this->mode == 'test') echo '<p>Would update CSS used by Unbranded Basic Blue theme to correct URL</p>';
			else
			{
				$values = array('css_relative_to_reason_http_base' => "true",
								'url' => "css/default_styles.css");
				reason_update_entity($entity->id(), $this->reason_user_id, $values);
				echo '<p>Updated CSS used by Unbranded Basic Blue theme to correct URL.</p>';
			}
			$did_update = true;
		}
		if (!isset($did_update)) echo '<p>The URL for the CSS used by Unbranded Basic Blue theme is up to date.</p>';
	}
	
	/**
	 * Ancient databases (pre reason 4b4 i think) had minisite_page and entity_table deleters that referenced
	 * minisite_page.php3 and entity_table.php3. Some old database might still point to these files, but they do
	 * not exist any longer. This method updates those type entities if needed.
	 */
	function _check_custom_deleters()
	{
		$minisite_page_type = new entity(id_of('minisite_page'));
		$entity_table_type = new entity(id_of('content_table'));
		if ($minisite_page_type->get_value('custom_deleter') == 'minisite_page.php3')
		{
			if ($this->mode == 'test') echo '<p>Would update minisite_page type to use minisite_page.php custom deleter.</p>';
			else
			{
				reason_update_entity($minisite_page_type->id(), $this->reason_user_id, array('custom_deleter'=>'minisite_page.php'));
				echo '<p>Updated minisite_page type to use minisite_page.php custom deleter.</p>';
			}
			$did_update = true;
		}
		if ($entity_table_type->get_value('custom_deleter') == 'entity_table.php3')
		{
			if ($this->mode == 'test') echo '<p>Would update entity_table type to use entity_table.php custom deleter.</p>';
			else
			{
				reason_update_entity($entity_table_type->id(), $this->reason_user_id, array('custom_deleter'=>'entity_table.php'));
				echo '<p>Updated entity_table type to use entity_table.php custom deleter.</p>';
			}
			$did_update = true;
		}
		if (!isset($did_update)) echo '<p>The custom deleter settings for the minisite_page and entity_table types are properly setup.</p>';
	}
	
	function provide_entity_info_admin_link()
	{
		// lets try to find if it exists
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('admin_link'));
		$es->add_relation('url.url LIKE "%cur_module=EntityInfo%"');
		$result = $es->run_one();
		if (!$result)
		{
			if($this->mode == 'run')
			{
				$values = array('url' => './?cur_module=EntityInfo',
								'relative_to_reason_http_base' => false,
								'new' => 0);
				
				$id = reason_create_entity(id_of('master_admin'), id_of('admin_link'), $this->reason_user_id, 'Entity Info', $values);
				create_relationship(id_of('master_admin'), $id, relationship_id_of('site_to_admin_link'));
				echo '<p>Created and added an administrative link in Master admin to the entity info admin module</p>';
								
			}
			else
			{
				echo '<p>Would add an administrative link in Master admin to the entity info admin module</p>';
			}
		}
		else
		{
			echo '<p>Your instance already has an administrative link to the EntityInfo admin module</p>';
		}
	}
	
	function provide_version_check_admin_link()
	{
		// lets try to find if it exists
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('admin_link'));
		$es->add_relation('url.url LIKE "%cur_module=VersionCheck%"');
		$result = $es->run_one();
		if (!$result)
		{
			if($this->mode == 'run')
			{
				$values = array('url' => './?cur_module=VersionCheck',
								'relative_to_reason_http_base' => false,
								'new' => 0);
				
				$id = reason_create_entity(id_of('master_admin'), id_of('admin_link'), $this->reason_user_id, 'Check For Updates', $values);
				create_relationship(id_of('master_admin'), $id, relationship_id_of('site_to_admin_link'));
				echo '<p>Created and added an administrative link in Master admin to the version check admin module</p>';
								
			}
			else
			{
				echo '<p>Would add an administrative link in Master admin to the version check admin module</p>';
			}
		}
		else
		{
			echo '<p>Your instance already has an administrative link to the version check admin module</p>';
		}
	}
	
	function provide_review_changes_admin_link()
	{
		// lets try to find if it exists
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('admin_link'));
		$es->add_relation('url.url LIKE "%cur_module=ReviewChanges%"');
		$result = $es->run_one();
		if (!$result)
		{
			if($this->mode == 'run')
			{
				$values = array('url' => './?cur_module=ReviewChanges',
								'relative_to_reason_http_base' => false,
								'new' => 0);
				
				$id = reason_create_entity(id_of('master_admin'), id_of('admin_link'), $this->reason_user_id, 'Review Changes', $values);
				create_relationship(id_of('master_admin'), $id, relationship_id_of('site_to_admin_link'));
				echo '<p>Created and added an administrative link in Master admin to the "review changes" admin module</p>';
								
			}
			else
			{
				echo '<p>Would add an administrative link in Master admin to the "review changes" admin module</p>';
			}
		}
		else
		{
			echo '<p>Your instance already has an administrative link to the "review changes" admin module</p>';
		}
	}
	
	function provide_orphan_manager_admin_link()
	{
		// lets try to find if it exists
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('admin_link'));
		$es->add_relation('url.url LIKE "%scripts/db_maintenance/orphan_manager.php%"');
		$result = $es->run_one();
		if (!$result)
		{
			if($this->mode == 'run')
			{
				$values = array('url' => 'scripts/db_maintenance/orphan_manager.php',
								'relative_to_reason_http_base' => true,
								'new' => 0);
				
				$id = reason_create_entity(id_of('master_admin'), id_of('admin_link'), $this->reason_user_id, 'Orphan Manager', $values);
				create_relationship(id_of('master_admin'), $id, relationship_id_of('site_to_admin_link'));
				echo '<p>Created and added an administrative link in Master admin to the orphan manager.</p>';
								
			}
			else
			{
				echo '<p>Would add an administrative link in Master admin to the orphan manager.</p>';
			}
		}
		else
		{
			echo '<p>Your instance already has an administrative link to the orphan manager.</p>';
		}
	}
}

force_secure_if_available();
$user_netID = reason_require_authentication();
$reason_user_id = get_user_id( $user_netID );
if(empty($reason_user_id))
{
	die('valid Reason user required');
}
if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have Reason upgrade rights to run this script');
}

?>
<h2>Reason: Database Cleanup and Maintenance</h2>
<p>This script addresses various small issues with wording, unnecessary fields in types, and more.</p>
<p><strong>What will this update do?</strong></p>
<ul>
<li>Changes "Places blog/publication on a page" to "Place a blog/publication" (addresses <a href="http://code.google.com/p/reason-cms/issues/detail?id=33">issue 33</a>)</li>
<li>Changes "Places a related publication on a page" to "Attach a related publication" (addresses <a href="http://code.google.com/p/reason-cms/issues/detail?id=33">issue 33</a>)</li>
<li>Adds the theme previewer to the theme type</li>
<li>Fixes incorrect CSS URL for unbranded basic blue (login site default) theme</li>
<li>Makes sure the custom deleter values for the entity_table and minisite_page type are correctly set</li>
<li>Adds an administrative link in Master Admin to the entity info admin module</li>
<li>Adds an administrative link in Master Admin to the version check admin module</li>
<li>Adds an administrative link in Master Admin to the "review changes" admin module</li>
<li>Adds an administrative link in Master Admin to the orphan manager</li>
<?php
/* TODO
<li>Removes the finish action for sites.</li>
<li>Removes the finish action for assets.</li>
*/
?>

</ul>

<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new databaseCleanup();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
<p><a href="index.php">Return to Index</a></p>
</body>
</html>
