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
		$s = "";
		$url = get_current_url();
		if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/sports\/?/", $url))
		{
			$s = 'sports';
		}
		echo $this->get_doctype()."\n";
		echo '<html  class="no-js ' . $s .'" id="luther-edu" lang="en">'."\n";
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
		
		// tracking for YouTube video that uses highslide to view
		// uses "a name=" field for the video page name in analytics
		if (!preg_match("/^localhost$/", REASON_HOST, $matches))
		{
			echo '<script type="text/javascript">'."\n";
			
			echo 'hs.Expander.prototype.onAfterExpand = function(sender) {
				if (this.a.name != "") {
					_gaq.push([\'_trackEvent\', \'video\', \'click\', this.a.name]);
				}
			}'."\n";
				
			//echo 'hs.Expander.prototype.onAfterExpand = function(sender) {
			//		if (this.a.name != "") {
			//			pageTracker._trackPageview(this.a.name);
			//		}
			//	}'."\n";
			
			echo '</script>'."\n";
		}

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

	function get_title()
	{
		$ret = '';
		if($this->use_default_org_name_in_page_title)
		{
			$ret .= FULL_ORGANIZATION_NAME.': ';
		}
		if ($this->site_id == id_of('luther_home'))
		{
			$ret .= "Luther Home";
		}
		else
		{
			$ret .= $this->site_info->get_value('name');
		}
		
		if(carl_strtolower($this->site_info->get_value('name')) != carl_strtolower($this->title))
		{
			$ret .= ": " . $this->title;
		}
		$crumbs = &$this->_get_crumbs_object();
		// Take the last-added crumb and add it to the page title
		if($last_crumb = $crumbs->get_last_crumb() )
		{
			if(empty($last_crumb['id']) || $last_crumb['id'] != $this->page_id)
			{
				$ret .= ': '.$last_crumb['page_name'];
			}
		}
		if (!empty ($this->textonly) )
		{
			$ret .= ' (Text Only)';
		}
		$ret = reason_htmlspecialchars(strip_tags($ret));
		$this->head_items->add_head_item('title',array(),$ret, true);
		//return $ret;
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
	
		// Username
		if ($this->has_content( 'sub_nav' ))
		{
			echo '<div id="subNav">'."\n";
			$this->run_section( 'sub_nav' );
			echo '</div>'."\n";
		}

		
		//$this->run_section( 'sbvideo' );
		

		if ($this->has_content( 'bannerad' ))
		{
			$this->run_section( 'bannerad' );
			echo '<hr>';
		}
		
		if ($this->has_content( 'sub_nav_2' ))
		{
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				$this->run_section( 'sub_nav_2');
				echo'<hr>'."\n";
			}
			else
			{				
				echo '<section class="contact-information">'."\n";
				$this->run_section( 'sub_nav_2' );
				echo '</section> <!-- class="contact-information" -->'."\n";
			}
		}
		
		if ($this->has_content( 'sub_nav_3' ))
		{
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information')
			{
				echo '<section class="contact-information">'."\n";
				$this->run_section( 'sub_nav_3' );
				echo '</section> <!-- class="contact-information" -->'."\n";
			}
			else 
			{
				$this->run_section( 'sub_nav_3' );
			}
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
		
		$url = get_current_url();
		
		echo '<div class="content content-tertiary">'."\n";
		
		if ($this->has_content( 'twitter_sub_nav' ) && $this->cur_page->get_value( 'custom_page' ) != 'luther2010_alumni'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_carousel'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_giving'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_live_at_luther'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_music'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_naa'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_public_information'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_sports')
		{
			$this->run_section( 'twitter_sub_nav' );
		}
		
		if ($this->has_content( 'pre_sidebar' ) && $this->cur_page->get_value( 'custom_page' ) != 'luther2010_alumni'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_giving'
			//&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_live_at_luther'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_music'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_naa'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_public_information'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_sports')
		{
			echo '<div id="preSidebar">'."\n";
			$this->run_section( 'pre_sidebar' );
			echo '<hr>'."\n";
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
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports'))
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
		
		if ($this->has_content( 'sidebar' ) && $this->cur_page->get_value( 'custom_page' ) != 'luther2010_sports')
		{
			echo '<div id="sidebar">'."\n";
			$this->run_section( 'sidebar' );
			echo '<hr>'."\n";
			echo '</div>'."\n";
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			echo '<section class="spotlight" role="group">'."\n";
			echo '<header class="red-stripe"><h1><span>Spotlight</span></h1></header>'."\n";
			$this->run_section( 'sidebar');
			echo '</section> <!-- class="spotlight" role="group" -->'."\n";
		}
		
		if ($this->has_content( 'twitter_sub_nav' ) && ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_carousel'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_live_at_luther'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports'))
		{
			$this->run_section( 'twitter_sub_nav' );
		}
		
		if ($this->has_content( 'pre_sidebar' ) && $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information')
		{
			echo '<aside class="news group">'."\n";
			echo '<header class="blue-stripe"><h1><span>Video of the Week</span></h1></header>'."\n";
			$this->run_section( 'pre_sidebar');
			echo '</aside> <!-- class="news group" -->'."\n";
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
		|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
		|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports'))
		{
			echo '<aside class="gallery group">'."\n";
			if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/art.?\/?/", $url))
			{
				echo '<header class="blue-stripe"><h1><span>Exhibitions</span></h1></header>'."\n";
			}
			else
			{
				echo '<header class="blue-stripe"><h1><span>Featured Gallery</span></h1></header>'."\n";
			}			
			$this->run_section( 'flickr_slideshow' );
			echo '</aside> <!-- class="gallery group" -->'."\n";
		}
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information' && $this->has_content( 'post_sidebar' ))
		{
			echo '<aside class="news group">'."\n";
			echo '<header class="blue-stripe"><h1><span>Alumni Corner</span></h1></header>'."\n";
			$this->run_section( 'post_sidebar');
			echo '</aside> <!-- class="news group" -->'."\n";
		}
		
		echo '</div> <!-- class="content content-tertiary" -->'."\n";
		//echo '</div>'."\n";
		//echo '</div class="span-48 last">'."\n";

        }


	function luther_breadcrumbs()
	{
		echo '<nav id="breadcrumbs">'."\n";
		$b = $this->_get_breadcrumb_markup($this->_get_breadcrumbs(), $this->site_info->get_value('base_breadcrumbs'), '&nbsp;&#187;&nbsp;');
		
		$url = get_current_url();
		if (preg_match("/story_id=\d+$/", $url) // publication inserts link to story as well as the story itself so remove the link
			|| preg_match("/[&?]event_id=\d+/", $url)) // event does too	
		{
			$ba = explode('&nbsp;&#187;&nbsp;', $b);
			array_splice($ba, -2, 1);
			$b = implode('&nbsp;&#187;&nbsp;', $ba);
		}
		$b = preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\1", $b);
		echo $b;
		echo '</nav>'."\n";

	}

	function luther_add_this()
	// insert "add this" capability to luther pages linking to facebook,
	// twitter, delicious, etc.
	{
		if ($this->cur_page->get_value( 'custom_page' ) != 'publication' && $this->cur_page->get_value('custom_page') != 'default'
			&& $this->cur_page->get_value('custom_page') != 'audio_video' && $this->cur_page->get_value('custom_page') != 'luther2010_alumni'
			&& $this->cur_page->get_value('custom_page') != 'luther2010_carousel'
			&& $this->cur_page->get_value('custom_page') != 'luther2010_giving'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_live_at_luther'
			&& $this->cur_page->get_value('custom_page') != 'luther2010_music'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_naa'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_public_information'
			&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_sports')
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
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_carousel')
		{
			$this->carousel_main_content();
			return;
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving')
		{
			$this->giving_main_content();
			return;
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_live_at_luther')
		{
			$this->live_at_luther_main_content();
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
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			$this->sports_main_content();
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
		echo '<link href="/javascripts/cluetip/jquery.cluetip.css" media="screen, projection" rel="stylesheet" type="text/css" />'."\n";
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

		echo '<link rel="icon" href="favicon.ico" type="image/x-icon">'."\n";
		echo '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">'."\n"; 
		$this->head_items->add_javascript( '/javascripts/highslide/highslide-full.js' );
		$this->head_items->add_javascript( '/javascripts/highslide/highslide-overrides.js' );

		$this->head_items->add_javascript( '//ajax.googleapis.com/ajax/libs/swfobject/2.1/swfobject.js');
		//$this->head_items->add_javascript( '//ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js' );
	}

	function create_body_tag()
	{
		$s = "";
		$bc = $this->_get_breadcrumbs();
		$url = get_current_url();
		if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/sports\/?/", $url))
		{
			$s = 'sports';
		}
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
		{
			return '<body id="home" class="style-home-00">'."\n";
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_carousel'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_live_at_luther'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			return '<body id="home" class="style-home-01 ' . $s . '">'."\n";
		}
		//elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving')
		//{
		//	return '<body id="home" class="style-home-02" >'."\n";
		//}
		elseif (($this->cur_page->get_value( 'custom_page' ) == 'events' && !preg_match("/[&?]event_id=\d+/", $url))
			|| $this->cur_page->get_value( 'custom_page' ) == 'sports_roster'
			|| $this->cur_page->get_value( 'custom_page' ) == 'sports_results'
			|| $this->cur_page->get_value( 'custom_page' ) == 'directory_aaron'
			|| $this->cur_page->get_value( 'custom_page' ) == 'admissions_application')
		{
			return '<body class="style-one-column ' . $s . '">'."\n";
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'spotlight_archive')
		{
			return '<body class="style-two-columns spotlight-archive ' . $s . '">'."\n";
		}
		//elseif (count($bc) <= 2 /*&& $this->admissions_has_related_or_timeline()*/)  // section
		//{
		//	return '<body id="home" class="style-home-01">'."\n";
		//}
		else
		{
			return '<body class="style-two-columns ' . $s . '">'."\n";
		}
    }

	function has_related_section()
	{
        if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home' || $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_live_at_luther'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			return true;
		}
		
		//if ($this->has_content( 'twitter_sub_nav' ))
		//{
		//	return true;
		//}
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'events')
		// no images allowed on events page, only for individual events
		{
			return false;
		}
		
		if ((($this->has_content('pre_sidebar') || $this->has_content('sidebar')) && $this->cur_page->get_value('custom_page') != 'standalone_login_page_stripped'))
		{
			//print_r($this->cur_page->_values);
			//print_r($this->cur_page->get_value('name'));
	
			// test if all sidebar images have keyword 'imagetop'
			// bannerad, or video.
			//$module =& $this->_get_module( 'sidebar' );
			$es = new entity_selector();
			$es->add_type(id_of('image'));
			$es->add_right_relationship($this->page_id, relationship_id_of('minisite_page_to_image'));
			$result = $es->run_one();
			if ($result != null)
			{
				foreach( $result AS $id => $image )
				{
					//echo $image->get_value('keywords');
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
		$url = get_current_url();
		
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

		if (!preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/art.?\/?/", $url))
		{
			echo '<section class="events group with-calendar" role="group">'."\n";
			if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/music\/?/", $url))
			{
				echo '<header class="red-stripe"><h1><span>Upcoming Music Events</span></h1></header>'."\n";
			}
			else 
			{
				echo '<header class="red-stripe"><h1><span>Upcoming Events</span></h1></header>'."\n";
			}
			$this->run_section( 'main_post' );		
			echo '</section> <!-- class="events group with-calendar" role="group" -->'."\n";
		}
		
		
		if ($this->has_content( 'content_blurb' ) && !preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/art.?\/?/", $url))
		{
			echo '<section class="spotlight" role="group">'."\n";
     		echo '<header class="red-stripe"><h1><span>Spotlight</span></h1></header>'."\n";
			$this->run_section( 'content_blurb' );
			echo '</section> <!-- class="spotlight" role="group" -->'."\n";
		}
     	
     	
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
	
	function carousel_main_content()
	// like the default page type but with a top carousel
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
	// contains carousel, title, breadcrumbs, add-this, and main content on the giving site
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
	
	function live_at_luther_main_content()
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
			$this->run_section( 'main_post' );
			echo '</div>'."\n";
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
	
	function sports_main_content()
	// contains carousel, title, breadcrumbs, add-this, and main content on the sports site
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
			echo '<header class="red-stripe"><h1><span>Headlines</span></h1></header>'."\n";
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

	function section()
	// section link with styling at top of left navigation column
	{		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
		{
			return;
		}
		
		$bc = $this->_get_breadcrumbs();	
		$sbtitle = $bc[0]["page_name"];
		$sbtitle = preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\2's \\1", $sbtitle);
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
