<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PageLCPreBannerModule';
	
	class PageLCPreBannerModule extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{
			
		}
		function has_content()
		{
			return true;
		}
		function run()
		{
                        if ($this->cur_page->get_value( 'custom_page' ) == 'luth
er_pageLC')                        {
                                echo '<body id="pageLC">'."\n";                        }
                        elseif ($this->cur_page->get_value( 'custom_page' ) == '
luther_pageLCR')
                        {
                                echo '<body id="pageLCR">'."\n";
                        }
                        elseif ($this->cur_page->get_value( 'custom_page' ) == '
luther_primaryLCR')
                        {
                                echo '<body id="primaryLCR">'."\n";
                        }


			echo '<div class="container">'."\n";
			echo '<div id="body" class="container">'."\n";
			echo '<div id="head">'."\n";
			echo '<div class="column span-77 last">'."\n";
                        luther_audience_navigation();

			echo '</div class="column span-77 last">'."\n";
			echo '<div id="logosearch" class="container">'."\n";
            		echo '<div class="column span-17">'."\n";

			echo '<div id="logo">'."\n";
			echo '<a href="http://www.luther.edu" title="Luther College Home"><span></span>'."\n";
			echo '<img alt="Luther College" src="/images/luther/logo.png"  /></a></div>'."\n";
			echo '</div class="column span-17">'."\n";
			echo '<div class="column span-60 last">'."\n";
	
		}
	}
?>
