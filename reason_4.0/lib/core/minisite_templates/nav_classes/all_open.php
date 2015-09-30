<?php
/**
 * @package reason
 * @subpackage minisite_navigation
 */
 
 /**
  * Include the base class
  */
	reason_include_once( 'minisite_templates/nav_classes/default.php' );

	/**
	 * A navigation class that shows all elements in the tree
	 */
	class AllOpenNavigation extends MinisiteNavigation
	{
		function is_open( $id )  // {{{
		{
			return true;
		} // }}}
	}
?>
