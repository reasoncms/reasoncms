<?php
/**
 * Second Level Minisite Navigation
 * @author matt ryan
 * @package reason
 * @subpackage minisite_navigation
 */
 
 /**
  * Include the default minisite navigation class
  */
	reason_include_once( 'minisite_templates/nav_classes/default.php' );

/**
 * Second Level Minisite Navigation Class
 * @author matt ryan
 * 
 * This navigation class does not show the root or the direct children of the root page
 * The one exception is this: it shows the direct child of the root page that is currently open, so that one can see the current "section" you are browsing.
 *
 * It is designed for use in "inverted L" style navigation schemes, 
 * where the top-level links for the site are arranged horizontally across the top 
 * of the page, and the navigation below that level is arranged vertically to the side.
 */
	class SecondLevelNavigation extends MinisiteNavigation
	{
			var $start_depth = 2;
			var $display_parent_of_open_branch = true;
	}
?>
