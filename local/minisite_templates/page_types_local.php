<?php

$GLOBALS['_reason_page_types_local'] = array(
		'default' => array(
			'pre_bluebar' => 'textonly_toggle_top',
			'main' => 'content',
			'main_post' => 'assets',
			'main_head' => 'page_title',
			'edit_link' => 'login_link',
			'pre_banner' => 'announcements',
			//'banner_xtra' => 'google_search_appliance',
			'banner_xtra' => 'nav_search_logo',
			'post_banner' => 'navigation_top',
			//'pre_sidebar' => 'assets',
			'sidebar' => 'luther_image_sidebar',
			'navigation' => 'navigation',
			'footer' => 'maintained',
			'sub_nav' => 'luther_username',
			'sub_nav_2' => 'blurb',
			'sub_nav_3' => '',
			'post_foot' => 'luther_footer',
			'imagetop' => 'luther_imagetop',
			'bannerad' => 'luther_bannerad',
                        //'sbvideo' => 'luther_sbvideo'
		),
		'admissions_account_signup' => array(
			'main_post' => 'applicant_account',
		),
        'admissions_home' => array(
			'banner_xtra' => '',
			'post_banner' => '',
			'sub_nav' => '',
			'sub_nav_2' => 'admissions_sub_nav_2',
			'sub_nav_3' => 'admissions_events_mini',
	        //	'main_post' => 'admissions_main_post',	
    	    //	'main_post' => '',	
        	'main' => '',	
			'main_post' => array(
                        'module'=> 'quote',
						'template' => '<blockquote><p><span class="openingQuote">&#8216;&#8216;</span>[[quote]]</p></blockquote><p class="cite">[[author]]</p>',
                        //'enable_javascript_refresh' => true,
                        'prefer_short_quotes' => true,
                        'num_to_display' => 1,
                        'rand_flag' => true,
			),
			//	'pre_sidebar' => 'admissions_pre_sidebar',
			'pre_sidebar' => array( // Spotlights
				'module' => 'publication',
				'related_publication_unique_names' => array( 'spotlight_archives' ),
				'related_mode' => 'true',
				'related_title' => '',
				'related_order' => 'random',
				'max_num_items' => 1,
				'markup_generator_info' => array(
					'list_item' => array (
						'classname' => 'SpotlightListItemMarkupGenerator',
						'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/admissions_spotlight.php'
					),
				),
			),
			'sidebar' => array( // Highlights
				'module' => 'publication',
				//'module' => 'luther_other_publication_news',
				'related_publication_unique_names' => array( 'headlinesarchive' ),
				'related_mode' => 'true',
				'related_title' => '',
				'related_order' => 'random',
				'max_num_items' => 1,
				'markup_generator_info' => array(
					'list_item' => array (
						'classname' => 'HeadlineListItemMarkupGenerator',
						'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/admissions_headline.php'
					),
				),
			),
			'post_sidebar' => 'blurb',	
		),
		'alumni_auction_registration' => array(
			'main_post' => 'alumni_auction_pricing',
		),
		'directions' => array(
			'main_post' => 'directions',
		),
		'events' => array(
			'main_post' => 'luther_events',
		),
        'event_with_form' => array(
        	'main' => 'events',
        	'main_post' => 'form',	
        ),
		'faculty' => array(
			'main_post' => 'luther_faculty'
		),
        //-----------------------Steve's Homepage Begin---------------
        'homepage' => array(
            'main_post'=>'',
            'main_head' => '',
            'main'=>'',
	    	'banner_xtra' => 'google_search_appliance',
            'sidebar'=>array(  
            	'module' => 'luther_other_publication_news',
				'max_num_to_show' => 5,
				),
            'pre_sidebar' => array( // Spotlights
            	'module' => 'publication',
				'related_publication_unique_names' => array( 'spotlight_archives' ),
				'related_mode' => 'true',
				'related_title' => '',
				'related_order' => 'random',
				'max_num_items' => 1,
				'markup_generator_info' =>
				   array(
				     'list_item' =>
				 	array (
					  'classname' => 'SpotlightListItemMarkupGenerator',
				          'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php'
					      ),
					 ),
            //'post_sidebar' => 'bannerAds',
            ),
         ),
        //-----------------------Steve's Homepage End-----------------
		'luther_news_page' => array(
			'main_post' => 'publication',
            		'sub_nav_3' => 'quote',
		),
		'luther_static_page' => array(
			'pre_banner' => '',
			'post_banner' => '',
		),
		'luther_pageLC' => array(
			'pre_banner' => '',
			'post_banner' => '',
		),
		'luther_pageLRC' => array(
			'pre_banner' => '',
			'post_banner' => '',
		),
		'luther_primaryLRC' => array(
            		'main_post'=>array(  
            			'module' => 'luther_other_publication_news',
				'max_num_to_show' => 5,
				),
			'pre_banner' => '',
			'post_banner' => '',
            		'sub_nav_3' => array( // Spotlights
            			'module' => 'publication',
				'related_publication_unique_names' => array( 'spotlight_archives' ),
				'related_mode' => 'true',
				'related_title' => '',
				'related_order' => 'random',
				'max_num_items' => 1,
				'markup_generator_info' =>
				   array(
				     'list_item' =>
				 	array (
					  'classname' => 'SpotlightListItemMarkupGenerator',
				          'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php'

					      ),
					 ),
				   ),
		),
		'luther_home_page' => array(
            		'main_post'=>'',
            		'main_head' => '',
            		'main'=>'',
            		'sidebar'=>array(  
            			'module' => 'luther_other_publication_news',
				'max_num_to_show' => 5,
				),
            		'pre_sidebar' => array( // Spotlights
            		'module' => 'publication',
				'related_publication_unique_names' => array( 'spotlight_archives' ),
				'related_mode' => 'true',
				'related_title' => '',
				'link_to_full_item' => 'true',
				'related_order' => 'random',
				'max_num_items' => 1,
				'markup_generator_info' =>
				   array(
				     'list_item' =>
				 	array (
					  'classname' => 'SpotlightListItemMarkupGenerator',
				          'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php'
					      ),
					 ),
				   ),
		),
		'onecard' => array(
            'main_post'=>'onecard_dashboard',
            'sidebar'=>'',
            'pre_sidebar' => '',
         ),
		'publication' => array(
			'main_post'=>'publication',
            		'main_head' => 'publication/luther_title',
			'main'=>'publication/description',
			'pre_banner' => '',
			'post_banner' => '',
			'sidebar'=>'',
			'pre_sidebar' => '',
        ),        
         'spotlight_archive' => array(
            'main_post' => array( // Spotlights
            	'module' => 'publication',
				'related_title' => '',
				//'link_to_full_item' => 'true',
				'markup_generator_info' =>
				   array(
				     'item' =>
				 	array (
					  'classname' => 'SpotlightItemMarkupGenerator',
				          'filename' =>'minisite_templates/modules/publication/item_markup_generators/spotlight.php'
					      ),
				     'list' =>
				 	array (
					  'classname' => 'SpotlightPublicationListMarkupGenerator',
				          'filename' =>'minisite_templates/modules/publication/publication_list_markup_generators/spotlight.php'
					      ),
				),
        	),     
            'main_head' => 'publication/luther_title',
            'main'=>'publication/description',
			'pre_banner' => '',
			'post_banner' => '',
            'sidebar'=>'',
            //'pre_sidebar' => '',
		),
		'standalone_login_page' => array(
			'main_post' => 'login',
		),
		'standalone_login_page_stripped' => array(
			'main_head' => '',
			'edit_link' => '',
			'banner_xtra' => 'nav_search_logo',
			'navigation' => '',
			'sub_nav' => '',
			'sub_nav_2' => '',
//			'post_foot' => 'textonly_toggle',
			'main' => 'login',
			'sidebar' => 'blurb',
		),
		'test_page' => array(
			'main_post' => 'content',
			'sub_nav_3'=> 'twitter',
		),
		'twitter' => array(
			'sub_nav'=> 'twitter',
			'sub_nav_2' => 'blurb',
			//'sub_nav_3' => 'blurb',
		),
);

