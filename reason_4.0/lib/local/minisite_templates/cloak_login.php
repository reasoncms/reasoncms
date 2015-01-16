<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/cloak.php' );
	reason_include_once( 'classes/module_sets.php' );
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'CloakLoginTemplate';
	
	class CloakLoginTemplate extends CloakTemplate
	{

		function get_body_tag_classes()
		{
			// Adds the class "cloakLogin" to the body tag.
			$classes = array();
			$classes[] = 'body cloakLogin';
			if($this->pages->root_node() == $this->page_id)
				$classes[] = 'siteHome';
			if($this->page_info->get_value('unique_name'))
				$classes[] = 'uname_'.$this->page_info->get_value('unique_name');
			return $classes;
		}

		// Let's just keep this stuff empty
		// This is a login page, so we should keep it nice and speedy

		function cloak_you_are_here($delimiter = '')
		{
		}

		function show_sidebar_tableless()
		{
		}
		
		function show_navbar()
		{		
		}

		function show_footer()
		{
		}

		function do_org_foot()
		{	
		}

	}
?>