<?php

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/luther.php' );
	reason_include_once( 'classes/module_sets.php' );
	reason_include_once( 'minisite_templates/nav_classes/luther_default.php' );
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'Luther2014LoginTemplate';
	
	class Luther2014LoginTemplate extends Luther2014Template
	{

		function do_org_head_items()
		{	
			// Stylesheets
			// Here we remove base.css, since this is a simple page and we don't need it.
			// We also call login.css here, instead of adding it in the Reason Admin (this is so we can control the output location).
			$this->head_items->add_stylesheet('http://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700');
			$this->head_items->add_stylesheet('http://fonts.googleapis.com/css?family=Open+Sans:300italic,300,400,400italic,600,600italic,700,700italic,800,800italic');
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/fonts/font-awesome-4.0.3/css/font-awesome.css');
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/dependencies/dependencies.css');
			$this->head_items->add_stylesheet('/reason/local/luther_2014/stylesheets/sites/login.css');
		}

		function do_org_foot()
		{
			google_analytics();
			 
			// Foundation scripts need to be directly before end of body
			// We don't need most of the scripts from luther.php, so we take them out here.
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/vendor/fastclick.js"></script>';
			echo '<script type="text/javascript" src="/reason/local/luther_2014/javascripts/foundation/foundation.js"></script>';

			// Initialize Foundation javascript
			echo '<script> $(document).foundation(); </script>';
		}

		function get_body_tag_classes()
		{
			// Adds the class "lutherLogin" to the body tag.
			$classes = array();
			$classes[] = 'body lutherLogin';
			if($this->pages->root_node() == $this->page_id)
				$classes[] = 'body lutherLogin siteHome';
			if($this->page_info->get_value('unique_name'))
				$classes[] = 'lutherLogin uname_'.$this->page_info->get_value('unique_name');
			return $classes;
		}

		function show_body_tableless()
		{
			// Here we strip out a lot of markup from luther.php. This is a simple page and we don't need the excess.
			echo '<div id="wrapper">'."\n";
			echo '<div id="wrapper-col">'."\n";
			
				// @todo: Hook up emergency preempt
				//$this->emergency_preempt();
		
				$this->show_luther_global_header();
				$this->show_luther_global_navigation();
			
			// Start minisite markup
			echo '<section id="minisite">'."\n";
			echo '<div class="minisiteWrap">'."\n";
			
				$this->show_banner();			
				$this->show_meat();
				$this->show_footer();
				
			echo '</div>';
			echo '</section>'."\n"; // End #minisite
			
				$this->show_luther_global_footer();

			echo '</div>'."\n"; // End #wrapper
			echo '</div>'."\n"; // End #wrapper
		}

		function show_banner()  // minisite banner
		{
			// @todo: Determine if we need this.  Could be used for emergency preempt, or other site wide announcements. Depends on what we do in luther.php.
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
		
			// Here we swap in an image for the logo.
			echo '<h1 class="siteTitle"><a href="/"><img src="/reason/local/luther_2014/images/logo_login.png" alt="Luther College" /></a></h1>'."\n";
			echo '</div>'."\n";

			echo '</header>';
		}
		
		function show_main_content_sections()
		{
		
			if($this->has_content( 'main_head' ) || $this->has_content( 'main' ) ) {
		
				echo '<div id="contentSections">'."\n";

				$this->run_section( 'main_head' );  // Page Title

				if ($this->has_content( 'main' )) 
				{
					echo '<div class="contentMain">'."\n";

					$this->run_section( 'main' );  // Login Module
					echo '</div>'."\n";
				}
				
			echo '</div>'."\n";

			}
		}

		// Let's just keep this stuff empty

		function show_sidebar_tableless()
		{
		}
		
		function show_navbar()
		{		
		}

		function show_footer()
		{
		}

	}
?>