function luther_audience_navigation()
{
        echo '<ul id="navmain">'."\n";
        echo '<li class="nm1"><a href="/admissions">Prospective Students</a></li>'."\n";
        echo '<li class="nm2"><a href="http://www.luther.edu/parents">Parents</a></li>'."\n";
        echo '<li class="nm3"><a href="http://www.luther.edu/visitors">Visitors</a></li>'."\n";
        echo '<li class="nm4"><a href="http://www.luther.edu/alumni">Alumni/ Friends</a></li>'."\n";
        echo '<li class="nm5"><a href="http://www.luther.edu/faculty-staff-students">Faculty/ Staff/ Students</a></li></ul>'."\n";
}

function luther_global_navigation()
{
        echo '<ul id="navglobal">'."\n";
        echo '<li class="ng1"><a href="http://www.luther.edu/academics">Academics</a></li>'."\n";
        echo '<li class="ng2"><a href="/admissions">Admissions</a></li>'."\n";
        echo '<li class="ng3"><a href="http://www.luther.edu/student-life">Student Life</a></li>'."\n";
        echo '<li class="ng4"><a href="http://www.luther.edu/news">News & Events</a></li>'."\n";
        echo '<li class="ng5"><a href="http://www.luther.edu/giving">Giving</a></li>'."\n";
        echo '<li class="ng6"><a href="http://www.luther.edu/about">About Luther</a></li>'."\n";
        echo '<li class="ng7"><a href="http://www.luther.edu/contact">Contact</a></li></ul>'."\n";
}

