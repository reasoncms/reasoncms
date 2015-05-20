<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/luther.php' );
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'Luther2015GivingTemplate';
	
	class Luther2015GivingTemplate extends Luther2014Template
	{
		
		function alter_reason_page_type($page_type)
		{
			parent::alter_reason_page_type($page_type);

			if (!preg_match('/\/giving\/givenow/', get_current_url()))
			{
				$page_type->set_region('post_main_head', 'giving/give_now_mini', 'minisite_templates/modules/giving/give_now_mini.php', array());
		
			}
		}

		function do_org_head_items()
		{
			parent::do_org_head_items();

			// Adds custom giving stylesheet
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/sites/giving.css');
		}

	}
?>