<?php
/**
 * @package reason
 * @subpackage content_listers
 */
	/**
	 * Register viewer with Reason. This is critically important to remember when extending.
	 */
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'generic_viewer';

	include_once( 'reason_header.php' );
	reason_include_once( 'classes/viewer.php' );

	/**
	 * A class that lists entities in the Reason administrative interface
	 */
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
					echo '<span class="listerPageNum">';
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
					echo '</span>';
				}
				$approx_string = ($this->real_count) ? ' of ' : ' of approx. ';
				echo "<span class=\"listerResultNum\">(Items $this->page_start - $this->page_end".$approx_string.$this->num_results. ' Results)';
				echo '</span>';
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
				if($this->_should_show_admin_functions_column())
					echo '<th class="listHead">Admin Functions</th></tr>'."\n";;
		} // }}}
		
		function _should_show_admin_functions_column()
		{
			if($this->state == 'pending')
			{
				return reason_user_has_privs($this->admin_page->user_id,'edit_pending');
			}
			elseif($this->state == 'deleted')
			{
				return reason_user_has_privs($this->admin_page->user_id,'delete');
			}
			else
			{
				return reason_user_has_privs($this->admin_page->user_id,'edit');
			}
		}
		
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
				echo '<td class="viewerCol_'.$col.'">'.$display.'</td>' ."\n";				
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
			if($this->_should_show_admin_functions_column())
			{
				if( empty( $options ) ) $options = array();
				if( $options[ 'state' ] == 'deleted' )
					$this->show_admin_delete( $row , $options );
				elseif( $options['state'] == 'pending' )
					$this->show_admin_pending( $row, $options );
				else
					$this->show_admin_live( $row , $options );
			}
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
			echo '<td class="viewerCol_admin">';
			if(reason_user_has_privs($this->admin_page->user_id,'edit'))
			{
				echo '<strong>';
				$preview_link = $this->admin_page->make_link(  array( 'cur_module' => 'Preview' , 'id' => $row->id() ) );
				echo '<a href="' . $preview_link . '">'. 'Preview</a>';
				if (reason_site_can_edit_type($this->admin_page->site_id, $this->admin_page->type_id))
				{
					$edit_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => $row->id() ) );
					$edit_block = '<a href="' . $edit_link . '">Edit</a>';
					if($row->has_lock())
					{
						$user = new entity($this->admin_page->user_id);
						if(reason_user_has_privs($this->admin_page->user_id,'bypass_locks'))
						{
							$edit_block .= ' <img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="Locks applied" width="12" height="12" />';
						}
						elseif( !$row->user_can_edit($user) )
						{
							$edit_block = ' <img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="Locked" width="12" height="12" />';
						}
					}
					echo ' | '.$edit_block;
				}
				echo '</strong>';
			}
			else
			{
				echo '&nbsp;';
			}
			echo '</td>'."\n";;
		} // }}}
		function show_admin_pending( $row , $options ) //{{{
		{
			echo '<td class="viewerCol_admin">';
			if(reason_user_has_privs($this->admin_page->user_id,'edit_pending'))
			{
				echo '<strong>';
				$preview_link = $this->admin_page->make_link(  array( 'cur_module' => 'Preview' , 'id' => $row->id() ) );
				echo '<a href="' . $preview_link . '">'. 'Preview</a>';
				if (reason_site_can_edit_type($this->admin_page->site_id, $this->admin_page->type_id))
				{
					$edit_link = $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => $row->id() ) );
					$edit_block = '<a href="' . $edit_link . '">Edit</a>';
					if($row->has_lock())
					{
						$user = new entity($this->admin_page->user_id);
						if(reason_user_has_privs($this->admin_page->user_id,'bypass_locks'))
						{
							$edit_block .= ' <img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="Locks applied" width="12" height="12" />';
						}
						elseif( !$row->user_can_edit($user) )
						{
							$edit_block = ' <img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="Locked" width="12" height="12" />';
						}
					}
					echo ' | '.$edit_block;
				}
				echo '</strong>';
			}
			else
			{
				echo '&nbsp;';
			}
			echo '</td>'."\n";;
		} // }}}
		function show_admin_delete( $row  , $options ) // {{{
		{
			echo '<td class="viewerCol_admin">';
			$links = array();
			$user = new entity($this->admin_page->user_id);
			if(!$row->user_can_edit_field('state',$user))
			{
				$links = array('<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="Locked" width="12" height="12" /> Locked');
			}
			else
			{
				if(reason_user_has_privs($this->admin_page->user_id,'publish'))
				{
					$link =  $this->admin_page->make_link( array( 'id' => $row->id(), 'cur_module' => 'Undelete' ) );
					$links[] = '<a href="'.$link.'">Undelete</a>';
				}
				if(reason_user_has_privs($this->admin_page->user_id,'expunge'))
				{
					$link =  $this->admin_page->make_link( array( 'id' => $row->id(), 'cur_module' => 'Expunge' ) );
					$links[] = '<a href="'.$link.'">Expunge</a>';
				}
				if( $row->field_has_lock('state') && reason_user_has_privs($this->admin_page->user_id,'manage_locks') )
				{
					$links [] = '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="Locked for some users" title="Locked for some users" width="12" height="12" />';
				}
			}
			if(!empty($links))
			{
				echo '<strong>' . implode( ' | ' , $links ) . '</strong>';
			}
			else
			{
				echo '&nbsp;';
			}
			echo '</td>'."\n";;
		} // }}}
		function alter_values() // {{{
		{
		} // }}}	
		function get_user_roles() //{{{
		{
			trigger_error( 'lister->get_user_roles() is deprecated. use global function reason_user_has_privs() instead');
		} // }}}
		function user_has_role( $role ) //{{{
		{
			trigger_error( 'lister->user_has_role is deprecated. use global function reason_user_has_privs() instead');
			return false;
		} // }}}
		function display() // {{{
		{
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
