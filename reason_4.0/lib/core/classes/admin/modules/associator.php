<?php
/**
 * @package reason
 * @subpackage admin
 */
 
/**
 * Include the default module and other needed utilities
 */
reason_include_once('classes/admin/modules/default.php');
reason_include_once('classes/admin/modules/lister.php' );
reason_include_once('function_libraries/util.php');

	
/**
 * An administrative module that provides an interface to associate Reason entities
 */
class AssociatorModule extends DefaultModule // {{{
{
	var $viewer;
	var $filter;
	var $associations;
	protected $_locked = false;
	protected $_show_lock_info = false;
	protected $_rel_direction = 'right';

	function AssociatorModule( &$page ) // {{{
	{
		$this->admin_page =& $page;
	} // }}}
	
	function show_live() // {{{
	{
		$s = '<a href="'.$this->admin_page->make_link( $this->admin_page->request +  array( 'state' => 'live' ) ) . '">Back to main list</a><br /><br />';
		return $s;
	} // }}}
	function show_next_nodes() // {{{
	{
		$finish_link = $this->admin_page->make_link( array( 'cur_module' => 'Finish' ) );
		$edit = $this->admin_page->make_link( array( 'cur_module' => 'Editor' ) );
		
		echo '<a href="'.$edit.'">Back to Edit</a><br />';
		echo '<a href="'.$finish_link.'">Finish</a><br />';
	} // }}}
	function get_associations() // {{{
	{
		$d = new DBSelector;

		$d->add_table('ar','allowable_relationship' );
	
		$d->add_table( 'allowable_relationship' );
		$d->add_table( 'relationship' );
		$d->add_table( 'entity' );
		
		$d->add_relation( 'allowable_relationship.name = "site_to_type"' );
		$d->add_relation( 'allowable_relationship.id = relationship.type' );
		$d->add_relation( 'relationship.entity_a = '.$this->admin_page->site_id );
		$d->add_relation( 'relationship.entity_b = ar.relationship_b' );
		$d->add_relation( 'entity.id = ar.relationship_b' );
		
		$d->add_field( 'entity' , 'id' , 'e_id' );
		$d->add_field( 'entity' , 'name' , 'e_name' );
		$d->add_field('ar','*');

		$d->add_relation( 'ar.relationship_a = ' . $this->admin_page->type_id );
		if (reason_relationship_names_are_unique())
		{
			$d->add_relation('ar.type = "association"');
		}
		else
		{
			$d->add_relation('ar.name != "owns"');
		}
		$d->add_relation('(ar.custom_associator IS NULL OR ar.custom_associator = "")');
		$r = db_query( $d->get_query() , 'Error selecting relationships' );
		$return_me = array();
		while( $row = mysql_fetch_array( $r , MYSQL_ASSOC ) )
			$return_me[ $row[ 'id' ] ] = $row;
		$this->associations = $return_me;
		if( empty( $this->admin_page->rel_id ) )
		{
			reset( $this->associations );
			list( $key , ) = each( $this->associations );

			$this->admin_page->rel_id = $key;
		}
	} // }}}
	function list_associations() // {{{
	{
		foreach( $this->associations AS $id => $ass )
		{
			if( $id == $this->admin_page->rel_id )
			{
				$start = '<strong>';
				$finish = '</strong>';
			}
			else
			{
				$start = '<a href="' . $this->admin_page->make_link( array( 'rel_id' => $id ) ) . '">';
				$finish = '</a>';
			}
			echo $start . 'Associate with ' . $ass[ 'e_name' ] . $finish . '<br />';
		}
	} // }}}
	
	function should_run()
	{
		if(empty($this->admin_page->id))
			return false;
		else
			return true;
	}

