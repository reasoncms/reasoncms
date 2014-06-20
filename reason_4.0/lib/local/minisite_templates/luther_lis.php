<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/luther.php' );
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'Luther2014LisTemplate';
	
	class Luther2014LisTemplate extends Luther2014Template
	{

		function alter_page_type($page_type)
		{
			$page_type['lis_site_announcements'] = array('module' => 'lis_site_announcements');
			return $page_type;
		}

		function show_luther_global_header() 
		{
			if ($this->has_content( 'lis_site_announcements' )) 
			{
				$this->run_section( 'lis_site_announcements' );
			}	
			if ($this->has_content( 'global_header' )) 
			{
				$this->run_section( 'global_header' );
			}
		}
		
		function do_org_head_items()
		{
			parent::do_org_head_items();
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/sites/lis.css');
		}
		
		function get_body_tag_classes()
		{
			$classes = array();
			$classes[] = 'body lutherLis';
			if($this->pages->root_node() == $this->page_id)
				$classes[] = 'body lutherLis siteHome';
			if($this->page_info->get_value('unique_name'))
				$classes[] = 'lutherLis uname_'.$this->page_info->get_value('unique_name');
			return $classes;
		}		
	}
?>