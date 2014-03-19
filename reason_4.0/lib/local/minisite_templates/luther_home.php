<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/luther.php' );
	reason_include_once( 'classes/module_sets.php' );
	reason_include_once( 'minisite_templates/nav_classes/luther_default.php' );
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'Luther2014HomeTemplate';
	
	class Luther2014HomeTemplate extends Luther2014Template
	{

		function alter_reason_page_type($page_type)
		{
			parent::alter_reason_page_type($page_type);
			// @todo: Alter page types to force empty regions, instead of copying entire functions and overriding one line
	
		}

		function do_org_head_items()
		{
			parent::do_org_head_items();
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/sites/home.css');
		}
	
		function get_body_tag_classes()
		{
			$classes = array();
			$classes[] = 'body';
			if($this->pages->root_node() == $this->page_id)
				$classes[] = 'body lutherHome siteHome';
			if($this->page_info->get_value('unique_name'))
				$classes[] = 'lutherHome uname_'.$this->page_info->get_value('unique_name');
			return $classes;
		}

		// @todo: use alter_reason_page_type instead for removing responsive title
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

			if ($this->has_content( 'responsive_page_title' ))
			{
				//$this->you_are_here(); // Breadcrumbs
				//$this->run_section( 'responsive_page_title' );
			}

			echo '</header>';
			
			if($this->has_content('post_banner'))
			{
				echo '<div id="postBanner">'."\n";
				$this->run_section('post_banner');
				echo '</div>'."\n";
			}
			
		}
		
		// The following:
		// 1. Removes breadcrumbs.
		// 2. Moves main_head content into contentSections
		// 3. Removes unused page locations
		//
		// @todo: use alter_reason_page_type instead for removing breadcrumbs
		function show_main_content_sections()
		{
		
			echo '<div id="welcomeRow">'."\n";

			if ($this->has_content( 'pre_main_head' )) 
			{
				echo '<div id="contentFeature">'."\n";
				$this->run_section( 'pre_main_head' );  // Features
				echo '</div>'."\n";
			}

			if ($this->has_content( 'main_head' )) 
			{
			}
				
			if($this->has_content( 'main_head' ) || $this->has_content( 'main' ) || $this->has_content( 'main_post' ) ) {
		
				echo '<div id="contentSections">'."\n";

				$this->run_section( 'main_head' );  // Page Title

				if ($this->has_content( 'main' )) 
				{
					echo '<div class="contentMain">'."\n";

					$this->run_section( 'main' );  // Welcome to Luther text
					echo '</div>'."\n";
				}
				if ($this->has_content( 'main_post' )) 
				{
					echo '<div class="contentPost">'."\n";
					$this->run_section( 'main_post' ); // Calls to Action
					echo '</div>'."\n";
				}
				
			echo '</div>'."\n";

			}
			echo '</div>'."\n";
		}

		function show_sidebar_tableless()
		{
			if($this->has_content( 'pre_sidebar' ) || $this->has_content( 'sidebar' ) || $this->has_content( 'post_sidebar' ) ) {
			
				echo '<div id="relatedSections">'."\n";
				
					if($this->has_content( 'pre_sidebar' ))
					{
						echo '<div id="preSidebar">'."\n"; // Headlines
						$this->run_section( 'pre_sidebar' );
						echo '</div>'."\n";
					}
					if($this->has_content( 'sidebar' ))
					{
						echo '<div id="sidebar">'."\n";
						echo '<h3>Campus Events</h3>'."\n";
						$this->run_section( 'sidebar' );
						echo '</div>'."\n";
					}
					if($this->has_content( 'post_sidebar' ))
					{
						echo '<div id="postSidebar">'."\n";
						echo '<h3>Featured Video</h3>'."\n";
						$this->run_section( 'post_sidebar' );
						echo '<a class="more" href="http://www.youtube.com/user/LutherCollegeMedia" target="_blank">YouTube Video Archive</a>'."\n";
						echo '</div>'."\n";
					}
				
				echo '</div>'."\n";
			}
			
			if($this->has_content( 'callouts' ) ) {
			
				echo '<div id="calloutSections">'."\n";
					$this->run_section( 'callouts' );
				echo '</div>'."\n";

			}


		}
		
		
		function show_navbar()
		{		
		}


	}
?>