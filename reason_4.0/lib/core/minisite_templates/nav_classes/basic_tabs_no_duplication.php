<?php
/**
 * Basic Tabs -- No Duplication Minisite Navigation
 * @author matt ryan
 * @package reason
 * @subpackage minisite_navigation
 */
 
 /**
  * Include the base class
  *
  * Include the basic tabs minisite navigation class
  */
	reason_include_once( 'minisite_templates/nav_classes/basic_tabs.php' );

/**
 * Basic Tabs -- No Duplication Minisite Navigation Class
 * @author matt ryan
 * 
 * This navigation class behaves similarly to the basic tabs class, but it does not
 * reiterate the parent of the open branch at the top of the secondary navigation area.
 *
 */
	class BasicTabsNoDuplicationNavigation extends BasicTabsNavigation
	{
		var $display_parent_of_open_branch = false;
	}
?>