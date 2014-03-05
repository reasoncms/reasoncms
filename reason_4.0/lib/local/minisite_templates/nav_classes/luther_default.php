<?php
/**
 * @package reason
 * @subpackage minisite_navigation
 */
 
 /**
  * Include the base class
  */
	include_once( 'reason_header.php' );
	reason_include_once( 'minisite_templates/nav_classes/default.php' );

	class LutherDefaultMinisiteNavigation extends MinisiteNavigation
	{
		var $start_depth = 0;
		var $display_parent_of_open_branch = true;
		var $link_to_current_page = true;
		
		function get_home_page_link_text()
		{
			$home_page = $this->values[ $this->root_node() ];
			if($home_page->get_value('link_name'))
			{
				return $home_page->get_value('link_name');
			}
			else
			{
				return $this->site_info->get_value('name');
			}
		}
		
	}
	
	
?>
