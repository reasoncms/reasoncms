<?php
/**
 * @package reason
 * @subpackage content_listers
 */
	/**
	 * Include parent class and register viewer with Reason
	 */
	reason_include_once( 'content_listers/default.php3' );
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'assoc_viewer';
	
	/**
	 * This viewer shows associated items and provides custom options for selecting and deselecting items
	 */
	class assoc_viewer extends generic_viewer
	{
		var $related_vals = array();
		var $alter_order_enable = false;
		var $is_relationship_sortable = false;
		var $rel_direction = 'a_to_b';
		var $_rel_locked = false;
		
		function alter_values() // {{{
		{
			$this->setup_associated_items();
			
			// show items the site owns or borrows - add sharing field
			$this->es->set_sharing( 'owns,borrows' );
			if (reason_relationship_names_are_unique())
			{
				$this->es->add_table( 'ar', 'allowable_relationship' );
				$this->es->add_field( 'ar' , 'type' , 'sharing' );
				$this->es->add_relation( 'r.type = ar.id' );
			}
			else $this->es->add_field( 'ar' , 'name' , 'sharing' );
			
			// modify entity selector to exclude items that are already associated
			if( $this->ass_vals )
			{
				$relation = 'entity.id NOT IN ('.implode(",",array_keys($this->ass_vals)).')';
				$this->es->add_relation( $relation );
			}
			
			// modify entity selector to exlude items that should not be available for association because they are already part of a many_to_one relationship
			// currently this will only run for unidirectional relationships since bidirectional rels are forced to be many_to_many
			if ($this->admin_page->module->associations[$this->admin_page->rel_id]['connections'] == 'many_to_one')
			{
				$ass_related_es = carl_clone($this->es);
				$ass_related_es->add_right_relationship_field(relationship_name_of($this->admin_page->rel_id), 'entity', 'id', 'related_id');
				$this->related_vals = $ass_related_es->run_one();
				if( $this->related_vals )
				{
					$relation = 'entity.id NOT IN ('.implode(",",array_keys($this->related_vals)).')';
					$this->es->add_relation( $relation );
				}
			}
		} // }}}
		
		function setup_associated_items()
		{
			// populate associated entity selector from scratch
			$ass_es = new entity_selector();
			$ass_es->add_type($this->type_id);
			
			if ($this->rel_direction == 'a_to_b') $ass_es->add_right_relationship($this->admin_page->id, $this->admin_page->rel_id );
			else $ass_es->add_left_relationship($this->admin_page->id, $this->admin_page->rel_id );
			$ass_es->add_right_relationship_field('owns', 'entity', 'id', 'site_owner_id');
			
			if ( ($this->rel_direction == 'a_to_b') && $this->check_is_rel_sortable() ) 
			{
				$this->columns['rel_sort_order'] = true;
				$ass_es->add_field( 'relationship', 'id', 'rel_id' );
				$ass_es->add_rel_sort_field($this->admin_page->id);
				$ass_es->set_order('relationship.rel_sort_order ASC');
				if($this->_cur_user_has_edit_privs() && !$this->get_relationship_lock_state())
				{
					$this->alter_order_enable = true;
				}
			}
			else
			{
				$ass_es->add_field('relationship','site','rel_site_id');
			}
			
			if ($this->assoc_viewer_order_by($ass_es)) $this->alter_order_enable = false;
			$this->ass_vals = $ass_es->run_one();
			
			// check sharing on associated entities
			foreach ($this->ass_vals as $k=>$val)
			{
				// setup sharing value
				if ($this->site_id == $val->get_value('site_owner_id')) $this->ass_vals[$k]->set_value('sharing', 'owns');
				else $this->ass_vals[$k]->set_value('sharing', $this->check_borrow_status($k));
			}
			
			// this verifies and updates the associated items rel_sort_order if this is an a to b relationship
			if ( ($this->rel_direction == 'a_to_b') && $this->check_is_rel_sortable())
			{
				if ( (count($this->ass_vals) == 1) && isset($this->columns['rel_sort_order'])) unset($this->columns['rel_sort_order']);
				if ($ass_es->orderby == 'relationship.rel_sort_order ASC') $rel_update_array = $this->validate_rel_sort_order($this->ass_vals, true);
				else $rel_update_array = $this->validate_rel_sort_order($this->ass_vals);
				if (count($rel_update_array) > 0)
				{
					foreach ($rel_update_array as $k=>$v) update_relationship($k, array('rel_sort_order' => $v));
				}
			}
		}
		
		/**
		 * Grabs an index of ids for items of the current type borrowed by the site and makes sure the associated item is part of the list
		 *
		 * @return string 'borrows' if the site borrows the item or ''
		 */
		function check_borrow_status($associated_value_id)
		{
			if (!isset($this->item_ids_borrowed_by_site))
			{
				$es = new entity_selector($this->site_id);
				$es->add_type($this->type_id);
				$es->set_sharing('borrows');
				$es->limit_tables();
				$es->limit_fields();
				$result = $es->run_one();
				if ($result) $this->item_ids_borrowed_by_site = array_flip(array_keys($result));
				else $this->item_ids_borrowed_by_site = array();
			}
			if (isset($this->item_ids_borrowed_by_site[$associated_value_id])) return 'borrows';
			else return '';
		}
		
		/**
		 * Sets a sort order for the associated items if a sort order was passed in the url
		 * 
	 	 * @return boolean did it set a custom order
	 	 */
		function assoc_viewer_order_by(&$ass_es)
		{
			if(!empty($this->admin_page->request[ 'order_by' ]))
			{
				// first, check aliases
				$alias = isset( $this->alias[ $this->admin_page->request[ 'order_by' ] ] ) ? $this->alias[ $this->admin_page->request[ 'order_by' ] ] : '';
				if( $alias ) $table = $alias[ 'table' ] . '.' . $alias[ 'field' ];
				
				// else check normal values
				else $table = table_of( $this->admin_page->request[ 'order_by' ] , $this->type_id);
				
				// if we found something, add the relation
				if($table) 
				{
					if('ASC' == $this->admin_page->request[ 'dir' ] || 'DESC' == $this->admin_page->request[ 'dir' ])
					{ 
						$ass_es->set_order($table . ' ' . $this->admin_page->request[ 'dir' ] );
						return true;
					}
				}
			}
			return false;
		}
	
		function show_all_items() // {{{
		{
			$this->show_disassociated_items();
		} // }}}
		
		function show_associated_items() // {{{
		{
			$this->select = false;
			if( $this->ass_vals )
			{
				$c = count( $this->ass_vals );
				echo '<div class="associatedItemsWrapper">'."\n";
				echo '<h4>Selected ('. $c .')</h4>'."\n";
				echo '<div class="associatedItems">'."\n";
				$class = (!empty($this->admin_page->rel_id)) ? ' class="'.htmlspecialchars(relationship_name_of($this->admin_page->rel_id)).'" ' : ' ';
				echo '<table id="associatedItems"'. $class . 'cellspacing="0" cellpadding="8">';
				
				$columns = count( $this->columns ) + 1;
				$row = 0;
				
				foreach( $this->ass_vals AS $id => $item )
				{
					if( ($row % $this->rows_per_sorting) == 0 )
						$this->show_sorting();
					$this->show_item( $item );
					$row++;
				}
				echo '</table>'."\n";
				echo '</div>'."\n";
				echo '</div>'."\n";
			}
		} // }}}
		
		function show_disassociated_items() // {{{
		{
			$this->remove_column('rel_sort_order');
			$this->select = true;
			$row = 0;
			$columns = count( $this->columns ) + 1;

			echo '<div class="unassociatedItemsWrapper">'."\n";
			$class = (!empty($this->admin_page->rel_id)) ? ' class="'.htmlspecialchars(relationship_name_of($this->admin_page->rel_id)).'" ' : ' ';
			echo '<table id="disassociatedItems"'. $class . 'cellspacing="0" cellpadding="8">';
			echo '<tr><td colspan="'.$columns.'" class="disassocHead">';

			$this->show_paging();
			echo '</td></tr>'."\n";
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
			echo '<tr><td colspan="'.$columns.'" class="disassocPaging">';
			$this->show_paging();
			echo '</td></tr>'."\n";
			echo '</table>'."\n";
			echo '</div>'."\n";
		} // }}}
		
		function show_sorting() // {{{
		{
			//$hide_sort = false;
			$show_rel_sort_order = (isset($this->columns['rel_sort_order'])) && $this->alter_order_enable;
			echo '<tr>';
			foreach( $this->columns AS $key => $val )
			{
				if ( is_int( $key ) ) $col = $val;
				else $col = $key;
				
				// set up sorting directions and such
				if ( ($col ==  $this->order_by) && !$show_rel_sort_order)
				{
					if ( $this->dir == 'DESC' )
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
				if ($show_rel_sort_order && ($col == 'rel_sort_order')) echo $col_display_name;
				elseif ($col == 'rel_sort_order') echo '<a href="'.carl_make_link(array('dir' => '', 'order_by' => '', 'page' => '' )).'">'.$col_display_name.'</a>';
				else echo '<a href="'.carl_make_link(array('dir' => $dir_link, 'order_by' => $col, 'page' => '' )).'">'.$col_display_name.'</a>'.$dir_show;
				echo '</th>';
			}
			echo '<th class="listHead">';
			$this->show_admin_paging();
			echo '</th></tr>'."\n";
		}
		
		function _cur_user_has_edit_privs()
		{
			$e = new entity($this->admin_page->id);
			return $e->get_value('state') == 'Pending' ? reason_user_has_privs($this->admin_page->user_id,'edit_pending') : reason_user_has_privs($this->admin_page->user_id,'edit');
		}
		
		function show_admin_paging() // {{{
		{
			if($this->_cur_user_has_edit_privs())
				echo ( $this->select ? 'Select' : 'Deselect' );
			else
				echo '&nbsp;';
		}
		
		function display() // {{{
		{
			$this->show_filters();
			$this->show_all_items();
		}
			
		function show_item_pre( $row , &$options )
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
		}
		
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
			static $is_rel_sortable;
			if (!isset($is_rel_sortable))
			{
				$q = 'SELECT is_sortable FROM allowable_relationship WHERE id = ' . $this->admin_page->rel_id;
   	     		$r = db_query( $q , 'error getting relationship info' );
   	     		$row = mysql_fetch_array( $r , MYSQL_ASSOC );
   	     		if ($row['is_sortable'] == 'yes') $is_rel_sortable = true;
        		else $is_rel_sortable = false;
        	}
        	return $is_rel_sortable;
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
			
			// resort original entities only if they are sorted by rel_sort_order - this code is kind of icky because we need to preserve keys.
			if ($changed == true)
			{
				if ($rel_sort_order == true)
				{
					foreach($assoc_entities as $k=>$v)
					{
						$container[$k] = $v->get_value('rel_sort_order');
					}
					$container = array_flip($container);
					ksort($container);
					foreach ($container as $key)
					{
						$new_assoc_entities[$key] = $assoc_entities[$key];
					}
					$assoc_entities = $new_assoc_entities;
					//entity_sort($assoc_entities, 'rel_sort_order', 'ASC', 'numerical'); // old way did not preserve keys
				}
				return $update_values;
			}
			else return array();
		}
		
		function increment_next($rel_sort, $used_values)
		{
			$rel_sort++;
			if (in_array($rel_sort, $used_values))
			{
				return $this->increment_next($rel_sort, $used_values);
			}
			else return ($rel_sort);
		}
		
		function get_col_display_name($string)
		{
			if ($string == 'rel_sort_order')
			return 'Sort';
			else return prettify_string($string);
		}
		
		function get_display_no_handler(&$row, $name)
		{
			if ($name == 'rel_sort_order')
			{
				$data['eid'] = $row->id();
				return $this->get_rel_sort($row->get_value( $name ), $data);
			}
			else return parent::get_display_no_handler($row, $name);
		}
		
		function show_admin_associate( $row , $options ) // {{{
		{
			if(!$this->_cur_user_has_edit_privs())
			{
				echo '<td class="viewerCol_admin">&nbsp;</td>';
				return;
			}
			$e_rel = $this->admin_page->rel_id;
			$e_id = $this->admin_page->id;
			$e = new entity( $e_id );
			$user = new entity( $this->admin_page->user_id );
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
			
			$entity_a_or_b = ($this->rel_direction == 'b_to_a') ? 'entity_a' : 'entity_b';
			
			$lock_check_dir = ($this->rel_direction == 'b_to_a') ? 'right' : 'left';
			
			$link = array( 'rel_id' => $e_rel, $entity_a_or_b => $row->id() );
			if($this->get_relationship_lock_state() || !$row->user_can_edit_relationship($this->admin_page->rel_id, $user, $lock_check_dir) )
			{
				$link = '';
				$name = 'Locked';
			}
			elseif( !$this->select )
			{
			
				// B TO A BEHAVIOR
				// if the associated item is borrowed, and that relationship is not in the scope of the current site,
				// we do not provide the DoDisassociate link.
				if ($this->rel_direction == 'b_to_a')
				{
					if (($row->get_value('sharing') == 'owns') || ($this->site_id == $row->get_value('rel_site_id')))
					{
						$link = array_merge( $link, array( 'cur_module' => 'DoDisassociate') );
					}
					else $link = '';
					$name = 'Deselect';
				}
				else
				{
					$link = array_merge( $link, array( 'cur_module' => 'DoDisassociate') );
					$name = 'Deselect';
				}
				
				
			}
			else
			{
				$link = array_merge( $link, array( 'cur_module' => 'DoAssociate') );
				$name = 'Select';
			}
			
			//echo '<td class="'.$options[ 'class' ].'"><strong>';
			echo '<td class="viewerCol_admin"><strong>';
			if( !$this->select AND $one_to_many )
				echo 'Selected';
			else
			{
				if (!empty($link))
				{
					// lets add a CSRF token for the GET request - we should make all these requests go via POST but 
					// this is better than nothing to prevent CSRF attacks.
					$link = array_merge( $link, (array ('admin_token' => $this->admin_page->get_admin_token() ) ) );
					echo '<a href="' .$this->admin_page->make_link( $link ).'">' . $name . '</a>';
				}
				else
				{
					echo $name;
				}
				
			}
			
				
			if(reason_user_has_privs($this->admin_page->user_id,'manage_locks') && $row->relationship_has_lock($this->admin_page->rel_id, $lock_check_dir) )
			{
					echo ' <img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="Locked for some users" title="Locked for some users" width="12" height="12" />';
			}
			if( empty( $this->admin_page->request[ CM_VAR_PREFIX.'type_id' ] ) )
			{
				$this->rel_type =& $this->admin_page->module->rel_type;
				
				// THIS IS HOW THE B TO A WAS CODED - REVIEW AND DELETE THIS CONDITION IF POSSIBLE
				if ($this->rel_direction == 'b_to_a')
				{
					$ass_mod = new AssociatorModule($this->admin_page);
					$ass_mod->rel_type =& $this->admin_page->module->rel_type;
					$edit_link = $ass_mod->get_second_level_vars();
					$edit_link[ 'new_entity' ] = '';
				}
				else
				{
					$edit_link = $this->admin_page->module->get_second_level_vars();
					$edit_link[ 'new_entity' ] = '';
				}
				$preview_link = $edit_link;
				$preview_link[ 'id' ] = $row->id();
				$preview_link[ 'cur_module' ] = 'Preview';
				$edit_link[ 'id' ] = $row->id();
				$edit_link[ 'cur_module' ] = 'Edit';
				
				$sharing = $row->get_value('sharing');
				$owned = (is_array($sharing)) ? in_array('owns', $sharing) : ($row->get_value('sharing') == 'owns');
				$borrowed = (is_array($sharing)) ? in_array('borrows', $sharing) : ($row->get_value('sharing') == 'borrows');
				echo ' | <a href="'.$this->admin_page->make_link( $preview_link ).'">Preview</a>';
				if( $owned && reason_site_can_edit_type($this->site_id, $this->rel_type) )
					echo ' | <a href="'.$this->admin_page->make_link( $edit_link ).'">Edit</a>';
				if ($borrowed)
					echo ' | Borrowed';
				if ( $owned && $borrowed )
				{
					echo '<p><strong>Note: </strong><em>Item is owned AND borrowed by the site.</em></p>';
				}
				if ( !$owned && !$borrowed )
				{
					echo '<p><strong>Note: </strong><em>Item is not currently owned or borrowed by the site.</em></p>';
				}
			}
			echo '</strong></td>';	
		} // }}}
		
		function set_relationship_lock_state( $state )
		{
			$this->_rel_locked = $state;
		}
		
		function get_relationship_lock_state()
		{
			return $this->_rel_locked;
		}
	}

	class reverse_assoc_viewer extends assoc_viewer
	{
		var $rel_direction = 'b_to_a';
	}
?>