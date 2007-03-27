<?php
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'categoryManager';

	class categoryManager extends ContentManager
	{
		function alter_data()
		{
			$this->change_element_type ('campus_pipeline_equivalent', 'hidden');
			$this->change_element_type('old_calendar_equivalent','hidden');
			$this->change_element_type('keywords','hidden');
			$this->change_element_type('description','hidden');
			//$this->set_display_name( 'keywords', 'Other terms for this category' );
			//$this->change_element_type('description','text');
		}
	}
?>
