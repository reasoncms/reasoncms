<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/html5_responsive.php' );
	reason_include_once( 'classes/module_sets.php' );
	reason_include_once( 'minisite_templates/nav_classes/luther_default.php' );

	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'Luther2014Template';

	class Luther2014Template extends HTML5ResponsiveTemplate
	{
		var $include_modules_css = false;
		var $nav_class = 'LutherDefaultMinisiteNavigation';

		function alter_reason_page_type($page_type)
		{
			parent::alter_reason_page_type($page_type); // Make sure we do everything the parent template does.

			if($regions = $page_type->module_regions(array('navigation', 'navigation_top')))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['wrapper_element']))
						$page_type->set_region_parameter($region, 'wrapper_element', 'div');
				}
			}

			// Global parameters for the events module
			if($regions = $page_type->module_regions('events'))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['list_chrome_markup']))
						$page_type->set_region_parameter($region, 'list_chrome_markup', 'minisite_templates/modules/events_markup/responsive/responsive_list_chrome.php');
				}
			}

			// Global parameters for the children module
			if($regions = $page_type->module_regions('children'))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['thumbnail_width']))
						$page_type->set_region_parameter($region, 'thumbnail_width', 600);
					if(!isset($module['module_params']['thumbnail_height']))
						$page_type->set_region_parameter($region, 'thumbnail_height', 400);
					//if(!isset($module['module_params']['provide_images']))
					//	$page_type->set_region_parameter($region, 'provide_images', true);
					if(!isset($module['module_params']['description_part_of_link']))
						$page_type->set_region_parameter($region, 'description_part_of_link', true);
					if(!isset($module['module_params']['html5']))
						$page_type->set_region_parameter($region, 'html5', true);
				}
			}

			// Global parameters for the image sidebar module
			if($regions = $page_type->module_regions('image_sidebar'))
			{
				foreach($regions as $region)
				{
					// if(!isset($module['module_params']['thumbnail_width']))
					// 	$page_type->set_region_parameter($region, 'thumbnail_width', 600);
					// if(!isset($module['module_params']['thumbnail_height']))
					// 	$page_type->set_region_parameter($region, 'thumbnail_height', 400);
					// if(!isset($module['module_params']['thumbnail_crop']))
					// 	$page_type->set_region_parameter($region, 'thumbnail_crop', 'fill');
					// if(!isset($module['module_params']['num_to_display']))
					// 	$page_type->set_region_parameter($region, 'num_to_display', 3);
					if(!isset($module['module_params']['caption_flag']))
						$page_type->set_region_parameter($region, 'caption_flag', '');
				}
			}

			// Global parameters for the pulbication module
			if($regions = $page_type->module_regions('publication'))
			{
				foreach($regions as $region)
				{
					if(!isset($module['module_params']['css']))
						$page_type->set_region_parameter($region, 'css', false);
					if(!isset($module['module_params']['use_filters']))
						$page_type->set_region_parameter($region, 'use_filters', true);
					if(!isset($module['module_params']['show_login_link']))
						$page_type->set_region_parameter($region, 'show_login_link', false);
				}
			}

		}

		function start_page()
		{
			// @todo:  Import extra stuff from luther2010.
			// @todo:  Move site specific logic into site minisite templates.

			$url = get_current_url();
			$this->get_title();

			// start page
			echo $this->get_doctype()."\n";
			echo '<html  class="no-js" id="luther-edu" lang="en">'."\n";
			echo '<head>'."\n";

			// meta, css, scripts
			// @todo:  Cleanup org_head_items
			$this->do_org_head_items();
			$this->add_extra_head_content_structured();
			echo $this->head_items->get_head_item_markup();

			// extra head content (from minisite page)
			if($this->cur_page->get_value('extra_head_content'))
			{
				echo "\n".$this->cur_page->get_value('extra_head_content')."\n";
			}

			echo "<!--[if lt IE 9]><link rel='stylesheet' type='text/css' href='/reason/local/luther_2014/stylesheets/ie.css' /><![endif]-->"."\n";

			echo '</head>'."\n";

			// start body
			echo $this->create_body_tag();

			// @todo:  Do we use this???
			$this->do_org_navigation();

		}

		// This is a copy of get_meta_information from default.php.
		// Typcially, we'd just use parent::get_meta_information(),
		// but in this case we want to keep from pulling all the scripts
		// included in html5_responsive, while still maintaining the
		// nessary funcationality from default.php.
		function get_meta_information()
		{
			// add the charset information
			$this->head_items->add_head_item('meta',array('http-equiv'=>'Content-Type','content'=>'text/html; charset=UTF-8' ) );

			// add favicon
			if($favicon_path = $this->_get_favicon_path() )
			{
				$this->head_items->add_head_item('link',array('rel'=>'shortcut icon','href'=>$favicon_path, ) );
			}

			// array of meta tags to search for in the page entity
			// key: entity field
			// value: meta tag to use
			$meta_tags = array(
				'description' => 'description',
				'author' => 'author',
				'keywords' => 'keywords'
			);

			// load meta elements from current page
			$tags_added = array();
			foreach( $meta_tags as $entity_field => $meta_name )
			{
				if( $this->cur_page->get_value( $entity_field ) )
				{
					$content = reason_htmlspecialchars( $this->cur_page->get_value( $entity_field ) );
					$this->head_items->add_head_item('meta',array('name'=>$meta_name,'content'=>$content) );
					$tags_added[] = $meta_name;
				}
			}

			if(!in_array('keywords',$tags_added) && $this->pages->root_node() == $this->page_id)
			{
				$content = reason_htmlspecialchars( $this->site_info->get_value( 'keywords' ) );
				$this->head_items->add_head_item('meta',array('name'=>'keywords','content'=>$content) );
			}

			if (!empty( $_REQUEST['no_search'] )
				|| $this->site_info->get_value('site_state') != 'Live'
				|| ( defined('THIS_IS_A_DEVELOPMENT_REASON_INSTANCE') && THIS_IS_A_DEVELOPMENT_REASON_INSTANCE )
				|| !$this->cur_page->get_value('indexable'))
			{
				$this->head_items->add_head_item('meta',array('name'=>'robots','content'=>'none' ) );
			}

			$this->head_items->add_javascript(JQUERY_URL, true);
			// Responsive stuff

			$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/respond/respond.min.js', false, array('before'=>'<!--[if lt IE 9]>','after'=>'<![endif]-->'));
			$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/ie8_fix_maxwidth.js', false, array('before'=>'<!--[if lt IE 9]>','after'=>'<![endif]-->'));
			$this->add_head_item('meta',array('name'=>'viewport','content'=>'width=device-width, minimum-scale=1.0, maximum-scale=1.0' ) );
		}

		function do_org_head_items()
		{
			// Javascripts
			$this->head_items->add_javascript('/reason/local/luther_2014/javascripts/vendor/modernizr.js');
			$this->head_items->add_javascript(JQUERY_URL, true);
			$this->head_items->add_javascript('/reason/local/luther_2014/javascripts/luther-gcse.js');

			// Stylesheets
			$this->head_items->add_stylesheet('https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700');
			$this->head_items->add_stylesheet('https://fonts.googleapis.com/css?family=Open+Sans:300italic,300,400,400italic,600,600italic,700,700italic,800,800italic');
			// $this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/fonts/font-awesome/css/font-awesome.css');
			$this->head_items->add_stylesheet('//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css');
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/dependencies/dependencies.css');
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/base.css');
			//$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/ie.css');

	}

		function do_org_foot()
		{
			google_analytics();

			// Foundation scripts need to be directly before end of body
			// Foundation recommends including jQuery at the bottom on the body (below). But this causes conflicts
			// with Reason scripts, like Features. Currently, we're just calling it in the head via in the normal reason way.
			//
			// @TODO: Create foot_items similar to head_items to allow easy adding of foot items in modules and templates.
			//

			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/vendor/fastclick.js"></script>';
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/foundation/foundation.js"></script>';
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/foundation/foundation.offcanvas.js"></script>';
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/foundation/foundation.tab.js"></script>';
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/foundation/foundation.tooltip.js"></script>';

			// Initialize Foundation javascript
			echo '<script> $(document).foundation(); </script>';

			// Custom
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/luther.js"></script>';
		}

		function get_body_tag_classes()
		{
			$classes = array();
			$classes[] = 'body';
			if($this->pages->root_node() == $this->page_id)
				$classes[] = 'siteHome';
			$classes[] = $this->get_page_type()->_page_type_name;
			if($this->page_info->get_value('unique_name'))
				$classes[] = 'uname_'.$this->page_info->get_value('unique_name');
			return $classes;
		}

		function has_related_section()
		{
			if(
			$this->has_content( 'pre_sidebar' ) ||
			$this->has_content( 'pre_sidebar_2' ) ||
			$this->has_content( 'sidebar' ) ||
			$this->has_content( 'sidebar_2' ) ||
			$this->has_content( 'post_sidebar' ) ||
			$this->has_content( 'post_sidebar_2' ) ||
			$this->has_content( 'post_sidebar_3' ) ||
			$this->has_content( 'call_to_action_blurb') )
			{
				return true;
			}
			return false;
		}

		function show_body_tableless()
		{
			echo '<div class="off-canvas-wrap">'."\n";
			echo '<div class="inner-wrap">';
			echo '<div id="wrapper">'."\n";
			echo '<div id="wrapper-col">'."\n";

			$this->show_luther_global_header();
			$this->show_luther_global_navigation();

			emergency_preempt();
			handle_ie8();

			// Generate classes on the minisite section based on the contents inside. Useful for CSS.
			// Originally appears in show_meat_tableless() in the default template.
			$hasSections = array();
			$blobclass = 'has';  // changed from default 'contains'
			$classes = array();

			foreach($this->sections as $section=>$show_function)

			{
				$has_function = 'has_'.$section.'_section';
				if($this->$has_function())
				{
					$hasSections[$section] = $show_function;
					$capsed_section_name = ucfirst($section);
					$classes[] = 'has'.$capsed_section_name;
					$blobclass .= substr($capsed_section_name,0,3);
				}
			}

			// Start minisite markup
			echo '<section id="minisite" class="'.implode(' ',$classes).' '.$blobclass.'">'."\n";
			echo '<div class="minisiteWrap">';

				$this->show_banner();
				$this->show_meat();
				$this->show_footer();

			echo '</div>';
			echo '</section>'."\n"; // End #minisite

				$this->show_luther_global_footer();

			echo '</div>'."\n"; // End #wrapper-col
			echo '</div>'."\n"; // End #wrapper
			echo '</div>'."\n"; // End .inner-wrap
			echo '</div>'."\n"; // End .off-canvas-wrap
		}

		function you_are_here($delimiter = ' <span>&raquo;</span> ')
		{
			echo '<div class="breadcrumbs">';
			echo '<a href="/"><span class="screenreader">Home</span><i class="fa fa-home"></i></a> <span>&raquo;</span> ';
			echo $this->_get_breadcrumb_markup($this->_get_breadcrumbs(), $this->site_info->get_value('base_breadcrumbs'), $delimiter);
			echo '</div>'."\n";
		}

		function show_luther_global_header()
		{
			if ($this->has_content( 'global_header' ))
			{
				$this->run_section( 'global_header' );
			}
		}

		function show_luther_global_navigation()
		{
			if ($this->has_content( 'global_navigation' ))
			{
				$this->run_section( 'global_navigation' );
			}
		}

		function show_luther_global_footer()
		{
			if ($this->has_content( 'global_footer' ))
			{
				$this->run_section( 'global_footer' );
			}
		}

		function show_luther_contact_blurb()
		{
			if ($this->has_content( 'contact_blurb' ))
			{
				$this->run_section( 'contact_blurb' );
			}
		}

		function show_luther_call_to_action_blurb()
		{
			if ($this->has_content( 'call_to_action_blurb' ))
			{
				$this->run_section( 'call_to_action_blurb' );
			}
		}

		function show_banner()  // minisite banner
		{
			if ($this->has_content( 'pre_banner' ))
			{
				echo '<div id="preBanner">';
				$this->run_section( 'pre_banner' );
				echo '</div>'."\n";
			}

			echo '<header id="minisiteBanner">';
			echo '<div id="banner" role="banner" aria-label="site">'."\n";

			if($this->should_show_parent_sites())
			{
				echo $this->get_parent_sites_markup();
			}

			echo '<h1 class="siteTitle"><a href="'.$this->site_info->get_value('base_url').'"><span>'.$this->site_info->get_value('name').'</span></a></h1>'."\n";
			echo '</div>'."\n";

			$this->you_are_here(); // Breadcrumbs for mobile

			echo '</header>';

			if($this->has_content('post_banner'))
			{
				echo '<div id="postBanner">'."\n";
				$this->run_section('post_banner');
				echo '</div>'."\n";
			}

		}

		function show_meat_tableless()
		{
			// changed from the default $section generation for more flexibility in markup order
			$this->show_navbar();
			echo '<section id="contentAndRelated">';
			$this->show_main_content();
			$this->show_sidebar();
			echo '</section>';
		}

		function show_main_content_sections()
		{

			if ($this->has_content( 'pre_main_head' ))
			{
				echo '<div id="contentFeature">'."\n";
				$this->run_section( 'pre_main_head' );  // Features
				echo '</div>'."\n";
			}

			if ($this->has_content( 'main_head' ))
			{
				echo '<header class="contentHead">'."\n";
				$this->you_are_here(); // Breadcrumbs
				$this->run_section( 'main_head' );  // Page Title
				echo '</header>'."\n";
			}

			if ($this->has_content( 'post_main_head' ))
			{
				echo '<div id="postMainHead">'."\n";
			$this->run_section( 'post_main_head' );
				echo '</div>'."\n";
			}

			if($this->has_content( 'main_head' ) || $this->has_content( 'main' ) || $this->has_content( 'main_post' ) || $this->has_content( 'main_post_2' ) || $this->has_content( 'main_post_3' ) ) {

				echo '<div id="contentSections">'."\n";

				if ($this->has_content( 'main' ))
				{
					echo '<div class="contentMain">'."\n";
					$this->run_section( 'main' );  // This location is for main content only
					echo '</div>'."\n";
				}
				if ($this->has_content( 'main_post' ))
				{
					echo '<div class="contentPost">'."\n";
					$this->run_section( 'main_post' ); // This location is for feeds only
					echo '</div>'."\n";
				}
				if ($this->has_content( 'main_post_2' ))
				{
					echo '<div class="contentPost2">'."\n";
					$this->run_section( 'main_post_2' );
					echo '</div>'."\n";
				}
				if ($this->has_content( 'main_post_3' ))
				{
					echo '<div class="contentPost3">'."\n";
					$this->run_section( 'main_post_3' );
					echo '</div>'."\n";
				}

			echo '</div>'."\n";
			}
		}

		function show_sidebar_tableless()
		{
			if(
				$this->has_content( 'pre_sidebar' ) ||
				$this->has_content( 'pre_sidebar_2' ) ||
				$this->has_content( 'sidebar' ) ||
				$this->has_content( 'sidebar_2' ) ||
				$this->has_content( 'post_sidebar' ) ||
				$this->has_content( 'post_sidebar_2' ) ||
				$this->has_content( 'post_sidebar_3' ) ||
				$this->has_content( 'call_to_action_blurb') ) {

			echo '<div id="relatedSections">'."\n";

				if($this->has_content( 'call_to_action_blurb' ))
				{
					echo '<div id="callToActionBlurb">'."\n";
					$this->run_section( 'call_to_action_blurb' );
					echo '</div>'."\n";
				}

				if($this->has_content( 'pre_sidebar' ))
				{
					echo '<div id="preSidebar">'."\n";
					$this->run_section( 'pre_sidebar' );
					echo '</div>'."\n";
				}
				if($this->has_content( 'pre_sidebar_2' ))
				{
					echo '<div id="preSidebar_2">'."\n";
					$this->run_section( 'pre_sidebar_2' );
					echo '</div>'."\n";
				}
				if($this->has_content( 'sidebar' ))
				{
					echo '<div id="sidebar">'."\n";
					$this->run_section( 'sidebar' );
					echo '</div>'."\n";
				}
				if($this->has_content( 'sidebar_2' ))
				{
					echo '<div id="sidebar_2">'."\n";
					$this->run_section( 'sidebar_2' );
					echo '</div>'."\n";
				}
				if($this->has_content( 'post_sidebar' ))
				{
					echo '<div id="postSidebar">'."\n";
					$this->run_section( 'post_sidebar' );
					echo '</div>'."\n";
				}
				if($this->has_content( 'post_sidebar_2' ))
				{
					echo '<div id="postSidebar_2">'."\n";
					$this->run_section( 'post_sidebar_2' );
					echo '</div>'."\n";
				}
				if($this->has_content( 'post_sidebar_3' ))
				{
					echo '<div id="postSidebar_3">'."\n";
					$this->run_section( 'post_sidebar_3' );
					echo '</div>'."\n";
				}

			echo '</div>'."\n";
			}
		}

		function show_navbar()
		{
			$wrapperClasses = array();
			if ($this->has_content( 'navigation' )) {
				$wrapperClasses[] = 'hasNav';
			}
			if ($this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) || $this->has_content( 'contact_blurb' ) )
			{
				$wrapperClasses[] = 'hasSubNav';
			}
			if(!empty($wrapperClasses))
			{
				echo '<nav id="navWrap" class="'.implode(' ',$wrapperClasses).'">'."\n";
				echo '<a class="toggle" href="#minisiteNavigation">'."\n";
				echo '<h1><span class="screenreader">' . $this->site_info->get_value('name') . '</span> <span class="helper-text">Menu <i class="fa fa-arrow-right"></i></span></h1>'."\n";
				echo '<i class="fa fa-bars"></i>'."\n";
				echo '<i class="fa fa-times"></i>'."\n";
				echo '</a>'."\n";
			}

			if ($this->has_content( 'navigation' ))
			{
				$this->run_section( 'navigation' );
			}

			if ($this->has_content( 'sub_nav' ) || $this->has_content( 'sub_nav_2' ) || $this->has_content( 'sub_nav_3' ) || $this->has_content( 'contact_blurb' ) )
			{
				echo '<div class="subNavElements">'."\n";
				if ($this->has_content( 'sub_nav' ))
				{
					echo '<aside id="subNav" class="subNavBlock" role="complementary">'."\n";
					$this->run_section( 'sub_nav' );
					echo '</aside>'."\n";
				}

				$this->show_luther_contact_blurb();

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
				echo '</nav>'."\n";
		}
		function show_footer()
		{
			echo '<footer id="footer" role="contentInfo">'."\n";
			$this->show_luther_contact_blurb();
			if ($this->has_content( 'pre_foot' ))
			{
				echo '<div id="preFoot">'."\n";
				$this->run_section( 'pre_foot' );
				echo '</div>'."\n";
			}
			if ($this->has_content( 'footer' ) || $this->has_content( 'edit_link' ))
			{
				echo '<div id="foot">'."\n";
				$this->run_section( 'footer' );
				$this->run_section( 'edit_link' );
				echo '</div>'."\n";
			}
			if ($this->has_content( 'post_foot' ))
			{
				echo '<div id="postFoot">'."\n";
				$this->run_section( 'post_foot' );
				echo '</div>'."\n";
			}
			echo '</footer>'."\n";
		}
	}
?>