function luther_google_search()
{
                        echo '<form id="search" action="http://find.luther.edu/search" method="get" name="gs">'."\n";
                        echo '<input type="text" value="" maxlength="256" size="32" name="q" />'."\n";
                        echo '<input id="searchButton" class="button" type="submit" value="Search" name="btnG" />'."\n";
                        echo '<input type="hidden" value="0" name="entqr"/>'."\n";
                        echo '<input type="hidden" value="xml_no_dtd" name="output"/>'."\n";
                        echo '<input type="hidden" value="date:D:L:d1" name="sort"/>'."\n";
                        echo '<input type="hidden" value="public_frontend" name="client"/>'."\n";
                        echo '<input type="hidden" value="1" name="ud"/>'."\n";
                        echo '<input type="hidden" value="UTF-8" name="oe"/>'."\n";
                        echo '<input type="hidden" value="UTF-8" name="ie"/>'."\n";
                        echo '<input type="hidden" value="public_frontend" name="proxystylesheet"/>'."\n";
                        echo '<input type="hidden" value="public_collection" name="site"/>'."\n";
                        echo '<input type="hidden" value="%3CHOME/%3E" name="proxycustom"/>'."\n";
                        echo '</form>'."\n";
}

function admissions_main_navigation()
{
        echo '<div class="main-nav">'."\n";
        echo '<div class="wrap clearfix">'."\n";
        echo '<ul class="nav">'."\n";
        echo '<li class="home"><a href="/admissions/">Home</a></li>'."\n";
        echo '<li class="fastFacts"><a href="/admissions/fastfacts/">Fast Facts</a>'."\n";
        	echo '<ul>'."\n";
        	echo '<li><a href="/admissions/fastfacts/luther/">Luther at a Glance</a></li>'."\n";
        	echo '<li><a href="/admissions/fastfacts/profiles/">Class Profiles</a></li>'."\n";
        	echo '<li><a href="/admissions/fastfacts/profile/">Campus Profile</a></li>'."\n";
        	echo '<li><a href="/admissions/fastfacts/directions/">Driving Directions</a></li>'."\n";
        	echo '<li><a href="http://www.luther.edu/about/campus/map/">Campus Map</a></li>'."\n";
        	echo '<li><a href="/admissions/fastfacts/decorah/">Decorah Area</a></li>'."\n";
        	echo '<li><a href="/admissions/fastfacts/counselors/">Meet Your Counselor</a></li>'."\n";
        	echo '</ul></li>'."\n";
        	echo '<li class="academics"><a href="/admissions/academics/">Academics</a>'."\n";
        	echo '<ul>'."\n";
        	echo '<li><a href="/admissions/academics/majors/">Majors & Minors</a></li>'."\n";
        	echo '<li><a href="/admissions/academics/preprofessional/">Preprofessional & Special Programs</a></li>'."\n";
        	echo '<li><a href="/admissions/academics/calendars/">Academic Calendars</a></li>'."\n";
        	echo '<li><a href="/admissions/academics/curriculum/">Curriculum & Graduation Requirements</a></li>'."\n";
        	echo '<li><a href="/admissions/academics/honors/">Honors Program</a></li>'."\n";
        	echo '<li><a href="/admissions/academics/studyabroad/">Study Abroad</a></li>'."\n";
        	echo '<li><a href="/admissions/academics/lis/">Library & Information Services</a></li>'."\n";
        	echo '<li><a href="/admissions/academics/sasc/">Student Academic Support Center</a></li>'."\n";
        	echo '<li><a href="/admissions/academics/sss/">Student Support Services</a></li>'."\n";
        	echo '<li><a href="/admissions/academics/undergrad/">Undergraduate Research</a></li>'."\n";
        	echo '</ul></li>'."\n";        	
        echo '<li class="lifeAtLuther"><a href="/admissions/lifeatluther/">Life at Luther</a>'."\n";
        	echo '<ul>'."\n";
        	echo '<li><a href="/admissions/academics/">Academics</a></li>'."\n";
        	echo '<li><a href="http://sports.luther.edu/">Athletics</a></li>'."\n";
        	echo '<li><a href="http://music.luther.edu/">Music</a></li>'."\n";	
        	echo '<li><a href="/admissions/lifeatluther/blogs/">Student Blogs</a></li>'."\n";
        	echo '<li><a href="http://www.luther.edu/about/video/">Luther Videos</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeatluther/collegeministries/">College Ministries</a></li>'."\n";        	
        	echo '<li><a href="/admissions/lifeatluther/diversity/">Diversity Center</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeatluther/residencelife/">Residence Life</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeatluther/diningservices/">Dining Services</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeatluther/counseling/">Counseling</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeatluther/recsports/">Recreational Sports</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeatluther/wellness/">Wellness Program</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeatluther/healthservice/">Health Service</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeatluther/safetysecurity/">Safety & Security</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeatluther/studentactivities/">Student Activities & Organizations</a></li>'."\n";
        	echo '</ul></li>'."\n";
        echo '<li class="lifeAfterLuther"><a href="/admissions/lifeafterluther/">Life after Luther</a>'."\n";
			echo '<ul>'."\n";
			echo '<li><a href="/admissions/lifeafterluther/careercenter/">Career Center</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeafterluther/major/">Choosing a Major</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeafterluther/internships/">Internships</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeafterluther/jobs/">Jobs, Graduate School, & Volunteering</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeafterluther/outcomes/">Outcomes&mdash;Class of 2008</a></li>'."\n";
        	echo '<li><a href="/admissions/lifeafterluther/reports/">Reports by Class</a></li>'."\n";
        	echo '</ul></li>'."\n";
        echo '<li class="financialAid"><a href="/admissions/financialaid/">Financial Aid</a>'."\n";
			echo '<ul>'."\n";
        	echo '<li><a href="/admissions/financialaid/tuition/">Tuition & Fees</a></li>'."\n";
        	echo '<li><a href="/admissions/financialaid/scholarshipsaid/">Scholarship & Aid</a></li>'."\n";
        	//echo '<li><a href="/admissions/financialaid/parents/">Parents</a></li>'."\n";
        	echo '<li><a href="/admissions/financialaid/faq/">FAQ</a></li>'."\n";
        	echo '<li><a href="http://www.luther.edu/financial-aid/staff/">Staff</a></li>'."\n";
        	echo '<li><a href="/admissions/financialaid/scholarshipsaid/forms/">Forms</a></li>'."\n";
        	echo '</ul></li>'."\n";
        echo '<li class="howToApply"><a href="/admissions/fastfacts/counselors">Staff</a></li>'."\n";
        echo '</ul>'."\n";

	luther_google_search();

        //echo '<div class="search">'."\n";
        //echo '<input type="text" name="search" id="search" />'."\n";
        //echo '<button type="submit"name="submit" id="submit"><img src="/stylesheets/admissions/images/search.png" alt="go!" /></button>'."\n";
        //echo '</div>'."\n";
        echo '</div>'."\n";
        echo '</div>'."\n";

        echo '<div class="body wrap clearfix">'."\n";

}

