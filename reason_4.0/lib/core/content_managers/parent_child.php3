<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Include the entity selector
	 */
	reason_include_once( 'classes/entity_selector.php' );

	/**
	 * Register the content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'parent_childManager';

	/**
	 * A content manager for hierarchical types
	 */
	class parent_childManager extends ContentManager
	{
		var $pages = array();
		var $parent_alrel_id;
		var $left_assoc_display_names = array(
											'ocs_page_parent' => 'Parent Page' ,
											'page_node_parent' => 'Parent Page' ,
											'minisite_page_parent' => 'Parent Page' );
		var $left_assoc_omit_link = array( 'ocs_page_parent' , 'minisite_page_parent', 'page_node_parent' );
		var $allow_creation_of_root_node = false;
		var $multiple_root_nodes_allowed = false;
		var $root_node_description_text = '-- Top-level item --';
		var $parent_id_defaults_to_null = true;
		var $existing_parent_id;
		var $roots = array();
		var $_children = array();
		var $_available_parents = null;
		var $parent_sort_order;
		
		/**
		 * Retrieves a hierarchical list of all items that can be used as
		 * parents for the item in question.
		 * 
		 * @return an array of "nodes"; each node is a two-element array, the
		 *         first member of which is an item, and the second member is an
		 *         array of nodes for that item's children
		 * 
		 * @author Eric Naeseth <enaeseth@gmail.com>
		 */
		function get_available_parents()
		{
			$list = array();
			$multiple_roots_allowed = $this->multiple_root_nodes_allowed;
			$current = $this->get_value('id');
			$roots = $this->root_node();
			
			if ($this->parent_id_defaults_to_null) {
			    // A "null parent" (i.e., no parent) is possible.
				$list[] = array(null, array());
			}
			
			if ($this->allow_creation_of_root_node) {
				if ($multiple_roots_allowed || empty($roots)) {
					$top = new Entity($current);
					$top->set_value('name', $this->root_node_description_text);
					$list[] = array($top, array());
				}
				
				if ($multiple_roots_allowed || !in_array($current, $roots)) {
				    // Build the tree by starting with the current root nodes
				    // and recursively finding their children.
				    
					$queue = array();
					    // `$queue` holds nodes whose children have not yet been
					    // looked up
					
					foreach ($roots as $root) {
						$item = $this->parent_option_items[$root];
						if ($item->id() == $current)
							continue;
						
						$list[] = array($item, array());
						$queue[] =& $list[count($list) - 1];
					}
					
					while (!empty($queue)) {
						$next =& $queue[0];
						if (!$next[0]) // the "null parent" possibility
							continue;
						$id = $next[0]->id();
						
						foreach ($this->children($id) as $child_id) {
							if ($child_id == $current)
								continue;
							
							$item = $this->parent_option_items[$child_id];
							$next[1][] = array($item, array());
							$queue[] =& $next[1][count($next[1]) - 1];
						}
						
						array_shift($queue);
					}
				}
			}
			
			return $list;
		}
		
		/**
		 * Converts the given array returned by {@link get_available_parents()}
		 * into a flat array suitable for a "select_no_sort" Plasmature element.
		 * 
		 * @author Eric Naeseth <enaeseth@gmail.com>
		 */
		function build_select_list($available_parents)
		{
			$list = array();
			
			$count = count($available_parents);
			for ($i = 0; $i < $count; $i++) {
				$frag = $this->_build_select_fragment($available_parents[$i]);
				foreach ($frag as $id => $name) {
					$list[$id] = $name;
				}
			}
			
			return $list;
		}
		
		/**
		 * @access private
		 * @author Eric Naeseth <enaeseth@gmail.com>
		 */
		function _build_select_fragment(&$entry, $depth=0) {
			$fragment = array();
			$entity =& $entry[0];
			$text = ($entity) ? $entity->get_value('name') : '(none)';
			$id = ($entity) ? $entity->id() : '';
			$prefix = str_repeat('&mdash;', $depth);
			
			$fragment[$id] = $prefix.$text;
			$child_count = count($entry[1]);
			for ($i = 0; $i < $child_count; $i++) {
				$sf = $this->_build_select_fragment($entry[1][$i], $depth + 1);
				foreach ($sf as $id => $name) {
					$fragment[$id] = $name;
				}
			}
			
			return $fragment;
		}
		
		function alter_data() // {{{
		{
			$r_id = $this->get_parent_relationship();
			if( $r_id )
			{
				$entity = new entity($this->_id);
				$user = new entity($this->admin_page->user_id);
				$parent = new entity_selector( $this->get_value( 'site_id' ) );
				$parent->add_type( $this->get_value( 'type_id' ) );
				$parent->add_field( 'entity2' , 'id' , 'parent_id' );
				$parent->add_table( 'relationship2' , 'relationship' );
				$parent->add_table( 'entity2' , 'entity' );

				$parent->add_relation( 'entity2.id =  relationship2.entity_b' );
				$parent->add_relation( 'entity.id = relationship2.entity_a' );
				$parent->add_relation( 'relationship2.type = "'.$r_id.'"' );
				if(!empty($this->parent_sort_order))
				{
					$parent->set_order($this->parent_sort_order);
				}

				$this->parent_option_items = $parent->run_one();

				$roots = $this->root_node();
				
				$p_id = $this->get_existing_parent_id();
				if(empty($p_id))
				{
					$temp_p_id = (integer) empty( $this->admin_page->request[ 'parent_id' ] ) ? false : $this->admin_page->request[ 'parent_id' ];
					if(!empty($this->parent_option_items[$temp_p_id]))
					{
						$p_id = $temp_p_id;
					}
				}
				
				$this->_available_parents = $this->get_available_parents();
				$list = $this->build_select_list($this->_available_parents);
				$list = $this->alter_tree_list($list, $p_id);
				
				$disabled_options = array();
				foreach($list as $k=>$v)
				{
					if(!empty($k))
					{
						$listpage = new entity($k);
						if(!$listpage->user_can_edit_relationship($r_id,$user,'left'))
						{
							$disabled_options[] = $k;
							$list[$k] = $v . ' (locked)';
						}
					}
				}
				
				$this->add_element( 'parent_id' , 'select_no_sort' , array( 'options' => $list, 'disabled_options' => $disabled_options) );
				$this->set_value( 'parent_id', $p_id );
				$this->set_display_name( 'parent_id' , 'Parent Page' );
				$this->add_required('parent_id');
				$this->add_element( 'r_id' , 'hidden' );
				$this->set_value( 'r_id' , $r_id );
				
				if(!empty($p_id))
				{
					$parent_entity = new entity($p_id);
				}
				
				if( in_array($this->_id, $roots) )
				{
					if(!$this->allow_creation_of_root_node)
					{
						$this->change_element_type( 'parent_id' , 'hidden' );
					}
					elseif( !empty($roots) && !$this->multiple_root_nodes_allowed )
					{
						$this->change_element_type( 'parent_id' , 'hidden' );
					}
				}
				elseif(!$entity->user_can_edit_relationship($r_id,$user,'right') || ( isset($parent_entity) && !$parent_entity->user_can_edit_relationship($r_id,$user,'left') ) )
				{
					$this->change_element_type( 'parent_id' , 'hidden' );
					$this->add_element( 'parent_info' , 'solidtext', array() );
					$this->set_value( 'parent_info', $parent_entity->get_value('name'));
					$this->set_display_name( 'parent_info' , 'Parent Page' );
					$this->add_comments( 'parent_info' , '<img 	class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="locked" width="12" height="12" />', 'before' );
					$this->move_element( 'parent_info', 'before', 'parent_id');
				}
			}
			else
			{
				$this->add_element( 'r_id' , 'hidden' );
				$this->set_value( 'r_id' , false );
				trigger_error('Parent-child content manager extended on type that does not apparently have a parent relationship');
			}
			
		} // }}}
		function alter_tree_list( $list, $parent_id )
		{
			return $list;
		}
		function root_node() // {{{
		{
			if(empty($this->roots))
			{
				foreach( $this->parent_option_items AS $value )
				{
					if($value->id() == $value->get_value( 'parent_id' ) )
					{
						$this->roots[] = $value->id();
					}
				}
			}
			return $this->roots;
		} // }}}
		function parent( $id ) // {{{
		{
			$x = $this->values[ $id ];
			if( $x )
				return $x->get_value( 'parent_id' );
			else return false;
		} // }}}
		function children( $id ) // {{{
		{
			if(empty($this->_children))
			{
				$roots = $this->root_node();
				foreach( $this->parent_option_items AS $item )
				{
					$parent_id = $item->get_value('parent_id');
					
					if(!isset($this->_children[$parent_id]))
					{
						$this->_children[$parent_id] = array();
					}
					if(!in_array( $item->id() , $roots ) )
					{
						$this->_children[$parent_id][] = $item->id();
					}
				}
			}
			if(!empty($this->_children[$id]))
			{
				return $this->_children[$id];
			}
			return array();
		} // }}}
		function get_parent_relationship() // {{{
		{
			return get_parent_allowable_relationship_id($this->get_value( 'type_id' ));
		} // }}}
		function get_existing_parent_id()
		{
			if(empty($this->existing_parent_id))
			{
				if($this->get_parent_relationship())
				{
					$d = new DBSelector;
					$d->add_table( 'r' , 'relationship' );

					$d->add_field ( 'r' , 'entity_b' , 'p_id' );

					$d->add_relation( 'r.type = "' . $this->get_parent_relationship().'"' );
					$d->add_relation( 'r.entity_a = "' . $this->get_value( 'id' ).'"' );

					$res = db_query( $d->get_query() , 'Error getting page parent in parentchildManager::alter_data()' );

					if( $row = mysql_fetch_array( $res , MYSQL_ASSOC ) )
						$this->existing_parent_id = $row[ 'p_id' ];
				}
			}
			if(!empty($this->existing_parent_id))
				return $this->existing_parent_id;
			else
				return false;
		}
		function make_parent( ) // {{{
		{
			if($this->has_new_parent())
			{
				$id = $this->get_value( 'id' );
				$q = 'DELETE FROM relationship WHERE entity_a = "' . $id . '" AND type = "' . $this->get_parent_relationship().'"';
				db_query( $q , 'error deleting existing parent relationship' );
				create_relationship( $id , $this->get_value( 'parent_id' ) , $this->get_parent_relationship() );
			}
		} // }}}
		
		function has_new_parent()
		{
			if (!isset($this->_has_new_parent))
			{
				$this->_has_new_parent = ($this->get_parent_relationship() && $this->get_value( 'parent_id' ) && $this->get_existing_parent_id() != $this->get_value( 'parent_id' ));
			}
			return $this->_has_new_parent;
		}
		
		function finish() // {{{
		{
			$val = $this->CMfinish();
			$this->make_parent();
			return $val;
		} // }}}

	}
