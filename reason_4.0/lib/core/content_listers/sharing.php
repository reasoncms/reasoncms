<?php
	reason_include_once( 'content_listers/associate.php' );
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'sharing_viewer';
	class sharing_viewer extends assoc_viewer
	{
		function alter_values() // {{{
		{
			$this->add_column( 'site' );
			$this->update_es();
			
			$ass_es = new entity_selector( $this->admin_page->site_id );
			$ass_es->add_type( $this->admin_page->type_id );
			$ass_es->set_sharing( 'borrows' );
			$ass_es->limit_fields();
			
			//grab site name as well
			if ($this->site_is_live()) $ass_es->add_right_relationship_field('owns', 'entity', 'state', 'site_state', '"Live"');
			$this->alias = $ass_es->add_right_relationship_field('owns', 'entity', 'name', 'site');
			$this->apply_order_and_limits($ass_es);
			
			$this->ass_vals = $ass_es->run_one();
			
			if( $this->ass_vals ) $this->es->add_relation('entity.id NOT IN('.implode(",", array_keys($this->ass_vals)).')');
			
			if (!(empty($this->admin_page->request['__old_rel_id'])))
			{
				$conn_name = $this->get_connection($this->admin_page->request['__old_rel_id']);
				if ($conn_name == 'many_to_one')
				{
					$ass_related_es = carl_clone($this->es);
					$ass_related_es->add_right_relationship_field(relationship_name_of($this->admin_page->request['__old_rel_id']), 'entity', 'id', 'related_id');
					$this->related_vals = $ass_related_es->run_one();
					if( $this->related_vals ) $this->es->add_relation('entity.id NOT IN('.implode(",", array_keys($this->related_vals)).')');
				}
			}
		} // }}}
		
		/**
		 * Function to check whether filters are active since view has not yet grabbed them
		 */
		function check_filters()
		{
			static $active_filters;
			if (!isset($active_filters))
			{
				foreach ($this->filters as $name=>$value )
				{
					if ( $value )
					{
						$key = 'search_' . $name;
						if ( !empty( $this->admin_page->request[ $key ]))
						{
							$active_filters = true;
							return true;
						}
						else $active_filters = false;
					}
				}
			}
			return $active_filters;
		}
			
		function apply_order_and_limits(&$es)
		{
			if(!empty($this->admin_page->request[ 'order_by' ]))
			{
				$alias = isset( $this->alias[ $this->admin_page->request[ 'order_by' ] ] ) ? $this->alias[ $this->admin_page->request[ 'order_by' ] ] : '';
				if( $alias )  //first, check aliases
				{
					$table = $alias[ 'table' ] . '.' . $alias[ 'field' ];
					$table_name = $alias['table_orig'];
				}
				else //then check normal values
				{
					$table = table_of( $this->admin_page->request[ 'order_by' ] , $this->type_id);
					$table_name = substr($table, 0, strpos($table, '.'.$this->admin_page->request[ 'order_by' ]));
				}
				if($table)  //if we've found one, add the relation
				{
					$es->set_order($table . ' ' . $this->admin_page->request[ 'dir' ] );
					if (!$this->check_filters()) $es->limit_tables($table_name);  // only limit if filters are not being used
				}
			}
			elseif (!$this->check_filters()) $es->limit_tables(); // only limit if filters are not being used
		}
		
		function get_connection($rel_id)
		{
			if (empty($rel_id)) return '';
			$q = 'SELECT * FROM allowable_relationship WHERE id = ' . $rel_id;
			$r = db_query( $q , 'Error checking allowable relationship connections' );
			$row = mysql_fetch_array( $r , MYSQL_ASSOC );
			if(!(empty($row[ 'connections' ]))) return $row['connections'];
			else return '';
		}
		
		function site_is_live() // {{{
		{
			$e = new entity( $this->site_id );
			if( $e->get_value( 'site_state' ) == "Live" )
				return true;
			return false;		
		} // }}}
		
		function show_admin_normal( $row , $options) // {{{
		{
			echo '<td align="left" class="'.$options[ 'class' ].'"><strong>';
			$borrow_array =  array( 'cur_module' => 'DoBorrow' , 'id' => $row->id() );
			if( !$this->select )
				$borrow_array[ 'unborrow' ] = 1;
			$borrow_link = $this->admin_page->make_link( $borrow_array );
			$preview_link = $this->admin_page->make_link( array( 'cur_module' => 'Preview' , 'id' => $row->id() ) );
			echo '<a href="' . $preview_link . '">Preview</a> | ';
			echo '<a href="' . $borrow_link . '">'. ( $this->select ? 'Borrow' : 'Don\'t Borrow' ) . '</a>';
			echo '</strong></td>';
		} // }}}
		
		function update_es() // {{{
		{
			// lets find the sites that share the type and limit our query to those sites
			$prep_es = new entity_selector();
			$prep_es->limit_tables();
			$prep_es->limit_fields();
			$prep_es->add_type(id_of('site'));
			$prep_es->add_left_relationship($this->admin_page->type_id, relationship_id_of('site_shares_type'));
			$state = ( $this->site_is_live()) ? 'Live' : 'All';
			$sites = $prep_es->run_one('', $state);
			
			$es = new entity_selector();
			$es->add_type( $this->admin_page->type_id );
			$limiter = (!empty($this->admin_page->request['search_site'])) ? $this->admin_page->request['search_site'] : array_keys($sites);
			$es->add_right_relationship_field('owns', 'entity', 'id', 'site_id', $limiter);
			$es->add_right_relationship_field('owns', 'entity', 'name', 'site');
			$this->apply_order_and_limits($es);
			$es->add_relation( '(entity.no_share IS NULL OR entity.no_share = 0)' ); // entity is shared
			$this->es = $es;
		} // }}}
		function show_item_post( $row , $options = false) // {{{
		{
			$this->show_admin_normal( $row , $options );
			echo '</tr>';
		} // }}}
		function show_admin_paging() // {{{
		{
			echo 'Preview/' . ( $this->select ? 'Borrow' : 'Don\'t Borrow' );
		} // }}}
	}
	


?>