function get_directory_images($folder)
// given a folder, returns an array of all image files in the folder
{
	$extList = array();
        $extList['gif'] = 'image/gif';
        $extList['jpg'] = 'image/jpeg';
        $extList['jpeg'] = 'image/jpeg';
        $extList['png'] = 'image/png';

	$handle = opendir($folder);
        while (false !== ($file = readdir($handle)))
	{
                $file_info = pathinfo($file);
                if (isset($extList[strtolower($file_info['extension'])]))
                {
			$fileList[] = $file;
                }
        }
        closedir($handle);
	return $fileList;
}

function admissions_get_banner_images()
// gets random top images for the admissions banner
// images must be in the appropriate folder below /images/admissions/
{
	$image_list = array();	

	// horizontal ministry image
	$dir_of_images = get_directory_images($_SERVER['DOCUMENT_ROOT'] . "images/admissions/ministry208x101");
	$image_list[0] = '/images/admissions/ministry208x101/'.$dir_of_images[time() % count($dir_of_images)];

	// two global square images
	$dir_of_images = get_directory_images($_SERVER['DOCUMENT_ROOT'] . "images/admissions/global101x101");
	$i = time() % count($dir_of_images);
	$image_list[1] = '/images/admissions/global101x101/'.$dir_of_images[$i];
	while ($i == ($j = time() % count($dir_of_images)));
	$image_list[2] = '/images/admissions/global101x101/'.$dir_of_images[$j];
	// global horizontal image
	$dir_of_images = get_directory_images($_SERVER['DOCUMENT_ROOT'] . "images/admissions/global208x101");
	$image_list[3] = '/images/admissions/global208x101/'.$dir_of_images[time() % count($dir_of_images)];

        //print_r($dir_of_images);
        //print_r($image_list);
	return $image_list;
}

