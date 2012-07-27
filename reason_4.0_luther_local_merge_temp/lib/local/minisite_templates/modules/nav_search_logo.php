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
			$theme = get_theme( $this->site_id );
			if ($theme->get_value('name') == 'luther') 
			{
				$this->luther_theme();
			}
			elseif ($theme->get_value('name') == 'luther2010') 
			{
				$this->luther2010_theme();
			}
			return;
		}

		function luther_theme()
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

		}

		function luther2010_theme()
		{
			echo '<header class="global" role="banner">'."\n";
			$url = get_current_url();
			if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/sports\/?/", $url))
			{
				echo '<h1 id="luther-logo"><a href="/sports" title="Luther College Athletics"><img alt="luther College" height="54" src="/images/luther2010/Norse-w-Luther-Helmet-294r-transparent.png" width="122" /></a></h1>'."\n";
			}
			else
			{
				echo '<h1 id="luther-logo"><a href="/" title="Luther College Home"><img alt="luther College" height="54" src="/images/luther2010/luther-college.png" width="289" /></a></h1>'."\n";
			}			
			echo '<nav id="nav-search" role="navigation">'."\n";
			echo '<ul>'."\n";
			if (luther_is_mobile_device())
			{
				echo '<li class="mobile"><a href="/mobile/">Mobile</a></li>'."\n";
			}
			echo '<li class="home"><a href="/">Home</a></li>'."\n";
			echo '<li class="directory"><a href="/directory/">Directory</a></li>'."\n";
			echo '<li class="index"><a href="/azindex/">A-Z Index</a></li>'."\n";
			echo '<li class="search">'."\n";
			luther2010_google_search();			
			echo '</li>'."\n";
			echo '</ul>'."\n";  
			echo '</nav>'."\n";
			echo '<nav id="nav-audience" role="navigation">'."\n";			
			luther2010_audience_navigation();
			echo '</nav>'."\n";  
			echo '</header>'."\n";
			echo '<nav id="nav-content" role="navigation">'."\n";
			luther2010_global_navigation();
			echo '</nav>'."\n";
		}
	}
?>
