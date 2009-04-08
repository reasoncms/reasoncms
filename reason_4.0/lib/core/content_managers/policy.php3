<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Include the parent class
	 */
	reason_include_once( 'content_managers/parent_child.php3' );
	
	/**
	 * Register module with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'PolicyManager';

	/**
	 * A content manager for policies
	 */
	class PolicyManager extends parent_childManager
	{
		var $allow_creation_of_root_node = true;
		var $multiple_root_nodes_allowed = true;
		var $root_node_description_text = '** Top-Level Policy (no parent) **';
		function alter_data() // {{{
		{
			parent::alter_data();
			
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			$this -> set_comments ("show_hide", form_comment('Note: hiding this policy will also hide its children.'));
			$this -> set_display_name ("show_hide", "Show or Hide?");
			if (!$this->get_value( 'show_hide' )) $this->set_value('show_hide', 'show');
			$this -> set_comments ("numbering_scheme", form_comment('This determines the way that the policy item\'s children items will be numbered.'));
			$this -> set_display_name ("numbering_scheme", "How to number children?");
			$this -> set_comments ("datetime", form_comment('If this policy was adopted on a specific date, please enter that date here (in the form mm/dd/yyy).'));
			$this -> set_display_name ("datetime", "Date Policy Adopted");
			if( $this->_id == $this->get_value( 'parent_id' ) )
			{
				$this->change_element_type( 'description', 'text');
				$this -> set_comments ("description", form_comment('Additional text that can be placed with the link to the policy'));
				//$this->change_element_type( 'parent_id', 'hidden');
			}
			else
			{
				$this -> set_display_name ("parent_id", "Parent Policy Item");
				$this->change_element_type( 'description', 'hidden');
				$this->change_element_type( 'keywords', 'hidden');
				$this->change_element_type( 'author', 'hidden');
				$this->change_element_type( 'datetime', 'hidden');
			}
			$this -> set_order (array ("name", "description", "content", "keywords", "datetime", "numbering_scheme", "sort_order", "author"));
		} // }}}
		
	}
?>
