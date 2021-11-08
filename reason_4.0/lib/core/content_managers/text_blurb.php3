<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'text_blurb';

	include_once( DISCO_INC . 'plugins/grade_level_notifier/grade_level_notifier.php' );

	/**
	 * A content manager for text blurbs
	 */
	class text_blurb extends ContentManager
	{
		function alter_data()
		{
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
		
			// Add reading level notifier plugin to content editor
			$this->add_readability_notifiers('content');
		}
	}
?>