function admissions_banner()
{
	$bims = admissions_get_banner_images();
        echo '</div>'."\n";
        echo '</div>'."\n";
        echo '</div>'."\n";

        echo '<div class="banner">'."\n";
        echo '<ul class="nav picnav">'."\n";
        echo '<li id="photoTour"><a href="http://www.luther.edu/about/campus/tour">Photo Tour</a></li>'."\n";
        echo '<li id="visitLuther"><a href="/admissions/visit/">Visit Luther</a></li>'."\n";
        echo '<li id="getInfo"><a href="/admissions/getinfo/">Get Info</a></li>'."\n";
        echo '<li id="applyNow"><a href="/admissions/apply/">Apply Now</a></li>'."\n";
        echo '</ul>'."\n";

        echo '<div class="info">'."\n";
        echo '<h2>Information for&hellip;</h2>'."\n";
        echo '<div class="infonav">'."\n";
        echo '<ul class="nav rowOne hasThree clearfix">'."\n";
        echo '<li class="applicants"><a href="/admissions/applicants/">Applicants</a></li>'."\n";
        echo '<li class="acceptedStudents"><a href="/admissions/accepted/">Accepted Students</a></li>'."\n";
        echo '<li class="parents"><a href="/admissions/parents/">Parents</a></li>'."\n";
        echo '</ul>'."\n";
        echo '<ul class="nav rowTwo hasTwo clearfix">'."\n";
        echo '<li class="transferStudents"><a href="/admissions/transfer/">Transfer Students</a></li>'."\n";
        echo '<li class="internationalStudents"><a href="/admissions/international/">International Students</a></li>'."\n";
        echo '</ul>'."\n";
        echo '</div>'."\n";
        echo '</div>'."\n";
        echo '<div class="images">'."\n";
        echo '<div class="row1">'."\n";
	echo '<img alt="" src="'. $bims[0] .'" class="wide"/>'."\n";
        echo '<img alt="" src="'. $bims[1] .'" />'."\n";
        echo '</div>'."\n";
        echo '<div class="row2">'."\n";
        echo '<img src="' . $bims[2] .'" />'."\n";
	echo '<img alt="" src="' . $bims[3] .'" class="wide"/>'."\n";
        echo '</div>'."\n";
        //echo '<div class="row3">'."\n";
        //echo '<img src="/images/admissions/5.jpg" class="wide" />'."\n";
        //echo '<img src="/images/admissions/6.jpg" />'."\n";
        //echo '</div>'."\n";
        //echo '</div>'."\n";
        //echo '</div>'."\n";
        //echo '</div>'."\n";
}    

