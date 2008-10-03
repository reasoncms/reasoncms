<?php
	reason_include_once( 'content_managers/parent_child.php3' );
	
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'MinisitePageManager';

	class MinisitePageManager extends parent_childManager
	{
		var $allow_creation_of_root_node = true;
		var $multiple_root_nodes_allowed = false;
		var $root_node_description_text = '-- Home Page --';
		var $parent_sort_order = 'sortable.sort_order ASC';
		function alter_data() // {{{
		{
			parent::alter_data();
			$this->_no_tidy[] = 'url_fragment';
			$this->_no_tidy[] = 'custom_page';
			$this->_no_tidy[] = 'extra_head_content';
			
			$this->set_allowable_html_tags('extra_head_content','all');

			$this->add_element( 'is_link', 'hidden' );
			if( !empty( $_REQUEST[ 'is_link' ] ) OR $this->get_value( 'url' ) )
				$this->set_value( 'is_link', true );
			else
				$this->set_value( 'is_link', false );
			$this->set_display_name( 'name', 'Title');
			$this->change_element_type( 'nav_display','select_no_sort' );
			$this->set_display_name( 'nav_display', 'Show this page in navigation' );
			if( !$this->get_value( 'nav_display' ) )
				$this->set_value( 'nav_display', 'Yes' );
				
			$this->set_comments( 'link_name', form_comment('If the page title is long, you can provide a shorter title for use in the site\'s navigation.<br /><em>Leave this field <strong>empty</strong> to use the full page title.</em>') );

			if( reason_user_has_privs( $this->admin_page->user_id, 'publish' ) )
			{
				if( $this->get_value( 'parent_id' ) != $this->admin_page->id AND !$this->is_new_entity() )
				{
					$this->change_element_type( 'state','select',array( 'options' => array( 'Live' => 'Live', 'Pending' => 'Pending' ) ) );
					$this->add_required( 'state' );
				} 
				elseif ( $this->is_new_entity() )
				{
					$this->add_element( 'state_action','select',array( 'options' => array( 'Live' => 'Live','Pending' => 'Pending' ), 'default' => 'Live' ) );
					$this->set_display_name( 'state_action', 'state' );
					$this->add_required( 'state_action' );
				}
			}	

			if ($this->entity->has_right_relation_of_type('minisite_page_parent') && $this->get_value('state') == 'Live')
			{
				$this->set_comments('state', form_comment('The state cannot be changed because this page has live children'));
				$this->change_element_type('state', 'solidtext');
				$this->remove_required('state');
			}

			// don't show the url for the root page.  it is defined by the site's base_url
			$roots = $this->root_node();
			if( $this->_id == $this->get_value( 'parent_id' ) || ($this->allow_creation_of_root_node && empty($roots) ) )
			{
				$this->change_element_type( 'url_fragment', 'hidden' );
				if(!$this->allow_creation_of_root_node)
				{
					$this->change_element_type( 'parent_id', 'hidden' );
				}
				$this->change_element_type( 'nav_display', 'hidden' );
				if(reason_user_has_privs( $this->admin_page->user_id, 'edit_home_page_nav_link'))
				{
					$site = new entity($this->admin_page->site_id);
					$this->set_comments('link_name',form_comment('The contents of this field will be used to indicate the home page in the site\'s navigation.<br />Leave this field <strong>empty</strong> to use the default text: <strong>'.$site->get_value('name').' Home</strong>'));
				}
				else
				{
					$this->change_element_type( 'link_name', 'hidden' );
				}
			}
			// if we have a subpage, show the url fragment field
			elseif( !$this->get_value( 'is_link' ) )
			{
				$this->set_display_name( 'url_fragment','Page URL Name' );
				$this->set_comments( 'url_fragment', form_comment('The page URL. Do not use spaces or an .html extension (ie "syllabus", "faculty_links", NOT "guestbook.html").') );
				$this->add_required( 'url_fragment' );
				$this->add_required( 'nav_display' );
			}

			if( !$this->get_value( 'is_link' ) )
			{
				$this->set_display_name( 'custom_page','Type of Page' );
				
				// for non-admin users
				if( !reason_user_has_privs( $this->admin_page->user_id, 'edit_head_items') )
				{
					$this->remove_element( 'extra_head_content' );
				}
				
				// for admin users
				if(reason_user_has_privs( $this->admin_page->user_id, 'assign_any_page_type'))
				{
					reason_require_once( 'minisite_templates/page_types.php' );
					$options = array();
					foreach( $GLOBALS['_reason_page_types'] AS $k => $asgneiosjk )
					{
						$options[ $k ] = prettify_string( $k );
					}
					$this->change_element_type( 'custom_page' , 'select' , array( 'options' => $options ) );
					$this->set_comments( 'custom_page', form_comment('<a href="'.REASON_HTTP_BASE_PATH.'scripts/page_types/view_page_type_info.php">Page type definitions</a>.') );

				}
				else
				{
					$this->alter_page_type_section();
				}
				
				$this->set_comments( 'name', form_comment('What should this page be called?') );
				$this->set_comments( 'author', form_comment('Source or original author of this page') );
				$this->set_comments( 'description', form_comment('A brief one or two sentence summary of the page') );
				$this->set_comments( 'keywords', form_comment('Comma-separated keywords (for search engines) ie "Dave, Hendler, College, Relations"') );
				$this->set_comments( 'nav_display', form_comment('If YES, the page shows up in the navigation box. If NO, the page does not.') );	
				$this->set_comments( 'parent_id', form_comment(''));
				$this->change_element_type( 'url', 'hidden' );

				
				// lokify the content box
				$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			}
			else
			{
				// loop thorugh all elements making them hidden, except for the important link fields
				$link_fields = array( 'name', 'url', 'parent_id', 'nav_display', 'description' );
				foreach($this->get_element_names() as $element_name)
				{
					if( !in_array( $element_name, $link_fields ) )
						$this->change_element_type( $element_name, 'hidden' );
				}
				
/*				foreach( $this->_elements AS $name => $el )
					if( !in_array( $name, $link_fields ) )
						$this->change_element_type( $name, 'hidden' ); */
				$this->add_required( 'url' );
				$this->set_comments( 'url', form_comment('The URL of the external link - should usually begin with http:// unless it is a link to a location within this site') );
				$this->set_comments( 'name', form_comment('The title of link displayed in your site\'s navigation.') );
				$this->set_comments( 'parent_id', form_comment('Use this field to choose the link\'s parent page.') );
			}
			$this->set_order(array('name', 'link_name', 'unique_name', 'author', 'description', 'keywords', 'url_fragment', 'extra_head_content', 'parent_id', 'nav_display', 'custom_page', 'content') );
			
		} // }}}
		function on_first_time() // {{{
		{
		} // }}}
		
		function alter_page_type_section()
		{
			$basic_options = array( 
				"default" => "Normal Page",
				"gallery" => 'Photo Gallery <span class="smallText">(Shows associated images in a gallery format)</span>',
				'show_children' => 'Shows children <span class="smallText">(Shows child pages in a list with their descriptions. Note: this includes pages not shown in navigation.)</span>',
				'show_siblings' => 'Shows siblings <span class="smallText">(Shows this page\'s sibling pages after the content of the page. Note: this includes pages not shown in navigation.)</span>',
			);
					
			$types_to_optional_pages = array(
				'form'=>array('form'=>'Form page <span class="smallText">(A form must be associated with page for this to work)</span>',),
				'publication_type'=>array('publication'=>'Blog/Publication page <span class="smallText">(A blog/publication must be associated with page for this to work)</span>',),
				'av'=>array('audio_video'=>'Media <span class="smallText">(Shows audio and/or video after the page content. At least one media work must be associated with page for this to work)</span>',),
				'external_url'=>array('feed_display_full'=>'Full-Page feed display <span class="smallText">Provides the contents of an RSS or Atom feed as the main content of the page. An external URL must be associated with the page for this to work.</span>','feed_display_sidebar'=>'Sidebar feed display <span class="smallText">Lists the contents of an RSS or Atom feed in the sidebar. An external URL must be associated with the page for this to work.</span>'),
			);
				
			if(!empty($types_to_optional_pages))
			{
				$es = new entity_selector();
				$es->add_type(id_of('type'));
				$es->add_right_relationship( $this->get_value('site_id'), relationship_id_of('site_to_type') );
				$es->add_relation( 'entity.unique_name IN ("'.implode('","',array_keys($types_to_optional_pages)).'")' );
				$types = $es->run_one();
				
				foreach($types as $type)
				{
					if(!empty($types_to_optional_pages[$type->get_value('unique_name')]))
					{
						foreach($types_to_optional_pages[$type->get_value('unique_name')] as $page_type=>$desc)
						{
							$basic_options[$page_type] = $desc;
						}
					}
				}
			}
			
			if ( !$this->get_value('custom_page') ) $this->set_value( 'custom_page', 'default' ); // set as default if no value
			
			if ( array_key_exists($this->get_value('custom_page'),$basic_options ) )
			{
				$this->change_element_type( 'custom_page' , 'radio_no_sort' , array( 'options' => $basic_options ) );
			}
			else $this->change_element_type( 'custom_page', 'solidtext' );	
		}
		
		function alter_tree_list( $list, $parent_id )
		{
			// remove external links from list of available parents
			// (except for actual parent of page for backwards compatibility)
			foreach( $list as $id=>$name )
			{
				if($id != $parent_id && !empty($id))
				{
					$e = new entity($id);
					if($e->get_value('url'))
					{
						unset($list[$id]);
					}
				}
			}
			return $list;
		}
		function run_error_checks() // {{{
		{
			if( !$this->get_value( 'is_link' ) && !in_array( $this->get_value( 'id' ) , $this->root_node() ) )
			{
				if( !$this->has_error( 'url_fragment' ) )
					if( !eregi( "^[0-9a-z_]*$" , $this->get_value('url_fragment') ) )
						$this->set_error( 'url_fragment', 'URLs may only contain letters, numbers, and underscores' );
				if( !$this->has_error( 'url_fragment' ) && !$this->has_error('parent_id') )
				{
					// get siblings.  make sure name is unique among siblings
					$es = new entity_selector( $this->get_value('site_id') );
					$es->add_type( id_of('minisite_page') );
					$es->add_left_relationship( $this->get_value('parent_id'), relationship_id_of('minisite_page_parent') );
					$es->add_left_relationship_field( 'minisite_page_parent', 'entity','id','parent_id' );
					$es->add_relation('entity.id != __entity__.id');
					$tmp = $es->run_one();
					$siblings = array();
					$unique_name = true;
					// loop through siblings checking url name against url name of this page
					foreach( $tmp AS $id => $sibling )
					{
						// don't match against self
						if( $id != $this->_id )
						{
							if( $sibling->get_value( 'url_fragment' ) == $this->get_value('url_fragment') )
								$unique_name = false;
							$siblings[] = $sibling->get_value('url_fragment');
						}
					}
					if( !$unique_name )
					{
						$this->set_error( 'url_fragment','Invalid URL Name.  Another sibling page shares this name.  Pick a unique name.' );
						$this->add_comments( 'url_fragment', form_comment('<font color="#ff0000"><strong>Used names:</strong> '.implode(', ', $siblings ).'</font>') );
					}
				}
				// make sure the page does not conflict with the asset and feed directory names
				// Asset dirname: MINISITE_ASSETS_DIRECTORY_NAME
				// Feeds dirname: MINISITE_FEED_DIRECTORY_NAME
				// This needs to be figured out more clearly
				/* if( !$this->has_error( 'url_fragment' ) )
				{
					$site = new entity( $this->get_value( 'site_id' ) );
					$es = new entity_selector( $site->id() );
					$es->add_type( id_of( 'minisite_page' ) );
					$parent_page = $es->run_one();
				} */
			}
		} // }}}
		function pre_show_form() // {{{
		{
			parent::pre_show_form();
			
			$roots = $this->root_node();
			if( $this->is_new_entity() && !$this->get_value( 'is_link' ) && !empty($roots))
				echo '&raquo; <a href="'.$this->admin_page->make_link( array( 'is_link' => 1 ) ).'">Create an external link instead of a page.</a><br /><br />';
		} // }}}
		function finish() // {{{
		{
			reason_include_once( 'function_libraries/URL_History.php' );
			
     		if( $this->get_value( 'state_action' ) )
			{
				$q = 'UPDATE entity set state = "'.$this->get_value( 'state_action' ).'" where id = ' . $this->admin_page->id;
				db_query( $q , 'Error finishing' );
			}

			// call parent finish function
			$res = parent::finish();

			update_URL_history( $this->get_value( 'id' ) );
			//include( REASON_INC.'micro_scripts/update_global_rewrites.php' );

			// finish up and return
			return $res;
		} // }}}
		function where_to() // {{{
		{
			if( $this->chosen_action == 'finish' && $this->get_value( 'fromweb' ) )
				return 'http://'.$_SERVER['HTTP_HOST'].$this->get_value( 'fromweb' );
			else
				return parent::where_to();
		} // }}}
	}
?>