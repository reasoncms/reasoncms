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

			// Way to add giving form to every page?
			//$page_type->set_region('pre_foot', 'blurb', 'blurb.php', array('blurb_unique_names_to_show' => 'luther_sports_affiliations'));
		}

		function do_org_head_items()
		{
			parent::do_org_head_items();

			// Adds custom giving stylesheet
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/sites/giving.css');
		}

		// function get_body_tag_classes()
		// {
		// 	// Adds the class lutherGiving to the body tag on all pages of the site
		// 	$classes = array();
		// 	$classes[] = 'body lutherGiving';
		// 	if($this->pages->root_node() == $this->page_id)
		// 		$classes[] = 'siteHome';
		// 	if($this->page_info->get_value('unique_name'))
		// 		$classes[] = 'uname_'.$this->page_info->get_value('unique_name');
		// 	return $classes;
		// }

	}
?>