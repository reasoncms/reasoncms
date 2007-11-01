<?php
	reason_include_once( 'content_listers/default.php3' );
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'assoc_viewer';
	
	class assoc_viewer extends generic_viewer
	{
		var $related_vals = array();
		var $alter_order_enable = false;
		var $is_relationship_sortable = false;
		
		function alter_values() // {{{
		{
			$is_relationship_sortable = $this->check_is_rel_sortable();
			if ($is_relationship_sortable) $this->columns['rel_sort_order'] = true;
			$this->es->set_sharing( 'owns,borrows' );
			$this->es->add_field( 'ar' , 'name' , 'sharing' );
			$ass_es = carl_clone($this->es);
			$ass_es->add_right_relationship( $this->admin_page->id , $this->admin_page->rel_id );
			
			if ($is_relationship_sortable) 
			{
				$ass_es->add_field( 'relationship', 'id', 'rel_id' );
				$ass_es->add_rel_sort_field($this->admin_page->id);
			}
			if(!empty($this->admin_page->request[ 'order_by' ]))
			{
				$alias = isset( $this->alias[ $this->admin_page->request[ 'order_by' ] ] ) ? $this->alias[ $this->admin_page->request[ 'order_by' ] ] : '';
				if( $alias )  //first, check aliases
					$table = $alias[ 'table' ] . '.' . $alias[ 'field' ];
				else //then check normal values
					$table = table_of( $this->admin_page->request[ 'order_by' ] , $this->type_id);
				if($table)  //if we've found one, add the relation
					$ass_es->set_order($table . ' ' . $this->admin_page->request[ 'dir' ] );
				elseif ($is_relationship_sortable)
				{
					//default to relationship sort order
					$ass_es->set_order('relationship.rel_sort_order' . ' ' . $this->admin_page->request[ 'dir' ]);
					$this->alter_order_enable = true;
				}
			}
			elseif ($is_relationship_sortable)
			{
				//default to relationship sort order
				$ass_es->set_order('relationship.rel_sort_order ASC');
				$this->order_by = 'rel_sort_order';
				$this->alter_order_enable = true;
			}
			
			$my_query = $ass_es->get_one_query();
			$this->ass_vals = $ass_es->run_one();
			if (count($this->ass_vals) == 1) unset($this->columns['rel_sort_order']);
			
			if ($is_relationship_sortable)
			{
				if ($ass_es->orderby == 'relationship.rel_sort_order ASC')
					$rel_update_array = $this->validate_rel_sort_order($this->ass_vals, true);
				else
					$rel_update_array = $this->validate_rel_sort_order($this->ass_vals);
				if (count($rel_update_array) > 0)
				{
					foreach ($rel_update_array as $k=>$v)
					{
						update_relationship($k, array('rel_sort_order' => $v));
					}
				}
			}
			
			if( $this->ass_vals )
			{
				$in = 'entity.id NOT IN (';
				$first = true;
				foreach( $this->ass_vals AS $item )
				{
					if( !$first )
						$in .=',';
					else
						$first = false;

					$in .= $item->id();
				}
				$in .= ')';
				$this->es->add_relation( $in );
			}
			
			if ($this->admin_page->module->associations[$this->admin_page->rel_id]['connections'] == 'many_to_one')
			{
				$ass_related_es = $this->es;
				$ass_related_es->add_right_relationship_field(relationship_name_of($this->admin_page->rel_id), 'entity', 'id', 'related_id');
				$this->related_vals = $ass_related_es->run_one();
				if( $this->related_vals )
				{
					$in = 'entity.id NOT IN (';
					$first = true;
					foreach( $this->related_vals AS $item )
					{
						if( !$first )
							$in .=',';
						else
							$first = false;
	
						$in .= $item->id();
					}
					$in .= ')';
					$this->es->add_relation( $in );
				}
			}
			
		} // }}}
		
		function show_all_items() // {{{
		{
			$this->show_disassociated_items();
		} // }}}
		
		function show_associated_items() // {{{
		{
			$this->select = false;
			echo '<table id="associatedItems" cellspacing="0" cellpadding="8">';
			if( $this->ass_vals )
			{
				$c = count( $this->ass_vals );
				$columns = count( $this->columns ) + 1;
				echo '<tr><td colspan="'.$columns.'" class="assocHead">';
				echo 'Selected&nbsp;('. $c .')</td></tr>';
				$row = 0;
				foreach( $this->ass_vals AS $id => $item )
				{
					if( ($row % $this->rows_per_sorting) == 0 )
						$this->show_sorting();
					$this->show_item( $item );
					$row++;
				}
			}
			echo '</table>';
		} // }}}
		
		function show_disassociated_items() // {{{
		{
			$this->remove_column('rel_sort_order');
			$this->select = true;
			$row = 0;
			$columns = count( $this->columns ) + 1;
			echo '<table cellspacing="0" cellpadding="8">';
			echo '<tr><td colspan="'.$columns.'">';
			$this->show_paging();
			echo '</td></tr>';
			foreach( $this->values AS $id => $item )
			{
				if (!array_key_exists($id, $this->related_vals)) // check for entities already related in a many_to_one relationship
				{
					if( ($row % $this->rows_per_sorting) == 0 )
						$this->show_sorting();
					$this->show_item( $item );
					$row++;
				}
			}
			$columns = count( $this->columns ) + 1;
			echo '<tr><td colspan="'.$columns.'">';
			$this->show_paging();
			echo '</td></tr>';
			echo '</table>';
		} // }}}
		
		function show_sorting() // {{{
		{
		$hide_sort = false;
		?>
					<tr>
			<?php
				foreach( $this->columns AS $key => $val )
				{
					if ( is_int( $key ) )
						$col = $val;
					else
						$col = $key;

					// set up sorting directions and such
					if ( $col ==  $this->order_by )
					{
						if ($col == 'rel_sort_order')
						{
							$hide_sort = true;
						}
						elseif ( $this->dir == 'DESC' )
						{
							$dir_show = ' v';
							$dir_link = 'ASC';
						}
						else
						{
							$dir_show = ' ^';
							$dir_link = 'DESC';
						}
					}
					else
					{
						$dir_link = 'ASC';
						$dir_show = '';
					}
					
					$col_display_name = $this->get_col_display_name($col);
					echo '<th class="listHead">';
					if ($hide_sort == true) echo $col_display_name;
					else echo '<a href="'.$this->get_link(array('dir' => $dir_link, 'order_by' => $col, 'page' => '' )).'">'.$col_display_name.'</a>'.$dir_show;
					echo '</th>';
				}
			?>
						<th class="listHead"><?php $this->show_admin_paging();?></th></tr>
		<?php
		} // }}}
		function show_admin_paging() // {{{
		{
			echo ( $this->select ? 'Select' : 'Deselect' );
		} // }}}
			function display() // {{{
			{
				$this->show_filters();
				$this->show_all_items();
			} // }}}
			
		function show_item_pre( $row , &$options ) // {{{
		{
			if (empty($this->row_counter)) $this->row_counter = 0;
			$this->row_counter++;
			static $row_num = 1;
			$row_num = 1 - $row_num;
			if ( $row_num )
				$class = 'listRow2';
			else
				$class = 'listRow1';
			if( !is_array( $options ) )
				$options = array();
			$options[ 'class' ] = $class;
			echo '<tr class="' . $class . '" id="row' . $this->row_counter . '">';
		} // }}}
		
		function show_item_post( $row , $options ) // {{{
		{
			if( empty( $options ) ) $options = false;
			$this->show_admin_associate( $row , $options );
			echo '</tr>';
		} // }}}
		
		function get_rel_sort($number, $data = array())
		{
			if ($this->alter_order_enable)
			{
				$c = count( $this->ass_vals );
				$id = $this->row_counter;
				$url_up = $this->admin_page->make_link( array( 'do' => 'moveup', 'rowid' => $id, 'eid' => $data['eid'] ) );
				$url_down = $this->admin_page->make_link( array( 'do' => 'movedown', 'rowid' => $id, 'eid' => $data['eid'] ) );
				
				$arrow_up = '<img src="'.REASON_ADMIN_IMAGES_DIRECTORY.'/arrow_up.gif" alt="move up" />';
				$arrow_down = '<img src="'.REASON_ADMIN_IMAGES_DIRECTORY.'/arrow_down.gif" alt="move down" />';
				$str = '';
				if ($id > 1) $str .= '<a class="sort_switch_up" href="'.$url_up.'">'.$arrow_up.'</a>';
				if ($id < $c) $str .= '<a class="sort_switch_down" href="'.$url_down.'">'.$arrow_down.'</a>';
           	 	return $str;
           	 }
           	 else return $number;
		}
		
		function check_is_rel_sortable()
		{
			$q = 'SELECT is_sortable FROM allowable_relationship WHERE id = ' . $this->admin_page->rel_id;
        	$r = db_query( $q , 'error getting relationship info' );
        	$row = mysql_fetch_array( $r , MYSQL_ASSOC );
        	if ($row['is_sortable'] == 'yes') return true;
        	else return false;
		}
		
		function validate_rel_sort_order(&$assoc_entities, $rel_sort_order = false)
		{
			$used_values = array('0' => '0');
			$get_highest = array();
			$changed = false;
			
			foreach ($assoc_entities as $entity)
			{
				$rel_sort = $entity->get_value('rel_sort_order');
				if (empty($rel_sort) || $rel_sort == 0)
				{
					$changed = true;
					$get_highest[$entity->id()] = $entity;
				}
				else
				{
					if (in_array($rel_sort, $used_values))
					{
						$rel_sort = $this->increment_next($rel_sort, $used_values);
						$assoc_entities[$entity->id()]->set_value('rel_sort_order', $rel_sort); // add rel_sort value to entity
						$update_values[$entity->get_value('rel_id')] = $rel_sort;
						$changed = true;
					}
					$used_values[$entity->id()] = $rel_sort;
				}
			}
			
			// handle subset which were undefined
			if (count($get_highest) > 0)
			{
				foreach ($get_highest as $entity)
				{
					$rel_sort = max($used_values) + 1;
					$assoc_entities[$entity->id()]->set_value('rel_sort_order', $rel_sort); // add rel_sort _value to entity
					$update_values[$entity->get_value('rel_id')] = $rel_sort;
					$used_values[$entity->id()] = $rel_sort;
					$changed = true;
				}
			}
			
			// resort original entities only if they are sorted by rel_sort_order
			if ($changed == true)
			{
				if ($rel_sort_order == true) entity_sort($assoc_entities, 'rel_sort_order', 'ASC', 'numerical');
				return $update_values;
			}
			else return array();
		}
		
		function increment_next($rel_sort, $used_values)
		{
			$rel_sort++;
			if (in_array($rel_sort, $used_values))
			{
				$this->increment_next($rel_sort, $used_values);
			}
			else return ($rel_sort);
		}
		
		function get_col_display_name($string)
		{
			if ($string == 'rel_sort_order')
			return 'Sort';
			else return prettify_string($string);
		}
		
		function show_admin_associate( $row , $options ) // {{{
		{
			$e_rel = $this->admin_page->rel_id;
			$e_id = $this->admin_page->id;
			$e = new entity( $e_id );
			$e_type = $e->get_value( 'type' );
			static $one_to_many = false;
			static $found_connections = false;
			if( !$found_connections )
			{
				$found_connections = true;
				$q = 'SELECT * FROM allowable_relationship WHERE id = ' . $e_rel .
					' AND required = "yes"';
				$r = db_query( $q , 'error selecting connections' );
				$ar = mysql_fetch_array( $r , MYSQL_ASSOC );
				if( $ar AND $ar[ 'connections' ] == 'one_to_many')
					$one_to_many = true;
				else $one_to_many = false;
			}
			$link = array( 'rel_id' => $e_rel, 'entity_b' => $row->id() );
			if( !$this->select )
			{
				//echo $row->get_value('rel_site_id');
				$link = array_merge( $link, array( 'cur_module' => 'DoDisassociate') );
				$name = 'Deselect';
			}
			else
			{
				$link = array_merge( $link, array( 'cur_module' => 'DoAssociate') );
				$name = 'Select';
			}
			//echo '<td class="'.$options[ 'class' ].'"><strong>';
			echo '<td><strong>';
			if( !$this->select AND $one_to_many )
				echo 'Selected';
			else
			{
				echo '<a href="' .$this->admin_page->make_link( $link ).'">' . $name . '</a>';
			}
			if( empty( $this->admin_page->request[ CM_VAR_PREFIX.'type_id' ] ) )
			{
				$this->rel_type =& $this->admin_page->module->rel_type;
				$edit_link = $this->admin_page->module->get_second_level_vars();
				$edit_link[ 'new_entity' ] = '';
				$preview_link = $edit_link;
				$preview_link[ 'id' ] = $row->id();
				$preview_link[ 'cur_module' ] = 'Preview';
				$edit_link[ 'id' ] = $row->id();
				$edit_link[ 'cur_module' ] = 'Edit';
				echo ' | <a href="'.$this->admin_page->make_link( $preview_link ).'">Preview</a>';
				if( $row->get_value( 'sharing' ) == 'owns' )
					echo ' | <a href="'.$this->admin_page->make_link( $edit_link ).'">Edit</a>';
				else 
					echo ' | Borrowed';
			}
				
			echo '</strong></td>';	
		} // }}}
	}

	class reverse_assoc_viewer extends assoc_viewer
	{
		function alter_values() // {{{
		{
			$this->es->set_sharing( 'owns,borrows' );
			$this->es->add_field( 'ar' , 'name' , 'sharing' );
			$ass_es = $this->es;
			$ass_es->add_left_relationship( $this->admin_page->id , $this->admin_page->rel_id );
			$ass_es->add_field('relationship','site','rel_site_id');
			
			if(!empty($this->admin_page->request[ 'order_by' ]))
			{
				$alias = isset( $this->alias[ $this->admin_page->request[ 'order_by' ] ] ) ? $this->alias[ $this->admin_page->request[ 'order_by' ] ] : '';
				if( $alias )  //first, check aliases
					$table = $alias[ 'table' ] . '.' . $alias[ 'field' ];
				else //then check normal values
					$table = table_of( $this->admin_page->request[ 'order_by' ] , $this->type_id);
				if($table)  //if we've found one, add the relation
					$ass_es->set_order($table . ' ' . $this->admin_page->request[ 'dir' ] );
			}
			$this->ass_vals = $ass_es->run_one();
			if( $this->ass_vals )
			{
				$in = 'entity.id NOT IN (';
				$first = true;
				foreach( $this->ass_vals AS $item )
				{
					if( !$first )
						$in .=',';
					else
						$first = false;

					$in .= $item->id();
				}
				$in .= ')';
				$this->es->add_relation( $in );
			}
		} // }}}
		function show_admin_associate( $row , $options ) // {{{
		{
			$e_rel = $this->admin_page->rel_id;
			$e_id = $this->admin_page->id;
			$e = new entity( $e_id );
			$e_type = $e->get_value( 'type' );
			static $one_to_many = false;
			static $found_connections = false;
			if( !$found_connections )
			{
				$found_connections = true;
				$q = 'SELECT * FROM allowable_relationship WHERE id = ' . $e_rel .
					' AND required = "yes"';
				$r = db_query( $q , 'error selecting connections' );
				$ar = mysql_fetch_array( $r , MYSQL_ASSOC );
				if( $ar AND $ar[ 'connections' ] == 'one_to_many')
					$one_to_many = true;
				else $one_to_many = false;
			}

			$link = array( 'rel_id' => $e_rel, 'entity_a' => $row->id() );
			if( !$this->select )
			{
				// if the associated item is borrowed, and that relationship is not in the scope of the current site,
				// we do not provide the DoDisassociate link.
				if (($row->get_value('sharing') == 'owns') || ($this->site_id == $row->get_value('rel_site_id')))
				{
					$link = array_merge( $link, array( 'cur_module' => 'DoDisassociate') );
					//echo $this->site_id;
				}
				else $link = '';
				$name = 'Deselect';
			}
			else
			{
				$link = array_merge( $link, array( 'cur_module' => 'DoAssociate') );
				$name = 'Select';
			}
			//echo '<td class="'.$options[ 'class' ].'"><strong>';
			echo '<td><strong>';
			if( !$this->select AND $one_to_many )
				echo 'Selected';
			else
			{
				if (!empty($link))	echo '<a href="' .$this->admin_page->make_link( $link ).'">' . $name . '</a>';
				else echo $name;
			}
			if( empty( $this->admin_page->request[ CM_VAR_PREFIX.'type_id' ] ) )
			{
				$this->rel_type =& $this->admin_page->module->rel_type;
				$ass_mod = new AssociatorModule($this->admin_page);
				$ass_mod->rel_type =& $this->admin_page->module->rel_type;
				$edit_link = $ass_mod->get_second_level_vars(); 
				$edit_link[ 'new_entity' ] = '';
				$preview_link = $edit_link;
				$preview_link[ 'id' ] = $row->id();
				$preview_link[ 'cur_module' ] = 'Preview';
				$edit_link[ 'id' ] = $row->id();
				$edit_link[ 'cur_module' ] = 'Edit';
				echo ' | <a href="'.$this->admin_page->make_link( $preview_link ).'">Preview</a>';
				if( $row->get_value( 'sharing' ) == 'owns' )
					echo ' | <a href="'.$this->admin_page->make_link( $edit_link ).'">Edit</a>';
				else 
					echo ' | Borrowed';
			}
				
			echo '</strong></td>';	
		} // }}}
	}
	

?>
