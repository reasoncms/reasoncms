<?php
/**
 * Basic Tabs Minisite Navigation
 * @author matt ryan
 * @package reason
 * @subpackage minisite_navigation
 */
 
 /**
  * Include the base class
  */
 /**
  * Include the default minisite navigation class
  */
	reason_include_once( 'minisite_templates/nav_classes/default.php' );

/**
 * Basic Tabs Minisite Navigation Class
 * @author matt ryan
 * 
 * This navigation class does not show the root or the direct children of the root page in the main nav area.
 * The one exception is this: it shows the direct child of the root page that is currently open, so that one can see the current "section" you are browsing.
 *
 * It is designed for use in "inverted L" style navigation schemes, 
 * where the top-level links for the site are arranged horizontally across the top 
 * of the page, and the navigation below that level is arranged vertically to the side.
 *
 * This class also displays top-level links above the content
 *
 */
	class BasicTabsNavigation extends MinisiteNavigation
	{
		var $start_depth = 2;
		var $display_parent_of_open_branch = true;
			
			
		function main_nav_has_content()
		{
			$children = $this->children($this->cur_page_id);
			$depth = $this->get_depth_of_item($this->cur_page_id);
			if((!empty($children) && $depth >= $this->start_depth-1) || $depth >= $this->start_depth)
			{
				return true;
			}
			return false;
		}
		function top_nav_has_content()
		{
			return true;
		}
		function show_top_nav()
		{
			$this->show_all_items_given_depths(0,1);
		}
	}
?>
