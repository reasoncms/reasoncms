<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once(DISCO_INC.'disco.php');
	
	/**
	 * The administrative module that produces the UI for managing entity locks
	 */
	class ManageLocksModule extends DefaultModule
	{
		var $_form;
		var $_entity;
		function ManageLocksModule( &$page )
		{
			$this->admin_page =& $page;
		}
		
		function init()
		{
			if(!defined('REASON_ENTITY_LOCKS_ENABLED') || !REASON_ENTITY_LOCKS_ENABLED || !reason_user_has_privs($this->admin_page->user_id, 'manage_locks'))
			{
				$this->admin_page->title = 'Not Able to Edit Locks';
				return;
			}
			
			$this->head_items->add_javascript(JQUERY_URL, true);
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'manage_locks.js');
			
			$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.'locks.css');
		
			$id = $this->admin_page->id;
			
			$this->_entity = $entity = new entity($id);
			
			$type = new entity($entity->get_value('type'));
			
			$this->admin_page->title = 'Editing Locks: "'.$entity->get_value('name').'" ('.$type->get_value('name').')';
			
			$disco = new disco();
			
			$this->_add_field_lock_elements($disco,$entity);
			
			$this->_add_relationship_lock_elements($disco,$entity);
			
			$disco->add_callback(array(&$this,'process_form'),'process');
			
			$disco->add_callback(array(&$this,'where_to'),'where_to');
			
			$this->_form = $disco;
		}
		
		function run() // {{{
		{
			$bypass_roles = array();
			if(!empty($this->_form))
			{
				$privs_table = reason_get_privs_table();
				foreach($privs_table as $role=>$privs)
				{
					if(in_array('bypass_locks',$privs))
					{
						$role_ent = new entity(id_of($role));
						$bypass_roles[] = $role_ent->get_value('name');
					}
				}
				if(count($bypass_roles) > 1)
				{
					end($bypass_roles);
					$i = key($bypass_roles);
					$bypass_roles[$i] = 'or '.$bypass_roles[$i];
				}
				if(count($bypass_roles) > 2)
				{
					$roles_str = implode(', ',$bypass_roles);
				}
				else
				{
					$roles_str = implode(' ',$bypass_roles);
				}
				echo '<p>If you place a lock on a field or a relationship, it prevents site maintainers from changing that aspect of the item.</p><p>Note that users with the '.$roles_str.' role can bypass these locks.</p>'."\n";
				echo '<p class="greyedOutNote">Greyed-out options are lockable, but are either currently hidden from normal users or are not available on this site.</p>'."\n";
				$this->_form->run();
			}
			else
			{
				if(!defined('REASON_ENTITY_LOCKS_ENABLED') || !REASON_ENTITY_LOCKS_ENABLED)
					echo 'Locking is not enabled in this instance of Reason. If you are an administrator you can turn locking on by adding or changing the setting REASON_ENTITY_LOCKS_ENABLED.';
				else
					echo 'You don\'t have the privilege to edit locks on this item.';
			}
		} // }}}
		
		function _add_field_lock_elements(&$disco,&$entity)
		{
			$cm = $this->_load_content_manager();
			
			$locks = $entity->get_locks();
			
			$shown_fields = array();
			$hidden_fields = array();
			$values = array();
			
			foreach($locks->get_lockable_fields() as $field)
			{
				$label = prettify_string($field);
				
				if($cm->is_element($field))
				{
					$el = $cm->get_element($field);
					if(!empty($el->display_name) && $el->display_name != '&nbsp;' && $el->display_name != ' ')
						$label = $el->display_name;
				}
				
				if(!$cm->is_element($field) || $cm->element_is_hidden($field))
				{
					$hidden_fields[$field] = '<span class="hiddenInContentManager">'.$label.'</span>';
				}
				else
				{
					$shown_fields[$field] = $label;
				}
				
				if($entity->field_has_lock($field))
					$values[] = $field;
			}
			
			$fields = $shown_fields + $hidden_fields;
			
			if(!empty($fields))
			{
				$disco->add_element('lock_all_fields','checkbox');
				if($entity->has_all_fields_lock())
					$disco->set_value('lock_all_fields',true);
			}
			
			$disco->add_element('lock_specific_fields','checkboxgroup_no_sort',array('options'=>$fields));
			$disco->set_value('lock_specific_fields',$values);
		}
		
		function _add_relationship_lock_elements(&$disco,&$entity)
		{
			$locks = $entity->get_locks();
			$site_types = $this->_get_site_types();
			
			$active_right_rels = array();
			$inactive_right_rels = array();
			$right_vals = array();
			
			$active_left_rels = array();
			$inactive_left_rels = array();
			$left_vals = array();
			if($entity->has_all_relationships_lock('left'))
				$left_vals[] = '*';
			
			foreach($locks->get_lockable_relationships() as $alrel_id => $alrel)
			{
				$right_extra = '';
				$left_extra = '';
				if(empty($alrel['display_name']))
				{
					$display_name = $alrel['name'];
				}
				else
				{
					$display_name = $alrel['display_name'].' <span class="smallText">('.$alrel['name'].')</span>';
				}
				
				if($alrel['relationship_a'] == $entity->get_value('type') && $alrel['relationship_b'] == $entity->get_value('type') && strpos($alrel['name'],'parent') !== false)
				{
					$is_parent_rel = true;
					$right_extra .= '-- Parent relationship';
					$left_extra .= '-- Child relationship';
				}
				else
				{
					$is_parent_rel = false;
				}
				
				if(!isset($site_types[$alrel['relationship_a']]) || !isset($site_types[$alrel['relationship_b']]))
				{
					$display_name = '<span class="notAvailableInSite">'.$display_name.'</span>';
					$is_active = false;
				}
				else
				{
					$is_active = true;
				}
				
				// right rel
				if( $alrel['relationship_a'] == $entity->get_value('type') )
				{
					if($is_active)
						$active_right_rels[$alrel_id] = $display_name.$right_extra;
					else
						$inactive_right_rels[$alrel_id] = $display_name.$right_extra;
					if($entity->relationship_has_lock($alrel_id, 'right'))
						$right_vals[] = $alrel_id;
				}
				
				// left rel
				if( $alrel['relationship_b'] == $entity->get_value('type') )
				{
					if($is_active)
						$active_left_rels[$alrel_id] = $display_name.$left_extra;
					else
						$inactive_left_rels[$alrel_id] = $display_name.$left_extra;
					if($entity->relationship_has_lock($alrel_id, 'left'))
						$left_vals[] = $alrel_id;
				}
			}
			
			$right_rels = $active_right_rels + $inactive_right_rels;
			$left_rels = $active_left_rels + $inactive_left_rels;
			
			if(!empty($right_rels))
			{
				///$right_rels = array_merge(array('*' => '<strong>All Right Relationships</strong>'), $right_rels );
				$disco->add_element('lock_all_right_relationships','checkbox');
				if($entity->has_all_relationships_lock('right'))
					$disco->set_value('lock_all_right_relationships',true);
					
				$right_options = array();
				$disco->add_element('lock_specific_right_relationships','checkboxgroup_no_sort',array('options'=>$right_rels));
				$disco->set_value('lock_specific_right_relationships',$right_vals);
			}
			if(!empty($left_rels))
			{
				$disco->add_element('lock_all_left_relationships','checkbox');
				if($entity->has_all_relationships_lock('left'))
					$disco->set_value('lock_all_left_relationships',true);
				$disco->add_element('lock_specific_left_relationships','checkboxgroup_no_sort',array('options'=>$left_rels));
				$disco->set_value('lock_specific_left_relationships',$left_vals);
			}
		}
		
		function _get_site_types()
		{
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_right_relationship($this->admin_page->site_id,relationship_id_of('site_to_type'));
			return $es->run_one();
		}
		
		
		function _load_content_manager()  // {{{
		{
			/*
			 * load_content_manager(): finds the appropriate content manager for the entity
			 * 	and does everything up to the error checks
			 * 
			 */
			reason_include_once( 'content_managers/default.php3' );
			$content_handler = $GLOBALS[ '_content_manager_class_names' ][ 'default.php3' ];
			$type = new entity( $this->admin_page->type_id );

			// set up a data member that init can get to after this method is called
			$this->entity_type = $type;
			if ( $type->get_value( 'custom_content_handler' ) )
			{
				$include_file = $type->get_value( 'custom_content_handler' );
				if( !preg_match( '/(php|php3)$/' , $include_file ) )
					$include_file .= '.php3';
				$include_path = 'content_managers/'.$include_file;
				reason_include_once( $include_path );
				$content_handler = $GLOBALS[ '_content_manager_class_names' ][ $include_file ];
			}
			
			/* Find editor who has 'normal' privs so that we can accurately
			communicate which fields are hidden to "normal" users. */
			$editor = $this->_get_user_on_current_site('editor_user_role');
			if(empty($editor))
				$editor = $this->_get_user_on_current_site('power_user_role');
			if(empty($editor))
				$editor = $this->_get_user_on_current_site('contribute_only_role');
			if(empty($editor))
				$editor = new entity($this->admin_page->user_id);
			
			$fake_admin_page = carl_clone($this->admin_page);
			$fake_admin_page->user_id = $editor->id();
			
			$fake_head_items = carl_clone($this->head_items);
			
			$disco_item = new $content_handler;
			$disco_item->admin_page = $fake_admin_page;
			$disco_item->set_head_items( $fake_head_items );
			$disco_item->prep_for_run( $fake_admin_page->site_id, $fake_admin_page->type_id, $fake_admin_page->id, $fake_admin_page->user_id );
			$disco_item->init();

			$disco_item->on_every_time();

			$disco_item->pre_error_check_actions();
			
			return $disco_item;
		} // }}}
		
		function _get_user_on_current_site($user_role)
		{
			$es = new entity_selector(id_of('master_admin'));
			$es->add_type(id_of('user'));
			$es->add_left_relationship(id_of($user_role),relationship_id_of('user_to_user_role'));
			$es->add_right_relationship($this->admin_page->site_id,relationship_id_of('site_to_user'));
			$es->set_num(1);
			$users = $es->run_one();
			
			if(!empty($users))
				return current($users);
			else
				return null;
		}
		
		function process_form(&$disco)
		{
			$locks_obj = $this->_entity->get_locks();
			
			if($disco->is_element('lock_all_fields') && $disco->get_value('lock_all_fields'))
			{
				$locks_obj->add_field_lock('*');
			}
			elseif($disco->is_element('lock_specific_fields'))
			{
				$locks_obj->remove_field_lock('*');
				
				$selected_fields = $disco->get_value('lock_specific_fields');
				if(empty($selected_fields))
					$selected_fields = array();
				
				$field_options = $disco->get_element_property('lock_specific_fields','options');
				if(empty($field_options))
					$field_options = array();
				
				foreach($field_options as $field => $label)
				{
					if(in_array($field,$selected_fields))
						$locks_obj->add_field_lock($field);
					else
						$locks_obj->remove_field_lock($field);
				}
			}
			
			if($disco->get_value('lock_all_right_relationships'))
			{
				$locks_obj->add_relationship_lock('-1','right');
			}
			elseif($disco->is_element('lock_specific_right_relationships'))
			{
				$locks_obj->remove_relationship_lock('-1','right');
				
				$selected_right_rels = $disco->get_value('lock_specific_right_relationships');
				if(empty($selected_right_rels))
					$selected_right_rels = array();
				
				$right_rel_options = $disco->get_element_property('lock_specific_right_relationships','options');
				if(empty($right_rel_options))
					$right_rel_options = array();
					
				foreach($right_rel_options as $rel_id => $label)
				{
					if(in_array($rel_id,$selected_right_rels))
						$locks_obj->add_relationship_lock($rel_id,'right');
					else
						$locks_obj->remove_relationship_lock($rel_id,'right');
				}
			}
			
			if($disco->get_value('lock_all_left_relationships'))
			{
				$locks_obj->add_relationship_lock('-1','left');
			}
			elseif($disco->is_element('lock_specific_left_relationships'))
			{
				$locks_obj->remove_relationship_lock('-1','left');
				
				$selected_left_rels = $disco->get_value('lock_specific_left_relationships');
				if(empty($selected_left_rels))
					$selected_left_rels = array();
				
				$left_rel_options = $disco->get_element_property('lock_specific_left_relationships','options');
				if(empty($left_rel_options))
					$left_rel_options = array();
				
				foreach($left_rel_options as $rel_id => $label)
				{
					if(in_array($rel_id,$selected_left_rels))
						$locks_obj->add_relationship_lock($rel_id,'left');
					else
						$locks_obj->remove_relationship_lock($rel_id,'left');
				}
			}
			
			
		}
		
		function where_to(&$disco)
		{
			return carl_make_redirect( array() );
		}
	} // }}}

?>
