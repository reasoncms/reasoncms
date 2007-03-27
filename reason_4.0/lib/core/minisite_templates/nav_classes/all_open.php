<?php
	reason_include_once( 'minisite_templates/nav_classes/default.php' );

	class AllOpenNavigation extends MinisiteNavigation
	{
		function is_open( $id )  // {{{
		{
			return true;
		} // }}}
	}
?>