function admissions_music_sports_banners()
{
	$image_list = array();
	// horizontal image
	$music = rand(1,1000) % 2;
	if ($music == 1)
	{
		$dir_of_images = get_directory_images($_SERVER['DOCUMENT_ROOT'] . "images/admissions/music208x101");
		$image_list[0] = '/images/admissions/music208x101/'.$dir_of_images[time() % count($dir_of_images)];
		$dir_of_images = get_directory_images($_SERVER['DOCUMENT_ROOT'] . "images/admissions/sports101x101");
		$image_list[1] = '/images/admissions/sports101x101/'.$dir_of_images[time() % count($dir_of_images)];
	}
	else  // horizontal sports and square music
	{
		$dir_of_images = get_directory_images($_SERVER['DOCUMENT_ROOT'] . "images/admissions/sports208x101");
		$image_list[0] = '/images/admissions/sports208x101/'.$dir_of_images[time() % count($dir_of_images)];
		$dir_of_images = get_directory_images($_SERVER['DOCUMENT_ROOT'] . "images/admissions/music101x101");
		$image_list[1] = '/images/admissions/music101x101/'.$dir_of_images[time() % count($dir_of_images)];
	
	}

	return $image_list;
}

function admissions_logo()
{
	//echo '<div class="sidebar">'."\n";
	echo '<div class="logo">'."\n";
	echo '<h1><a href="/">Luther College</a></h1>'."\n";
	echo '<h2><a href="/admissions/">Admissions</a></h2>'."\n";
	echo '</div>'."\n";
}    

function google_analytics()
{
	echo '<script type="text/javascript">'."\n";
	echo 'var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");'."\n";
	echo 'document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));'."\n";
	echo '</script>'."\n";
	echo '<script type="text/javascript">'."\n";
	echo 'var pageTracker = _gat._getTracker("UA-129020-8");'."\n";
	echo 'pageTracker._setDomainName("luther.edu");'."\n";
	echo 'pageTracker._trackPageview();'."\n";                echo '</script>'."\n";
}

?>
