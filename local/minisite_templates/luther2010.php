<?php

/**
 * A sample Reason template, with minimal overloading of methods
 */
 
/**
 * Include the base template so we can extend it
 */
reason_include_once( 'minisite_templates/default.php' );

/**
 * Register this new template with Reason
 */
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'LutherTemplate2010';

class LutherTemplate2010 extends MinisiteTemplate
{
	// reorder sections so that navigation is first instead of last
	var $sections = array('navigation'=>'show_navbar','content'=>'show_main_content','related'=>'show_sidebar');
	var $doctype = '<!DOCTYPE html">';
	public $luther_add_this_complete = false;

        function start_page() 
        {

                $this->get_title();

                // start page
                echo $this->get_doctype()."\n";
		echo '<html lang="en">'."\n";
                echo '<head>'."\n";
                //echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n";

                $this->do_org_head_items();

                echo $this->head_items->get_head_item_markup();

                if($this->cur_page->get_value('extra_head_content'))
                {
                        echo "\n".$this->cur_page->get_value('extra_head_content')."\n";
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
                $this->show_banner_xtra();
                //echo '</div>'."\n";
		if($this->has_content('post_banner'))
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

		echo '<div class="span-24">'."\n";
        	echo '<div id="nav">'."\n";
                // Navigation area
                echo '<div id="navigation">'."\n";

                if ($this->has_content( 'navigation' ))
                {
                        $this->run_section( 'navigation' );
                }
		echo '</div>'."\n";
		echo '</div id="nav">'."\n";

                if ($this->has_content( 'sub_nav' ))
                {
                        echo '<div id="subNav">'."\n";
                        $this->run_section( 'sub_nav' );
                        echo '</div>'."\n";
                }

		$this->run_section( 'bannerad' );
                $this->run_section( 'sbvideo' );
                if ($this->has_content( 'twitter_sub_nav' ))
                {
                        $this->run_section( 'twitter_sub_nav' );
                }
                if ($this->has_content( 'sub_nav_2' ))
		// Contact Information
                {
                        $this->run_section( 'sub_nav_2' );
                }

                if ($this->has_content( 'sub_nav_3' ))
                {
                        $this->run_section( 'sub_nav_3' );
                }


		echo '</div class="span-24">'."\n";

        } 

	function show_sidebar_tableless()
	// right column
        {
		echo '<div class="span-24 last">'."\n";
                if($this->has_content( 'pre_sidebar' ))
                {
                        echo '<div id="preSidebar">'."\n";
                        $this->run_section( 'pre_sidebar' );
                        echo '</div>'."\n";
                }
		if ($this->has_content( 'sidebar' ))
                {
                        echo '<div id="sidebar">'."\n";
                        $this->run_section( 'sidebar' );
                        echo '</div>'."\n";
                }
                if($this->has_content( 'post_sidebar' ))
                {
                        echo '<div id="postSidebar">'."\n";
                        $this->run_section( 'post_sidebar' );
                        echo '</div>'."\n";
                }
		echo '</div> <!--  class="span-24 last" -->'."\n";
		echo '</div>'."\n";
		echo '</div class="span-48 last">'."\n";

        }


	function luther_breadcrumbs()
	{
		echo '<div id="crumbs">'."\n";
		echo $this->_get_breadcrumb_markup($this->_get_breadcrumbs(), $this->site_info->get_value('base_breadcrumbs'), '&nbsp;&#187;&nbsp;');
		echo '</div>'."\n";

	}

	function luther_add_this()
	// insert "add this" capability to luther pages linking to facebook,
	// twitter, delicious, etc.
	{
		if ($this->cur_page->get_value( 'custom_page' ) != 'publication'			&& $this->cur_page->get_value('custom_page') != 'default'
			&& $this->cur_page->get_value('custom_page') != 'audio_video')
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
			$this->home_events_news_spotlight();
			return;
		}

		echo '<div class="span-48 last">'."\n";

                $this->run_section( 'imagetop' );
		$this->luther_breadcrumbs();
					if (!$this->luther_add_this_complete)
					{
						$this->luther_add_this();
					}

                if ($this->has_content( 'main_head' ))
                {
                        echo '<div class="contentHead">'."\n";
                        $this->run_section( 'main_head' );
                        echo '</div>'."\n";
                }

		if ($this->cur_page->get_value( 'custom_page' ) != 'spotlight_archive' && $this->cur_page->get_value( 'custom_page' ) != 'luther_publication')
		{
			echo '<div class="span-48">'."\n";
		}

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

		// rough-in right column if there is no content
		if ($this->has_related_section() == false) {
			$this->show_sidebar_tableless();	
		}
        }

