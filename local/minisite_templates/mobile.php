<?php

/**
* Mobile Template for Luther site.
* June 2010
 */
 
/**
 * Include the base template so we can extend it
 */
reason_include_once( 'minisite_templates/default.php' );

/**
 * Register this new template with Reason
 */
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'MobileLutherTemplate';

/**
 * A sample Reason template that completely overloads the run method -- full XHTML
 */
class MobileLutherTemplate extends MinisiteTemplate
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
		$this->head_items->add_stylesheet('/stylesheets/luther/mobile/screen.css');
		$this->head_items->add_javascript('/javascripts/modernizr-1.1.min.js');
		
		echo '<!DOCTYPE html>'."\n";
		echo '<html lang="en" class="no-js">'."\n";
		echo '<head>'."\n";
		echo $this->head_items->get_head_item_markup();
		
		if($this->cur_page->get_value('extra_head_content'))
		{
			echo "\n".$this->cur_page->get_value('extra_head_content')."\n";
		}
			
		echo '</head>'."\n";

		echo '<body>'."\n";
		//echo '<div class="hide"><a href="#content" class="hide">Skip Navigation</a></div>'."\n";
		
		// Here's the organization-specific banner
		echo '<div id="myBanner">';
		echo '<a href="http://m.luther.edu"><img src="/images/luther2010/mobile/luther_mobile_header_beta.png" alt="Home" /></a>';
		echo '</div>'."\n";
		
		echo '<div id="wrapper">'."\n";
		echo '<div id="bannerAndMeat">'."\n";
		if ($this->has_content( 'pre_banner' ))
		{	
			echo '<div id="preBanner">';
			$this->run_section( 'pre_banner' );
			echo '</div>'."\n";
		}
		echo '<div id="banner">'."\n";
		if ($this->has_content( 'banner_xtra' ))
		{	
			echo '<div id="bannerXtra">';
			$this->run_section( 'banner_xtra' );
			echo '</div>'."\n";
		}
		echo '</div>'."\n";
		
		// Breadcrumb navigation
		//echo '<div id="breadcrumbs">';
		//echo $this->_get_breadcrumb_markup($this->_get_breadcrumbs(), $this->site_info->get_value('base_breadcrumbs'), ' :: ');
		//echo '</div>'."\n";
		
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
		echo '</div>'."\n";
		
		
		// Footer
		echo '<div id="footer">'."\n";
		echo '<div class="module1">';
		$this->run_section( 'footer' );
		echo '</div>';
		echo '<div class="module2 lastModule">';
		if ($this->has_content( 'post_foot' ))
			$this->run_section( 'post_foot' );
		echo '</div>';
		echo '</div>'."\n";
		
		echo '</div>'."\n";
		echo '</body>'."\n";
	}
}
?>
