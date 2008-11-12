<?php
	reason_include_once( 'classes/entity_selector.php' );
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'parent_childManager';

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
		var $parent_sort_order;
		function alter_data() // {{{
		{
			$r_id = $this->get_parent_relationship();
			if( $r_id )
			{
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
				
				$list = array();
				
				if($this->parent_id_defaults_to_null)
				{
					$list[''] = '--';
				}
				if($this->allow_creation_of_root_node)
				{
					if(empty($roots) || $this->multiple_root_nodes_allowed)
					{
						$list[$this->get_value( 'id' )] = $this->root_node_description_text;
					}
				}
				if( 
					($this->allow_creation_of_root_node && $this->multiple_root_nodes_allowed) ||
					(!in_array( $this->get_value( 'id' ) , $roots ))
				 )
				 {
					foreach( $roots AS $root )
					{
						$list = $list + $this->recurse_children( $root, $this->get_value( 'id' ) );
					}
				}
				
				$list = $this->alter_tree_list( $list, $p_id );
				$this->add_element( 'parent_id' , 'select_no_sort' , array( 'options' => $list ) );
				$this->set_value( 'parent_id', $p_id );
				$this->set_display_name( 'parent_id' , 'Parent Page' );
				$this->add_required('parent_id');
				$this->add_element( 'r_id' , 'hidden' );
				$this->set_value( 'r_id' , $r_id );
				
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
					if(!isset($this->_children[$item->get_value( 'parent_id' )]))
					{
						$this->_children[$item->get_value( 'parent_id' )] = array();
					}
					if(!in_array( $item->id() , $roots ) )
					{
						$this->_children[$item->get_value( 'parent_id' )][] = $item->id();
					}
				}
			}
			if(!empty($this->_children[$id]))
			{
				return $this->_children[$id];
			}
			return array();
		} // }}}

		
		function recurse_children( $id , $current, $depth='') // {{{
		{
			//if ( $id > 0 ) { // Added 4/17/03 nate
				$item = $this->parent_option_items[ $id ];
				if( $id == $current )
					return array();
				$children_list = array( $id => $depth . $item->get_value( 'name' ) );

				$children = $this->children( $id );
				reset( $children );
				foreach($children as $value)
				{
					$children_list = $children_list + $this->recurse_children( $value, $current, $depth . "--" );
				}
				return $children_list;
			//}
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
