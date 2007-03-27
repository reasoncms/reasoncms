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
			
			//grab site name as well
			$ass_es->add_table( 'ar2' , 'allowable_relationship' );
			$ass_es->add_table( 'r2' , 'relationship' );

			$ass_es->add_relation( 'ar2.name = "owns"' );
			$ass_es->add_relation( 'ar2.id = r2.type' );
			$ass_es->add_relation( 'r2.entity_b = entity.id' );
			
			$ass_es->add_table( 'site_entity' , 'entity' );
			$ass_es->add_field( 'site_entity' , 'name' , 'site' );
			$ass_es->add_relation( 'site_entity.id = r2.entity_a' );


			if( $this->site_is_live() )
			{
				$ass_es->add_table( 'site_table' , 'site' );
				$ass_es->add_relation( 'site_entity.id = site_table.id' );
				$ass_es->add_relation( 'site_table.site_state = "Live"' );
			}
			$this->alias = $this->alias + array( 'site' => array( 'table' => 'site_entity' , 'field' => 'name' ) );

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
			
			if (!(empty($this->admin_page->request['__old_rel_id'])))
			{
				$conn_name = $this->get_connection($this->admin_page->request['__old_rel_id']);
				if ($conn_name == 'many_to_one')
				{
					$ass_related_es = $this->es;
					$ass_related_es->add_right_relationship_field(relationship_name_of($this->admin_page->request['__old_rel_id']), 'entity', 'id', 'related_id');
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
			}
		} // }}}
		
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
			$es = new entity_selector();
			$es->add_type( $this->admin_page->type_id );

			//some other site owns this entity
			$es->add_table( 'r','relationship' );
			$es->add_table( 'ar','allowable_relationship' );
			$es->add_relation( 'r.entity_a != '.$this->admin_page->site_id);
			$es->add_relation( 'r.entity_b = entity.id');
			$es->add_relation( 'r.type = ar.id' );
			$es->add_relation( 'ar.name = "owns"' );
			
			//some other site is sharing this entity
			$es->add_table( 'r2','relationship' );
			$es->add_table( 'ar2','allowable_relationship' );
			$es->add_relation( 'r2.entity_a = r.entity_a' );
			$es->add_relation( 'r2.type = ar2.id' );
			$es->add_relation( 'ar2.name = "site_shares_type"' );
			$es->add_relation( 'r2.entity_b = entity.type' );

			//grab site name as well
			$es->add_table( 'site_entity' , 'entity' );
			$es->add_field( 'site_entity' , 'name' , 'site' );
			$es->add_relation( 'site_entity.id = r2.entity_a' );
			if( $this->site_is_live() )
			{
				$es->add_table( 'site_table' , 'site' );
				$es->add_relation( 'site_entity.id = site_table.id' );
				$es->add_relation( 'site_table.site_state = "Live"' );
			}
			
			//entity is shared
			$es->add_relation( '(entity.no_share IS NULL OR entity.no_share = 0)' );

			//for filtering by site
			if( !empty( $this->admin_page->request['search_site'] ) )
				$es->add_relation( 'site_entity.id = ' . $this->admin_page->request['search_site'] );


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
