<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
	/**
	 * Register module with Reason and include dependencies
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PageTitleModule';

	/**
	 * A minisite module that displays the title of the current page
	 */
	class PageTitleModule extends DefaultMinisiteModule
	{
		var $_init_title;
	
		function init( $args = array() )
		{
			$this->_init_title = $this->parent->title;
			// register the module for inline editing
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$inline_edit->register_module($this, $this->user_can_inline_edit());
		}
		
		/**
		 * Determines whether or not the user can inline edit. Only admin users may 
		 * perform inline editing for the page title.
		 *
		 * @return boolean;
		 */
		function user_can_inline_edit()
		{
			if (!isset($this->_user_can_inline_edit))
			{
				// Additionally, check to see if the user has editing privileges for the 'name' field
				$page_entity = new entity($this->page_id);
				
				if($netid = reason_check_authentication())
				{
					if($user_id = get_user_id($netid))
					{
						$user = new entity($user_id);
						$field_check = $page_entity->user_can_edit_field('name', $user);
					}
				}
				$this->_user_can_inline_edit = ($netid && reason_check_access_to_site($this->site_id) && $field_check);
			}
			return $this->_user_can_inline_edit;
		}
	
		function has_content()
		{
			return !empty( $this->parent->title );
		}
		
		function run()
		{
			echo '<div class="pageTitle">'."\n";
			$inline_edit =& get_reason_inline_editing($this->page_id);
			if ($inline_edit->available_for_module($this) && ($this->_init_title == $this->parent->title) )
			{
				$this->run_editable();
			}
			else
			{
				echo $this->get_formatted_page_title();
			}
			echo '</div>'."\n";
		}
		
		function run_editable()
		{
			// Show a small disco inline editing form if it is activated, display the title
			// and an activation button if not.
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$active = $inline_edit->active_for_module($this);
			$class = ($active) ? 'editable editing' : 'editable';
			echo '<div class="'.$class.'">'."\n";
			if ($inline_edit->active_for_module($this))
			{												
				$form = new Disco();
				$form->strip_tags_from_user_input = true;
				$form->allowable_HTML_tags = '';
				$form->add_element('page_title', 'text');
				$form->set_value('page_title', $this->parent->title);
				$form->add_required('page_title');
				$form->add_callback(array(&$this, 'process_editable'),'process');
				$form->add_callback(array(&$this, 'where_to_editable'), 'where_to');
				$form->run();
			}
			else
			{
				$url = carl_make_link($inline_edit->get_activation_params($this));
				
				$link = '<p><a href="'.$url.'" class="editThis">Edit Page Title</a></p>'."\n";
				$pre = '<div class="editRegion">'."\n";
				$post = '</div>'."\n";
				$output = $pre . $this->get_formatted_page_title() . $link . $post;
				
				echo $output;
			}
			echo '</div>'."\n";
		}
		
		/**
		 * After we save the title change we also need to destroy the navigation cache for the site.
		 */
		function process_editable(&$disco)
		{
			$page = new entity($this->page_id);
			$values['name'] = trim(strip_tags($disco->get_value('page_title')));
			if ($page->get_value('name') != $values['name'])
			{
				$user_id = get_user_id(reason_check_authentication());
				reason_update_entity($this->page_id, $user_id, $values, true);
			
				// clear nav cache
				reason_include_once('classes/object_cache.php');
				$cache = new ReasonObjectCache($this->site_id . '_navigation_cache');
				$cache->clear();
			}
		}
		
		function where_to_editable(&$disco)
		{
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$url = carl_make_redirect($inline_edit->get_deactivation_params($this));
			return $url;
		}
	
		function get_formatted_page_title()
		{
			return '<h2 class="pageTitle"><span>'.$this->parent->title.'</span></h2>';
		}
		
	}
?>