	function init() // {{{
	{
		if(!$this->should_run())
		{
			trigger_error('Associator module needs an ID to run; none provided.');
			return;
		}
		reason_include_once( 'classes/filter.php' );
		reason_include_once( 'content_listers/associate.php' );
		include_once( CARL_UTIL_INC . 'basic/misc.php' );
		$this->head_items->add_javascript(JQUERY_URL, true);
		$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.'assoc.css');
		$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'table_update.js');
		$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'associator.js');
		$this->get_associations();
		if(empty($this->associations[ $this->admin_page->rel_id ]))
		{
			trigger_error( $this->admin_page->rel_id.' is not a valid relationship type id');
			die();
		}
		$current_assoc = $this->associations[ $this->admin_page->rel_id ];
		$type = new entity( $current_assoc[ 'e_id' ] );
		// save the type entity in an object scope
		$this->rel_type = carl_clone($type);
		$this->admin_page->title = 'Selecting ' . $type->get_value('name');
		
		$entity = new entity($this->admin_page->id);
		$user = new entity($this->admin_page->user_id);
		
		if(!$entity->user_can_edit_relationship( $this->admin_page->rel_id, $user, $this->_rel_direction ) )
		{
			$this->_locked = true;
		}
		elseif($entity->relationship_has_lock( $this->admin_page->rel_id, $this->_rel_direction ) && reason_user_has_privs($this->admin_page->user_id,'manage_locks') )
		{
			$this->_show_lock_info = true;
		}
		
		$this->get_views( $type->id() );
		if( empty( $this->views ) )//add generic lister if not already present
			$this->views = array();
		else
		{
			reset( $this->views );
			$c = current( $this->views );
			if( $c )
			{
				$lister = $c->id();
				$this->admin_page->request[ 'lister' ] = $lister;
			}
			else
				$lister = '';
		}
		$lister = ( isset($lister) ? $lister : '');
		$this->get_viewer( $this->admin_page->site_id, $type->id(), $lister );
		
		$this->filter = new filter;
		$this->filter->set_page( $this->admin_page );
		$this->filter->grab_fields( $this->viewer->filters );
	} // }}}
	function get_views( $type_id ) // {{{
	{
		$ds = new entity_selector();
		$ds->add_type( id_of('view'));
		$ds->limit_tables('sortable');
		$ds->limit_fields();
		$ds->set_order( 'sortable.sort_order' );
		$ds->add_right_relationship( $type_id , relationship_id_of( 'type_to_default_view' ) );
		$default_views = $ds->run_one();
		
		$ssvs = new entity_selector();
		$ssvs->add_type( id_of( 'view' ) );
		$ds->limit_tables('sortable');
		$ds->limit_fields();
		$ssvs->add_left_relationship( $type_id , relationship_id_of( 'view_to_type' ) );
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
	function get_viewer($site_id , $type_id , $lister) //{{{
	{
		$this->viewer = new assoc_viewer;
		$this->viewer->set_page( $this->admin_page );
		$this->viewer->set_relationship_lock_state( $this->_locked );
		$this->viewer->init( $site_id, $type_id , $lister ); 
	} // }}}

	
	function some_site_shares_type() // {{{
	{
		$sharables = $this->admin_page->get_sharable_relationships();
		return(isset($sharables[$this->rel_type->id()]));
	} // }}}
	function get_second_level_vars() // {{{
	{
		$new_vars = array(
			'site_id' => $this->admin_page->site_id,
			'type_id' => $this->rel_type->id(),
			'id' => '',
			'cur_module' => 'Editor',
			'rel_id' => '',
			'lister' => '',
			'new_entity' => 1,
		);
		foreach( $this->admin_page->request AS $key => $val )
			$new_vars[ CM_VAR_PREFIX.$key ] = $val;
		return $new_vars;	
	} // }}}
	function do_add_link() // {{{
	{
		$site = new entity($this->admin_page->site_id);
		if(!$this->rel_type->has_right_relation_with_entity($site, relationship_id_of( 'site_cannot_edit_type' )))
		{
			echo '<div class="addLink">'."\n";
			$type_name = $this->rel_type->get_value( 'name' );
			$new_vars = $this->get_second_level_vars();
			echo '<a href="'.$this->admin_page->make_link( $new_vars ).'">Add '.$type_name.'</a>';
			echo '</div>'."\n";
		
		}
	} // }}}
	function do_sharing_link() // {{{
	{
		echo '<div class="viewFilter">'."\n";
		$type_name = $this->rel_type->get_value( 'name' );
		if( $this->admin_page->cur_module == 'Associator' )
		{
			$new_vars = $this->get_second_level_vars();
			$new_vars[ 'cur_module' ] = 'Sharing';
			$new_vars[ 'new_entity' ] = '';
			echo '<a href="'.$this->admin_page->make_link( $new_vars ).'">Borrow '.$type_name.' from another site.</a><br />';
		}
		if( $this->admin_page->cur_module == 'Sharing' )
		{
			echo '<a href="'.$this->admin_page->make_link( array( 'cur_module' => 'Cancel' ) ).'">Back to Selecting '.$type_name.'.</a><br />';
		}
		echo '</div>'."\n";
	} // }}}
	
	function get_selected_jump_link()
	{
		if ($this->show_jump_links())
		{
			return '<a id="itemsSelect" name="itemsSelect" /></a><div class="jumpLinkTop"><span class="jumpToSelected"><a class="jump" href="#itemsSelected">Top</a></span></div>'."\n";
		}
		return '';
	}
	
	function get_select_jump_link()
	{
		if ($this->show_jump_links())
		{
			$verb = ($this->admin_page->cur_module == 'Sharing') ? 'Borrow' : 'Select';
			$noun = $this->rel_type->get_value('plural_name');
			return '<a id="itemsSelected" name="itemsSelected" /></a><div class="jumpLinkFind"><span class="jumpToSelected"><a class="jump" href="#itemsSelect">Find and '.$verb.' '.$noun.'</a></span></div>'."\n";
		}
		return '';
	}
	
	
	

	
	
	
	/**
	 * We want to show the jump links if we have more than 5 associated items.
	 */
	function show_jump_links()
	{
		if (!isset($this->_show_jump_links))
		{
			if (isset($this->viewer->ass_vals))
			{
				$this->_show_jump_links = (count($this->viewer->ass_vals) > 5);
			}
			else $this->show_jump_links = false;
		}
		return ($this->_show_jump_links);
	}
	
	function run() // {{{
	{
		if(!$this->should_run())
		{
			echo '<p>There is a problem with the link to this page. Please try to get to this page in another way.</p>';
			return;
		}
		
		echo '<div class="associatorModule">';
		if( !empty ($this->admin_page->request[ 'error_message' ]) )
		{
			$e_mess = array( 1 => 'This is a required relationship, You must first choose an item of this type to go with your entity',
						   );

			echo '<span class="words_error">' . $e_mess[ $this->admin_page->request[ 'error_message' ] ] . '</span><br />';
		}
		if($this->_locked)
		{
			echo '<div class="lockNotice"><img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="locked" width="12" height="12" /> This relationship is locked. If you need to attach or detach items, please contact an administrator.</div>';
		}
		elseif($this->_show_lock_info)
		{
			echo '<div class="lockNotice"><img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px_grey_trans.png" alt="locked" width="12" height="12" /> Note: this relationship is locked for some users.</div>';
		}
		$colspan = count( $this->viewer->columns ) + 1;
		
		// use plural type name
		echo $this->get_select_jump_link();
		$this->viewer->show_associated_items();
		if($this->_locked)
		{
			echo '</div>'."\n";
			return;
		}
		
		echo $this->get_selected_jump_link();
		echo '<div class="assocHead">Not Selected</div>'."\n";
		echo '<div class="assocFilters">';
		$list_mod = new ListerModule($this->admin_page);
		$list_mod->filter =& $this->filter;
		$list_mod->show_filters();
		echo '</div>';
		if( empty( $this->admin_page->request[ CM_VAR_PREFIX.'type_id' ] ) && $this->admin_page->cur_module == 'Associator' && reason_user_has_privs($this->admin_page->user_id, 'add') )
		{
			echo '<div class="assocAdd">';
			$this->do_add_link();
			echo '</div>'."\n";
		}
		
		$assoc_ok = !$this->admin_page->is_second_level() && $this->admin_page->cur_module == 'Associator' && $this->some_site_shares_type();
		$sharing_ok = $this->admin_page->is_second_level() && $this->admin_page->cur_module == 'Sharing';
		
		if( reason_user_has_privs($this->admin_page->user_id, 'borrow') && ( $assoc_ok || $sharing_ok ) )
		{
		echo '<div class="assocSharing">';
		$this->do_sharing_link();
		echo '</div>'."\n";
		}
		echo '<div class="assocList">';
		$this->viewer->do_display();
		echo '</div>'."\n";
		
		echo '</div>'."\n"; //associatorModule
	} // }}}
} // }}}	
?>