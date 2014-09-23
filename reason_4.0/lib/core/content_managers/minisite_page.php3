<?php
/**
 * @package reason
 * @subpackage content_managers
 */
 
/**
 * Register the content manager with Reason
 */
reason_include_once( 'content_managers/parent_child.php3' );
reason_include_once('classes/url_manager.php');
reason_include_once('classes/page_types.php');
reason_include_once('minisite_templates/page_types.php');
reason_require_once( 'minisite_templates/page_types.php' );
reason_include_once( 'classes/plasmature/head_items.php' );
include_once( DISCO_INC . 'plugins/input_limiter/input_limiter.php' );

$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'MinisitePageManager';

/**
 * A content manager for minisite pages
 */
class MinisitePageManager extends parent_childManager
{
	var $allow_creation_of_root_node = true;
	var $multiple_root_nodes_allowed = false;
	var $root_node_description_text = '-- Home Page --';
	var $parent_sort_order = 'sortable.sort_order ASC';
	
	function init( $externally_set_up = false)
	{
		parent::init($externally_set_up);
		// If we're creating an external link rather than a page, change the page title accordingly
		if (!$this->has_url())
		{
			if ( !($this->get_value( 'name' ) ) AND !(strlen($this->get_value( 'name' )) > 0))
				$this->admin_page->title = 'Adding Link';
			else
				$this->admin_page->title = 'Editing "'.$this->get_value('name').'" (Link)';
		}
	}
	
