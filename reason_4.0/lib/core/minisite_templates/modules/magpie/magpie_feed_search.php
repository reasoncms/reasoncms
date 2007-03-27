<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ 'magpie/' . basename( __FILE__, '.php' ) ] = 'Magpie_Feed_Search';

	class Magpie_Feed_Search extends DefaultMinisiteModule
	{
		function has_content()
		{
			return true;
		}
		function run()
		{
			reason_include_once( 'minisite_templates/modules/magpie/reason_rss.php' );
					
			$rfd = new reasonFeedDisplay();
			$rfd->set_page_query_string_key('view_page');
			$rfd->set_search_query_string_key('search');
       		echo $rfd->generate_search();
		}
	}
?>
