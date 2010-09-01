<?php

/**
 * LutherHome Reason template, with full overloading of the run method
 * Written by: Steve Smith
 * March 2009
 */
 
/**
 * Include the base template so we can extend it
 */
reason_include_once( 'minisite_templates/default.php' );

/**
 * Register this new template with Reason
 */
$GLOBALS[ '_minisite_template_class_names' ][ basename( __FILE__) ] = 'LutherHomeTemplate';

/**
 * A sample Reason template that completely overloads the run method -- full XHTML
 */
class LutherHomeTemplate extends MinisiteTemplate
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

<body id="home">
  <div class="container">
  <div id="body" class="container">
    <div id="head">
      <div id="logosearch" class="container">
        <div class="column span-17 ">
  	      <div id="logo">
            <a href="/" title="Luther College Home"><span></span>
	      		<img alt="Luther College" src="/images/luther/logo-stacked.jpg"/>
	     	</a>
          </div id="logo">
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


      		<ul id="navmain"><li class="nm1"><a href="/admissions">Prospective Students</a></li><li class="nm2"><a href="http://www.luther.edu/parents">Parents</a></li><li class="nm3"><a href="http://www.luther.edu/visitors">Visitors</a></li><li class="nm4"><a href="http://www.luther.edu/alumni">Alumni/ Friends</a></li><li class="nm5"><a href="http://www.luther.edu/faculty-staff-students">Faculty/ Staff/ Students</a></li></ul>

        </div class="column span-60 last">
	  </div id="logosearch" class="container">
    </div id="head">

	<div id="homeglobalimagetop" class="container">
	  <div class="column span-17">
	    <ul id="navglobal">
	    	<li class="ng1"><a href="http://www.luther.edu/academics">Academics</a></li>
	    	<li class="ng2"><a href="/admissions">Admissions</a></li>
	    	<li class="ng3"><a href="http://www.luther.edu/student-life">Student Life</a></li>
	    	<li class="ng4"><a href="http://www.luther.edu/news">News & events</a></li>
	    	<li class="ng5"><a href="http://www.luther.edu/giving">Giving</a></li>
	    	<li class="ng6"><a href="http://www.luther.edu/about">About Luther</a></li>
	    	<li class="ng7"><a href="http://www.luther.edu/contact">Contact</a></li>
	    </ul id="navglobal">
	  </div class="column span-17">
	  <div class="column span-60 last">
        <div id="imagetopframe">
        	<img alt="" src="/images/luther/homepage/banners/rotate.php"/>
        </div> <!--id="imagetopframe"-->
	  </div> <!--class="column span-60 last"-->
    </div> <!--id="homeglobalimagetop" class="container"-->
		
<?php
		echo '<div id="wrapper">'."\n";
		echo '<div id="bannerAndMeat">'."\n";
             
                echo '<div class="container">'."\n";
                //echo '<div id="spotlight">'."\n";
                //echo '<div class="column span-17">'."\n";
             
		//if ($this->has_content( 'navigation' )) 
		//{ 
		//	$this->run_section( 'navigation' );
		//}
                //echo '<img src="/images/luther/torgerson_lisa_5740_25020081231112008_largeEx.gif" alt="" />'."\n";
                
        //--------------------------Spotlight  Begin
        if ($this->has_content( 'pre_sidebar' )) 
		{ 
			$this->run_section( 'pre_sidebar' );
		}
		//--------------------------Spotlight End
		

                echo '<div class="column span-21 prepend-1 append-1 borderleft border">'."\n";
                echo '<div id="headlines">'."\n";
	      echo '<h2>Headlines</h2>'."\n";
		//echo '<div id="meat">'."\n";
		//if ($this->has_content( 'main_post' )) 
		//{
		//	echo '<div class="contentPost">'."\n";
		//	$this->run_section( 'main_post' );
		//	echo '</div>'."\n";
		//}
		
		
		//--------------------------Headlines Publication Begin
		if ($this->has_content( 'sidebar' ))
		{
			$this->run_section( 'sidebar' );
		}
		//--------------------------Headlines Publication End
		
                echo '<div id="headline-archive">'."\n";
		echo '<a href="/headlines">more news &gt;</a>'."\n";
                echo '</div>  <!-- id="headline-archive" -->'."\n";

                echo '</div>  <!-- id="headlines" -->'."\n";
                echo '</div>  <!-- class="column span-21 prepend-1 append-1 borderleft border" -->'."\n";

                echo '<div class="column span-20 prepend-1 last">'."\n";
                echo '<div id="features">'."\n";
	            echo '<h2>Features</h2>'."\n";
                echo '<a href="http://www.luther.edu/studyabroad">'."\n";
                echo '<img src="/images/luther/banner_study_abroad_216x78.gif" alt=""/></a>'."\n";
                echo '<a href="/admissions/visit">'."\n";
                echo '<img src="/images/luther/banner_ad_visit_216x78.gif" alt=""/></a>'."\n";
                echo '<a href="/admissions/apply">'."\n";
                echo '<img src="/images/luther/banner_ad_apply_216x78.gif" alt=""/></a>'."\n";
                //echo '<a href="http://www.luther.edu/about/video">'."\n";
                //echo '<img src="/images/luther/banner_ad_video_216x78.gif" alt=""/></a>'."\n";

                echo '</div>  <!-- id="features" -->'."\n";
                echo '</div>  <!--  class="column span-20 prepend-1 last" -->'."\n";



		echo '</div>'."\n";

		echo '</div>'."\n";
		
		
		echo '</div>'."\n";