	function init_head_items()
	{
		parent::init_head_items();
		if ($this->has_url()) {
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.
				'content_managers/page_parent_url.js');
		}
		$this->head_items->add_stylesheet(REASON_ADMIN_CSS_DIRECTORY.
				'content_managers/minisite_page.css');
		$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.
				'content_managers/page.js');
	}
	
	/**
	 * Returns true if the page gets its own URL in the hierarchy of its
	 * minisite, and false if otherwise.
	 *
	 * @author Eric Naeseth <enaeseth@gmail.com>
	 */
	function has_url()
	{
		return !$this->get_value('is_link');
	}
	
	/**
	 * Converts the given array returned by {@link get_available_parents()}
	 * into an array that maps entity ID's to paths.
	 * 
	 * @author Eric Naeseth <enaeseth@gmail.com>
	 */
	function build_path_map($available_parents)
	{
		$map = array();
		$site = new Entity($this->get_value('site_id'));
		$root = rtrim($site->get_value('base_url'), '/');
		
		$count = count($available_parents);
		for ($i = 0; $i < $count; $i++) {
			$map += $this->_build_path_map_fragment($root,
				$available_parents[$i]);
		}
		
		return $map;
	}
	
	/**
	 * @access private
	 * @author Eric Naeseth <enaeseth@gmail.com>
	 */
	function _build_path_map_fragment($path, &$entry)
	{
		$fragment = array();
		$entity =& $entry[0];
		
		if (!$entity)
			return $fragment;
		
		if ($entity->get_value('url_fragment')) {
			$path = implode('/',
				array($path, $entity->get_value('url_fragment')));
		}
		
		$fragment[$entity->id()] = $path.'/';
		$child_count = count($entry[1]);
		for ($i = 0; $i < $child_count; $i++) {
			$fragment += $this->_build_path_map_fragment($path,
				$entry[1][$i]);
		}
		
		return $fragment;
	}
	
	function _get_deprecated_modules()
	{
		if(isset($GLOBALS['_reason_deprecated_modules']))
			return $GLOBALS['_reason_deprecated_modules'];
		else
			return array();
	}
	
	function alter_data()
	{
		$fields = array('name', 'link_name', 'parent_id', 'parent_info', 'url_fragment', 'custom_page', 'page_type_note', 'content', 'visibility_heading', 'state', 'state_action', 'nav_display', 'indexable','metadata_heading', 'author', 'description', 'keywords', 'administrator_section_heading', 'extra_head_content_structured', 'extra_head_content', 'unique_name');
		
		parent::alter_data();
		$this->_no_tidy[] = 'url_fragment';
		$this->_no_tidy[] = 'custom_page';
		$this->_no_tidy[] = 'extra_head_content';
		$this->_no_tidy[] = 'extra_head_content_structured';
		
		$this->set_allowable_html_tags('extra_head_content','all');
		$this->set_allowable_html_tags('extra_head_content_structured','all');

		$this->add_element( 'is_link', 'hidden' );
		if( !empty( $_REQUEST[ 'is_link' ] ) OR $this->get_value( 'url' ) )
			$this->set_value( 'is_link', true );
		else
			$this->set_value( 'is_link', false );

		$this->set_display_name( 'name', 'Title');
		$this->set_display_name('link_name', 'Title Used in Navigation');
		$this->set_element_properties( 'link_name', array('size' => 25) );
		$this->set_comments( 'link_name', form_comment('If the page title is long, you can shorten the title for use in the site\'s navigation.') );
		
		// don't show the url for the root page.  it is defined by the site's base_url
		$roots = $this->root_node();
		if( $this->_id == $this->get_value( 'parent_id' ) || ($this->allow_creation_of_root_node && empty($roots) ) )
		{
			if ($this->_id == $this->get_value( 'parent_id' )) 
			{
				$this->change_element_type( 'url_fragment', 'hidden' );
				$this->change_element_type( 'nav_display', 'hidden' ); //
			}
			if(!$this->allow_creation_of_root_node)
			{
				$this->change_element_type( 'parent_id', 'hidden' );
			}
			if(reason_user_has_privs( $this->admin_page->user_id, 'edit_home_page_nav_link'))
			{
				$site = new entity($this->admin_page->site_id);
				$this->set_comments('link_name',form_comment('This is the text of the home page link in the site\'s navigation.'));
				if (!$this->get_value('link_name')) $this->set_value('link_name', $site->get_value('name').' Home');
			}
			else
			{
				$this->change_element_type( 'link_name', 'hidden', array('userland_changeable' => true) );
			}
		}
		// if we have a subpage, show the url fragment field
		elseif( $this->has_url() && !($this->get_value( 'id' ) == $this->get_value('parent_id')))
		{
			$this->set_element_properties( 'url_fragment', array('size' => 12) );
			$this->set_display_name( 'url_fragment', 'Page URL' );
			// Note that the contents of the url_comment_replace block are replaced by javascript to indicate
			// a slight sematic difference in the behavior of the field when javascript is enabled.
			// You may need to change the javascript to see any wording change here.
			$this->set_comments( 'url_fragment', form_comment('<span class="url_comment_replace">The final part of the page\'s Web address.</span> <span class="rules">Only use letters and numbers; separate words with hyphens (-). Please avoid upper-case letters.</span>') );
			$this->add_required( 'url_fragment' );
			$this->_add_page_url_elements($this->_available_parents);
		}

		if (!$this->get_value('link_name')) $this->set_value('link_name', $this->get_value('name'));
		
		$this->change_element_type( 'nav_display','radio_no_sort', array(
			'options'=>array(
				'Yes'=>'Include this page in the site\'s navigation links',
				'No'=>'Hide this page from navigation (page will still be accessible by URL)'),
			'display_name'=>'Navigation',
				));
		if( !$this->get_value( 'nav_display' ) ) $this->set_value( 'nav_display', 'Yes' );
		
		$add = 'Allow search engines to add this page to their index';
		$site = new entity( $this->get_value( 'site_id' ) );
		if ($site->get_value('site_state') != 'Live') $add .= ' (once this site has been made live)';
		$this->change_element_type( 'indexable','radio_no_sort', array(
			'options'=>array(
				1=>$add,
				0=>'Ask seach engines to ignore this page and the links it contains'),
			'display_name'=>'Search Engines',
				));
			
		$visibility_text = '<h4>Visibility</h4><p>These options control how easily people can find this page. 
			<em>If this page should not be visible to the public,</em> you should set State = Pending'; 
		if ($this->has_association('page_to_access_group'))
			$visibility_text .= ' or use Restrict Access in the sidebar to attach an access group.';
		else
			$visibility_text .= ' or contact your Reason administrator about adding access groups to your site.';
			
		$this->add_element('visibility_heading', 'comment', array('text' => $visibility_text));

		$this->add_element('metadata_heading', 'comment', array('text' => '<h4>Metadata</h4>
			<p>These fields provide additional information about the page, but are typically not displayed
			to site visitors. Description and Keywords are visible to search engines, and can affect how 
			easily this page can be found by searching.</p>'));
		
		if( reason_user_has_privs( $this->admin_page->user_id, 'publish' ) )
		{
			if( $this->get_value( 'parent_id' ) != $this->admin_page->id AND !$this->is_new_entity() )
			{
				$this->change_element_type( 'state','select',array( 'options' => array( 'Live' => 'Live', 'Pending' => 'Pending' ) ) );
				$this->add_required( 'state' );
			} 
			/* Why are we creating a special new state element for new pages? Figure out if we can
			   just use the state element all the time. */
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
		} else {
			$this->set_comments('state', form_comment('LIVE pages are published on your site. PENDING pages are not published, and are only visible under Pending Items in the Reason page list.'));			
		}
				
		if( $this->has_url() )
		{
			$this->set_display_name( 'custom_page','Type of Page' );
			
			// for non-admin users
			if( !reason_user_has_privs( $this->admin_page->user_id, 'edit_head_items') )
			{
				$this->remove_element( 'extra_head_content' );
				$this->remove_element( 'extra_head_content_structured' );
			}
			else
			{
				$this->change_element_type( 'extra_head_content_structured', 'head_items' );
				$this->set_display_name('extra_head_content_structured','Header Items');
				$this->set_display_name('extra_head_content','Additional Header Content');
				$this->add_comments('extra_head_content',form_comment('If you need to add headers that are not simple css or javascript, enter raw HTML header markup here.'));
			}
			
			$this->alter_page_type_section();
			
			// for admin users
			$rpts =& get_reason_page_types();
			if(reason_user_has_privs( $this->admin_page->user_id, 'assign_any_page_type'))
			{
				$options = array(''=>'--');
				$pts = $rpts->get_page_types();
				$deprecated_mods = $this->_get_deprecated_modules();
					
				foreach( $pts AS $pt) 
				{
					$options[ $pt->get_name() ] = prettify_string( $pt->get_name() );
					
					if ($pt->has_module($deprecated_mods))
					{
						$options[$pt->get_name()] .= ' (deprecated)';
					}

				}
				ksort($options);
				$primary = $this->get_element_property('custom_page', 'options');
				if(!empty($primary))
					$this->change_element_type( 'custom_page' , 'radio_with_other_no_sort' , array( 'options' => $primary, 'other_options' => $options ) );
				else
					$this->change_element_type('custom_page' , 'select_no_sort', array( 'options' => $options ));
				$this->set_comments( 'custom_page', form_comment('<a href="'.REASON_HTTP_BASE_PATH.'scripts/page_types/view_page_type_info.php">Page type definitions</a>.') );

			}
			
			$page_type_for_note = $this->get_value('custom_page') ? $this->get_value('custom_page') : 'default';
			if($pt = $rpts->get_page_type( $page_type_for_note ) )
			{
				if($note = $pt->meta('note'))
				{
					$this->add_element('page_type_note','commentWithLabel',array('text'=>'<div class="note">'.$note.'</div>'));
					$this->set_display_name('page_type_note', 'Note');
				}
			}
			
			$this->set_comments( 'name', form_comment('What should this page be called?') );
			$this->set_comments( 'author', form_comment('Source or original author of this page') );
			$this->set_comments( 'description', form_comment('A brief summary of the page. For best results when the page is indexed by search engines, try to not exceed 156 characters.') );
			$this->set_comments( 'keywords', form_comment('Comma-separated keywords (for search engines) ie "Dave, Hendler, College, Relations"') );
			$this->set_comments( 'parent_id', form_comment(''));
			$this->change_element_type( 'url', 'hidden' );

			
			// lokify the content box
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
		}
		else
		{
			// loop through all elements making them hidden, except for the important link fields
			$fields = array( 'name', 'url', 'parent_id', 'nav_display', 'description', 'administrator_section_heading', 'unique_name', );
			foreach($this->get_element_names() as $element_name)
			{
				if( !in_array( $element_name, $fields ) )
					$this->change_element_type( $element_name, 'hidden', array('userland_changeable' => true) );
			}
			
			$this->set_element_properties( 'nav_display', array(
				'options'=>array(
					'Yes'=>'Include this link in the site\'s navigation',
					'No'=>'Hide this link in the navigation')
				));
			$this->add_required( 'url' );
			$this->set_comments( 'url', form_comment('The URL of the external link - should usually begin with http:// unless it is a link to a location within this site') );
			$this->set_comments( 'name', form_comment('The title of link displayed in your site\'s navigation.') );
			$this->set_comments( 'parent_id', form_comment('Use this field to choose the link\'s parent page.') );
			$this->set_comments( 'description', form_comment('A brief description for this link; only displayed if the parent page shows its children.') );
		}
		
		// Suggest a limit of 156 characters so that google will display the complete description.
		$this->change_element_type('description', 'textarea', array('rows' => 4));
		$limiter = new DiscoInputLimiter($this);
		$limiter->suggest_limit('description', 156);
		$limiter->auto_show_hide('description', false);
		
		$administrator_fields = array('extra_head_content_structured', 'extra_head_content', 'unique_name');
		$has_administrator_field = false;
		foreach($administrator_fields as $field)
		{
			if($this->is_element($field) && !$this->element_is_hidden($field) )
			{
				$has_administrator_field = true;
				break;
			}
		}
		if($has_administrator_field)
		{
			$this->add_element('administrator_section_heading', 'comment', array('text' => '<h4>Administrator Tools</h4>'));
		}
				
		$this->set_order($fields);
	}
	
	function _add_page_url_elements($parents)
	{
		foreach($this->build_path_map($parents) as $id=>$path)
		{
			$this->add_element('path_to_'.$id, 'protected');
			$this->set_value('path_to_'.$id, $path);
		}
	}
	
	function on_first_time()
	{
	}
	
	function alter_page_type_section()
	{
		$basic_options = array( 
			'default' => 'Normal Page',
			'gallery' => 'Photo Gallery <span class="smallText">(Shows associated images in a gallery format)</span>',
			'show_children' => 'Shows children <span class="smallText">(Shows child pages in a list with their descriptions. Note: this includes pages not shown in navigation.)</span>',
			'show_siblings' => 'Shows siblings <span class="smallText">(Shows this page\'s sibling pages after the content of the page. Note: this includes pages not shown in navigation.)</span>',
		);
				
		$types_to_optional_pages = array(
			'form'=>array('form'=>'Form page <span class="smallText">(A form must be associated with page for this to work)</span>',),
			'publication_type'=>array('publication'=>'Blog/Publication page <span class="smallText">(A blog/publication must be associated with page for this to work)</span>',),
			'av'=>array('audio_video'=>'Media <span class="smallText">(Shows audio and/or video after the page content. At least one media work must be associated with page for this to work)</span>',),
			'external_url'=>array('feed_display_full'=>'Full-Page feed display <span class="smallText">Provides the contents of an RSS or Atom feed as the main content of the page. An external URL must be associated with the page for this to work.</span>','feed_display_sidebar'=>'Sidebar feed display <span class="smallText">Lists the contents of an RSS or Atom feed in the sidebar. An external URL must be associated with the page for this to work.</span>'),
			'text_blurb'=>array('sidebar_blurb'=>'Sidebar blurbs <span class="smallText">(Shows blurbs in the sidebar instead of images)</span>',),
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
		
		if ( array_key_exists($this->get_value('custom_page'),$basic_options ) || reason_user_has_privs( $this->admin_page->user_id, 'assign_any_page_type') )
		{
			$this->change_element_type( 'custom_page' , 'radio_no_sort' , array( 'options' => $basic_options ) );
		}
		else 
		{
			$this->change_element_type( 'custom_page', 'solidtext' );
		}
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
	
	function run_error_checks()
	{
		if( $this->has_url() && !in_array( $this->get_value( 'id' ) , $this->root_node() ) )
		{
			if( !$this->has_error( 'url_fragment' ) )
				if( !preg_match( "|^[0-9a-z_\-]*$|i" , $this->get_value('url_fragment') ) )
					$this->set_error( 'url_fragment', 'URLs may only contain letters, numbers, hyphens, and underscores' );
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
	}
	
	function pre_show_form()
	{
		parent::pre_show_form();
		
		$roots = $this->root_node();
		if( $this->is_new_entity() && $this->has_url() && !empty($roots))
			echo '&raquo; <a href="'.$this->admin_page->make_link( array( 'is_link' => 1, 'parent_id' => $this->get_value('parent_id') ) ).'">Create an external link instead of a page.</a><br /><br />';
	}
	
	function finish()
	{
		reason_include_once( 'function_libraries/URL_History.php' );
		
		if( $this->get_value( 'state_action' ) )
		{
			$q = 'UPDATE entity set state = "'.$this->get_value( 'state_action' ).'", new=0 where id = ' . $this->admin_page->id;
			db_query( $q , 'Error finishing' );
			$this->set_value('state', $this->get_value('state_action'));
		}
			
		if ($this->has_new_parent() || $this->has_new_url_fragment() || $this->state_has_changed())
		{
			// call parent finish function - this changes the parent if there is a new parent
			$res = parent::finish();
			if (($this->get_value('state') != 'Pending') && $this->has_url()) update_URL_history( $this->get_value( 'id' ) );
			
			// update rewrites if it is not set as a finish action - maintains backwards compatibility in case reason 4 beta 8
			// upgrade scripts have not been run.
			$type = new entity($this->admin_page->type_id);
			if ($type->get_value('finish_actions') != 'update_rewrites.php') 
			{
				$urlm = new url_manager($this->admin_page->site_id);
				$urlm->update_rewrites();
			}
			else
			{
				$script_url = REASON_HTTP_BASE_PATH . 'scripts/upgrade/4.0b7_to_4.0b8/remove_rewrite_finish_actions.php';
				trigger_error('It appears you still need to run the remove_rewrites_finish_actions.php upgrade script located at ' . $script_url);
			}
		}
		
		if ($this->has_new_parent() || $this->has_new_url_fragment() || $this->state_has_changed() || $this->has_new_link_name() || $this->has_new_name() || $this->nav_display_changed())
		{
			reason_include_once('classes/object_cache.php');
			$cache = new ReasonObjectCache($this->admin_page->site_id . '_navigation_cache');
			$cache->clear();
		}
		
		return true;
	}
	
	function has_new_link_name()
	{
		return ($this->entity->get_value('link_name') != $this->get_value('link_name'));
	}
	
	function has_new_name()
	{
		return ($this->entity->get_value('name') != $this->get_value('name'));
	}
	
	function has_new_url_fragment()
	{
		if (!isset($this->_has_new_url_fragment))
		{
			$this->_has_new_url_fragment = ($this->entity->get_value('url_fragment') != $this->get_value('url_fragment'));
		}
		return $this->_has_new_url_fragment;
	}
	
	function state_has_changed()
	{
		if (!isset($this->_state_has_changed))
		{
			$this->_state_has_changed = ( ($this->entity->get_value('state') != $this->get_value('state')) || $this->get_value('state_action') );
		}
		return $this->_state_has_changed;
	}
	
	function nav_display_changed()
	{
		if (!isset($this->_nav_display_changed))
		{
			$this->_nav_display_changed = ($this->entity->get_value('nav_display') != $this->get_value('nav_display'));
		}
		return $this->_nav_display_changed;
	}
	
	function process()
	{
		//Avoid home pages having url fragments
		if ($this->get_value( 'id' ) == $this->get_value('parent_id')){
			$this->set_value('url_fragment', '');
			$this->set_value('nav_display', 'Yes');
		}
		
		$site = new entity($this->admin_page->site_id);
		if ($this->get_value('link_title') == $this->get_value('name') ||
			$this->get_value('link_title') == $site->get_value('name').' Home')
			$this->set_value('link_title', '');
		
		parent::process();
	}
	
	/**
	 * @todo remove support for relative fromweb value in Reason 4 RC1
	 */
	function where_to()
	{
		if( $this->chosen_action == 'finish' && $this->get_value( 'fromweb' ) )
		{
			$fromweb = $this->get_value('fromweb');
			if ( (strpos($fromweb, 'http://') === FALSE) && (strpos($fromweb, 'https://') === FALSE) ) // is fromweb relative?
			{
				return 'http://'.$_SERVER['HTTP_HOST'].$this->get_value( 'fromweb' );
			}
			else return $this->get_value('fromweb');
		}
		else
			return parent::where_to();
	}
}
?>
