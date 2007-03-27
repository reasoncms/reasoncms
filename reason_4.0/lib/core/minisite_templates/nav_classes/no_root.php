<?php
	include_once( 'reason_header.php' );
	reason_include_once( 'minisite_templates/nav_classes/default.php' );

	class NoRootNavigation extends MinisiteNavigation
	{
		var $start_depth = 1;
	}
?>
