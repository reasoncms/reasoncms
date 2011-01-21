<?php

/**
 * A sample Reason template, with minimal overloading of methods
 */
 
/**
 * Include the base template so we can extend it
 */
reason_include_once( 'minisite_templates/default.php' );
reason_include_once( 'minisite_templates/nav_classes/no_root.php' ); 
reason_include_once( 'function_libraries/root_finder.php');
reason_include_once( 'function_libraries/relationship_finder.php');

/**
 * Register this new template with Reason
 */
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'LutherTemplate2010';

class LutherTemplate2010 extends MinisiteTemplate
{
	// reorder sections so that navigation is first instead of last
	var $sections = array('navigation'=>'show_navbar','content'=>'show_main_content','related'=>'show_sidebar');
	var $doctype = '<!DOCTYPE html>';
	public $luther_add_this_complete = false;
	var $include_modules_css = false;
	var $nav_class = 'NoRootNavigation'; 
	

	function start_page() 
	{

		$this->get_title();

		// start page
		echo $this->get_doctype()."\n";
		echo '<html  class="no-js" id="luther-edu" lang="en">'."\n";
		echo '<head>'."\n";

		$this->do_org_head_items();

		echo $this->head_items->get_head_item_markup();

		if ($this->cur_page->get_value('extra_head_content'))
		{
			echo "\n".$this->cur_page->get_value('extra_head_content')."\n";
		}

		echo '<!--[if (gte IE 6)&  (lte IE 8)]>'."\n";
		echo '<script type="text/javascript" src="/javascripts/nwmatcher/nwmatcher-1.2.3.js"></script>'."\n";
		echo '<script type="text/javascript" src="/javascripts/selectivizr/selectivizr-1.0.0.js"></script>'."\n";
		echo '<![endif]-->'."\n";

		echo '</head>'."\n";

		echo $this->create_body_tag();
		echo '<div class="hide"><a href="#content" class="hide">Skip Navigation</a></div>'."\n";
		if ($this->has_content( 'pre_bluebar' ))
			$this->run_section( 'pre_bluebar' );
		//$this->textonly_toggle( 'hide_link' );
		if (empty($this->textonly))
		{
			$this->do_org_navigation();
			// You are here bar
			$this->you_are_here();
		}
		else
		{
			$this->do_org_navigation_textonly();
		}
	}

	function show_body_tableless()
	{
		if (!empty($this->textonly))
		{
			$class = 'textOnlyView';
		}
		else
		{
			$class = 'fullGraphicsView';
		}
		echo '<div id="wrapper" class="'.$class.'">'."\n";
		echo '<div id="bannerAndMeat">'."\n";
		$this->show_banner();
		$this->emergency_preempt();		
		echo '<div class="container group">'."\n";		
		$this->show_meat();		
		echo '</div> <!-- class="container group" -->'."\n";
		echo '</div> <!-- id="bannerAndMeat" -->'."\n";
		$this->show_footer();
		echo '</div> <!-- id="wrapper" class="'.$class.'" -->'."\n";
		
	}

	function show_banner_tableless()
        {
                if ($this->has_content( 'pre_banner' ))
                {      
                        //echo '<div id="preBanner">';
                        $this->run_section( 'pre_banner' );
                        //echo '</div>'."\n";
                }
                //echo '<div id="banner">'."\n";
                if($this->should_show_parent_sites())
                {
                        echo $this->get_parent_sites_markup();
                }
                //echo '<h1><a href="'.$this->site_info->get_value('base_url').'"><span>'.$this->site_info->get_value('name').'</span></a></h1>'."\n";
                // top navigation bar
                $this->show_banner_xtra();
                //echo '</div>'."\n";
				if ($this->has_content('post_banner'))
                {
                 //       echo '<div id="postBanner">'."\n";
                        $this->run_section('post_banner');
                  //      echo '</div>'."\n";
                }
        }

	function show_navbar_tableless()
	// left column navigation
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
		{
			$this->home_topimage_quote();
			return;
		}

		echo '<div class="content content-secondary">'."\n";
		
		// Section
		$this->section();
		
		// Navigation area
		echo '<nav id="nav-section" role="navigation">'."\n";
		if ($this->has_content( 'navigation' ))
		{
			$this->run_section( 'navigation' );
		}
		echo '</nav> <!-- id="nav-section" role="navigation" -->'."\n";
	
