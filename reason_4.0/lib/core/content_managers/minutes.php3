<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register the content manager with Reason
	 */
	reason_include_once( 'content_managers/parent_child.php3' );
	
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'MinuteManager';

	/**
	 * A content manager for minutes
	 */
	class MinuteManager extends ContentManager
	{
		function alter_data() // {{{
		{
			parent::alter_data();
			
			$this -> set_display_name ("datetime", "Meeting Date &amp; Time");
			$this->add_required( 'datetime' );
			$this -> set_comments ("datetime", form_comment('mm/dd/yyyy'));
			$this -> set_display_name ("organization", "Committee or Organization Name");
			$this -> set_display_name ("bigger_author", "Meeting Secretary");
			$this->change_element_type( 'description', 'hidden');
			$this -> set_display_name ("bigger_content", "Minutes");
			$this -> set_display_name ("minutes_status", "Pending or Published?");
			$this->add_required( 'minutes_status' );
			if (!$this->get_value( 'minutes_status' ))
				$this->set_value( 'minutes_status', 'published');
		
			$this->change_element_type( 'bigger_content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			
			$org = $this->get_value('organization');
			$site = new entity($this->admin_page->site_id);
			if( $site->get_value( 'name' ) && empty($org) )
			{
				$this->set_value( 'organization' , $site->get_value( 'name' ) );
			}
			
			$name = $this->get_value( 'name' );
			if (empty($name))
			{
				$this->set_value( 'name' , 'Minutes for '.date('m/d/Y') );
			}
		
			$this -> set_order (array ("name", 'organization', "datetime", "location", "bigger_author", "present_members", "absent_members", "guests", "bigger_content", "keywords", 'minutes_status'));
		} // }}}
		
	}
?>
