<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NavSearchLogoModule';
	
	class NavSearchLogoModule extends DefaultMinisiteModule
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

			if ($this->cur_page->get_value( 'custom_page' ) == 'luther_pageLC')
			{
				echo '<body id="pageLC">'."\n";
			}
			elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther_pageLRC')
			{
				echo '<body id="pageLRC">'."\n";
			}
			elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther_primaryLRC')
			{
				echo '<body id="primaryLRC">'."\n";
			}
			elseif ($this->cur_page->get_value( 'custom_page' ) == 'spotlight_archive')
			{
				echo '<body id="spotlightarchive">'."\n";
			}
			else
			{
				echo '<body id="pageLC">'."\n";
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

			luther_google_search();

            		echo '</div class="column span-60 last">'."\n";
          		echo '</div id="logosearch" class="container">'."\n";
          		echo '<div class="column span-77 last">'."\n";
			luther_global_navigation();

                        echo '</div class="column span-77 last">'."\n";
    		//	echo '</div id="head">'."\n";

			return;
		}
	}
?>
