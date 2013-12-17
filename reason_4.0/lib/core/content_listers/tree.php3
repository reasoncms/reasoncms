<?php
/**
 * @package reason
 * @subpackage content_listers
 */
	/**
	 * Include parent class and register viewer with Reason.
	 */
	include_once( 'reason_header.php' );
	reason_include_once( 'content_listers/default.php3' );

	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'tree_viewer';

	/**
	 * A lister/viewer that generally handles hierarchically organized types
	 */
	class tree_viewer extends generic_viewer
	{
		var $id = 0;
		var $open = '';
		var $filter_es;
		var $filter_values= array();

		var $dir = 'ASC';
		var $order_by = 'sortable.sort_order';
		var $filter_es_name = 'filter_es';
		var $root_id = false;
		var $children = array();
		
		function root_node() // {{{
		{
			if (empty($this->root_id))
			{
				foreach( $this->values as $value )
				{
					if( !empty($value) && $value->id() == $value->get_value( 'parent_id' ) )
					{
						$this->root_id = $value->id();
						return $this->root_id;
					}
				}
			}
			return $this->root_id;
		} // }}}
		function parent( $id ) // {{{
		{
			if( !empty( $this->values[ $id ] ) && $this->values[ $id ]->get_value( 'parent_id' ) )
			{
				return $this->values[ $id ]->get_value( 'parent_id' );
			}
			else
				return false;
		} // }}}
		function children( $id ) // {{{
		{
			$ret = array();
			if(empty($this->children))
			{
				$root = $this->root_node();
				foreach( $this->values as $item )
				{
					if(!empty($item) && $item->id() != $root )
					{
						if(!isset($this->children[$item->get_value('parent_id')]))
						{
							$this->children[$item->get_value('parent_id')] = array();
						}
						$this->children[$item->get_value('parent_id')][] = $item->id();
					}
				}
			}
			if(isset($this->children[$id]))
			{
				$ret = $this->children[$id];
			}
			return $ret;
		} // }}}

		function alter_values() // {{{
		{
			$parent = new entity_selector();
			$parent->add_field( 'entity2' , 'id' , 'parent_id' );
			
			$parent->add_table( 'allowable_relationship2' , 'allowable_relationship' );
			$parent->add_table( 'relationship2' , 'relationship' );
			$parent->add_table( 'entity2' , 'entity' );

			$parent->add_relation( 'entity2.id =  relationship2.entity_b' );
			$parent->add_relation( 'entity.id = relationship2.entity_a' );
			$parent->add_relation( 'relationship2.type = allowable_relationship2.id' );
			$parent->add_relation( 'allowable_relationship2.name LIKE "%parent%"' );
			$parent->set_order( 'sortable.sort_order' );

			$this->es->swallow( $parent );
			$this->remove_column( 'id' );
		} // }}}	
		function is_open( $id )  // {{{
		{
			if( $id == $this->root_node() )
				return true;
			elseif( preg_match( '/,' . $id . ',/' , $this->open ) )
				return true;
			else
				return false;
		} // }}}
		function make_tree( &$item , &$root , $depth, $counter = 0 ) // {{{
		{
			if( $this->has_filters() AND !empty( $this->filter_values[ $item ] ) )
			{
				$this->options = array( 'color' => true , 'depth' => $depth );
				$this->show_item( $this->values[ $item  ] );
			}
			else
			{
				$this->options = array( 'depth' => $depth );
				$this->show_item( $this->values[ $item  ] );
			}
			if( $this->is_open( $item ) )
			{
				$children = $this->children( $item );
				foreach( $children as $child )
					 $this->make_tree( $child , $root, $depth +1);
			}
		} // }}}

		function show_all_items() // {{{
		{
				?>
				<table cellpadding="8" border="0" cellspacing="0" bgcolor="#ffffff">
				<?php
				$root = $this->root_node();
				$this->show_sorting();
				$this->make_tree( $root , $root , 0);
					
				?>
				</table>
				<?php
	 	} // }}} 
		function show_paging() // {{{
		{
		} // }}}
		function show_sorting() // {{{
		{
				echo '<tr>';
				echo '<th class="listHead"><strong>Id</strong></th>';
				echo '<th class="listHead">&nbsp;</th>';
				foreach( $this->columns as $key => $val )
				{
					if ( is_int( $key ) )
						$col = $val;
					else
						$col = $key;

					echo '<th class="listHead">';
					echo prettify_string($col);
					echo '</th>';
				}
			if($this->_should_show_admin_functions_column())
				echo '<th class="listHead">Admin Functions</th>';
		} // }}}

		function show_item_pre( $row , &$options) // {{{
		{
			if(empty($row))
				return;
			$classes = array();
			
			if( !empty($options[ 'class' ]) ) 
			{
				$classes[] = $options[ 'class' ];
			}
			if( !empty($options[ 'color' ]) ) 
				$classes[] = 'highlightRow';
			else 
				$classes[] = 'listRow2';
			if($row->id() != $this->root_node())
			{
				$classes[] = 'childOf'.$this->parent( $row->id() );
			}
			foreach($this->children( $row->id() ) as $child_id)
			{
				$classes[] = 'parentOf'.$child_id;
			}
			echo '<tr class="'.implode(' ',$classes).'" id="'.$row->id().'row"><td class="viewerCol_id">' . $row->id() . '</td>';
			
			$open_link = $this->open;
			echo '<td class="viewerCol_item_pre">';

			echo '&nbsp;';
			if( $row->id() == $this->root_node() )
				echo '<img src="'.REASON_HTTP_BASE_PATH.'ui_images/item_root.gif" width="13" height="13" border="0" alt="root node" />';
			elseif( $this->children( $row->id() ) )
			{
				$remove_filter = $this->set_no_filters();
				/* if( $this->is_open( $row->id() ) )
				{
					$open_link = preg_replace( '/,'.$row->id().',/', '' , $open_link );
					if( substr( $open_link , ( strlen( $open_link ) - 1 ) , 1 ) == ',' )
						$open_link .= '0';
					echo '<a href="'.$this->admin_page->make_link( array_merge( array( 'open' => $open_link ),$remove_filter ) ).'">'.'<img src="'.REASON_HTTP_BASE_PATH.'ui_images/item_open.gif" width="13" height="13" border="0" alt="open item. click to close." />'.'</a>';
				}
				else
				{
					$open_link .= ',' . $row->id() . ',';
					if( substr( $open_link,(strlen( $open_link )-1),1 ) == ',' )
						$open_link .= '0';
					echo '<a href="'.$this->admin_page->make_link( array_merge( array( 'open' => $open_link ),$remove_filter ) ).'">'.'<img src="'.REASON_HTTP_BASE_PATH.'ui_images/item_closed.gif" width="13" height="13" border="0" alt="closed item. click to open." />'.'</a>';
				} */
				echo '<a class="openToggler" title="Show/Hide Children">'.'<img src="'.REASON_HTTP_BASE_PATH.'ui_images/item_closed.gif" width="13" height="13" border="0" alt="Click to toggle visibility of child items." />'.'</a>';
			}
			echo '</td>';
		} // }}}
		function show_item_main( $row , $options) // {{{
		{
			if(empty($row))
				return;
			foreach( $this->columns as $name => $val )
			{
				$display = '';
				$col = $name;
				if ( (is_string( $val ) ) OR (is_array( $val ) ) )
					$handler = $val;
				else
					$handler = '';
			
				if( $handler )
				{
					if( is_array( $handler ) )
					{
						$first_field = true;
						foreach( $handler as $show )
						{
							if( $row->get_value( $show ) )
							{
								$val_to_show = $row->get_value( $show );
								if ( $first_field )
								{
									$first_field = false;
									$display .= '<strong>'.$val_to_show.'</strong>';
								}
								else
									$display .= $val_to_show;
								$display .= '<br />';
							}
						}
					}
					else
					{
						$value = ($row->has_value( $col )) ? $row->get_value( $col ) : $row;
						
						if (method_exists($this, $handler))
							$display = $this->$handler( $value );
						else if (function_exists($handler))
							$display = $handler( $value );
					}
				}
				else
				{
					$wrapper_count = 0;
					if( $name == 'name' )
					{
						$display = '';
						for( $i = 0; $i < $options[ 'depth' ]; $i++ )
							$wrapper_count++;
						$display .= '<div class="treeItemWrapDepth'.$wrapper_count.'">';
						$display .=  '<strong>' . $row->get_value( 'name' ) . '</strong>';
						if ( $actions = $this->get_additional_actions($row) )
						{
							$display .= '<div class="addlActions">';
							$display .= join('&sdot;', $actions);
							$display .= '</div>';
						}
						$display .= '</div>';
					}
					else
						$display = $row->get_value( $name );	
				}
				echo '<td class="viewerCol_'.$col.'"';
				echo '>'.$display.'</td>';
			}
		} // }}} 
		
		function get_additional_actions($row)
		{
			$actions = array();
			
			if( reason_user_has_privs($this->admin_page->user_id,'edit') && count( $this->children( $row->id() ) ) > 1 )
			{
				$actions[] = '<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'Sorting','parent_id' => $row->id() ) ).'" class="smallText sortChildren">Sort children</a>';
				if ($row->id() == $this->root_node())
					$actions[] =  '<a href="#" class="smallText ExpandAll">Expand/close all</a>';
			}				

			return $actions;
		}
		
		function show_admin_live( $row , $options) // {{{
		{
			echo '<td align="left" class="viewerCol_admin '.$options[ 'class' ].'"><strong>';
			$edit_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => $row->id() ) );
			$preview_link = $this->admin_page->make_link(  array( 'cur_module' => 'Preview' , 'id' => $row->id() ) );
			$add_child_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'parent_id' => $row->id() , 'id' => '', 'new_entity' => 1 ) );

			echo '<a href="' . $add_child_link . '">Add Child</a> | <a href="' . $edit_link . '">'. 'Edit</a>';
			echo ' | <a href="' . $preview_link . '">Preview</a>';
			echo '</strong></td>';
		} // }}}

		function has_filters() // {{{
		{
			if( $this->filters ) 
			{
				foreach( $this->filters as $name => $value )
				{
					if( $value )
					{
						$key = 'search_' . $name;

						//global $$key;
						//if( $$key )
						//	return true;

						if (isset($this->admin_page->request[$key])) return true;
					}
				}
			}
			return false;
		} // }}}
		function set_no_filters() // {{{
		{
			$rm = array();
			foreach( $this->filters as $key => $dummy_variable )
			{
				$name = 'search_' . $key;
				$rm[ $name ] = '';
			}
			return $rm;
		} // }}}
		function change_open() // {{{
		{
			$this->open = '';
			foreach( $this->filter_values as $key => $dummy_variable )
				$this->force_open( $key , false );
		} // }}}
		function force_open( $key , $open = true) // {{{
		{
			if( $open AND !$this->is_open( $key ) )
				$this->open .= ',' . $key . ',';
			
			$root = $this->root_node();
			$lastkey = '';
			while( ($key != $root) && ($key != $lastkey) )
			{
				$lastkey = $key;
				$key = $this->parent($key);
				if( !$this->is_open( $key ) )
					$this->open .= ',' . $key . ',';
			}
		} // }}}
		
		function grab_filters() // {{{
		{
			$this->filter_es = carl_clone($this->es);
			parent::grab_filters();
		} // }}}
		function load_values() // {{{
		{
			if( $this->order_by )
				$this->es->set_order( $this->order_by );
			$this->es->limit_fields();
			$this->es->exclude_tables_dynamically();
			$this->values = $this->es->run_one();
			if( $this->has_filters() )
			{
				$this->filter_values = $this->filter_es->run_one();
				$this->change_open();
			}
		} // }}}
		function do_display() // {{{
		{
			if( $this->values AND (!$this->has_filters() OR $this->filter_values) )
				$this->display();
			else
				$this->show_no_results();
		} // }}}
		
		
		function get_depth_of_item($id)
		{
			$parent_id = $this->parent($id);
			$root = $this->root_node();
			if($id == $root)
				return 0;
			elseif($parent_id == $root)
				return 1;
			
			$depth = 1;
			while($parent_id != $root)
			{
				if($depth > 60 )
				{
					trigger_error('get_depth_of_item() for id '.$id.' failed -- reached maximum depth of 60');
					return NULL;
				}
				elseif(empty($parent_id))
				{
					trigger_error('get_depth_of_item() for id '.$id.' failed -- break in parent tree');
					return NULL;
				}
				$depth++;
				$parent_id = $this->parent($parent_id);
			}
			return $depth;
			
		}
		
		function get_tree_data()
		{
			$ret = array();
			$root_id = $this->root_node();
			if(!empty($root_id))
			{
				$ret[$root_id] = $this->recurse_tree_data($root_id);
			}
			return $ret;
		}
		
		function recurse_tree_data($id)
		{
			$ret = array();
			$ret['item'] = $this->values[$id];
			$children = $this->children($id);
			if(!empty($children))
			{
				$ret['children'] = array();
				foreach($children as $child_id)
				{
					$ret['children'][$child_id] = $this->recurse_tree_data($child_id);
				}
			}
			return $ret;
		}
	}
?>
