<?php

/**
 * LutherOne Reason template, with full overloading of the run method
 * Written by: Brian Jones
 * Feb 2009
 */
 
/**
 * Include the base template so we can extend it
 */
reason_include_once( 'minisite_templates/default.php' );

/**
 * Register this new template with Reason
 */
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'LutherOneTemplate';

/**
 * A sample Reason template that completely overloads the run method -- full XHTML
 */
class LutherOneTemplate extends MinisiteTemplate
{
	/**
	 * Do the markup for the whole page
	 * @return void
	 */
	function run()
	{
		// This adds the page title to the head items
		$this->get_title();
		
		// these lines add any css and javascript that might be part of an organization's standards
		// NOTE: don't add your theme-specific CSS here.
		// By adding it to the theme in Reason rather than here you 
		// can reuse this template across multiple themes.
		$this->head_items->add_stylesheet('/stylesheets/blueprint/screen768.css','', TRUE);
		$this->head_items->add_javascript('/javascripts/highslide/highslide-full.js');
		
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
		echo '<head>'."\n";
		echo $this->head_items->get_head_item_markup();
		
		if($this->cur_page->get_value('extra_head_content'))
		{
			echo "\n".$this->cur_page->get_value('extra_head_content')."\n";
		}
			
		echo '</head>'."\n";

		echo '<body>'."\n";
		echo '<div class="hide"><a href="#content" class="hide">Skip Navigation</a></div>'."\n";


?>
<body id="pageLC">
  <div class="container">
  <div id="body" class="container">

    <div id="head">
      <div class="column span-77 last">
      	<ul id="navmain"><li class="nm1"><a href="/admissions">Prospective Students</a></li><li class="nm2"><a href="/parents">Parents</a></li><li class="nm3"><a href="/visitors">Visitors</a></li><li class="nm4"><a href="/alumni">Alumni/ Friends</a></li><li class="nm5"><a href="/faculty-staff-students">Faculty/ Staff/ Students</a></li></ul>
	  </div class="column span-77 last">
	  <div id="logosearch" class="container">
	    <div class="column span-17 ">

  	      <div id="logo">
  <a href="/" title="Luther College Home"><span></span>
	  <img alt="Luther College" src="/images/luther/logo.png"  />
	</a>
</div>
	    </div class="column span-17">
	    <div class="column span-60 last">
<?php
		if ($this->has_content( 'banner_xtra' ))
		{	
	//		echo '<div id="bannerXtra">';
			$this->run_section( 'banner_xtra' );
	//		echo '</div>'."\n";
		}
?>

	    </div class="column span-60 last">
	  </div id="logosearch" class="container">
	  <div class="column span-77 last">

	    <ul id="navglobal"><li class="ng1"><a href="/academics">Academics</a></li><li class="ng2"><a href="/admissions">Admissions</a></li><li class="ng3"><a href="/student-life">Student Life</a></li><li class="ng4"><a href="/news">News & events</a></li><li class="ng5"><a href="/giving">Giving</a></li><li class="ng6"><a href="/about">About Luther</a></li><li class="ng7"><a href="/contact">Contact</a></li></ul>
	  </div class="column span-77 last">
    </div id="head">


<?php
		
		// Here's the organization-specific banner
		//echo '<div id="myBanner">';
		//echo '<a href="http://example.com"><img src="/url/of/my/logo.png" alt="My Logo" /></a>';
		//echo '</div>'."\n";
		
		echo '<div id="wrapper">'."\n";
		echo '<div id="bannerAndMeat">'."\n";
             
echo '<div class="column span-24 append-1 ">'."\n";
  	echo '<div id="nav">'."\n";
		// Navigation area
		echo '<div id="navigation">'."\n";
		if ($this->has_content( 'navigation' )) 
		{ 
			$this->run_section( 'navigation' );
		}
		if ($this->has_content( 'sub_nav' )) 
		{ 
			echo '<div id="subNav">'."\n";
			$this->run_section( 'sub_nav' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'sub_nav_2' ))
		{
		//	$this->run_section( 'sub_nav_2' );
		}
		
		if ($this->has_content( 'sub_nav_3' ))
		{
			$this->run_section( 'sub_nav_3' );
		}
		echo '</div>'."\n";
		
  	echo '</div id="nav">'."\n";
echo '</div class="column span-24 append-1 ">'."\n";
echo '<div class="column span-50 prepend-1 last">'."\n";
		if ($this->has_content( 'pre_banner' ))
		{	
			echo '<div id="preBanner">';
			$this->run_section( 'pre_banner' );
			echo '</div>'."\n";
		}
		//echo '<div id="banner">'."\n";
		//echo '<h1><a href="'.$this->site_info->get_value('base_url').'"><span>'.$this->site_info->get_value('name').'</span></a></h1>'."\n";
		//echo '</div>'."\n";
		
		// Breadcrumb navigation
		echo '<div id="crumbs">';
		echo $this->_get_breadcrumb_markup($this->_get_breadcrumbs(), $this->site_info->get_value('base_breadcrumbs'), ' :: ');
		echo '</div>'."\n";
		
		if($this->has_content('post_banner'))
		{
			echo '<div id="postBanner">'."\n";
			$this->run_section('post_banner');
			echo '</div>'."\n";
		}
		echo '<div id="meat">'."\n";
		
		// Main content area
		echo '<div id="content">'."\n";
		if ($this->has_content( 'main_head' )) 
		{
			echo '<div class="contentHead">'."\n";
			$this->run_section( 'main_head' );
			echo '</div>'."\n";
		}

		// Related items area
		echo '<div id="related">'."\n";
		if($this->has_content( 'pre_sidebar' ))
		{
			echo '<div class="preSidebar">'."\n";
			$this->run_section( 'pre_sidebar' );
			echo '</div>'."\n";
		}
		if($this->has_content( 'sidebar' ))
		{
			echo '<div class="sidebar">'."\n";
			$this->run_section( 'sidebar' );
			echo '</div>'."\n";
		}
		if($this->has_content( 'post_sidebar' ))
		{
			echo '<div class="postSidebar">'."\n";
			$this->run_section( 'post_sidebar' );
			echo '</div>'."\n";
		}
		echo '</div>'."\n";

		if ($this->has_content( 'main' )) 
		{
			echo '<div class="contentMain">'."\n";
			$this->run_section( 'main' );
			echo '</div>'."\n";
		}
		if ($this->has_content( 'main_post' )) 
		{
			echo '<div class="contentPost">'."\n";
			$this->run_section( 'main_post' );
			echo '</div>'."\n";
		}
		echo '</div>'."\n";
		
		
		echo '</div>'."\n";
//		echo '</div>'."\n";
		
		
		// Footer

		echo '<div id="footer">'."\n";
		echo '<div class="module1">';
		$this->run_section( 'footer' );
		echo '</div>';
		echo '<div class="module2 lastModule">';
		$this->run_section( 'edit_link' );
		//echo '<div class="poweredBy">Powered by <a href="http://reason.carleton.edu">Reason CMS</a></div>';
		echo '</div>'."\n";
		
		echo '</div>'."\n";
		echo '</div>'."\n";
		echo '</div>'."\n";
echo '</div id="body" class="container">'."\n";
echo '</div class="column span-50 prepend-1 last">'."\n";

echo '<div class="column span-77 last">'."\n";
		if ($this->has_content( 'post_foot' ))
			$this->run_section( 'post_foot' );
		echo '</div>';
echo '</div class="column span-77 last">'."\n";
echo '</body>'."\n";
	}
}
?>
