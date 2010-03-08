<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class, include dependencies, & register module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/content_base.php' );
	include_once( DISCO_INC . 'disco.php' );
	reason_include_once( 'function_libraries/admin_actions.php' );
	reason_include_once( 'classes/inline_editing.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EditableContentModule';

	/**
	 * A minisite template that enables editing of the current page's content
	 */
	class EditableContentModule extends ContentModule
	{
		var $cleanup_rules = array(
			'inline_page_edit' => array( 'function' => 'turn_into_int' ),
			'editable_content' => array( 'function' => 'turn_into_string' ),
		);
		
		// register myself as an editable module
		function init( $args = array() )
		{
			parent::init( $args );
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$inline_edit->register_module($this, $this->user_can_inline_edit());
		}
		
		/**
		 * If inline editing is available and enabled lets return true
		 */
		function has_content()
		{
			if ($this->inline_editing_active()) return true;
			else
			{
				return parent::has_content();
			}
		}
		
		function run()
		{
			if ($this->inline_editing_active())
			{
				$this->run_editable();
			}
			else parent::run();
		}
		
		/**
		 * Present an interface to edit / create content
		 */
		function run_editable()
		{
			echo '<div id="pageContent" class="editable">'."\n";
			if( !empty( $this->request[ 'inline_page_edit' ] ) )
			{
				$form = new EditContentForm;
				$form->init();
				// We use the name 'editable_content' instead of 'content' because on the front end, every page has an anchor named 'content', which
				// would conflict and cause problems for things like document.getElementById.
				$form->change_element_type( 'editable_content' , html_editor_name($this->site_id) , html_editor_params($this->site_id, $this->get_html_editor_user_id()) );
				$form->set_display_name('editable_content',' ');
				$form->set_value( 'editable_content', $this->content );
				$form->set_value( 'inline_page_edit', 1 );
				$form->set_page_id( $this->page_id );
				$form->set_user_id( $this->get_update_entity_user_id() );
				$form->run();
			}
			else
			{
				$url = carl_make_link(array('inline_page_edit' => '1'));
				if (!carl_empty_html($this->content))
				{
					$link = '<p><a href="'.$url.'" class="editThis">Edit Content</a></p>';
					$pre = '<div class="editRegion">';
					$post = '</div>';
					$output = $pre . $this->content . $link . $post;
				}
				else
				{
					$link = '<p><a href="'.$url.'" class="editThis">Create Content</a></p>';
					$pre = '<div class="editRegion">';
					$post = '</div>';
					$output = $pre . $link . $post;
				}
				echo $output;
			}
			echo '</div>'."\n";
		}
		
		/**
		 * Returns true if inline editing is active and the user has permissions to inline edit
		 *
		 * @return boolean
		 */
		function inline_editing_active()
		{
			$inline_editing =& get_reason_inline_editing($this->page_id);
			return ($inline_editing->is_inline_editing_enabled() && $this->user_can_inline_edit());			
		}
		
		/**
		 * Determines whether or not the user can inline edit.
		 *
		 * Returns true in two cases:
		 *
		 * 1. User is a site administrator of the page.
		 * 2. A site user entity attached to the page has the username of the logged in user.
		 *
		 * @return boolean;
		 */
		function user_can_inline_edit()
		{
			if (!isset($this->_user_can_inline_edit))
			{
				$this->_user_can_inline_edit = (reason_check_access_to_site($this->site_id) || $this->get_site_user());
			}
			return $this->_user_can_inline_edit;
		}
		
		/**
		 * @return obj site user entity that corresponds to the logged in user or false if it does not exist
		 */
		function get_site_user()
		{
			if (!isset($this->_site_user))
			{
				if ($net_id = reason_check_authentication())
				{
					$es = new entity_selector( $this->site_id );
					$es->add_type( id_of( 'site_user_type' ) );
					$es->add_relation( 'entity.name = "'.$net_id.'"' );
					$es->add_right_relationship( $this->page_id, relationship_id_of( 'page_to_site_user' ) );
					$es->set_num(1);
					$result = $es->run_one();
					$this->_site_user = ($result) ? reset($result) : false;
				}
				else $this->_site_user = false;
			}
			return $this->_site_user;
		}
		
		/**
		 * @return int reason user entity that corresponds to logged in user or 0 if it does not exist
		 */
		function get_html_editor_user_id()
		{
			if ($net_id = reason_check_authentication())
			{
				$reason_id = get_user_id($net_id);
				if (!empty($reason_id)) return $reason_id;
			}
			return 0;
		}
		
		/**
		 * @return int reason user entity or id of site_user entity that corresponds to logged in user
		 */
		function get_update_entity_user_id()
		{
			if ($net_id = reason_check_authentication())
			{
				$reason_id = get_user_id($net_id);
				if (!empty($reason_id)) return $reason_id;
				elseif ($site_user = $this->get_site_user()) return $site_user->id();
			}
			return false;
		}
	}
	
	class EditContentForm extends Disco
	{
		var $strip_tags_from_user_input = true;
		var $allowable_HTML_tags = REASON_DEFAULT_ALLOWED_TAGS;
		
		var $elements = array(
			'editable_content' => 'textarea',
			'inline_page_edit' => 'hidden',
		);
		var $actions = array(
			'save' => 'Save',
			'save_and_finish' => 'Save and Finish Editing',
		);
		var $page_id; // private, use getter/setter methods
		var $user_id; // private, use getter/setter
		
		function finish()
		{
			$id = $this->get_page_id();
			$values['content'] = tidy($this->get_value( 'editable_content' ));
			$archive = ($this->chosen_action == 'save_and_finish') ? true : false;
			reason_update_entity( $id, $this->get_user_id(), $values, $archive );
		}
		function where_to()
		{
			if( $this->chosen_action =='save' )
			{
				$url = get_current_url();
			}
			else
			{
				$url = carl_make_redirect(array('inline_page_edit' => ''));
			}
			return $url;
		}
		function get_page_id()
		{
			return $this->page_id;
		}
		function set_page_id( $id )
		{
			$this->page_id = $id;
		}
		function get_user_id()
		{
			return $this->user_id;
		}
		function set_user_id( $id )
		{
			$this->user_id = $id;
		}
	}
?>
