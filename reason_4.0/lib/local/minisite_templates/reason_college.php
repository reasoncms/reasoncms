<?php

   /* 
	*  REASON COLLEGE TEMPLATE
	*
	*   ReasonCollegeTemplate (/lib/local/minisite_templates/reason_college.php)...
	*  	  extends CloakTemplate (/lib/local/minisite_templates/cloak.php)...
	*     which extends HTML5ResponsiveTemplate (/lib/core/minisite_templates/html5_responsive.php)...
	*     which extends DefaultTemplate (/lib/core/minisite_templates/default.php)
	*  
	*  To extend a function without duplicating the parent's code, use parent::functionName();
	*  To override a parent's function but call a grandparent's function, use ClassName::functionName();. Ex: MinisiteTemplate::alter_reason_page_type($page_type); 
	*/

	// include the MinisiteTemplate class
	reason_include_once( 'minisite_templates/cloak.php' );
	//reason_include_once('classes/module_sets.php');
	
	// this variable must be the same as the class name
	$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__)  ] = 'ReasonCollegeTemplate';
	
	class ReasonCollegeTemplate extends CloakTemplate
	{

		// function alter_reason_page_type($page_type)
		// {
		// 	CloakTemplate::alter_reason_page_type($page_type);

		// 	// $page_type->set_region('pre_foot', 'blurb', 'blurb.php', array('blurb_unique_names_to_show' => 'luther_sports_affiliations'));
		// 	// $page_type->set_region('page_location', 'module', 'module_file_name.php', array('module_parameter' => 'paremeter_value'));
		// 	// $page_type->set_region('pre_banner', 'global_header', 'global_header.php', array());
		// 	//$page_type->set_region('pre_bluebar', 'page_title', 'page_title.php', array());
		// 	//$page_type->set_region('new_page_location', 'global_header', 'global_header.php', array());
		// 	//$page_type->set_region('new_page_location', 'test_module', '/minisite_templates/modules/test/test_module.php', array());

		// }


		function show_cloak_header()
		{
			if ($this->has_content( 'new_page_location' )) 
			{
				$this->run_section( 'new_page_location' );
			}	

			?>
			<div id="cloakHeader">
				<div id="cloakMasthead">
					<h1 id="globalLogo">
						<a href="/">
							<span>Reason College</span>
						</a>
					</h1>
					<ul id="globalNavigationToggles">
						<li class="globalNavToggle">
							<a href="#globalNav" id="globalNavToggle">
								<span class="menuJumpText">Jump to global navigation</span>
							</a>
						</li>
						<li class="utilityNavToggle">
							<a href="#utilityNav" id="utilityNavToggle">
								<span class="utilityJumpText">Jump to search utility navigation</span>
							</a>
						</li>
					</ul>
				</div>
				<nav id="utilityNav" class="closed">
					<ul>
						<li class="search">
							<a href="#">Search</a>
							<form method="" action="" name="globalSearch" class="globalSearchForm open">
								<input type="text" name="" placeholder="Search Reason College" class="searchInputBox" />
								<input type="submit" name="" value="Search" class="searchSubmitLink" />
							</form>
						</li>
						<li class="directory"><a href="#">Directory</a></li>
						<li class="az"><a href="#">A to Z Index</a></li>
					</ul>
				</nav>
				<!-- <div id="grandDaddyNav" class="reveal-modal tiny" data-reveal>-->
				<div id="globalNav" class="closed">
					<nav id="audienceNav">
						<ul>
							<li><a href="#">Prospective Students</a></li>
							<li><a href="#">Alumni</a></li>
							<li><a href="#">Current Students</a></li>
							<li><a href="#">Parents</a></li>
							<li><a href="#">Faculty &amp; Staff</a></li>
						</ul>
					</nav>
					<nav id="sectionNav">
						<ul>
							<li><a href="#">Admissions</a></li>
							<li><a href="#">Academics</a></li>
							<li><a href="#">Student Life</a></li>
							<li><a href="#">Athletics</a></li>
							<li><a href="#">Giving</a></li>
							<li><a href="#">About</a></li>
						</ul>
					</nav>
				</div>
<!-- 					<a class="close-reveal-modal"><span>Close</span></a>
				</div> -->
			</div>
			
			<?php
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

$this->show_cloak_header();

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


		// Adds show_cloak_branded_header()
		function show_body_tableless()
		{
			$class = 'fullGraphicsView';
			echo '<div id="wrapper" class="'.$class.'">'."\n";
			//$this->show_cloak_header();
			echo '<div id="bannerAndMeat">'."\n";
			$this->show_banner();
			$this->cloak_you_are_here();
			//$this->cloak_show_main_head();
			$this->show_meat();
			echo '</div>'."\n";
			$this->show_footer();
			echo '</div>'."\n";
		}
}
?>