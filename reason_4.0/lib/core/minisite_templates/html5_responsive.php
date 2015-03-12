<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/default.php' );
	reason_include_once('classes/module_sets.php');
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'HTML5ResponsiveTemplate';
	
	class HTML5ResponsiveTemplate extends MinisiteTemplate
	{
		var $doctype = '<!DOCTYPE html>';
		var $section_elements = array('content'=>'main','related'=>'aside');
		var $section_roles = array('content'=>'main','related'=>'complementary');
		
		function alter_reason_page_type($page_type)
		{
			parent::alter_reason_page_type($page_type);
			if($regions = $page_type->module_regions(array('navigation', 'navigation_top')))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['wrapper_element']))
						$page_type->set_region_parameter($region, 'wrapper_element', 'nav');
				}
			}
			if($regions = $page_type->module_regions('children'))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['html5']))
						$page_type->set_region_parameter($region, 'html5', true);
				}
			}
			// Anything with publications?
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
								'classname' => 'ResponsiveItemMarkupGenerator', 
								'filename' => 'minisite_templates/modules/publication/item_markup_generators/responsive.php',
							);
						}
					}
					
				}
				
				$page_type->set_region_parameter($region, 'markup_generator_info', $markup_generators);
			}
			
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
		/**
		 * @todo test to make sure that scaling doesn't cause the weird iOS bug any more
		 */
		function get_meta_information()
		{
			parent::get_meta_information();
			$this->add_head_item('meta',array('name'=>'viewport','content'=>'width=device-width, minimum-scale=1.0, maximum-scale=2.0' ) );
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
			
			if($navJump = $this->get_navjump_id())
				echo '<div class="navJump"><a href="'.$navJump.'" title="Navigation menu"><span class="navJumpText">Jump to navigation menu</span></a></div>'."\n";
			
			echo '<h1><a href="'.$this->site_info->get_value('base_url').'"><span>'.$this->site_info->get_value('name').'</span></a></h1>'."\n";
			$this->show_banner_xtra();
			echo '</header>'."\n";
			// todo: get the navigation module to output a <nav> element
			if($this->has_content('post_banner'))
			{
				echo '<div id="postBanner">'."\n";
				$this->run_section('post_banner');
				echo '</div>'."\n";
			}
		}
		/**
		 * @todo add siblings as a fallback
		 * @todo use module sets
		 */
		function get_navjump_id()
		{
			$page_type = $this->get_page_type();
			$navJump = NULL;
			foreach($page_type->module_regions('navigation') as $region)
			{
				if($this->has_content($region))
				{
					$navJump = '#minisiteNavigation';
					break;
				}
			}
			if(empty($navJump))
			{
				foreach($page_type->module_regions(array('children', 'children_full_titles')) as $region)
				{
					if($this->has_content($region))
					{
						
						$navJump = '#childrenModule1';
						break;
					}
				}
			}
			if(empty($navJump))
			{
				foreach($page_type->module_regions('children_and_grandchildren') as $region)
				{
					if($this->has_content($region))
					{
						$navJump = '#childrenAndGrandchildren';
						break;
					}
				}
			}
			return $navJump;
		}
		function show_navbar()
		{
			$wrapperClasses = array();
			if ($this->has_content( 'navigation' )) {
				$wrapperClasses[] = 'hasNav';
			}
			if ($this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) )
			{
				$wrapperClasses[] = 'hasSubNav';
			}
			if(!empty($wrapperClasses))
				echo '<div id="navInnerWrap" class="'.implode(' ',$wrapperClasses).'">'."\n";
			if ($this->has_content( 'navigation' )) 
			{
				$this->run_section( 'navigation' );
			}
			if ($this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) )
			{
				echo '<div class="subNavElements">'."\n";
				if ($this->has_content( 'sub_nav' )) 
				{ 
					echo '<aside id="subNav" class="subNavBlock" role="complementary">'."\n";
					$this->run_section( 'sub_nav' );
					echo '</aside>'."\n";
				}
				if ($this->has_content( 'sub_nav_2' ))
				{
					echo '<aside id="subNav2" class="subNavBlock" role="complementary">'."\n";
					$this->run_section( 'sub_nav_2' );
					echo '</aside>'."\n";
				}
				if ($this->has_content( 'sub_nav_3' ))
				{
					echo '<aside id="subNav3" class="subNavBlock" role="complementary">'."\n";
					$this->run_section( 'sub_nav_3' );
					echo '</aside>'."\n";
				}
				echo '</div>'."\n";
			}
			if(!empty($wrapperClasses))
				echo '</div>'."\n";
		}
		function show_footer()
		{
			echo '<footer id="footer" role="contentInfo">'."\n";
			echo '<div class="module1">';
			$this->run_section( 'footer' );
			echo '</div>';
			echo '<div class="module2 lastModule">';
			$this->run_section( 'edit_link' );
			if ($this->has_content( 'post_foot' ))
				$this->run_section( 'post_foot' );
			echo '</div>';
			$this->show_reason_badge();
			echo '</footer>'."\n";
		}
}
?>