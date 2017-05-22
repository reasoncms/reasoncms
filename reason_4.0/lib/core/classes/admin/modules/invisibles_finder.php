	<?php
/**
* Finds Reason entities that do not belong to a site
*
* @package reason
* @subpackage admin
*/

/**
* Include the default module
*/
reason_include_once('classes/admin/modules/default.php');
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'function_libraries/relationship_finder.php' );
		include_once(DISCO_INC.'disco.php');

class InvisiblesFinderModule extends DefaultModule
{
	function OrphanManagerModule( &$page )
	{
		$this->admin_page =& $page;
	}
	function init()
	{	
		
		$this->admin_page->title = 'Find Invisibles';
		if( !reason_user_has_privs( $this->admin_page->user_id, 'db_maintenance' ) )
		{
			return;
		}

		$this->admin_page->show['leftbar'] = false;
	}
	
	function get_form()
	{
		if(!isset($this->disco))
		{
			$d = new Disco();
			
			$d->set_actions(array('run'=>'Run'));
			
			$d->add_element('type', 'select', array('options' => $this->entities_to_options($this->get_all_types())));
			
			$d->add_element('site', 'select', array('options' => $this->entities_to_options($this->get_all_sites())));
			
			$this->disco = $d;
		}
		return $this->disco;
	}
	
	function get_all_types()
	{
		static $types;
		if(!isset($types))
		{
			$es = new entity_selector(id_of('master_admin'));
			$es->add_type(id_of('type'));
			$types = $es->run_one();
		}
		return $types;
	}
	
	function get_all_sites()
	{
		static $sites;
		if(!isset($sites))
		{
			$es = new entity_selector(id_of('master_admin'));
			$es->add_type(id_of('site'));
			$sites = $es->run_one();
		}
		return $sites;
	}
	
	function entities_to_options($entities)
	{
		$options = array();
		foreach($entities as $id => $e)
			$options[$id] = $e->get_value('name');
		return $options;
	}

	function run()
	{
		
		if( !reason_user_has_privs( $this->admin_page->user_id, 'db_maintenance' ) )
		{
			echo 'You do not have the "db_maintenace" privilege necessary to use this tool.';
			return;
		}

		echo '<p>Note: Finding invisible entities across without specifying a type or a site may be slow or cause out-of-memory problems in a large Reason instance.</p>';

		$d = $this->get_form();
		$d->run();
		
		if($d->successfully_submitted())
		{
			$all_types = $this->get_all_types();
			$all_sites = $this->get_all_sites();
			$site = NULL;
			if($d->get_value('site') && isset($all_sites[$d->get_value('site')]))
			{
				$site = $all_sites[$d->get_value('site')];
			}
			if($d->get_value('type') && isset($all_types[$d->get_value('type')]))
			{
				$type = $all_types[$d->get_value('type')];
				$invisibles = $this->get_invisible_entities($type, $site);
				$this->render_invisible_entities($type, $invisibles);
			}
			else
			{
				foreach($all_types as $type)
				{
					$invisibles = $this->get_invisible_entities($type, $site);
					$this->render_invisible_entities($type, $invisibles, $site);
				}
			}
		}
	}
	
	function get_invisible_entities($type, $site = NULL)
	{
		$ret = array();
		
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('site'));
		$es->add_left_relationship($type->id(), relationship_id_of('site_to_type'));
		$es->limit_tables();
		$es->limit_fields();
		$sites_with = $es->run_one();
		$sites_with_ids = array_keys($sites_with);
		
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('site'));
		if($site)
		{
			$es->add_relation('entity.id = "'.reason_sql_string_escape($site->id()).'"');
			$es->set_num(1);
		}
		array_walk($sites_with_ids, 'reason_sql_string_escape');
		$es->add_relation('entity.id NOT IN ("'.implode('","',$sites_with_ids).'")');
		$sites_without = $es->run_one();
		
		if(empty($sites_without))
			return array();
		
		$es = new entity_selector(array_keys($sites_without));
		$es->add_type($type->id());
		$es->set_sharing( 'owns' );
		$es->limit_tables();
		$es->limit_fields();
		$invisibles = $es->run_one();
		
		return $invisibles;
	}
	
	function render_invisible_entities($type, $entities, $site = NULL)
	{
		$num = count($entities);
		if(!$site || $num)
			echo '<h3>'.$type->get_value('name').' ('.count($entities).')</h3>';
		
		if(!empty($entities))
		{
			echo '<ul>';
			foreach($entities as $entity)
			{
				echo '<li><a href="?cur_module=EntityInfo&amp;entity_id_test='.urlencode($entity->id()).'">'.$entity->id().': '.$entity->get_value('name').'</a></li>';
			}
			echo '</ul>';
		}
	}
}