	function do_org_head_items()
        {
                // Just here as a hook for branding head items (js/css/etc.)
		//$this->head_items->add_javascript( '/javascripts/highslide/highslide-with-html.js' );

		$this->head_items->add_javascript( '/javascripts/highslide/highslide-full.js' );
		$this->head_items->add_javascript( '/javascripts/highslide/highslide-overrides.js' );
		if ($this->cur_page->get_value('custom_page') != 'image_slideshow')
		{
			//$this->head_items->add_javascript( '/javascripts/highslide/highslide-overrides.js' );
		}
		if ($this->cur_page->get_value('custom_page') == 'image_slideshow')
		{
		//	$this->head_items->add_javascript( '/javascripts/highslide/highslide-gallery-overrides-dim.js' );
			//$this->head_items->add_stylesheet('/javascripts/highslide/highslide-gallery-overrides.css');
		}

			//$this->head_items->add_javascript( '/javascripts/prototype.js' );
			//$this->head_items->add_javascript( '/javascripts/effects.js' );
			//$this->head_items->add_javascript( '/javascripts/dragdrop.js' );
			//$this->head_items->add_javascript( '/javascripts/controls.js' );
			//$this->head_items->add_javascript( '/javascripts/application.js' );
			//$this->head_items->add_javascript( '/javascripts/scriptaculous.js' );
		$this->head_items->add_javascript( '//ajax.googleapis.com/ajax/libs/swfobject/2.1/swfobject.js');
		$this->head_items->add_javascript( '//ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js' );
        }

	function has_related_section()
        {
                if((($this->has_content('pre_sidebar') || $this->has_content('sidebar')) && $this->cur_page->get_value('custom_page') != 'standalone_login_page_stripped'))
                {
			//print_r($this->cur_page->_values);
			//print_r($this->cur_page->get_value('name'));

			// test if all sidebar images have keyword 'imagetop'
			// bannerad, or video.
			$module =& $this->_get_module( 'sidebar' );
			foreach( $module->images AS $id => $image )
                        {
                                if (!preg_match("/imagetop|bannerad|video/", $image->get_value('keywords')))
                                {
                                        return true;
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

		echo '<div class="span-72">'."\n";
                $this->run_section( 'navigation' );
		echo '</div class="span-72">'."\n";

		echo '<div class="span-24 last">'."\n";
                $this->run_section( 'sub_nav' );
		echo '</div class="span-24 last">'."\n";
		
	}

	function home_events_news_spotlight()
	// contains events, news, and spotlight in fold of home page
	{

		echo '<div class="span-24">'."\n";
                $this->run_section( 'main_head');
		echo '</div class="span-24">'."\n";

		echo '<div class="span-24">'."\n";
                $this->run_section( 'main');
		echo '</div class="span-24">'."\n";
		
		echo '<div class="span-48 last">'."\n";
                $this->run_section( 'main_post');
		echo '</div class="span-48 last">'."\n";
	}

	function home_bannerads()
	// contains bottom 4 bannerads on home page
	{
		echo '<div class="span-96 last">'."\n";
                $this->run_section( 'sidebar');
		echo '</div class="span-96 last">'."\n";

	}

}

?>
