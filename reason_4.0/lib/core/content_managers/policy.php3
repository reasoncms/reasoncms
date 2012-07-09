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
		function init_head_items()
		{
			parent::init_head_items();
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'content_managers/policy.js');
		}
		function alter_data() // {{{
		{
			parent::alter_data();
			
			$this -> set_display_name ('name', 'Title');
			$this -> set_display_name ('content', 'Policy Content');
			
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			$this -> set_comments ("show_hide", form_comment('Note: hiding this policy will also hide its children.'));
			$this -> set_display_name ("show_hide", "Show or Hide?");
			if (!$this->get_value( 'show_hide' )) $this->set_value('show_hide', 'show');
			$this -> set_comments ("numbering_scheme", form_comment('This determines the way that the policy item\'s children items will be numbered.'));
			$this -> set_display_name ("numbering_scheme", "How to number children?");
			$this -> set_display_name ("parent_id", 'Parent Policy');
			$this->change_element_type( 'description', 'text');
			
			$this->set_comments ("description", form_comment('Additional text that can be placed with the link to the policy'));
			$this->change_element_type( 'approvals' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id));
			$this->set_element_properties( 'approvals', array('rows'=>5));
			if($rel_id = relationship_id_of('policy_to_access_group'))
				$this->add_relationship_element( 'limit_access', id_of('group_type'), $rel_id, 'right', 'select');
			if($rel_id = relationship_id_of('policy_to_relevant_audience'))
				$this->add_relationship_element( 'audiences', id_of('audience_type'), $rel_id, 'right', 'checkbox', false);
			
			$this->set_order (array ('parent_id', 'name', 'description', 'content', 'approvals', 'last_revised_date', 'last_reviewed_date', 'keywords', 'audiences', 'numbering_scheme', 'limit_access', 'show_hide', 'no_share'));
		} // }}}
		
	}
?>
