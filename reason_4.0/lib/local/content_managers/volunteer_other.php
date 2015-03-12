<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'VolunteerOther';

	/**
	 * A content manager for text blurbs
	 */
	class VolunteerOther extends ContentManager
	{
		function alter_data()
		{
			$this->set_comments( 'location', form_comment('Office location (if applicable)') );
			$this->add_required( 'title' );
			$this->change_element_type( 'content', 'tiny_mce' );
			$this->set_order( array('name', 'unique_name', 'title','email','phone', 'location', 'content') );
		}
	}
?>
