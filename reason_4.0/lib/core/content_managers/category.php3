<?php
/**
 * A content manager for categories
 * @package reason
 * @subpackage content_managers
 */
 
  /**
   * Store the class name so that the admin page can use this content manager
   */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'categoryManager';

	/**
	 * A content manager for categories
	 *
	 * This class customizes the editing interface for category entities
	 *
	 * @todo Eliminate the stupid campus_pipeline_equivalent and old_calendar_equivalent fields from the database
	 */
	class categoryManager extends ContentManager
	{
		function alter_data()
		{
			//$this->change_element_type ('campus_pipeline_equivalent', 'hidden');
			//$this->change_element_type('old_calendar_equivalent','hidden');
			$this->change_element_type('keywords','hidden');
			$this->change_element_type('description','hidden');
			//$this->set_display_name( 'keywords', 'Other terms for this category' );
			//$this->change_element_type('description','text');
		}
	}
?>
