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
		$inline_edit =& get_reason_inline_editing($this->page_id);
		if ($inline_edit->available_for_module($this)) return true;
		else
		{
			return parent::has_content();
		}
	}
	
	function run()
	{
		$inline_edit =& get_reason_inline_editing($this->page_id);
		if ($inline_edit->available_for_module($this))
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
		$inline_edit =& get_reason_inline_editing($this->page_id);
		$active = $inline_edit->active_for_module($this);
		$class = ($active) ? 'editable editing' : 'editable';
		echo '<div id="pageContent" class="'.$class.'">'."\n";
		if( $active )
		{
			$form = new Disco();
			$form->strip_tags_from_user_input = true;
			$form->allowable_HTML_tags = REASON_DEFAULT_ALLOWED_TAGS;
			$form->actions = array('save' => 'Save', 'save_and_finish' => 'Save and Finish Editing');
			$form->add_element('editable_content', html_editor_name($this->site_id), html_editor_params($this->site_id, $this->get_html_editor_user_id()));
			$form->set_display_name('editable_content',' ');
			$form->set_value('editable_content', $this->content );
			$form->add_callback(array(&$this, 'process_editable'),'process');
			$form->add_callback(array(&$this, 'where_to_editable'), 'where_to');
			$form->run();
		}
		else
		{
			$url = carl_make_link($inline_edit->get_activation_params($this));
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
	
	function process_editable(&$disco)
	{
		$values['content'] = tidy($disco->get_value( 'editable_content' ));
		$archive = ($disco->get_chosen_action() == 'save_and_finish') ? true : false;
		reason_update_entity( $this->page_id, $this->get_update_entity_user_id(), $values, $archive );
	}
	
	function where_to_editable(&$disco)
	{
		if( $disco->get_chosen_action() == 'save' )
		{
			$url = get_current_url();
		}
		else
		{
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$url = carl_make_redirect($inline_edit->get_deactivation_params($this));
		}
		return $url;
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
?>