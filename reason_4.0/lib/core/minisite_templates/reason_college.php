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

// this variable must be the same as the class name
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__)  ] = 'ReasonCollegeTemplate';

class ReasonCollegeTemplate extends CloakTemplate
{

	function alter_reason_page_type($page_type)
	{
		CloakTemplate::alter_reason_page_type($page_type);
		$page_type->set_region('new_page_location', 'reason_college/static_header', 'minisite_templates/modules/reason_college/static_header.php', array());
	}

	function show_cloak_header()
	{
		if ($this->has_content( 'new_page_location' )) 
		{
			$this->run_section( 'new_page_location' );
		}	
	}

	function show_banner()
	{
		echo '<div class="sticky">'."\n";

		if ($this->has_content( 'pre_banner' ))
		{	
			echo '<div id="preBanner">';
			$this->run_section( 'pre_banner' );
			echo '</div>'."\n";
		}

		$this->show_cloak_header();

		echo '<header id="banner" role="banner" aria-label="site" class="top-bar" data-topbar role="navigation" data-options="sticky_on: large">'."\n";
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

		if($this->has_content('post_banner'))
		{
			echo '<div id="postBanner">'."\n";
			$this->run_section('post_banner');
			echo '</div>'."\n";
		}
	}
}
?>