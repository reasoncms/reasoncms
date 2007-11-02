<?php
	///////////////////////////////////////////////////////////////////////////////
	// MAKE SURE THIS VARIABLE IS SET IF OVERLOADING
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'generic_viewer';
	///////////////////////////////////////////////////////////////////////////////

	include_once( 'reason_header.php' );
	reason_include_once( 'classes/viewer.php' );

	class generic_viewer extends Viewer
	{
		var $columns = array(
						'id' => true,
						'name' => true, 
						'last_modified' => 'prettify_mysql_timestamp'
						);
		var $num_per_page = 40;	
		var $page_start;
		var $page_end;
		var $num_results;
		var $num_pages;
		var $order_by = 'last_modified';
		var $dir = 'DESC';
		var $user_roles = array();

		var $options = array();

		function set_page( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function test_limits( $c ) // {{{
		{
			if(!$this->num_results)
				$this->num_results = $c;
			if(!$this->num_pages)
				$this->num_pages = ceil($this->num_results / $this->num_per_page);
			$page = isset( $this->admin_page->request[ 'page' ] ) ? $this->admin_page->request[ 'page' ] : 0;
			if(!$this->page) $this->page=1;
			if( $this->page > $this->num_pages ) $this->page = $this->num_pages;
			if( $this->page < 1 ) $this->page = 1;
			
			if(!$this->page_start)
				$this->page_start = $this->num_per_page * ( $this->page - 1 ) + 1;
			if(!$this->page_end )
			{
				$this->page_end = $this->page_start + $this->num_per_page - 1;
				if($this->page_end > $c )
					$this->page_end = $c;
			}
		} // }}}
		function get_showable_pages() // {{{
		{
			$range = 5;
			$showable_pages = array( 1 );
			$showable_pages[] = $this->num_pages;  //make sure first and last pages are there

			for( $i = $this->page - $range; $i <= $this->page + $range; $i++ )
				$showable_pages[] = $i;
			
			return $showable_pages;
		} // }}}
		function filter_showable_pages( &$showable_pages ) // {{{
		{
			asort( $showable_pages );
			$first = true;
			foreach( $showable_pages AS $k => $i ) //remove potential duplicates
			{
				if( $first )
				{
					$first = false;
					$last = $i;
				}
				else
				{
					if( $last == $i )
						unset( $showable_pages[ $k ] );
					else
						$last = $i;
				}
				if( $i < 1 || $i > $this->num_pages )
					unset( $showable_pages[ $k ] );
			}	
		} // }}}
		function show_paging() // {{{
		{
			$c = (!$this->real_count) ? $this->es->get_one_count($this->state) : $this->real_count;
			if( $c )
			{
				// show paging
			//	echo "<span class=\"paginationNav smallText\">";
				$this->test_limits( $c );	
				if( $this->num_pages > 1 )
				{
					echo '<nobr>';
					echo ' Page: ';
					echo '&nbsp;';
					$showable_pages = $this->get_showable_pages();
					$this->filter_showable_pages( $showable_pages );
					$first = true;
					foreach( $showable_pages AS $i )
					{
						if( $first )
						{
							$first = false;
							$last_page = $i;
						}
						else
						{
							if( $i != ( $last_page + 1 ) )
								echo '...&nbsp;';
							$last_page = $i;
						}
						if ( $i == $this->page )
							echo '<strong>'.$i.'</strong>';
						else 
							echo '<a href="'. $this->admin_page->make_link( array_merge( $this->admin_page->request , array( 'page' => $i ) ) ) .'">'.$i.'</a>';
						echo '&nbsp;';
					}
					echo '</nobr>';
				}
				$approx_string = ($this->real_count) ? ' of ' : ' of approx. ';
				echo "<nobr>(Items $this->page_start - $this->page_end".$approx_string.$this->num_results. ' Results)';
				echo '</nobr>';//</span>';
			}
		} // }}}
		function show_sorting() // {{{
		{
		?>
					<tr>
			<?php
				reset( $this->columns );
				while( list( $key,$val ) = each( $this->columns ) )
				{
					if ( is_int( $key ) )
						$col = $val;
					else
						$col = $key;

					// set up sorting directions and such
					if ( $col ==  $this->order_by )
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
					echo '<a href="'.carl_make_link(array('dir' => $dir_link, 'order_by' => $col, 'page' => '' )).'">'.$col_display_name.'</a>'.$dir_show;
					echo '</th>';
				}
				if(!$this->user_has_role( 'contribute_only_role' ) || $this->state == 'pending')
					echo '<th class="listHead">Admin Functions</th></tr>'."\n";;
		} // }}}
		
		function get_col_display_name($string)
		{
			return prettify_string($string);
		}
		
		function show_item( &$row , $options = false) // {{{
		{
			if( !$options )
				$options = $this->get_options();
			$this->show_item_pre( $row , $options );	
			$this->show_item_main( $row , $options );
			$this->show_item_post( $row , $options );
			$this->reset_options();
		} //  }}} 

		function show_item_pre( $row , &$options ) // {{{
		{
			static $row_num = 1;
			$row_num = 1 - $row_num;
			if ( $row_num )
				$class = 'listRow2';
			else
				$class = 'listRow1';
			if( !is_array( $options ) )
				$options = array();
			$options[ 'class' ] = $class;
			echo '<tr class="' . $class . '">'."\n";;
		} // }}}
		function show_item_main( $row , $options) // {{{
		{
			reset( $this->columns );
			while( list( $name, $val ) = each( $this->columns ) )
			{
				$display = '';
				$col = $name;
				if ( (is_string( $val ) ) OR (is_array( $val ) ) )
				{
					$handler = $val;
				}
				else
				{
					$handler = '';
				}
			
				if( $handler )
				{
					if ( is_array( $handler ) )
					{
						$first_field = true;
						reset( $handler );
						while( list( ,$show ) = each ( $handler ) )
						{
							if ( $row->get_value( $show ) )
							{
								$val_to_show = $row->get_value( $show );
								if (is_array($val_to_show)) 
								{
									$val_to_show = implode(", ", $val_to_show);
								}
								if ( $first_field )
								{
									$first_field = false;
									$display .= '<strong>'.$val_to_show.'</strong>';
								}
								else
									$display .= $val_to_show;
								//$display .= '<br />';
							}
						}
					}
					else
						$display = $handler( $row->get_value( $col ) );
				}
				else $display = $this->get_display_no_handler($row, $name);
				if (is_array($display)) $display = '<ul><li>'.implode('</li><li>', $display).'</li></ul>';
				echo '<td>'.$display.'</td>' ."\n";				
			}
		} // }}}
		
		function get_display_no_handler(&$row, $name)
		{
			if( $name == 'name' ) $display = $row->get_display_name();
			else $display = $row->get_value( $name );
			return $display;
		}
		
		function show_item_post( $row , $options ) // {{{
		{
			if( empty( $options ) ) $options = array();
			if(!$this->user_has_role( 'contribute_only_role' ))
			{
				//if( isset( $options[ 'assoc' ] ) AND $options[ 'assoc' ] == 'associate' )
				//	$this->show_admin_associate( $row , $options);
				if( $options[ 'state' ] == 'deleted' )
					$this->show_admin_delete( $row , $options );
				elseif( $options['state'] == 'pending' )
					$this->show_admin_pending( $row, $options );
				else
					$this->show_admin_live( $row , $options );
			}
			elseif( $options['state'] == 'pending' )
					$this->show_admin_pending( $row, $options );
			echo '</tr>' . "\n";
		} // }}}
		
		function get_options() // {{{
		{
			$this->options[ 'state' ] = $this->state;
			if( $this->assoc )
				$this->options[ 'assoc' ] = $this->assoc;
			return $this->options;
		} // }}}
		function reset_options() // {{{
		{
			$this->options = array();
		} // }}}

		function show_admin_live( $row , $options) // {{{
		{
			//echo '<td align="left" class="'.$options[ 'class' ].'"><strong>';
			echo '<td align="left"><strong>';
			$edit_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => $row->id() ) );
			$preview_link = $this->admin_page->make_link(  array( 'cur_module' => 'Preview' , 'id' => $row->id() ) );

			echo '<a href="' . $preview_link . '">'. 'Preview</a> | <a href="' . $edit_link . '">Edit</a>';
			echo '</strong></td>'."\n";;
		} // }}}
		function show_admin_pending( $row , $options ) //{{{
		{
			$this->show_admin_live( $row, $options );
		} // }}}
		function show_admin_delete( $row  , $options ) // {{{
		{
			//echo '<td class="'.$options[ 'class' ].'">';
			echo '<td>';
			$link =  $this->admin_page->make_link( array( 'id' => $row->id(), 'cur_module' => 'Delete' , 'undelete' => 'undelete' ) );
			echo '<strong><a href="'.$link.'">Undelete</a>';
			
			$link =  $this->admin_page->make_link( array( 'id' => $row->id(), 'cur_module' => 'Delete' ) );
			echo ' | <a href="'.$link.'">Expunge</a></strong>';
			echo '</td>'."\n";;
		} // }}}
		function alter_values() // {{{
		{
		} // }}}	
		function get_user_roles() //{{{
		{
			$urs = new entity_selector();
			$urs->add_type(id_of('user_role'));
			$urs->add_right_relationship( $this->admin_page->user_id, relationship_id_of('user_to_user_role') );
			$user_roles = $urs->run_one();
			foreach($user_roles as $ur)
			{
				$this->user_roles[$ur->get_value('unique_name')] = $ur;
			}
		} // }}}
		function user_has_role( $role ) //{{{
		{
			if(array_key_exists($role, $this->user_roles))
				return true;
			else
				return false;
		} // }}}
		function display() // {{{
		{
			/* HACK HACK HACK
			we really need to fix the inheritance of listers so that front and back end stuff are better isolated from each other. */
			if(!empty($this->admin_page))
				$this->get_user_roles();
			$this->show_filters();
			$this->show_paging();
			echo '<div class="list">';
			$this->show_all_items();
			echo '</div>';
		} // }}}

		function show_no_items() // {{{
		{
			$state = !empty( $this->admin_page->request[ 'state' ] ) ? $this->admin_page->request[ 'state' ] : '';
			switch( $state )
			{
				case 'pending': echo 'There are no pending items.'; break;
				case 'deleted': echo 'There are no deleted items.'; break;
				default: echo 'There are no live items.'; break;
			}
		} // }}}
	}
?>
