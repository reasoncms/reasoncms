<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/luther.php' );
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'Luther2014MagazineTemplate';
	
	class Luther2014MagazineTemplate extends Luther2014Template
	{
		
		function do_org_head_items()
		{
			parent::do_org_head_items();
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/sites/magazine.css');
		}
		
		function get_body_tag_classes()
		{
			$classes = array();
			$classes[] = 'body lutherMagazine';
			if($this->pages->root_node() == $this->page_id)
				$classes[] = 'body lutherMagazine siteHome';
			if($this->page_info->get_value('unique_name'))
				$classes[] = 'lutherMagazine uname_'.$this->page_info->get_value('unique_name');
			return $classes;
		}		
	}
?>