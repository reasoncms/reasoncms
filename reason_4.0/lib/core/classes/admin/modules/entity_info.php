<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * Provide basic information on any entity ID
	 *
	 * This module limits its access to users who have the privilege to view sensitive data,
	 * as it can show some basic info about any entity.
	 */
	class EntityInfoModule extends DefaultModule
	{
		
		function EntityInfoModule( &$page )
		{
			$this->admin_page =& $page;
		}
		
		function init()
		{
			$this->admin_page->title = 'Get Basic Entity Information';
			
			$this->head_items->add_javascript(JQUERY_URL, true);
			$this->head_items->add_javascript(REASON_HTTP_BASE_PATH . 'js/entity_info.js');
			
		}
		
		function run()
		{
			if(!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data'))
			{
				echo '<p>Sorry; use of this module is restricted.</p>'."\n";
				return;
			}
			if(!empty($this->admin_page->request['entity_id_test']))
			{
				$id = $this->admin_page->request['entity_id_test'];
				settype($id, 'integer');
			}
			if(empty($id))
			{
				$id = '';
			}
			echo '<form method="get" action="?"><label for="entity_id_test">Entity ID:</label> <input type="text" name="entity_id_test" id="entity_id_test" value="'.$id.'"/><input type="submit" value="submit" /><input type="hidden" name="cur_module" value="EntityInfo" /></form>';
			if(!empty($id))
			{
				$entity = new entity($id);
				if($entity->get_values())
				{
					echo '<div class="EntityInfo">'."\n";
					$this->show_entity_header($entity);
					
					$this->show_entity_data($entity);
					
					$this->show_pages($entity);
					
					$this->show_borrowing_sites($entity);
					
					$this->show_entity_relationships($entity);
					echo '</div>'."\n";
				}
				else
				{
					echo '<p>The Reason ID '.$id.' does not belong to a real entity. It may have been deleted.</p>';
				}
			}
		}
		
		function show_entity_header($entity)
		{
			echo '<h3>'.$entity->get_display_name().' '.$this->get_edit_preview_links($entity).'</h3>'."\n";

		}
		
		function show_entity_data($entity)
		{
			//echo '<h4>Entity data:</h4>';
			$entity_type = new entity($entity->get_value('type'));
			echo '<p><strong>Type</strong>: '.$entity_type->get_value('name').'</p>';
			
			$owner = $entity->get_owner();
			if(is_object($owner))
			{
				echo '<p><strong>Owner site</strong>: '.$owner->get_value('name').' (ID: '.$this->get_id_markup($owner->id()).')</p>'."\n";
			}
			else
			{
				echo '<p>No owner site (orphan entity)</p>'."\n";
			}			
			
			echo '<table class="entityInfoDataTable">'."\n";
			echo '<tr>'."\n";
			echo '<th>Attribute</th>'."\n";
			echo '<th>Value</th>'."\n";
			echo '</tr>'."\n";
			
			$id_markup_keys = array('type', 'last_edited_by', 'created_by');
			
			foreach ($entity->get_values() as $key => $value)
			{
				echo '<tr>'."\n";
				echo '<td>'.$key.'</td>'."\n";
				if (in_array($key, $id_markup_keys))
				{
					echo '<td>'.$this->get_id_markup($value).'</td>'."\n";				
				}
				else
				{
					echo '<td>'.$value.'</td>'."\n";
				}
				
				echo '</tr>'."\n";
			}
			echo '</table>'."\n";
		}
		
		
		function show_pages($entity)
		{
			// only do anything if $entity is a site type
			if ($entity->get_value('type') == 3)
			{
				$pages = $this->get_site_pages($entity);
				if (!empty($pages))
				{
					echo '<h3>Pages owned by this Site</h3>'."\n";
					
					echo '<table class="entityInfoTable">'."\n";
					echo '<tr>'."\n";
					echo '<th>Page Name</th>'."\n";
					echo '<th>ID #</th>'."\n";
					echo '</tr>'."\n";
					foreach ($pages as $page)
					{
						echo '<tr>'."\n";
						echo '<td>'.$page->get_value('name').'</td>'."\n";
						echo '<td>'.$this->get_id_markup($page->id()).'</td>'."\n";
						echo '</tr>'."\n";
					}
					echo '</table>'."\n";
				}
			}
		}
		
		
		function show_entity_relationships($entity)
		{
			echo '<h3>Entity Relationships</h3>'."\n";
			$right_has_content = $this->show_entity_relationships_info($entity, true);
			$left_has_content = $this->show_entity_relationships_info($entity, false);
			if (!$right_has_content && !$left_has_content)
			{
				echo '<p>(none)</p>'."\n";
			}
		}
		
		function show_entity_relationships_info($entity, $show_right)
		{			
			$rel_info = $show_right ? $this->get_entity_relationships_info($entity, true) : $this->get_entity_relationships_info($entity, false);
			
			if (!empty($rel_info))
			{
				$rel_types = $this->separate_relationships($rel_info);
				$descriptions = $this->separate_descriptions($rel_info);
				
				foreach ($rel_types as $type => $rels)
				{
					echo '<h4>'.relationship_name_of($type).' - ('.$descriptions[$type].')</h4>'."\n";
					echo '<table class="entityInfoTable">'."\n";	
					echo '<tr>'."\n";

					$e = $show_right ? new entity($rels[0]['relationship_a']) : new entity($rels[0]['relationship_b']);
					echo '<th>'.$e->get_value('name').' Entity Name</th>'."\n";
					echo '<th>ID #</th>'."\n";
					echo '</tr>'."\n";

					foreach ($rels as $rel)
					{
						$other = $show_right ? new entity($rel['entity_a']) : new entity($rel['entity_b']);
						echo '<tr>'."\n";
						echo '<td>'.$other->get_value('name').'</td>'."\n";
						echo '<td>'.$this->get_id_markup($other->id()).'</td>'."\n";
						echo '</tr>'."\n";
					}
					echo '</table>'."\n";	
					echo '<br/>';
				}
			}
			else
			{
				return false;
			}
			return true;
		}
		
		function show_borrowing_sites($entity)
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_left_relationship($entity->id(), get_borrows_relationship_id($entity->get_value('type')));
			$results = $es->run_one();
			
			echo '<h3>Sites that borrow Entity</h3>'."\n";
			if (count($results) > 0)
			{
				echo '<table class="entityInfoTable">'."\n";
				echo '<tr>'."\n";
				echo '<th>Site Name</th>'."\n";
				echo '<th>ID #</th>'."\n";
				echo '</tr>'."\n";
				
				foreach ($results as $site)
				{
					echo '<tr>'."\n";
					echo '<td>'.$site->get_value('name').'</td>'."\n";
					echo '<td>'.$this->get_id_markup($site->id()).'</td>'."\n";
					echo '</tr>'."\n";
				}
				echo '</table>'."\n";
			}
			else
			{
				echo '<p>(none)</p>'."\n";
			}
		}
		
		function get_id_markup($test_id)
		{
			$link = carl_make_link(array('entity_id_test' => $test_id));
			$markup = '<a href="'. $link . '">'. $test_id . '</a>';
			return $markup;
		}
		
		
		function get_entity_relationships_info($entity, $right)
		{
			$dbq = new DBSelector;
			$dbq->add_table('r', 'relationship');
			$dbq->add_field('r', 'entity_a');
			$dbq->add_field('r', 'entity_b');
			$dbq->add_field('r', 'type');
			
			$dbq->add_table('ar', 'allowable_relationship');
			$dbq->add_field('ar', 'id');
			$dbq->add_field('ar', 'type');
			$dbq->add_field('ar', 'relationship_a');
			$dbq->add_field('ar', 'relationship_b');
			$dbq->add_field('ar', 'description');
			
			if ($right)
			{
				$dbq->add_relation('r.entity_b = '.$entity->id());
			}
			else
			{
				$dbq->add_relation('r.entity_a = '.$entity->id());
			}
			
			$dbq->add_relation('ar.id = r.type');
			if (reason_relationship_names_are_unique())
			{
				$dbq->add_relation('ar.type = "association"');
			}
			else
			{
				$dbq->add_relation('ar.name != "owns"');
				$dbq->add_relation('ar.name != "borrows"');
			}
			$rels = $dbq->run();
			return $rels;
		}
		
		
		function get_site_pages($entity)
		{
			$e = new entity_selector();
			$e->add_type(id_of('minisite_page'));
			$e->add_right_relationship($entity->id(), relationship_id_of('site_owns_minisite_page'));
			
			$results = $e->run_one();
			return $results;
		}
		
		function separate_relationships($rels)
		{
			$rel_types = array();
			foreach ($rels as $rel)
			{
				$rel_types[$rel['id']][] = $rel;
			}
			return $rel_types;
		}
		
		function separate_descriptions($rels)
		{
			$descriptions = array();
			foreach ($rels as $rel)
			{
				$descriptions[$rel['id']] = $rel['description'];
			}
			return $descriptions;	
		}
		
		function get_edit_preview_links($entity)
		{
			$owner = $entity->get_owner();
			if(empty($owner))
				return '<span class="edit_preview_links">Unable to edit or preview -- no owner site</span>'."\n";
			
			$markup = '';
			$markup .= '<span class="edit_preview_links">'."\n";
			
			$values = array('site_id' => $entity->get_owner()->id(), 'type_id' => $entity->get_value('type'), 'id' => $entity->id(), 'cur_module' => 'Editor');
			$url = carl_make_link($values);		
			$markup .= '(<a href="'.$url.'">Edit</a> | '."\n";
			
			$values['cur_module'] = 'Preview';
			$url = carl_make_link($values);		
			$markup .= '<a href="'.$url.'">Preview</a>)'."\n";
			$markup .= '</span>'."\n";
			
			return $markup;
		}
		
	}
?>