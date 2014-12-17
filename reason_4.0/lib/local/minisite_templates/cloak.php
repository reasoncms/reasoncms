<?php

   /* 
	*  CLOAK BASE TEMPLATE
	*
	*  CloakTemplate (/lib/local/minisite_templates/cloak.php)...
	*     extends HTML5ResponsiveTemplate (/lib/core/minisite_templates/html5_responsive.php)...
	*     which extends DefaultTemplate (/lib/core/minisite_templates/default.php)
	*  
	*  To extend a function without copying the parent's code, use parent::functionName();
	*/

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/html5_responsive.php' );
	//reason_include_once('classes/module_sets.php');
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'CloakTemplate';
	
	class CloakTemplate extends HTML5ResponsiveTemplate
	{
		// var $doctype = '<!DOCTYPE html>';
		// var $section_elements = array('content'=>'main','related'=>'aside');
		// var $section_roles = array('content'=>'main','related'=>'complementary');
		
		// function alter_reason_page_type($page_type)
		// {
		// 	parent::alter_reason_page_type($page_type);
		// 	if($regions = $page_type->module_regions(array('navigation', 'navigation_top')))
		// 	{
		// 		foreach($regions as $region)
		// 		{
		// 			if(!isset($module['module_params']['wrapper_element']))
		// 				$page_type->set_region_parameter($region, 'wrapper_element', 'nav');
		// 		}
		// 	}
		// 	if($regions = $page_type->module_regions('children'))
		// 	{
		// 		foreach($regions as $region)
		// 		{
		// 			if(!isset($module['module_params']['html5']))
		// 				$page_type->set_region_parameter($region, 'html5', true);
		// 		}
		// 	}
		// 	// Anything with publications?
		// 	$ms = reason_get_module_sets();
		// 	if($regions = $page_type->module_regions($ms->get('publication_item_display')))
		// 	{
		// 		foreach($regions as $region)
		// 		{
		// 			$module = $page_type->get_region($region);
					
		// 			if(isset($module['module_params']['markup_generator_info']))
		// 				$markup_generators = $module['module_params']['markup_generator_info'];
		// 			else
		// 				$markup_generators = array();
					
		// 			if(empty($module['module_params']['related_mode']))
		// 			{
		// 				if(empty($markup_generators['item']))
		// 				{
		// 					$markup_generators['item'] = array (
		// 						'classname' => 'ResponsiveItemMarkupGenerator', 
		// 						'filename' => 'minisite_templates/modules/publication/item_markup_generators/responsive.php',
		// 					);
		// 				}
		// 			}
					
		// 		}
				
		// 		$page_type->set_region_parameter($region, 'markup_generator_info', $markup_generators);
		// 	}
			
		// 	// Need to create markup generator framework for publication chrome
			
		// 	if($regions = $page_type->module_regions($ms->get('event_display')))
		// 	{
		// 		foreach($regions as $region)
		// 		{
		// 			$module = $page_type->get_region($region);
					
		// 			// If uses archive list chrome
		// 			if(
		// 				(isset($module['module_params']['list_chrome_markup']) && 	'minisite_templates/modules/events_markup/archive/archive_events_list_chrome.php' == $module['module_params']['list_chrome_markup'])
		// 				||
		// 				'events_archive' == $module['module_name']
		// 			)
		// 			{
		// 				$page_type->set_region_parameter($region, 'list_chrome_markup', 'minisite_templates/modules/events_markup/responsive/responsive_archive_list_chrome.php');
		// 			}
		// 			// If uses hybrid list chrome
		// 			elseif(
		// 				(isset($module['module_params']['list_chrome_markup']) && 'minisite_templates/modules/events_markup/hybrid/hybrid_events_list_chrome.php' == $module['module_params']['list_chrome_markup'])
		// 				||
		// 				'events_hybrid' == $module['module_name']
		// 			)
		// 			{
		// 				$page_type->set_region_parameter($region, 'list_chrome_markup', 'minisite_templates/modules/events_markup/responsive/responsive_hybrid_list_chrome.php');
		// 			}
		// 			// If uses default list chrome
		// 			elseif(!isset($module['module_params']['list_chrome_markup'])
		// 				|| 'minisite_templates/modules/events_markup/default/events_list_chrome.php' == $module['module_params']['list_chrome_markup']
		// 			)
		// 			{
		// 				$page_type->set_region_parameter($region, 'list_chrome_markup', 'minisite_templates/modules/events_markup/responsive/responsive_list_chrome.php');
		// 			}
		// 		}
		// 	}
		// }

		function get_meta_information()
		{
			parent::get_meta_information();
			$this->head_items->add_javascript('/reason/local/cloak/js/vendor/modernizr.js');
		}

		function show_banner()
		{
			if ($this->has_content( 'pre_banner' ))
			{	
				echo '<div id="preBanner">';
				$this->run_section( 'pre_banner' );
				echo '</div>'."\n";
			}
			echo '<header id="banner" role="banner" aria-label="site">'."\n";
			if($this->should_show_parent_sites())
			{
				echo $this->get_parent_sites_markup();
			}

			echo '<h1><a href="'.$this->site_info->get_value('base_url').'"><span>'.$this->site_info->get_value('name').'</span></a></h1>'."\n";
			
			// Return search and navigation icons
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

		function cloak_you_are_here($delimiter = ' &gt; ')
		{
			echo '<div id="breadcrumbs" class="locationBarText">';
			echo '<div class="breadcrumb">';
			echo 'You are here: ';
			echo $this->_get_breadcrumb_markup($this->_get_breadcrumbs(), $this->site_info->get_value('base_breadcrumbs'), $delimiter);
			echo '</div>'."\n";
			echo '</div>'."\n";
		}

		// Cloak adds search icon that toggles open the search bar.
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
				$this->run_section( 'banner_xtra' );
				echo '<a class="close-reveal-modal"><span>Close</span></a>';
				echo '</div>'."\n";
				echo '</div>'."\n";
			}
		}

		// Here we add cloak_you_are_here() to it's new location
		function show_body_tableless()
		{
			$class = 'fullGraphicsView';
			echo '<div id="wrapper" class="'.$class.'">'."\n";
			echo '<div id="bannerAndMeat">'."\n";
			$this->show_banner();
			$this->cloak_you_are_here();
			$this->show_meat();
			echo '</div>'."\n";
			$this->show_footer();
			echo '</div>'."\n";
		}
		
		// function get_navjump_id()
		// {
		// 	$page_type = $this->get_page_type();
		// 	$navJump = NULL;
		// 	foreach($page_type->module_regions('navigation') as $region)
		// 	{
		// 		if($this->has_content($region))
		// 		{
		// 			$navJump = '#minisiteNavigation';
		// 			break;
		// 		}
		// 	}
		// 	if(empty($navJump))
		// 	{
		// 		foreach($page_type->module_regions(array('children', 'children_full_titles')) as $region)
		// 		{
		// 			if($this->has_content($region))
		// 			{
						
		// 				$navJump = '#childrenModule1';
		// 				break;
		// 			}
		// 		}
		// 	}
		// 	if(empty($navJump))
		// 	{
		// 		foreach($page_type->module_regions('children_and_grandchildren') as $region)
		// 		{
		// 			if($this->has_content($region))
		// 			{
		// 				$navJump = '#childrenAndGrandchildren';
		// 				break;
		// 			}
		// 		}
		// 	}
		// 	return $navJump;
		// }
		
		// function show_navbar()
		// {
		// 	$wrapperClasses = array();
		// 	if ($this->has_content( 'navigation' )) {
		// 		$wrapperClasses[] = 'hasNav';
		// 	}
		// 	if ($this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) )
		// 	{
		// 		$wrapperClasses[] = 'hasSubNav';
		// 	}
		// 	if(!empty($wrapperClasses))
		// 		echo '<div id="navInnerWrap" class="'.implode(' ',$wrapperClasses).'">'."\n";
		// 	if ($this->has_content( 'navigation' )) 
		// 	{
		// 		$this->run_section( 'navigation' );
		// 	}
		// 	if ($this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) )
		// 	{
		// 		echo '<div class="subNavElements">'."\n";
		// 		if ($this->has_content( 'sub_nav' )) 
		// 		{ 
		// 			echo '<aside id="subNav" class="subNavBlock" role="complementary">'."\n";
		// 			$this->run_section( 'sub_nav' );
		// 			echo '</aside>'."\n";
		// 		}
		// 		if ($this->has_content( 'sub_nav_2' ))
		// 		{
		// 			echo '<aside id="subNav2" class="subNavBlock" role="complementary">'."\n";
		// 			$this->run_section( 'sub_nav_2' );
		// 			echo '</aside>'."\n";
		// 		}
		// 		if ($this->has_content( 'sub_nav_3' ))
		// 		{
		// 			echo '<aside id="subNav3" class="subNavBlock" role="complementary">'."\n";
		// 			$this->run_section( 'sub_nav_3' );
		// 			echo '</aside>'."\n";
		// 		}
		// 		echo '</div>'."\n";
		// 	}
		// 	if(!empty($wrapperClasses))
		// 		echo '</div>'."\n";
		// }

		// function show_footer()
		// {
		// 	echo '<footer id="footer" role="contentInfo">'."\n";
		// 	echo '<div class="module1">';
		// 	$this->run_section( 'footer' );
		// 	echo '</div>';
		// 	echo '<div class="module2 lastModule">';
		// 	$this->run_section( 'edit_link' );
		// 	if ($this->has_content( 'post_foot' ))
		// 		$this->run_section( 'post_foot' );
		// 	echo '</div>';
		// 	$this->show_reason_badge();
		// 	echo '</footer>'."\n";
		// }

		function do_org_foot()
		{	
			// FOUNDATION SCRIPTS AND DEPENDENCIES

			// Foundation recommends placing scripts directly before end of body.
			// Foundation also recommends including jQuery at the bottom of the body. But this causes conflicts
			// with Reason scripts, like Features. Currently, we're just calling it in the head via in the normal Reason way.

			echo '<script type="text/javascript" src="/reason/local/cloak/js/vendor/fastclick.js"></script>'."\n";
			echo '<script type="text/javascript" src="/reason/local/cloak/js/vendor/foundation.min.js"></script>'."\n";
			echo '<script type="text/javascript" src="/reason/local/cloak/js/app.js"></script>'."\n";
		}
}
?>