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

	/**
	 * A nav class that does not show the home page
	 */
	class NoRootNavigation extends MinisiteNavigation
	{
		var $start_depth = 1;
	}
?>
