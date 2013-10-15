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
	var $include_modules_css = false;
	var $nav_class = 'NoRootNavigation'; 
	

	function start_page() 
	{
		$url = get_current_url();
		// redirect www.luther.edu to www.luther.edu/mobile if using mobile device
		// do not redirect on subsequent visits for as long as browser is open
		if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/$/", $url)
			&& luther_is_mobile_device()
			&& $_COOKIE['mobileDeviceAndRedirected'] != 'true')	
		{
			setcookie('mobileDeviceAndRedirected', 'true');
	    	header("Location: /mobile");
		}

		$this->get_title();

		// start page
		$s = "";
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
				
			echo '</script>'."\n";
		}
		
		// echo '<script>' . "\n";
		// echo '  (function() {' . "\n";
		// echo '    var cx = \'005935510434836484605:yecpxhsqj6s\';' . "\n";
		// echo '    var gcse = document.createElement(\'script\'); gcse.type = \'text/javascript\'; gcse.async = true;' . "\n";
		// echo '    gcse.src = (document.location.protocol == \'https:\' ? \'https:\' : \'http:\') +' . "\n";
		// echo '        \'//www.google.com/cse/cse.js?cx=\' + cx;' . "\n";
		// echo '    var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(gcse, s);' . "\n";
		// echo '  })();' . "\n";
		// echo '</script>' . "\n";

		echo $this->create_body_tag();
		echo '<script>' . "\n";
 		echo '(function() {' . "\n";
	    	echo 'var cx = \'005935510434836484605:yecpxhsqj6s\';' . "\n";
		    echo 'var gcse = document.createElement(\'script\');' . "\n";
		    echo 'gcse.type = \'text/javascript\';' . "\n";
		    echo 'gcse.async = true;' . "\n";
		    echo 'gcse.src = (document.location.protocol == \'https:\' ? \'https:\' : \'http:\') +' . "\n";
		    echo '\'//www.google.com/cse/cse.js?cx=\' + cx;' . "\n";
		    echo 'var s = document.getElementsByTagName(\'script\')[0];' . "\n";
		    echo 's.parentNode.insertBefore(gcse, s);' . "\n";
		echo '})();' . "\n";
		echo '</script>' . "\n";

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
		if ($this->has_content( 'edit_link' )  && ($this->cur_page->get_value( 'custom_page' ) != 'luther2010_home_feature'))
		{
			$this->run_section( 'edit_link');
		}
		echo '</div> <!-- id="wrapper" class="'.$class.'" -->'."\n";
		
	}
	function end_page()
	{
		// finish body and html
		$this->do_org_foot();
		//$this->_do_testing_form();
		if (($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home'  
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home_feature')
				&& !preg_match("/^localhost$/", REASON_HOST, $matches))
		{
		// crazy egg javascript heat map on home page
		echo '
			<script type="text/javascript">
			setTimeout(function(){
				var a=document.createElement("script");
				var b=document.getElementsByTagName("script")[0];
				a.src=document.location.protocol+"//dnn506yrbagrg.cloudfront.net/pages/scripts/0015/0241.js?"+Math.floor(new Date().getTime()/3600000);
				a.async=true;a.type="text/javascript";b.parentNode.insertBefore(a,b)}, 1);
			</script>'."\n";
		}
		echo '</body>'."\n";
		
		$url = get_current_url();
		if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/admissions\/?/", $url) && !luther_is_local_ip())
		{
			// reachlocal remarketing code for admissions site
			echo '<script src="http://i.simpli.fi/dpx.js?cid=25&action=100&segment=2769357&m=1"></script>'."\n";
		}
		
		echo '</html>'."\n";
	}

	function show_banner_tableless()
	{
		if ($this->has_content( 'pre_banner' ))
		{      
			$this->run_section( 'pre_banner' );
		}
		if ($this->has_content( 'lis_site_announcements' ))
		{      
			$this->run_section( 'lis_site_announcements' );
		}
		if ($this->should_show_parent_sites())
		{
			echo $this->get_parent_sites_markup();
		}
		// top navigation bar
		$this->show_banner_xtra();
		if ($this->has_content('post_banner'))
		{
			$this->run_section('post_banner');
		}
	}

	function show_navbar_tableless()
	// left column navigation
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home_feature')
		{
			$this->home_topimage_quote();
			return;
		}

		echo '<div class="content content-secondary">'."\n";
		
		if ($this->has_content( 'pre_nav' ))
		{
			$this->run_section( 'pre_nav' );
		}
		
		// Navigation area
		if ($this->site_id == id_of('connect'))
		{
			echo '<nav id="nav-section" class="show-icon" role="navigation">'."\n";
		}
		else 
		{
			echo '<nav id="nav-section" role="navigation">'."\n";
		}
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

		if ($this->has_content( 'sub_nav_2' ))
		{
			$this->run_section( 'sub_nav_2' );
			echo '<hr>';
		}
		
		if ($this->has_content( 'sub_nav_3' ))
		{
			$this->run_section( 'sub_nav_3');
			echo'<hr>'."\n";
		}
		
		if ($this->has_content( 'sub_nav_4' ))
		{
			$this->run_section( 'sub_nav_4');
			echo'<hr>'."\n";
		}
		
		if ($this->has_content( 'sub_nav_5' ))
		{
			$this->run_section( 'sub_nav_5');
			echo'<hr>'."\n";
		}
		echo '</div> <!-- class="content content-secondary" -->'."\n";

	} 

	function show_sidebar_tableless()
	// right column
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home_feature')
		{	
			$this->home_events_news_spotlight();
			$this->run_section( 'post_sidebar_2');  // banner at bottom
			return;
		}
		
		$url = get_current_url();
		
		echo '<div class="content content-tertiary">'."\n";
		
		if ($this->has_content( 'pre_sidebar' ))
		{
			echo '<div id="preSidebar">'."\n";
			$this->run_section( 'pre_sidebar' );
			echo '<hr>'."\n";
			echo '</div>'."\n";
		}
		if ($this->has_content( 'pre_sidebar_2' ))
		{
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_news_portal')
			{
				echo '<aside class="news group">'."\n";
				echo '<header class="blue-stripe"><h1><span>Luther News</span></h1></header>'."\n";
			}
			$this->run_section( 'pre_sidebar_2' );
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_news_portal')
			{
				echo '</aside> <!-- class="news group" -->'."\n";
			}
			echo '<hr>'."\n";
		}
		if ($this->has_content( 'pre_sidebar_3' ))
		{
			$this->run_section( 'pre_sidebar_3' );
			echo '<hr>'."\n";
		}		
		
		if ($this->has_content( 'sidebar' ))
		{
			echo '<div id="sidebar">'."\n";
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				echo '<aside class="news group">'."\n";
				echo '<header class="blue-stripe"><h1><span>Video of the Week</span></h1></header>'."\n";
			}
			elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_form'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_publication'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_sidebar_news'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music')
			{
				echo '<aside class="news group">'."\n";
				echo '<header class="blue-stripe"><h1><span>Featured Video</span></h1></header>'."\n";
			}
			
			$this->run_section( 'sidebar' );
			
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_form'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_publication'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_sidebar_news'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				echo '</aside> <!-- class="news group" -->'."\n";
			}
			echo '<hr>'."\n";
			echo '</div>'."\n";
		}		
		if ($this->has_content( 'sidebar_2' ))
		{
			$this->run_section( 'sidebar_2' );
			echo '<hr>'."\n";
		}
		if ($this->has_content( 'sidebar_3' ))
		{
			$this->run_section( 'sidebar_3' );
			echo '<hr>'."\n";
		}					
		if ($this->has_content( 'sidebar_4' ))
		{
			$this->run_section( 'sidebar_4' );
			echo '<hr>'."\n";
		}
		if ($this->has_content( 'sidebar_5' ))
		{
			// Override default behavior of related publication on landing pages
			// If no related publicatons are attached, no news listing will appear
			$es = new entity_selector();
			$es->add_type(id_of('publication_type'));
			$es->add_right_relationship($this->page_id, relationship_id_of('page_to_related_publication'));
			$result = $es->run_one();
			if ($result == null)
			{
				return;
			}	
			$first = reset($result);
			$title = $first->get_value('name');
						
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_form'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_publication'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_sidebar_news'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_news_portal'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music')
			{
				echo '<aside class="news group">'."\n";
				if (count($result) == 1 && strlen($title) < 25)   // need name to fit on one line
				{	
					echo '<header class="blue-stripe"><h1><span>' . $title .'</span></h1></header>'."\n";
				}
				else
				{
					echo '<header class="blue-stripe"><h1><span>News</span></h1></header>'."\n";
				}
			}
				
			$this->run_section( 'sidebar_5' );
			
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_form'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_publication'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_sidebar_news'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_news_portal'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music')
			{
				echo '</aside> <!-- class="news group" -->'."\n";
			}
			
			echo '<hr>'."\n";
		}	
		
		if ($this->has_content( 'post_sidebar' ))
		{
			echo '<div id="post_sidebar">'."\n";
			$url = get_current_url();
			if ($this->cur_page->get_value( 'custom_page' ) == 'flickr_slideshow_sidebar'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_form'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_publication'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_sidebar_news'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information')
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
				echo "<div id=\"gallery\">\n";
				echo "<div class=\"gallery-info\">\n";
				echo "<div id=\"gallerycontainer\">\n";
			}		
			if ($this->cur_page->get_value( 'custom_page' ) != 'flickr_slideshow_sidebar'	
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_landing'
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_landing_feature'
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_landing_feature_form'
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_landing_feature_publication'
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_landing_feature_sidebar_news'
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_music'
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_public_information')
			{
				echo "<hr>\n";
			}
			
			$this->run_section( 'post_sidebar' );
			
			if ($this->cur_page->get_value( 'custom_page' ) == 'flickr_slideshow_sidebar'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_form'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_publication'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_sidebar_news'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information')
			{
				echo "</div>   <!-- id=\"gallerycontainer\"-->\n";
				echo "</div>   <!-- class=\"gallery-info\"-->\n";
				echo "</div>   <!-- id=\"gallery\"-->\n";
				echo '</aside> <!-- class="gallery group" -->'."\n";
			}
			echo '<hr>'."\n";
			echo '</div>'."\n";
		}		
		if ($this->has_content( 'post_sidebar_2' ))
		{
			$this->run_section( 'post_sidebar_2' );
			echo '<hr>'."\n";
		}
		if ($this->has_content( 'post_sidebar_3' ))
		{
			$this->run_section( 'post_sidebar_3' );
			echo '<hr>'."\n";
		}		
		echo '</div> <!-- class="content content-tertiary" -->'."\n";

	}

	function show_main_content_sections()
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home_feature')
		{
			return;
		}

		if ($this->has_content( 'main_head' ))
		{			
			$this->run_section( 'main_head' );			
		}
		
		echo '<div class="content content-primary">'."\n";
				
		if ($this->has_content( 'main_head_2' ))
		{			
			$this->run_section( 'main_head_2' );			
		}
		if ($this->has_content( 'main_head_3' ))
		{			
			$this->run_section( 'main_head_3' );			
		}
		if ($this->has_content( 'main_head_4' ))
		{			
			$this->run_section( 'main_head_4' );			
		}
		if ($this->has_content( 'main_head_5' ))
		{			
			$this->run_section( 'main_head_5' );			
		}

		if ($this->has_content( 'main' ))
		{
			echo '<div class="contentMain">'."\n";
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'main_2' ))
		{			
			$this->run_section( 'main_2' );			
		}
		if ($this->has_content( 'main_3' ))
		{			
			$this->run_section( 'main_3' );			
		}
		if ($this->has_content( 'main_4' ))
		{			
			$this->run_section( 'main_4' );			
		}
		if ($this->has_content( 'main_5' ))
		{			
			$this->run_section( 'main_5' );			
		}

		if ($this->has_content( 'main_post' ))
		{
			echo '<div class="contentPost">'."\n";
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_news_portal')
			{
				echo '<header class="blue-stripe"><h1><span>Academic Blog</span></h1></header>'."\n";
			}
			$this->run_section( 'main_post' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'main_post_2' ))
		{
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				echo '<aside class="gallery group">'."\n";
				if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
					|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
					|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music')
				{
					echo '<header class="red-stripe"><h1><span>Featured Gallery</span></h1></header>'."\n";
				}
				else
				{
					echo '<header class="blue-stripe"><h1><span>Featured Gallery</span></h1></header>'."\n";
				}
			}
			
			echo "<div id=\"gallery\">\n";
			echo "<div class=\"gallery-info\">\n";
			echo "<div id=\"gallerycontainer\">\n";
				
			if ($this->cur_page->get_value( 'custom_page' ) != 'luther2010_admissions'
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_alumni'
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_music'
				&& $this->cur_page->get_value( 'custom_page' ) != 'luther2010_sports')
			{
				echo "<hr>\n";
			}
						
			$this->run_section( 'main_post_2' );

			echo "</div>   <!-- id=\"gallerycontainer\"-->\n";
			echo "</div>   <!-- class=\"gallery-info\"-->\n";
			echo "</div>   <!-- id=\"gallery\"-->\n";
			
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
				|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				echo '</aside> <!-- class="gallery group" -->'."\n";
			}
		}
		if ($this->has_content( 'main_post_3' ))
		{			
			$this->run_section( 'main_post_3' );			
		}
		
		echo '</div> <!-- class="content content-primary" -->'."\n";        
		// rough-in right column if there is no content
		if ($this->has_related_section() == false)
		{
			$this->show_sidebar_tableless();	
		}
	}
	
	function show_main_content_tabled()
	{
		if($this->has_content( 'main_head' )
			|| $this->has_content( 'main_head_2' )
			|| $this->has_content( 'main_head_3' )
			|| $this->has_content( 'main_head_4' )
			|| $this->has_content( 'main_head_5' )
			|| $this->has_content( 'main' )
			|| $this->has_content( 'main_2' )
			|| $this->has_content( 'main_3' )
			|| $this->has_content( 'main_4' )
			|| $this->has_content( 'main_5' )
			|| $this->has_content( 'main_post' ) 
			|| $this->has_content( 'main_post_2' ) 
			|| $this->has_content( 'main_post_3' ) ) 
		{
			if (empty($this->textonly))
				echo '<td valign="top" class="contentTD">'."\n";
			echo '<div class="content"><a name="content"></a>'."\n";
			$this->show_main_content_sections();
			echo '</div>'."\n";
			if (empty($this->textonly))
				echo '</td>'."\n";
		}
	}

	function do_org_head_items()
	{
		// Just here as a hook for branding head items (js/css/etc.)
		echo '<meta http-equiv="X-UA-Compatible" content="IE=edge" />'."\n";
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/modules.css');
		$this->head_items->add_stylesheet('/javascripts/highslide/highslide.css');
		$this->head_items->add_stylesheet('/javascripts/cluetip/jquery.cluetip.css');
		$this->head_items->add_stylesheet('/stylesheets/luther2010/master.css');
		$this->head_items->add_stylesheet('/stylesheets/luther2010/reason.css');
		$this->head_items->add_stylesheet('/stylesheets/luther2010/print.css', 'print');
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther_tab_widget'
			|| ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature' && $this->has_content('main_head_5')))
		{
			$this->head_items->add_stylesheet(JQUERY_UI_CSS_URL);
			//$this->head_items->add_stylesheet('/stylesheets/luther2010/aristoJqueryUITheme.css');
		}	
		//echo '<link rel="stylesheet" type="text/css" href="'.REASON_HTTP_BASE_PATH.'css/modules.css" />'."\n";
		//echo '<link href="/javascripts/highslide/highslide.css" media="screen, projection" rel="stylesheet" type="text/css" />'."\n";
		//echo '<link href="/javascripts/cluetip/jquery.cluetip.css" media="screen, projection" rel="stylesheet" type="text/css" />'."\n";
		//echo '<link href="/stylesheets/luther2010/master.css" media="screen, projection" rel="stylesheet" type="text/css" />'."\n";
		//echo '<link href="/stylesheets/luther2010/reason.css" media="screen, projection" rel="stylesheet" type="text/css" />'."\n";  
  		//echo '<link href="/stylesheets/luther2010/print.css" media="print" rel="stylesheet" type="text/css" />'."\n";
		$this->head_items->add_javascript( '/javascripts/modernizr-1.1.min.js' );
		$this->head_items->add_javascript( JQUERY_UI_URL, true );
  		$this->head_items->add_javascript( JQUERY_URL, true );
  		// echo '<script src="/javascripts/modernizr-1.1.min.js" type="text/javascript"></script>'."\n";
  		//echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script>'."\n";
		//echo '<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js"></script>'."\n";
		echo '<!--[if lt IE 9]><link href="/stylesheets/luther2010/ie8.css" media="all" rel="stylesheet" type="text/css" /><![endif]-->'."\n";  
  		echo '<!--[if lt IE 8]><link href="/stylesheets/luther2010/ie7.css" media="all" rel="stylesheet" type="text/css" /><![endif]-->'."\n";
  		echo '<!--[if lt IE 7]><link href="/stylesheets/luther2010/ie6.css" media="all" rel="stylesheet" type="text/css" /><![endif]-->'."\n";  		
  		
		echo '<meta property="og:title" content="' . $this->title . '" />'."\n";
		echo '<meta property="og:type" content="university" />'."\n";
		echo '<meta property="og:url" content="' . get_current_url() . '" />'."\n";
		echo '<meta property="og:site_name" content="Luther College" />'."\n";
		echo '<meta property="og:image" content="" />'."\n";
		echo '<meta property="og:street-address" content="700 College Drive"/>'."\n";
		echo '<meta property="og:locality" content="Decorah" />'."\n";
		echo '<meta property="og:region" content="Iowa" />'."\n";
		echo '<meta property="og:country" content="USA" />'."\n";
		echo '<meta property="og:email" content="www@luther.edu"/>'."\n";
		echo '<meta property="og:phone_number" content="563-387-2000"/>'."\n";

		echo '<link rel="icon" href="/favicon.ico" type="image/x-icon">'."\n";
		echo '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">'."\n"; 
		$this->head_items->add_javascript('/javascripts/highslide/highslide-full.js' );
		$this->head_items->add_javascript('/javascripts/highslide/highslide-overrides.js' );
		$this->head_items->add_javascript('//ajax.googleapis.com/ajax/libs/swfobject/2.1/swfobject.js');
		
		$this->head_items->add_javascript('/javascripts/jquery.tmpl.js');
		$this->head_items->add_javascript('/javascripts/jquery.metadata.js');
		$this->head_items->add_javascript('/javascripts/tablesorter.min.js');
		$this->head_items->add_javascript('/javascripts/jquery.hoverIntent.min.js');
		$this->head_items->add_javascript('/javascripts/cluetip/jquery.cluetip.js');
		//$this->head_items->add_javascript('/javascripts/jquery.init.js');  // jquery.init.js moved to luther_footer.php
		$this->head_items->add_javascript('/reason/jquery.watermark-3.1.3/jquery.watermark.min.js');
		$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/jquery.tools.min.js');
		$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/jquery.maskedinput-1.3.1.min.js');
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
		elseif (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/admissions\/?/", $url))
		{
			$s = 'admissions';
		}
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home_feature') 
		{
			return '<body id="home" class="style-home-00">'."\n";
		}
		else if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_carousel'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_form'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_publication'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_sidebar_news'
			|| $this->cur_page->get_value( 'custom_page' ) == 'publication_feature_autoplay'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_news_portal'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports'
        	|| preg_match("/^feature/", $this->cur_page->get_value( 'custom_page' )))
		{
			return '<body id="home" class="style-home-01 ' . $s . '">'."\n";
		}
		//elseif ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving')
		//{
		//	return '<body id="home" class="style-home-02" >'."\n";
		//}
		elseif (($this->cur_page->get_value( 'custom_page' ) == 'events' && !preg_match("/[&?]event_id=\d+/", $url))
			|| ($this->cur_page->get_value( 'custom_page' ) == 'events_instancewide' && !preg_match("/[&?]event_id=\d+/", $url))
			|| ($this->cur_page->get_value( 'custom_page' ) == 'event_slot_registration' && !preg_match("/[&?]event_id=\d+/", $url))
			|| $this->cur_page->get_value( 'custom_page' ) == 'sports_roster'
			|| ($this->cur_page->get_value( 'custom_page' ) == 'sports_results' && !preg_match("/[&?]event_id=\d+/", $url))
			|| $this->cur_page->get_value( 'custom_page' ) == 'directory'
			|| $this->cur_page->get_value( 'custom_page' ) == 'admissions_application'
			|| $this->cur_page->get_value( 'custom_page' ) == 'study_skills_assessment'
			|| $this->cur_page->get_value( 'custom_page' ) == 'net_price_calculator')
		{
			return '<body class="style-one-column ' . $s . '">'."\n";
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'search_results')
		{
			return '<body class="style-search-results ' . $s . '">' ."\n"; 
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'spotlight_archive')
		{
			return '<body class="style-two-columns spotlight-archive ' . $s . '">'."\n";
		}
		elseif ($this->cur_page->get_value( 'custom_page' ) == 'spotlight_detailed_list')
		{
			return '<body class="style-two-columns spotlight-detailed-list ' . $s . '">'."\n";
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
    
	function has_content_section()
	{
		if($this->has_content( 'main_head' )
			|| $this->has_content( 'main_head_2' )
			|| $this->has_content( 'main_head_3' )
			|| $this->has_content( 'main_head_4' )
			|| $this->has_content( 'main_head_5' )
			|| $this->has_content( 'main' )
			|| $this->has_content( 'main_2' )
			|| $this->has_content( 'main_3' )
			|| $this->has_content( 'main_4' )
			|| $this->has_content( 'main_5' )
			|| $this->has_content( 'main_post' ) 
			|| $this->has_content( 'main_post_2' ) 
			|| $this->has_content( 'main_post_3' ) )
		{
			return true;
		}
		return false;
	}

	function has_related_section()
	{
        if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_admissions'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_carousel'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_home_feature'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_form'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_publication'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_landing_feature_sidebar_news'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
        	|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			return true;
		}
		
		//if ($this->has_content( 'twitter_sub_nav' ))
		//{
		//	return true;
		//}
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'events'
			|| $this->cur_page->get_value( 'custom_page' ) == 'events_instancewide'
			|| $this->cur_page->get_value( 'custom_page' ) == 'event_slot_registration'
				)
		// no images allowed on events page, only for individual events
		{
			return false;
		}
		
		if ((($this->has_content('sidebar') || $this->has_content('sidebar_2')) && $this->cur_page->get_value('custom_page') != 'standalone_login_page_stripped'))
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

			if ($this->cur_page->get_value('custom_page') == 'audio_video'
				|| $this->cur_page->get_value('custom_page') == 'audio_video_reverse_chronological')
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
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home_feature')
		{
			$this->run_section( 'sub_nav');
			$this->run_section( 'navigation');
		}
		else
		{
			$this->run_section( 'navigation');
			$this->run_section( 'sub_nav');	
		}
		
		echo '</div> <!-- class="container-carousel-and-attribute" -->'."\n";         

		
	}

	function home_events_news_spotlight()
	// contains events, news, and spotlight in fold of home page
	{
		echo '<div class="container-events-news-and-spotlight">'."\n";
		
		echo '<section class="events" role="group">'."\n";
		echo '<header class="red-stripe"><h1><span>Events</span></h1></header>'."\n";
			$this->run_section( 'pre_sidebar');
		echo '</section> <!-- class="events" role="group" -->'."\n";

		echo '<section class="news" role="group">'."\n";
		echo '<header class="red-stripe"><h1><span>News</span></h1></header>'."\n";
			$this->run_section( 'sidebar');
		echo '</section> <!-- class="news" role="group" -->'."\n";
		
		echo '<section class="spotlight" role="group">'."\n";
		if ($this->has_content('sidebar_4'))
		{
			echo '<header class="red-stripe"><h1><span>Video</span></h1></header>'."\n";
			$this->run_section( 'sidebar_4');
		}
		else
		{
			echo '<header class="red-stripe"><h1><span>Spotlight</span></h1></header>'."\n";
			$this->run_section( 'post_sidebar');
		}
			
		echo '</section> <!-- class="spotlight" role="group" -->'."\n";
			
		echo '</div> <!-- class="container-events-news-and-spotlight" -->'."\n";
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
