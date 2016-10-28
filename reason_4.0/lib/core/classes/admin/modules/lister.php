<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * The administrative module that handles entity lists in the admin UI
	 */
	class ListerModule extends DefaultModule // {{{
	{
		var $viewer;
		var $filter;
		var $views = 0;
		var $admin_page;
		var $viewer_entity;
		var $import_modules = array('image'=>'ImageImport');

		function ListerModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
			
		function list_header( ) // {{{
		{
			echo $this->_produce_borrowing_nav();
			echo '<div class="listTools">'."\n";
			echo '<div class="actionsAndViews">'."\n";
			if(reason_user_has_privs($this->admin_page->user_id,'add'))
			{
				$this->show_add();
			}
			$this->show_other();
			$this->show_view_box();
			if(reason_user_has_privs($this->admin_page->user_id,'edit'))
			{
				$this->show_sorting();
			}
			echo '</div>'."\n";
			$this->show_filters();
			echo '</div>'."\n";
		} // }}}
		function _produce_borrowing_nav()
		{
			$ret = '';
			if(reason_user_has_privs($this->admin_page->user_id,'borrow'))
			{
				$sharables = $this->admin_page->get_sharable_relationships();
				if(isset($sharables[$this->admin_page->type_id]))
				{
					/* $type = new entity($this->admin_page->type_id);
					$name = $type->get_value('plural_name') ? $type->get_value('plural_name') : $type->get_value('name');
					if(function_exists('mb_strtolower'))
						$name = mb_strtolower($name);
					else
						$name = strtolower($name); */
					$ret .= '<div class="borrowNav">'."\n";
					$ret .= '<ul>';
					$ret .= '<li class="current addEdit"><strong><img src="'.REASON_HTTP_BASE_PATH.'silk_icons/bullet_edit.png" alt="" /> Add &amp; edit</strong></li>';
						$ret .= '<li class="borrow"><a href="'.$this->admin_page->get_borrowed_list_link($this->admin_page->type_id).'"><img src="'.REASON_HTTP_BASE_PATH.'silk_icons/car.png" alt="" /> Borrow</a></li>';
					$ret .= '</ul>'."\n";
					$ret .= '</div>'."\n";
				}
			}
			return $ret;
		}
		function show_filters() // {{{
		{
			echo '<div class="viewFilter">'."\n";
			echo '<h4>Search</h4>';
			$this->filter->run();
			echo '</div>'."\n";
		} // }}}
		function show_add() // {{{
		{
			// lets make sure permissions allow this
			if (reason_site_can_edit_type($this->admin_page->site_id, $this->admin_page->type_id))
			{
				$type = new entity($this->admin_page->type_id);
				$offer_import_link = array_key_exists($type->get_value('unique_name'),$this->import_modules);
				$class = $offer_import_link ? 'includesImport' : 'noImport';
				echo '<div class="addLink '.$class.'">';
				echo '<a href="'. $this->admin_page->make_link(  array( 'cur_module' => 'Editor' , 'id' => '', 'new_entity' => 1) ).'">Add '.$type->get_value( 'name' ).'</a>'."\n";
				if($offer_import_link)
				{
					$import_module = $this->import_modules[$type->get_value('unique_name')];
					echo '<div class="smallText importBlock"><a href="'.$this->admin_page->make_link(  array( 'cur_module' => $import_module , 'id' => '') ).'">Batch Import '.( $type->get_value( 'plural_name' ) ? $type->get_value( 'plural_name' ) : $type->get_value('name') ).'</a></div>'."\n";
				}
				echo '</div>'."\n";
			}
			else
			{
				echo '<div class="addLink">'."\n";
				echo '<strong>Warning</strong><p>The site is not allowed to add or edit items of this type.</p><p>If this is unexpected, contact an administrator.</p>';
				echo '</div>'."\n";
			}
		} // }}}
		function show_other() // {{{
		{
			echo '<div class="viewInfo">'."\n";
			$this->show_live();
			$this->show_deleted( $this->admin_page->site_id, $this->admin_page->type_id );
			$this->show_pending( $this->admin_page->site_id, $this->admin_page->type_id );
			echo '</div>'."\n";
		} // }}}
		function show_view_box() // {{{
		{
			if( empty( $this->views ) && !is_array( $this->views ) )
				$this->get_views();
			if(count( $this->views ) > 1)
			{
				echo '<div class="viewInfo">'."\n";
				if( !empty( $this->views ) )
				{
					echo '<form name="form2" method="get"><select name="lister" class="viewMenu">';
					foreach( $this->views AS $view )
					{
						echo '<option value="' . $view->id() . '"';
						if( $view->id() == $this->admin_page->request[ 'lister' ] )
							echo ' selected="selected"';
						echo '>View: ' . strip_tags($view->get_value( 'display_name' )) . "</option>\n";
					}
					if( !empty( $this->admin_page->request[ 'state' ] ))
						$args = array_merge($this->admin_page->get_default_args(),array('state' => $this->admin_page->request['state']));
					else
						$args = $this->admin_page->get_default_args();
						
					foreach ( $args as $key => $value)
					{
						echo '<input type="hidden" name="'.htmlspecialchars($key, ENT_QUOTES).'" value="'.htmlspecialchars($value, ENT_QUOTES).'" />';
					}
					echo ' <input type="submit" value="Go" class="viewMenuSubmit" name="__button_submit">';
					echo '</select></form>';
				}
				echo '</div>'."\n";
			}
		} // }}}
		function show_deleted() // {{{
		{
			$es = new entity_selector($this->admin_page->site_id);
			$es->add_type( $this->admin_page->type_id );
			$es->limit_tables();
			$es->limit_fields();
			$c = $es->get_one_count( 'Deleted' );
			$this->deleted_item_count = $c;
			if( !empty( $this->admin_page->request[ 'state' ] ) && $this->admin_page->request[ 'state' ] == 'deleted' )
			{
				echo '<strong>Deleted Items <span class="count">(' . $c . ')</span></strong><br />';
			}
			else
			{
				if( $c > 0 )
					echo '<a href="'.$this->admin_page->make_link( array( 'state' => 'deleted' ) ). '">Deleted Items <span class="count">(' . $c . ')</span></a><br />';
				else
					echo 'Deleted Items <span class="count">(' . $c . ')</span><br />';
			}
		} // }}}
		function show_pending() // {{{
		{
			$es = new entity_selector($this->admin_page->site_id);
			$es->add_type( $this->admin_page->type_id );
			$es->limit_tables();
			$es->limit_fields();
			$c = $es->get_one_count( 'Pending' );
			$this->pending_item_count = $c;
			if( !empty( $this->admin_page->request[ 'state' ] ) && $this->admin_page->request[ 'state' ] == 'pending' )
			{
				echo '<strong>Pending Items <span class="count">(' . $c . ')</span></strong><br />';
			}
			else
			{
				if( $c > 0 )
					echo '<a href="'.$this->admin_page->make_link( array( 'state' => 'pending' ) ). '">Pending Items <span class="count">(' . $c . ')</span></a><br />';
				else
					echo 'Pending Items <span class="count">(' . $c . ')</span><br />';
			}
		} // }}}
		function show_live() // {{{
		{
			$es = new entity_selector($this->admin_page->site_id);
			$es->add_type( $this->admin_page->type_id );
			// I was moving over the new_entity stuff and saw this hadn't been updated. I thought we were really looking to get this up, and I remember that it worked correctly on webdev, so I just moved these two lines over as well. If something is going wrong, it might be because of this. --Footie
			$es->set_sharing( 'owns' );
			$es->limit_tables();
			$es->limit_fields();
			//die( 'turned sharing to "owns"' );

			$c = $es->get_one_count( 'Live' );
			$this->live_item_count = $c;
			if( empty( $this->admin_page->request[ 'state' ] ) || $this->admin_page->request[ 'state' ] == 'live' )
			{
				echo '<strong>Current Items <span class="count">(' . $c . ')</span></strong><br />';
			}
			else
				echo '<a href="'.$this->admin_page->make_link( array( 'state' => 'live' ) ). '">Current Items <span class="count">(' . $c . ')</span></a><br />';
		} // }}}
		function show_sorting() // {{{
		{
			$fields = get_fields_by_type( $this->admin_page->type_id );
			$type = new entity( $this->admin_page->type_id );
			$state = !empty($this->admin_page->request['state']) ? $this->admin_page->request['state'] : false;
			if( ( $type->get_value('custom_sorter') || ( is_array($fields) && in_array( 'sort_order' , $fields ) ) ) && ($state == 'live' || !$state))
			{
				echo '<div class="viewInfo">'."\n";
				$link = $this->admin_page->make_link( array( 'cur_module' => 'Sorting' , 'default_sort' => true ) );
				echo '<a href="'.$link.'">Sort these items</a></div>'."\n";
			}
		} // }}}
	
		function get_views( ) // {{{
		{	
			$default_views = array();
			
			$ds = new entity_selector();
			$ds->add_type( id_of('view'));
			$ds->set_order( 'sortable.sort_order' );
			$ds->add_right_relationship( $this->admin_page->type_id , relationship_id_of( 'type_to_default_view' ) );
			$default_views = $ds->run_one();
			
			$ssvs = new entity_selector();
			$ssvs->add_type( id_of( 'view' ) );
			$ssvs->add_left_relationship( $this->admin_page->type_id , relationship_id_of( 'view_to_type' ) );
			$ssvs->add_left_relationship( $this->admin_page->site_id , relationship_id_of( 'view_to_site' ) );
			$ssvs->set_order( 'sortable.sort_order' );
			$site_specific_views = $ssvs->run_one();
			
			$this->views = $site_specific_views;
			foreach($default_views as $id=>$view)
			{
				if(!array_key_exists($id, $site_specific_views))
				{
					$this->views[$id] = $view;
				}
			}
			
			if( !empty( $this->admin_page->request[ 'lister' ] ) && array_key_exists($this->admin_page->request[ 'lister' ], $this->views) )
			{
				$view = $this->views[ $this->admin_page->request[ 'lister' ] ];
				if( $view->id() == $this->admin_page->request[ 'lister' ] )
				{
					$viewer_type = $view->get_left_relationship( 'view_to_view_type' );
				}
			}
			elseif( !empty($this->views) )
			{
				reset( $this->views );
				$view = current( $this->views );
				$viewer_type = $view->get_left_relationship( 'view_to_view_type' );
			}
			if(!empty($viewer_type))
			{
				reset($viewer_type);
				$this->viewer_entity = current($viewer_type);
			}
		} // }}}
		function init() // {{{
		{
			$this->verify_parameters();
			reason_include_once ( 'classes/filter.php' );
			reason_include_once ( 'classes/viewer.php' );
			reason_include_once ( 'classes/entity_selector.php' );
			reason_include_once ( 'content_listers/default.php3' );
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'lister_dropdown.js');
			include_once( CARL_UTIL_INC . 'basic/misc.php' );
			$this->admin_page->set_show( 'breadcrumbs' , false );

			$type = new entity( $this->admin_page->type_id );
			$this->admin_page->title = ( $type->get_value( 'plural_name' ) ? $type->get_value( 'plural_name' ) : $type->get_value('name') );
			if($icon_url = reason_get_type_icon_url($type,false))
			{
				$this->admin_page->title = '<img src="'.$icon_url.'" alt="" /> '.$this->admin_page->title;
			}
			$lister = isset($this->admin_page->request[ 'lister' ]) ? $this->admin_page->request[ 'lister' ] : '';
			if( !isset( $state ) OR !$state OR $state == 'live' )// actually listing entities{{{
			{
				$this->get_views();
				if( empty( $this->views ) )//add generic lister if not already present
					$this->views = array();
				else
				{
					if( empty( $lister ) )
					{
						reset( $this->views );
						$c = current( $this->views );
						$lister = $c->id();
						$this->admin_page->request[ 'lister' ] = $lister;
					}
				}	
			}
			
			$content_viewer = $GLOBALS[ '_content_lister_class_names' ][ 'default.php3' ];
			if( !isset( $this->admin_page->request[ 'state' ] ) || strtolower($this->admin_page->request[ 'state' ]) == 'live' )
			{	
				if( count( $this->views ) > 0 ) //grab appropriate viewer
				{
					if( $this->viewer_entity )
					{
						reason_include_once( 'content_listers/'.$this->viewer_entity->get_value( 'url' ) );
						$content_viewer = $GLOBALS[ '_content_lister_class_names' ][ $this->viewer_entity->get_value( 'url' ) ];
					}
				}
			}
				
			$this->viewer = new $content_viewer;
			
			// check if the viewer pays attention to hierarchy and if so, add collapse javascript
			// this is not exactly pretty, but better than including collapse.js on every page.
			if (array_key_exists('children',get_object_vars($this->viewer)))
			{
				$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'collapse.js');
			}
			
			$this->viewer->set_page( $this->admin_page );
			$this->viewer->init( $this->admin_page->site_id, $this->admin_page->type_id, isset($lister) ? $lister : '' ); // }}}
			$this->filter = new filter;
			$this->filter->set_page( $this->admin_page );
			$this->filter->grab_fields( $this->viewer->filters );
		} // }}}

		function run() // {{{
		{
			$this->list_header();
			$this->viewer->do_display();
			echo '<div class="paging">';
			$this->viewer->show_paging();
			echo '</div>';
			// echo '<a href="scripts/tab_delimited_export.php?site_id='.$this->admin_page->site_id.'&amp;type_id='.$this->admin_page->type_id.'">Export Spreadsheet (Tab Delimited)</a>';
		} // }}}
		
		/**
		 * Make sure conditions are okay for the lister to run and if not, redirect appropriately
		 */
		function verify_parameters()
		{
			if (empty($this->admin_page->site_id) && ($this->admin_page->cur_module == 'Lister') )
			{
				header('Location: ' . carl_make_redirect(array('cur_module' => '', 'site_id' => '')));
				exit;
			}
			if (empty($this->admin_page->type_id) && ($this->admin_page->cur_module == 'Lister') )
			{
				header('Location: ' . carl_make_redirect(array('cur_module' => '', 'type_id' => '')));
				exit;
			}
		}
	} // }}}
?>