		/*// Username
		if ($this->has_content( 'sub_nav' ))
		{
			echo '<div id="subNav">'."\n";
			$this->run_section( 'sub_nav' );
			echo '</div>'."\n";
		}

		
		$this->run_section( 'sbvideo' );*/
		
		$this->run_section( 'bannerad' );
		
		// Contact Information
		echo '<section class="contact-information">'."\n";		
		if ($this->has_content( 'sub_nav_2' ))
		{
			$this->run_section( 'sub_nav_2' );
		}
		echo '</section> <!-- class="contact-information" -->'."\n";
		

		if ($this->has_content( 'sub_nav_3' ))
		{
			$this->run_section( 'sub_nav_3' );
		}

		echo '</div> <!-- class="content content-secondary" -->'."\n";

        } 

	function show_sidebar_tableless()
	// right column
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
		{	
			$this->home_events_news_spotlight();
			$this->run_section( 'bannerad');
			return;
		}
		
		echo '<div class="content content-tertiary">'."\n";
		
		if ($this->has_content( 'twitter_sub_nav' ) && $this->cur_page->get_value( 'custom_page' ) != 'luther2010_alumni'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_giving'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_music'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_naa'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_public_information')
		{
			$this->run_section( 'twitter_sub_nav' );
		}
		
		if ($this->has_content( 'pre_sidebar' ) && $this->cur_page->get_value( 'custom_page' ) != 'luther2010_alumni'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_giving'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_music'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_naa'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_public_information')
		{
			echo '<div id="preSidebar">'."\n";
			$this->run_section( 'pre_sidebar' );
			echo '</div>'."\n";
		}
		elseif ($this->has_content( 'pre_sidebar' ) && ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'))
		{
			echo '<section class="spotlight" role="group">'."\n";
			echo '<header class="red-stripe"><h1><span>Spotlight</span></h1></header>'."\n";
			$this->run_section( 'pre_sidebar');
			echo '</section> <!-- class="spotlight" role="group" -->'."\n";
		}
		elseif ($this->has_content( 'pre_sidebar' ) && ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
			/*|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'*/))
		{
			echo '<aside class="quote">'."\n";
			echo '<blockquote>'."\n";
			$this->run_section( 'pre_sidebar');
			echo '</blockquote>'."\n";
			echo '</aside>'."\n";
		}
		
		if ($this->has_content( 'flickr_slideshow' ) && $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving')
		{
			echo '<aside class="gallery group">'."\n";
			echo '<header class="blue-stripe"><h1><span>Featured Gallery</span></h1></header>'."\n";			
			$this->run_section( 'flickr_slideshow' );
			echo '</aside> <!-- class="gallery group" -->'."\n";
		}
		
		if ($this->has_content( 'sidebar' ))
		{
			echo '<div id="sidebar">'."\n";
			$this->run_section( 'sidebar' );
			echo '</div>'."\n";
		}
		
		if ($this->has_content( 'twitter_sub_nav' ) && ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'))
		{
			$this->run_section( 'twitter_sub_nav' );
		}
				
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni' && $this->has_content( 'post_sidebar' ))
		{
			echo '<aside class="news group">'."\n";
			echo '<header class="blue-stripe"><h1><span>News</span></h1></header>'."\n";
			$this->run_section( 'post_sidebar');
			echo '</aside> <!-- class="news group" -->'."\n";
		}
		elseif ($this->has_content( 'post_sidebar' ) //&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_alumni'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_music'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_public_information')
		{
			echo '<div id="postSidebar">'."\n";
			$this->run_section( 'post_sidebar' );
			echo '</div>'."\n";
		}
		//elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni')
		//{
		//	echo '<section class="events" role="group">'."\n";
		//	echo '<header class="blue-stripe"><h1><span>Upcoming Events</span></h1></header>'."\n";
		//	$this->run_section( 'post_sidebar');
		//	echo '</section> <!-- class="events" role="group" -->'."\n";
		//}
		elseif ($this->has_content( 'flickr_slideshow' ) && ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
		|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
		|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'))
		{
			echo '<aside class="gallery group">'."\n";
			echo '<header class="blue-stripe"><h1><span>Featured Gallery</span></h1></header>'."\n";			
			$this->run_section( 'flickr_slideshow' );
			echo '</aside> <!-- class="gallery group" -->'."\n";
		}
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information' && $this->has_content( 'post_sidebar' ))
		{
			echo '<aside class="quote">'."\n";
			echo '<blockquote>'."\n";
			$this->run_section( 'post_sidebar');
			echo '</blockquote>'."\n";
			echo '</aside>'."\n";
		}
		
		echo '</div> <!-- class="content content-tertiary" -->'."\n";
		//echo '</div>'."\n";
		//echo '</div class="span-48 last">'."\n";

        }


	function luther_breadcrumbs()
	{
		echo '<nav id="breadcrumbs">'."\n";
		echo $this->_get_breadcrumb_markup($this->_get_breadcrumbs(), $this->site_info->get_value('base_breadcrumbs'), '&nbsp;&#187;&nbsp;');
		echo '</nav>'."\n";

	}

	function luther_add_this()
	// insert "add this" capability to luther pages linking to facebook,
	// twitter, delicious, etc.
	{
		if ($this->cur_page->get_value( 'custom_page' ) != 'publication' && $this->cur_page->get_value('custom_page') != 'default'
			&& $this->cur_page->get_value('custom_page') != 'audio_video' && $this->cur_page->get_value('custom_page') != 'luther2010_alumni'
			&& $this->cur_page->get_value('custom_page') != 'luther2010_giving'
			&& $this->cur_page->get_value('custom_page') != 'luther2010_music'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_naa'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_public_information')
		{
			return;
		}
		echo '<!-- AddThis Button BEGIN -->'."\n";
		echo '<div class="addthis_toolbox addthis_default_style">'."\n";
		echo '<a href="http://www.addthis.com/bookmark.php?v=250&amp;pub=lutheraddthis" class="addthis_button_compact"></a>'."\n";
		echo '<span class="addthis_separator">|</span>'."\n";
		echo '<a class="addthis_button_facebook"></a>'."\n";
		echo '<a class="addthis_button_twitter"></a>'."\n";
		echo '<a class="addthis_button_email"></a>'."\n";
		echo '<a class="addthis_button_print"></a>'."\n";
		echo '</div>'."\n";
		echo '<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pub=lutheraddthis"></script>'."\n";
		echo '<!-- AddThis Button END -->'."\n";
		$this->luther_add_this_complete = true;
	}

	function show_main_content_sections()
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
		{
			return;
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_music')
		{
			$this->music_main_content();
			return;
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni')
		{
			$this->alumni_main_content();
			return;
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving')
		{
			$this->giving_main_content();
			return;
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa')
		{
			$this->naa_main_content();
			return;
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information')
		{
			$this->public_information_main_content();
			return;
		}

		echo '<div class="content content-primary">'."\n";
		
		$this->luther_breadcrumbs();
		
		if (!$this->luther_add_this_complete)
		{
			$this->luther_add_this();
		}
		
		// page title
		if ($this->has_content( 'main_head' ))
		{			
			$this->run_section( 'main_head' );			
		}

		$this->run_section( 'imagetop' );

		if ($this->has_content( 'main' ))
		{
			echo '<div class="contentMain">'."\n";
			if (!$this->luther_add_this_complete)
			{
				$this->luther_add_this();
			}
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}
		

		if ($this->has_content( 'main_post' ))
		{
			echo '<div class="contentPost">'."\n";
			if (!$this->luther_add_this_complete)
			{
				$this->luther_add_this();
			}
			$this->run_section( 'main_post' );
			echo '</div>'."\n";
		}
	
		if ($this->has_content( 'content_blurb' ))
		{
			$this->run_section( 'content_blurb' );
		}

		if ($this->has_content( 'flickr_slideshow' ))
		{
			$this->run_section( 'flickr_slideshow' );
		}
                
		if ($this->has_content( 'norse_calendar' ))
		{
			$this->run_section( 'norse_calendar' );
		}
		
		echo '</div> <!-- class="content content-primary" -->'."\n";        
                // rough-in right column if there is no content
		if ($this->has_related_section() == false)
		{
			$this->show_sidebar_tableless();	
		}
	}

	function do_org_head_items()
	{
		// Just here as a hook for branding head items (js/css/etc.)
		echo '<meta http-equiv="X-UA-Compatible" content="IE=edge" />'."\n"; 
		echo '<link rel="stylesheet" type="text/css" href="/reason/css/modules.css" />'."\n";
		echo '<link href="/javascripts/highslide/highslide.css" media="screen, projection" rel="stylesheet" type="text/css" />'."\n";
		echo '<link href="/stylesheets/luther2010/master.css" media="screen, projection" rel="stylesheet" type="text/css" />'."\n";
		echo '<link href="/stylesheets/luther2010/reason.css" media="screen, projection" rel="stylesheet" type="text/css" />'."\n";  
  		echo '<script src="/javascripts/modernizr-1.1.min.js" type="text/javascript"></script>'."\n";
		echo '<!--[if lt IE 9]><link href="/stylesheets/luther2010/ie8.css" media="all" rel="stylesheet" type="text/css" /><![endif]-->'."\n";  
  		echo '<!--[if lt IE 8]><link href="/stylesheets/luther2010/ie7.css" media="all" rel="stylesheet" type="text/css" /><![endif]-->'."\n";
  		echo '<!--[if lt IE 7]><link href="/stylesheets/luther2010/ie6.css" media="all" rel="stylesheet" type="text/css" /><![endif]-->'."\n";
  		
		echo '<meta property="og:title" content="Luther College" />'."\n";
		echo '<meta property="og:type" content="university" />'."\n";
		echo '<meta property="og:url" content="http://www.luther.edu/" />'."\n";
		echo '<meta property="og:site_name" content="Luther College" />'."\n";
		echo '<meta property="og:image" content="" />'."\n";
		echo '<meta property="og:street-address" content="700 College Drive"/>'."\n";
		echo '<meta property="og:locality" content="Decorah" />'."\n";
		echo '<meta property="og:region" content="Iowa" />'."\n";
		echo '<meta property="og:country" content="USA" />'."\n";

		echo '<meta property="og:email" content="www@luther.edu"/>'."\n";
		echo '<meta property="og:phone_number" content="563-387-2000"/>'."\n";

		$this->head_items->add_javascript( '/javascripts/highslide/highslide-full.js' );
		$this->head_items->add_javascript( '/javascripts/highslide/highslide-overrides.js' );

		$this->head_items->add_javascript( '//ajax.googleapis.com/ajax/libs/swfobject/2.1/swfobject.js');
		//$this->head_items->add_javascript( '//ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js' );
	}

	function create_body_tag()
	{
		$bc = $this->_get_breadcrumbs();
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
		{
			return '<body id="home" class="style-home-00">'."\n";
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information')
		{
			return '<body id="home" class="style-home-01" >'."\n";
		}
		//elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving')
		//{
		//	return '<body id="home" class="style-home-02" >'."\n";
		//}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'events')
		{
			return '<body class="style-one-column" >'."\n";
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'spotlight_archive')
		{
			return '<body class="style-two-columns spotlight-archive" >'."\n";
		}
		//elseif (count($bc) <= 2 /*&& $this->admissions_has_related_or_timeline()*/)  // section
		//{
		//	return '<body id="home" class="style-home-01">'."\n";
		//}
		else
		{
			return '<body class="style-two-columns">'."\n";
		}
    }

	function has_related_section()
	{
        if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home' || $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information')
		{
			return true;
		}
		
		if ((($this->has_content('pre_sidebar') || $this->has_content('sidebar')) && $this->cur_page->get_value('custom_page') != 'standalone_login_page_stripped'))
		{
			//print_r($this->cur_page->_values);
			//print_r($this->cur_page->get_value('name'));
	
			// test if all sidebar images have keyword 'imagetop'
			// bannerad, or video.
			$module =& $this->_get_module( 'sidebar' );
			if ($module != null)
			{
				foreach( $module->images AS $id => $image )
				{
					if (!preg_match("/imagetop|bannerad|video/", $image->get_value('keywords')))
					{
						return true;
					}
				}
			}

			if ($this->cur_page->get_value('custom_page') == 'audio_video')
			{
				return false;
			}
			if ($this->cur_page->get_value('custom_page') == 'audio_video_sidebar' || $this->cur_page->get_value('custom_page') == 'feed_display_sidebar')
			{
				return true;
			}
			// return true if media works has been attached to page.
			$es = new entity_selector();
			$es->add_type(id_of('av'));
			$es->add_right_relationship($this->page_id, relationship_id_of('minisite_page_to_av'));
			$result = $es->run_one(); 
			if ($result != false)
			{
				return true;
			}	
			
		}
		return false;
	}

	function home_topimage_quote()
	// contains top image carousel and text blurb on the home page
	{

		echo '<div class="container-carousel-and-attribute">'."\n";
		$this->run_section( 'navigation');
		$this->run_section( 'sub_nav');
		echo '</div> <!-- class="container-carousel-and-attribute" -->'."\n";         

		
	}

	function home_events_news_spotlight()
	// contains events, news, and spotlight in fold of home page
	{
		echo '<div class="container-events-news-and-spotlight">'."\n";
		
		echo '<section class="events" role="group">'."\n";
		echo '<header class="red-stripe"><h1><span>Events</span></h1></header>'."\n";
		//echo ''."\n";
			$this->run_section( 'pre_sidebar');
		echo '</section> <!-- class="events" role="group" -->'."\n";

		echo '<section class="news" role="group">'."\n";
		echo '<header class="red-stripe"><h1><span>News</span></h1></header>'."\n";
			$this->run_section( 'sidebar');
		echo '</section> <!-- class="news" role="group" -->'."\n";
		
		echo '<section class="spotlight" role="group">'."\n";
		echo '<header class="red-stripe"><h1><span>Spotlight</span></h1></header>'."\n";
			$this->run_section( 'post_sidebar');
		echo '</section> <!-- class="spotlight" role="group" -->'."\n";
			
		echo '</div> <!-- class="container-events-news-and-spotlight" -->'."\n";
	}
	
	function music_main_content()
	// contains carousel, title, breadcrumbs, add-this, events, and spotlight on music site
	{
		$this->run_section( 'imagetop' );
		
		echo '<div class="content content-primary">'."\n";
		
		$this->luther_breadcrumbs();
		
		// page title
		if ($this->has_content( 'main_head' ))
		{			
			$this->run_section( 'main_head' );			
		}
		
		if ($this->has_content( 'main' ))
		{
			echo '<div class="contentMain">'."\n";
			
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}

		echo '<section class="events group with-calendar" role="group">'."\n";
		echo '<header class="red-stripe"><h1><span>Upcoming Music Events</span></h1></header>'."\n";
		$this->run_section( 'main_post' );		
		echo '</section> <!-- class="events group with-calendar" role="group" -->'."\n";
		
		echo '<section class="spotlight" role="group">'."\n";
     	echo '<header class="red-stripe"><h1><span>Spotlight</span></h1></header>'."\n";
		if ($this->has_content( 'content_blurb' ))
		{
			$this->run_section( 'content_blurb' );
		}
     	echo '</section> <!-- class="spotlight" role="group" -->'."\n";
     	
     	$this->luther_add_this();
     	
     	echo '</div> <!-- class="content content-primary" -->'."\n";
	}
	
	function alumni_main_content()
	// contains carousel, title, breadcrumbs, add-this, and main content on the alumni site
	{
		$this->run_section( 'imagetop' );
		
		echo '<div class="content content-primary">'."\n";
		
		$this->luther_breadcrumbs();
		
		// page title
		if ($this->has_content( 'main_head' ))
		{			
			$this->run_section( 'main_head' );			
		}
		
		if ($this->has_content( 'main' ))
		{
			echo '<div class="contentMain">'."\n";
			
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}

		echo '<section class="events group with-calendar" role="group">'."\n";
		echo '<header class="red-stripe"><h1><span>Upcoming Alumni Events</span></h1></header>'."\n";
		$this->run_section( 'main_post' );		
		echo '</section> <!-- class="events group with-calendar" role="group" -->'."\n";
		
		if ($this->has_content( 'content_blurb' ))
		{
			$this->run_section( 'content_blurb' );
		}

		if ($this->has_content( 'flickr_slideshow' ))
		{
			echo '<aside class="gallery group">'."\n";
			echo '<header class="red-stripe"><h1><span>Featured Gallery</span></h1></header>'."\n";			
			$this->run_section( 'flickr_slideshow' );
			echo '</aside> <!-- class="gallery group" -->'."\n";
		}
                
     	
     	$this->luther_add_this();
     	
     	echo '</div> <!-- class="content content-primary" -->'."\n";
	}

	function giving_main_content()
	// contains carousel, title, breadcrumbs, add-this, and main content on the alumni site
	{
		$this->run_section( 'imagetop' );
		
		echo '<div class="content content-primary">'."\n";
		
		$this->luther_breadcrumbs();
		
		// page title
		if ($this->has_content( 'main_head' ))
		{			
			$this->run_section( 'main_head' );			
		}
		
		if ($this->has_content( 'main' ))
		{
			echo '<div class="contentMain">'."\n";
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}

		if ($this->has_content( 'main_post' ))
		{
			echo '<div class="contentPost">'."\n";
			$this->run_section( 'main_post' );
			echo '</div>'."\n";
		}
	
		if ($this->has_content( 'content_blurb' ))
		{
			$this->run_section( 'content_blurb' );
		}

     	$this->luther_add_this();
     	
     	echo '</div> <!-- class="content content-primary" -->'."\n";
	}
	
	function naa_main_content()
	// contains carousel, title, breadcrumbs, add-this, and main content on the norse athletic association site
	{
		$this->run_section( 'imagetop' );
		
		echo '<div class="content content-primary">'."\n";
		
		$this->luther_breadcrumbs();
		
		// page title
		if ($this->has_content( 'main_head' ))
		{			
			$this->run_section( 'main_head' );			
		}
		
		if ($this->has_content( 'main' ))
		{
			echo '<div class="contentMain">'."\n";
			
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}
		
		if ($this->has_content( 'main_post' ))
		{
			echo '<div class="contentPost">'."\n";
			echo '<header class="red-stripe"><h1><span>News</span></h1></header>'."\n";
			$this->run_section( 'main_post' );
			echo '</div>'."\n";
		}

     	$this->luther_add_this();
     	
     	echo '</div> <!-- class="content content-primary" -->'."\n";
	}
	
	function public_information_main_content()
	// contains carousel, title, breadcrumbs, add-this, and main content on the norse athletic association site
	{
		$this->run_section( 'imagetop' );
		
		echo '<div class="content content-primary">'."\n";
		
		$this->luther_breadcrumbs();
		
		// page title
		if ($this->has_content( 'main_head' ))
		{			
			$this->run_section( 'main_head' );			
		}
		
		if ($this->has_content( 'main' ))
		{
			echo '<div class="contentMain">'."\n";
			
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}
		
		if ($this->has_content( 'main_post' ))
		{
			echo '<div class="contentPost">'."\n";
			echo '<header class="red-stripe"><h1><span>News</span></h1></header>'."\n";
			$this->run_section( 'main_post' );
			echo '</div>'."\n";
		}
		
		if ($this->has_content( 'content_blurb' ))
		{
			echo '<section class="events group with-calendar" role="group">'."\n";
			echo '<header class="red-stripe"><h1><span>Events Calendar</span></h1></header>'."\n";
			$this->run_section( 'content_blurb' );		
			echo '</section> <!-- class="events group with-calendar" role="group" -->'."\n";
		}    
     	
     	$this->luther_add_this();
     	
     	echo '</div> <!-- class="content content-primary" -->'."\n";
	}

	function section()
	// section link with styling at top of left navigation column
	{		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
		{
			return;
		}
		
		$bc = $this->_get_breadcrumbs();	
		$sbtitle = $bc[0]["page_name"];
		$sblink = $bc[0]["link"];
		echo '<a class="blue" href="' . $sblink . '" id="section-sign">'."\n";
		echo '<div><header><h2>' . $sbtitle . '</h2></header></div></a>'."\n";
		
	}
	
	function emergency_preempt()
	// Display one or more site-wide preemptive emergency messages
	// if one or more text blurbs are placed on the page /preempt
	{
		$site_id = get_site_id_from_url("/preempt");
		$page_id = root_finder( $site_id );   // see 'lib/core/function_libraries/root_finder.php'
		
		$es = new entity_selector();
		$es->add_type(id_of('text_blurb'));
		$es->add_right_relationship($page_id, relationship_id_of('minisite_page_to_text_blurb'));
		$result = $es->run_one();
		
		if ($result == null)
		{
			return;
		}
		
		echo '<div class="emergency">'."\n";	
		foreach( $result AS $id => $page )
		{
			echo $page->get_value('content')."\n";
		}
		echo '</div>  <!-- class="emergency"-->'."\n";			
	}

}

?>