//		echo '</div>'."\n";

		echo '<div id="imageside">'."\n";
                echo '<div class="column span-15">'."\n";
		echo '<div class="imagesideframe">'."\n";
		echo '<a href="http://sports.luther.edu">';
		echo '<img src="/images/luther/homepage/subsite_nav/sports_banner.gif" alt="Norse Sports"/></a>';
		echo '</div> <!-- class="imagesideframe" -->'."\n";
                echo '</div> <!-- class="column span-15" -->'."\n";
                echo '<div class="column span-15">'."\n";
		echo '<div class="imagesideframe">'."\n";
//		echo '<a href="http://lutherbookshop.com" onClick="javascript:pageTracker._trackPageview(\'/lutherbookshop\'); ">';
		echo '<a href="/bookshop/">';
		echo '<img src="/images/luther/homepage/subsite_nav/bookshop_banner.gif" alt="Bookshop"/></a>';
		echo '</div> <!-- class="imagesideframe" -->'."\n";
                echo '</div> <!-- class="column span-15" -->'."\n";
                echo '<div class="column span-15">'."\n";
		echo '<div class="imagesideframe">'."\n";
		echo '<a href="http://music.luther.edu">';
		echo '<img src="/images/luther/homepage/subsite_nav/music_banner.gif" alt="Music at Luther"/></a>';
		echo '</div> <!-- class="imagesideframe" -->'."\n";
                echo '</div> <!-- class="column span-15" -->'."\n";
                echo '<div class="column span-15">'."\n";
		echo '<div class="imagesideframe">'."\n";
		echo '<a href="/programming/calendar/">';
		//echo '<a href="http://eventcentral.myxa.com/cgi-bin/display_results.fcg?style=luther&amp;amp;amp;group=LUTHER&amp;amp;amp;restrict_group=LUTHER&amp;amp;amp;start_date=today&amp;amp;amp;search=perfs&amp;amp;amp;file=lc_eventsummary.ttml&amp;amp;amp;allow_session=1">';
		echo '<img src="/images/luther/homepage/subsite_nav/events_banner.gif" alt="Events Calendar"/></a>';
		echo '</div> <!-- class="imagesideframe" -->'."\n";
                echo '</div> <!-- class="column span-15" -->'."\n";
                echo '<div class="column span-15">'."\n";
		echo '<div class="imagesideframe">'."\n";
		echo '<a href="http://www.luther.edu/about/campus/virtualtour">';
		echo '<img src="/images/luther/homepage/subsite_nav/virtual_tour.gif" alt="Virtual Tour"/></a>';
		echo '</div> <!-- class="imagesideframe" -->'."\n";
                echo '</div> <!-- class="column span-15" -->'."\n";
		echo '</div> <!-- id="imageside" -->'."\n";




		// Footer

		echo '<div id="footer">'."\n";
		echo '<div class="module1">';
		//$this->run_section( 'footer' );
		echo '</div>';
	//	echo '<div class="module2 lastModule">';
	//	$this->run_section( 'edit_link' );
		//echo '<div class="poweredBy">Powered by <a href="http://reason.carleton.edu">Reason CMS</a></div>';
		echo '</div>'."\n";
		
echo '</div class="column span-50 prepend-1 last">'."\n";

echo '<div class="column span-77 last">'."\n";
		if ($this->has_content( 'post_foot' ))
			$this->run_section( 'post_foot' );
		echo '</div>';
		
echo '</div class="column span-77 last">'."\n";
echo '</div id="body" class="container">'."\n";
echo '</body>'."\n";
  }
}
?>
