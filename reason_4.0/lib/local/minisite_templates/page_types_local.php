<?php
reason_include_once( 'carl_util/luther_util.php' );

$GLOBALS['_reason_page_types_local'] = array(
	
	'default' => array(
		'global_header' => 'global/global_header',
		'global_navigation' => 'global/global_navigation',
			/*'lis_site_announcements' => 'lis_site_announcements',*/
			/*'pre_banner' => 'announcements',*/
			'pre_banner' => '',
			'banner_xtra' => '=',
			'post_banner' => '',
			'pre_main_head' => array(
				'module' => 'feature/feature',
				'width' => '1200',
				'height' => '575',
				'autoplay_timer' => 6,
			),
			/* experimental -- don't remove
			'letterboard_image' => array(
				'module' => 'image_sidebar',
				'num_to_display' => 1,
				'thumbnail_width' => 900,
				'thumbnail_height' => 600,
				'thumbnail_crop' => 'fill',
			),*/
			'main_head' => 'page_title',
			'main' => 'content',
			'main_post' => '',
			'main_post_2' => '',
			'main_post_3' => '',
			'pre_sidebar' => 'assets',
			'sidebar' =>'image_sidebar', // default parameters set in alter_reason_pagetype in luther.php
			'post_sidebar' => 'blurb',
			'navigation' => 'navigation',
			'sub_nav' => 'social_account/social_account',
			'sub_nav_2' => 'blurb_contact',
			'sub_nav_3' => '',
			'edit_link' => 'login_link',
			'footer' => 'maintained',
			'post_foot' => '',
		'global_footer' => 'global/global_footer',
	),

	/* Page types, in alphbetical order */

	'admissions_home' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'sub_nav' => '',
		'sub_nav_2' => 'admissions_sub_nav_2',
		'sub_nav_3' => 'admissions_events_mini',
		'main' => '',	
		'main_post' => array(
			'module'=> 'quote',
			'template' => '<blockquote><p><span class="openingQuote">&#8216;&#8216;</span>[[quote]]</p></blockquote><p class="cite">[[author]]</p>',
			'prefer_short_quotes' => true,
			'num_to_display' => 1,
			'rand_flag' => true,
		),
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
	'all_band' => array(
		'main_post' => 'all_band/all_band',
	),
	'a_to_z' => array(
		'main_post' => 'atoz',
	),
	'audio_video' => array(
		'main_post' => 'luther_av',
		'sidebar' => '',
	),
	'audio_video_reverse_chronological' => array(
		'main_post' => 'luther_av',
		'sidebar' => '',
	),
	'audio_video_full_size' => array(   // one large video in main content area
		'main_post' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'sidebar' => '',
	),
	'audio_video_full_size_sidebar_blurb' => array(   // one large video in main content area with text blurb in right sidebar
		'main_4' => '',
		'main_post' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'pre_sidebar' => 'main_blurb',
		'sidebar' => '',
	),
	'caf_cam' => array(
		'main_post' => 'caf_cam',
	),
	'caf_menu_upload' => array(
		'main_post' => 'caf_menu_upload',
	),
	'directions' => array(
		// 'main' => 'directions',  // @todo: USE BRIAN'S GOOGLE MAP
		'main_post' => 'content',
	),
	'directory' => array(
		'main' => 'directory',  // todo: MAKE DIRECTORY CODE RESPONSIVE-ABLE
	),
	'discovery_camps' => array(
		'main_post' => 'discovery_camps/discovery_camps',
	),
		'dorian_jh_camp' => array(
		'main_post' => 'dorian_jh_camps/dorian_jh_camps',
	),
	'dorian_sh_camp' => array(
		'main_post' => 'dorian_sh_camp/dorian_sh_camp',
	),
	'dorian_vocal_nomination' => array(
		'main_post' => 'dorian_vocal/dorian_vocal',
	),
	'events_instancewide' => array(
		'main_post' => array(
			'module' => 'events_instancewide',
			'list_chrome_markup' => 'minisite_templates/modules/events_markup/responsive/responsive_list_chrome.php',
		),
		'navigation' => '',
		'sub_nav' => '',
		'sub_nav_2' => '',
		'sub_nav_3' => '',
	),
	'faculty' => array(
		'main_post' => 'luther_faculty'
		//'main_post' => 'faculty'
	),
	'faculty_first' => array(
		'main' => 'luther_faculty',
		'main_post' => 'content'
	),
	'flickr_slideshow_sidebar' => array(
		'main_post_2' => '',
		'post_sidebar' => 'luther_flickr_slideshow',		
	),
	'gift_page_engine' => array(
		'main_post' => 'gift_form/gift_form',
	),
	'homecoming_registration' => array(
		'main_post' => 'homecoming_registration/homecoming_registration',
	),
	'homecoming_attendees' => array(
		'main_post' => 'homecoming_attendees',
	),
	'image_slideshow' => array(
		'main_post_2' => 'luther_image_slideshow',
		'sidebar_2' => '',
	),
	'luther_google_map' => array(
		'main_post' => 'luther_google_map',
	),
	'luther_homepage' => array(
		'pre_main_head' => array(
			'module' => 'feature/feature',
			'width' => '1660',
			'height' => '680',
			'autoplay_timer' => 6,
		),
		'main_head' => '',
		'main' => '',
		/*'main_post' => array(
			'module' => 'blurb',
			'num_to_display' => '1',
		),*/
		'pre_sidebar' => array(
			'module' => 'publication',
			'related_mode' => 'true',
			'markup_generator_info' =>
				array(
					'list_item' => array(
						'classname' => 'RelatedListItemNoDescriptionMarkupGenerator', 
						'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item_no_description.php',
						),
					//'list' => array(
					//	'classname' => 'RelatedListHTML5MarkupGenerator', 
					//	'filename' => 'minisite_templates/modules/publication/publication_list_markup_generators/related_list_html5.php',
					//),
				 ),
			'max_num_items' => 3,
			'related_title' => 'Headlines',
			'css' => '',
		),
		'sidebar' => array(
			'module' => 'events_mini',
			//'module' => 'events_upcoming',
			'ideal_count' => 4,
			'title' => 'Campus Events',
		),
		'post_sidebar' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'callouts' => array(
			'module' => 'blurb',
			'num_to_display' => '4',
		),
		'navigation' => '',
		'sub_nav' => '',
	),
	'luther_landing' => array(		
		//'main_post' => get_luther_headlines(3),
		'pre_sidebar' => array(
			'module' => 'events_mini',
			'title' => luther_get_event_title(),
			'ideal_count' => 5,
			'default_view_min_days' => 1,
			'calendar_link_text' => 'View all events',
		),
		'sidebar' => luther_get_related_publication(3),
		//'post_sidebar' => 'luther_flickr_slideshow',
	),
	'luther_sports' => array(
		//'main' => 'luther_sports_results_mini',
		'main' => array(
			'module' => 'luther_sports_results_mini',
			'list_chrome_markup' => 'minisite_templates/modules/events_markup/sports/sports_events_list_chrome.php',
				'list_markup' => 'minisite_templates/modules/events_markup/sports/sports_events_list.php',
				'list_item_markup' => 'minisite_templates/modules/events_markup/sports/sports_events_list_item.php',
				'ideal_count' => 10,
		),
		'pre_sidebar' => array(
			'module' => 'events_mini',
			'title' => 'Schedule',
			'ideal_count' => 7,
			'calendar_link_text' => 'Complete schedule',
		),
		'sidebar' => luther_get_related_publication(3),
	),
	'net_price_calculator' => array(
		'main_post' => 'net_price_calculator',
	),
	'norsecard' => array(
		'main_post' => 'norsecard',
	),
	'open_id' => array(
		'main_post'=>'open_id',
	),

	// @todo: CAN WE USE CORE, or do we need get_luther_publication?

	//'publication' => get_luther_publication(),
	//'publication_feature_autoplay' => get_luther_publication("publication_feature_autoplay"),
	//'publication_section_nav' => get_luther_publication("publication_section_nav"),
	
	'sidebar_blurb' => array(
		'main_4' => '',
		'pre_sidebar_2' => 'main_blurb',
	),
	'slate_form' => array(
		'main_2' => 'slate_form'
	),
	'sports_roster' => array(
		'main' => 'luther_sports_roster',
		'main_post' => 'content',
		'pre_sidebar_3' => '',
		'sidebar' => '',
		'sidebar_2' => '',
		'sidebar_4' => '',
	),
	'search_results' => array(
		'main' => 'luther_search',
		'main_head_2' => '',
		'main_head_3' => '',
		'main_head_4' => '',
	),
	'sports_results' => array(
		'main' => 'luther_sports_results_mini',
		'pre_sidebar_3' => '',
		'sidebar' => 'luther_events_image_sidebar',
		'sidebar_2' => '',
		'sidebar_4' => '',
	),
	'spotlight_detailed_list' => array(
		'pre_banner' => '',
		'post_banner' => '',
		'main_head_4' => 'publication/luther_title',
		'main_head_5' => '',
		'main_post' => array( // Spotlights
			'module' => 'publication',
			'related_title' => '',
			'markup_generator_info' =>array(
				'list_item' =>array (
					'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight_detailed_list.php'
				),
			),
		),
		'main'=>'publication/description',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'pre_sidebar_3' => '',		
		'sidebar'=> '',
		'sidebar_2' => 'luther_publication_image_sidebar',
		'sidebar_4' => '',
	),
	'spotlight_archive' => array(
		'pre_banner' => '',
		'post_banner' => '',
		'main_head_4' => 'publication/luther_title',
		'main_head_5' => '',
		'main_post' => array( // Spotlights
			'module' => 'publication',
			'related_title' => '',
			'markup_generator_info' =>array(
				'item' =>array (
					'classname' => 'SpotlightItemMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/item_markup_generators/spotlight.php'
				),
				'list' =>array (
					'classname' => 'SpotlightPublicationListMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/publication_list_markup_generators/spotlight.php'
				),
			),
		),
		'main'=>'publication/description',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'pre_sidebar_3' => '',		
		'sidebar'=> '',
		'sidebar_2' => 'luther_publication_image_sidebar',
		'sidebar_4' => '',
	),
	'standalone_login_page_stripped' => array(
		'global_header' => '',
		'global_navigation' => '',
		'main' => 'login',
		'global_footer' => '',
	),
	'study_skills_assessment'=> array(
		'main_post'=> 'study_skills_assessment/study_skills_assessment', 
	),
	'stream' => array(
		'main' => 'stream',
		'main_post' => 'content',
		'pre_sidebar' => 'main_blurb',
		'main_4' => '',
	),
	'transcript_request' => array(
		'main_post' => 'transcript_request/transcript_request',
	),
	'webcams' => array(
		'main' => 'content',
		'main_post' => 'webcams',
	),
);
?>