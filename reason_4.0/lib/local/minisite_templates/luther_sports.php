<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/luther.php' );
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'Luther2014SportsTemplate';
	
	class Luther2014SportsTemplate extends Luther2014Template
	{
		
		function do_org_head_items()
		{
			parent::do_org_head_items();
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/sites/sports.css');
		}
		
		function get_body_tag_classes()
		{
			$classes = array();
			$classes[] = 'body';
			if($this->pages->root_node() == $this->page_id)
				$classes[] = 'body lutherSports siteHome';
			if($this->page_info->get_value('unique_name'))
				$classes[] = 'lutherSports uname_'.$this->page_info->get_value('unique_name');
			return $classes;
		}

		function show_footer()
		{
			echo '<footer id="footer" role="contentInfo">'."\n";
			$this->run_section( 'footer' );
			$this->run_section( 'edit_link' );
			if ($this->has_content( 'post_foot' ))
				$this->run_section( 'post_foot' );
			echo '</footer>'."\n";
		}
	}
?>