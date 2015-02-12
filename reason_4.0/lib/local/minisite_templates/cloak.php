<?php

   /* 
	*  CLOAK BASE TEMPLATE
	*
	*  CloakTemplate (/lib/local/minisite_templates/cloak.php)...
	*     extends HTML5ResponsiveTemplate (/lib/core/minisite_templates/html5_responsive.php)...
	*     which extends DefaultTemplate (/lib/core/minisite_templates/default.php)
	*  
	*  To extend a function without duplicating the parent's code, use parent::functionName();
	*  To override a parent's function but call a grandparent's function, use ClassName::functionName();. Ex: MinisiteTemplate::alter_reason_page_type($page_type); 
	*/

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/html5_responsive.php' );
	reason_include_once('classes/module_sets.php');
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'CloakTemplate';
	
	class CloakTemplate extends HTML5ResponsiveTemplate
	{
		// Don't include default Reason module styles. We'll include our own. Some modules continue to show styles anyway.
		var $include_modules_css = false;

		function alter_reason_page_type($page_type)
		{
			// Make sure we do everything the default template does.
			MinisiteTemplate::alter_reason_page_type($page_type); 

			// GLOBAL MODULE SETTINGS
			// Here we set custom paramaters without having to set them for each page type.
			// Any parameters in the page type file will take precedent.

			// Children
			if($regions = $page_type->module_regions('children'))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['thumbnail_width']))
						$page_type->set_region_parameter($region, 'thumbnail_width', 600);
					if(!isset($module['module_params']['thumbnail_height']))
						$page_type->set_region_parameter($region, 'thumbnail_height', 400);
					if(!isset($module['module_params']['thumbnail_crop']))
						$page_type->set_region_parameter($region, 'thumbnail_crop', 'fill');
					//if(!isset($module['module_params']['provide_images']))
					//	$page_type->set_region_parameter($region, 'provide_images', true);
					if(!isset($module['module_params']['description_part_of_link']))
						$page_type->set_region_parameter($region, 'description_part_of_link', true);	
					if(!isset($module['module_params']['html5']))
						$page_type->set_region_parameter($region, 'html5', true);
				}
			}

			// Image Sidebar
			// Find additional parameters for image_sidebar in minisite_templates/modules/image_sidebar.php
			if($regions = $page_type->module_regions('image_sidebar'))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['thumbnail_width']))
						$page_type->set_region_parameter($region, 'thumbnail_width', 400);
				}
			}

			// Features
			if($regions = $page_type->module_regions('feature/feature'))
			{
				foreach($regions as $region)
				{
					// if(!isset($module['module_params']['width']))
					// 	$page_type->set_region_parameter($region, 'width', 700);
					// if(!isset($module['module_params']['width']))
					// 	$page_type->set_region_parameter($region, 'height', 460);
					// if(!isset($module['module_params']['autoplay_timer']))
					// 	$page_type->set_region_parameter($region, 'autoplay_timer', 4);
					if(!isset($module['module_params']['view']))
						$page_type->set_region_parameter($region, 'view', 'cloak');
				}
			}

			// Events Mini
			if($regions = $page_type->module_regions('events_mini'))
			{
				foreach($regions as $region)
				{
					// if(!isset($module['module_params']['default_list_markup']))
					// 	$page_type->set_region_parameter($region, 'default_list_markup', 'minisite_templates/modules/events_markup/mini/cloak_mini_events_list.php');
					// if(!isset($module['module_params']['ideal_count']))
					// 	$page_type->set_region_parameter($region, 'ideal_count', 2);
				}
			}

			// Navigation
			if($regions = $page_type->module_regions(array('navigation', 'navigation_top')))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['wrapper_element']))
						$page_type->set_region_parameter($region, 'wrapper_element', 'nav');
				}
			}

			// Publications
			if($regions = $page_type->module_regions('publication'))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['css']))
						$page_type->set_region_parameter($region, 'css', false);
				}
			}

			$ms = reason_get_module_sets();
			if($regions = $page_type->module_regions($ms->get('publication_item_display')))
			{
				foreach($regions as $region)
				{
					$module = $page_type->get_region($region);
					
					if(isset($module['module_params']['markup_generator_info']))
						$markup_generators = $module['module_params']['markup_generator_info'];
					else
						$markup_generators = array();
					
					if(empty($module['module_params']['related_mode']))
					{
						if(empty($markup_generators['item']))
						{
							$markup_generators['item'] = array (
								'classname' => 'CloakItemMarkupGenerator', 
								'filename' => 'minisite_templates/modules/publication/item_markup_generators/cloak.php',
							);
						}
					}
				}
				
				$page_type->set_region_parameter($region, 'markup_generator_info', $markup_generators);
			}
			
			// Copied directly from html5_responsive.php
			// Need to create markup generator framework for publication chrome
			if($regions = $page_type->module_regions($ms->get('event_display')))
			{
				foreach($regions as $region)
				{
					$module = $page_type->get_region($region);
					
					// If uses archive list chrome
					if(
						(isset($module['module_params']['list_chrome_markup']) && 	'minisite_templates/modules/events_markup/archive/archive_events_list_chrome.php' == $module['module_params']['list_chrome_markup'])
						||
						'events_archive' == $module['module_name']
					)
					{
						$page_type->set_region_parameter($region, 'list_chrome_markup', 'minisite_templates/modules/events_markup/responsive/responsive_archive_list_chrome.php');
					}
					// If uses hybrid list chrome
					elseif(
						(isset($module['module_params']['list_chrome_markup']) && 'minisite_templates/modules/events_markup/hybrid/hybrid_events_list_chrome.php' == $module['module_params']['list_chrome_markup'])
						||
						'events_hybrid' == $module['module_name']
					)
					{
						$page_type->set_region_parameter($region, 'list_chrome_markup', 'minisite_templates/modules/events_markup/responsive/responsive_hybrid_list_chrome.php');
					}
					// If uses default list chrome
					elseif(!isset($module['module_params']['list_chrome_markup'])
						|| 'minisite_templates/modules/events_markup/default/events_list_chrome.php' == $module['module_params']['list_chrome_markup']
					)
					{
						$page_type->set_region_parameter($region, 'list_chrome_markup', 'minisite_templates/modules/events_markup/responsive/responsive_list_chrome.php');
					}
				}
			}
		}

		function get_meta_information()
		{
			MinisiteTemplate::get_meta_information();
			$this->add_head_item('meta',array('name'=>'viewport','content'=>'width=device-width, initial-scale=1.0' ) );
			
			$this->head_items->add_javascript('/reason/local/cloak/js/vendor/modernizr.js');
			$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/html5shiv/html5shiv-printshiv.js', true, array('before'=>'<!--[if lt IE 9]>','after'=>'<![endif]-->'));
			$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/respond/respond.min.js', false, array('before'=>'<!--[if lt IE 9]>','after'=>'<![endif]-->'));
			$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/ie8_fix_maxwidth.js', false, array('before'=>'<!--[if lt IE 9]>','after'=>'<![endif]-->'));
			
			// fitvids will move to the default template at some point, but for now it is here.
		
			$this->head_items->add_javascript(JQUERY_URL, true);
			$this->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'fitvids/jquery.fitvids_outside.js');
			$this->head_items->add_head_item('script', array(), $content = '$(document).ready(function(){$("body").fitVids();});' );
		}

		function show_banner()
		{
			echo '<div class="sticky">'."\n";
			//echo '<div class="top-bar" data-topbar role="navigation" data-options="sticky_on: large">'."\n";
			if ($this->has_content( 'pre_banner' ))
			{	
				echo '<div id="preBanner">';
				$this->run_section( 'pre_banner' );
				echo '</div>'."\n";
			}
			echo '<header id="banner" role="banner" aria-label="site" class="top-bar" data-topbar role="navigation" data-options="sticky_on: large">'."\n";
			//echo '<header id="banner">'."\n";
			if($this->should_show_parent_sites())
			{
				echo $this->get_parent_sites_markup();
			}

			echo '<h1><a href="'.$this->site_info->get_value('base_url').'"><span>'.$this->site_info->get_value('name').'</span></a></h1>'."\n";
			
			// Include custom search and navigation icons...
			// ...IF navigation is included on the page...
			// ...OR if a module runs in banner_extra.

			if($navJump = $this->get_navjump_id() || $this->has_content( 'banner_xtra' ))
			{
				echo '<ul id="navigationToggles">';
				if($navJump = $this->get_navjump_id())
				{
					echo '<li class="navJump"><a href="'.$navJump.'" title="Navigation menu"><span class="navJumpText">Jump to navigation menu</span></a></li>'."\n";
				}
				if($this->has_content( 'banner_xtra' ))
				{
					// Foundation Reveal Modal (lightbox)
					// http://foundation.zurb.com/docs/components/reveal.html
					echo '<li class="searchToggle"><a href="#" data-reveal-id="search" id="search-toggle"><span class="searchJumpText">Jump to site search</span></a></li>';
				}
				echo "</ul>";
			}

			$this->show_banner_xtra();
			
			echo '</header>'."\n";
			echo '</div>'."\n";
		//	echo '</div>'."\n";

			if($this->has_content('post_banner'))
			{
				echo '<div id="postBanner">'."\n";
				$this->run_section('post_banner');
				echo '</div>'."\n";
			}
		}

		// Cloak moves the location of the breadcumb naivgation.
		// In the default template (/lib/core/minisite_templates/default.php), you_are_here() runs in start_page().
		// Rather than overloading start_page() for the minor change of removing breadcrumbs, we're going to overload you_are_here();
		// and craate an identical breadcrumb function with a different name, and place it in a new location.
		function you_are_here($delimiter = '')
		{
		}

		function cloak_you_are_here($delimiter = ' <span class="delimiter">&raquo;</span> ')
		{
			echo '<div id="breadcrumbs" class="locationBarText">';
			echo '<div class="breadcrumb">';
			echo '<span class="label">You are here:</span> ';
			echo $this->_get_breadcrumb_markup($this->_get_breadcrumbs(), $this->site_info->get_value('base_breadcrumbs'), $delimiter);
			echo '</div>'."\n";
			echo '</div>'."\n";
		}

		// Cloak adds a search icon that toggles open the search bar.
		//
		// This function assumes you run the search module in the banner_xtra page location.
		// If you run a different module in banner_xtra in your page_types file, you will probably want
		// to remove this function override so that you don't get a search icon nav toggle link that toggles a different module.
		function show_banner_xtra()
		{
			if ($this->has_content( 'banner_xtra' ))
			{	
				echo '<div id="bannerXtra">';

				// Foundation Reveal Modal (lightbox)
				// http://foundation.zurb.com/docs/components/reveal.html
				echo '<div id="search" class="reveal-modal tiny" data-reveal>';
				// echo '<div class="reveal-heading">';
				// echo 'Search';
				$this->run_section( 'banner_xtra' );
				echo '<a class="close-reveal-modal"><span>Close</span></a>';
				// echo '</div>'."\n";
				echo '</div>'."\n";
				echo '</div>'."\n";
			}
		}

		// Adds cloak_you_are_here() to it's new location
		// Adds cloak_show_main_head() to it's new location
		function show_body_tableless()
		{
			$class = 'fullGraphicsView';
			echo '<div id="wrapper" class="'.$class.'">'."\n";
			echo '<div id="bannerAndMeat">'."\n";
			$this->show_banner();
			$this->cloak_you_are_here();
			//$this->cloak_show_main_head();
			$this->show_meat();
			echo '</div>'."\n";
			$this->show_footer();
			echo '</div>'."\n";
		}

		function do_org_foot()
		{	
			// FOUNDATION SCRIPTS AND DEPENDENCIES

			// Foundation recommends placing scripts directly before end of body.
			// Foundation also recommends including jQuery at the bottom of the body. But this causes conflicts
			// with Reason scripts, like Features. Currently, we're just calling it in the head via in the normal Reason way.

			echo '<script type="text/javascript" src="/reason/local/cloak/js/vendor/isotope.pkgd.min.js"></script>'."\n";
			echo '<script type="text/javascript" src="/reason/local/cloak/js/vendor/fastclick.js"></script>'."\n";
			echo '<script type="text/javascript" src="/reason/local/cloak/js/vendor/foundation.min.js"></script>'."\n";
			echo '<script type="text/javascript" src="/reason/local/cloak/js/app.js"></script>'."\n";
		}
}
?>