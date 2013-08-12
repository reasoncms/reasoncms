<?php

$GLOBALS['_reason_page_types_local'] = array(
	'default' => array(
		'pre_bluebar' => 'textonly_toggle_top',
		'pre_banner' => 'announcements',
		'banner_xtra' => 'nav_search_logo',
		'lis_site_announcements' => 'lis_site_announcements',
		'post_banner' => 'navigation_top',
		'edit_link' => 'login_link',
		'main_head' => '',
		'main_head_2' => 'luther_breadcrumbs',
		'main_head_3' => 'luther_addthis',
		'main_head_4' => 'page_title',
		'main_head_5' => 'luther_imagetop',
		'main' => 'content',
		'main_2' => 'norse_calendar',
		'main_3' => '',
		'main_4' => 'main_blurb',
		'main_5' => '',
		'main_post' => 'assets',
		'main_post_2' => 'luther_flickr_slideshow',
		'main_post_3' => '',
		'pre_nav' => 'luther_section_heading',
		'navigation' => 'navigation',
		'sub_nav' => '',		
		'sub_nav_2' => 'luther_bannerad',
		'sub_nav_3' => 'contact_blurb',
		'sub_nav_4' => '',
		'sub_nav_5' => '',
		'pre_sidebar' => '',   // main_blurb on landing pages
		'pre_sidebar_2' => '',   // spotlight on landing pages
		'pre_sidebar_3' => array(
			'module' => 'feature/feature',
			'shuffle' => false,
			'autoplay_timer' => 12,
			'width'=>222,
			'height'=>148
		),
		'sidebar' => 'luther_av',
		'sidebar_2' => 'luther_image_sidebar',
		'sidebar_3' => '',   // events on landing pages
		'sidebar_4' => 'twitter',
		'sidebar_5' => '',   // news on landing pages
		'post_sidebar' => '',   // flickr slideshow on landing pages
		'post_sidebar_2' => '',
		'post_sidebar_3' => '',
		'footer' => 'maintained',						
		'post_foot' => 'luther_footer',
	),
	
	/*'default' => array(
		'pre_bluebar' => 'textonly_toggle_top',
		'main' => 'content',
		'main_post' => 'assets',
		'main_head' => 'page_title',
		'edit_link' => 'login_link',
		'pre_banner' => 'announcements',
		//'banner_xtra' => 'google_search_appliance',
		'banner_xtra' => 'nav_search_logo',
		'post_banner' => 'navigation_top',
		'pre_sidebar' => 'luther_av',
		'sidebar' => 'luther_image_sidebar',
		'navigation' => 'navigation',
		'footer' => 'maintained',
		'sub_nav' => 'luther_username',
		'twitter_sub_nav' => 'twitter',
		'sub_nav_2' => 'contact_blurb',
		'sub_nav_3' => '',
		'post_foot' => 'luther_footer',
		'imagetop' => 'luther_imagetop',
		'bannerad' => 'luther_bannerad',
		'flickr_slideshow' => 'luther_flickr_slideshow',
		'norse_calendar' => 'norse_calendar',
		'sbvideo' => 'luther_sbvideo',
		'content_blurb' => 'main_blurb',  */

	'aaron_test_page' => array(
		'main' => 'mobile_directions',
		'main_post' => 'content',
	),
	'admissions_account_signup' => array(
		'main_post' => 'applicant_account',
	),
	'admissions_application' => array(
		'main' => 'open_id_status',
		'main_post' => 'admissions_application',
	),
	'admissions_application_export' => array(
		'main' => 'admissions_application_export',
		'main_post' => 'children',
	),
	'admissions_clear_export_table' => array(
		'main' => 'content',
		'main_post' => 'admissions_clear_export_table',
	),
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
	'alumni_auction_registration' => array(
		'main_post' => 'alumni_auction_pricing',
	),
	'app_dev_on_call' => array(
		'main_post' => 'app_dev_on_call',
	),
	'a_to_z' => array(
		'banner_xtra' => 'nav_search_logo',
		'navigation' => 'navigation',
		'sub_nav_2' => 'luther_bannerad',
		'sub_nav' => 'luther_username',	
		'main_head' => '',
		'main_post' => 'atoz',
		'post_foot' => 'luther_footer',
		'sidebar' => 'luther_av',
	),
	'audio_video' => array(
		'main_post' => 'luther_av',
		'sidebar' => '',
	),
	'audio_video_reverse_chronological' => array(
		'main_post' => 'luther_av',
		'sidebar' => '',
	),
	'audio_video_on_current_site' => array(
		'main_post' => 'luther_av',
		'sidebar' => '',
	),
	'audio_video_sidebar' => array(

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
		'main' => 'directions',
		'main_post' => 'content',
	),
	'directory_simple_campus' => array(
		'main' => 'directory_campus_simple',
	),
	'directory_alumni' => array(
		'main' => 'directory_search_alumni',
		'sidebar' => 'login_link',
	),
	'directory' => array(
		'main' => 'directory',
		//'main_head' => 'login_link',
	),
	'django_form' => array(
		'main_post' => 'django_form',
	),
	'discovery_camps' => array(
		'main_post' => 'discovery_camps/discovery_camps',
	),
	'dorian_band_nomination' => array(
		'main_post' => 'dorian_band/dorian_band',
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
	'events' => array(
		'main_post' => 'luther_events',
	),
	'event_with_form' => array(
		'main' => 'events',
		'main_3' => '',
		'main_post' => 'form',
	),
	'faculty' => array(
		'main_post' => 'luther_faculty'
	),
	'faculty_first' => array(
		'main' => 'luther_faculty',
		'main_post' => 'content'
	),
	'feature' => array(
		'main_head' => array(
			'module' => 'feature/feature',
			'shuffle' => false,
			'width'=>716,
			'height'=>288
			//'width'=>222,
			//'height'=>148
		),
		'main_head_5' => '',
		'pre_sidebar_3' => '',
	),
	'feature_autoplay' => array(
		'main_head' => array(
			'module' => 'feature/feature',
			'shuffle' => false,
			'autoplay_timer' => 12,
			'width'=>716,
			'height'=>288
			//'width'=>222,
			//'height'=>148
		),
		'main_head_5' => '',
		'pre_sidebar_3' => '',
	),
	'flickr_slideshow_sidebar' => array(
		'main_post_2' => '',
		'post_sidebar' => 'luther_flickr_slideshow',		
	),
	'form' => array(
		'main' => 'form_content',
		'main_3' => '',
		'main_post' => 'form'
	),
	'form_sidebar_blurbs' => array(
		'main' => 'form_content',
		'main_3' => '',
		'main_post' => 'form',
		'sidebar' => 'main_blurb',
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
			'markup_generator_info' =>array(
				'list_item' =>array (
					'classname' => 'SpotlightListItemMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php',
				),
			),
		),
	),//-----------------------Steve's Homepage End-----------------
	'image_slideshow' => array(
		'main_post_2' => 'luther_image_slideshow',
		'sidebar_2' => '',
	),
	'jenson_medal' => array(
		'main_post' => 'jenson_medal_voting',
	),
	'lfw_registration' => array(
		'main_post' => 'lfw/lfw_form',
	),
	'luther2010_admissions' => array(
		'main_head' => array(
			'module' => 'feature/feature',
			'shuffle' => false,
			'autoplay_timer' => 12,
			'width'=>716,
			'height'=>288
		),
		'main_head_5' => '',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_post' => 'luther_events_mini',
		'pre_sidebar' => 'main_blurb',
		'pre_sidebar_2' => '',
		'pre_sidebar_3' => '',
		'sidebar_2' => array( // Spotlights
			'module' => 'publication',
			'related_publication_unique_names' => array('spotlights_admissions'),
			'related_mode' => 'true',
			'related_title' => '',
			'related_order' => 'random',
			'max_num_items' => 1,
			'markup_generator_info' =>array(
				'list_item' =>array (
					'classname' => 'SpotlightListItemMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php'
				),
			),
		),
		'sidebar_5' => get_luther_related_publication(3),
	),
	'luther2010_alumni' => array(
		'main_head' => array(
			'module' => 'feature/feature',
			'shuffle' => false,
			'autoplay_timer' => 12,
			'width'=>716,
			'height'=>288
		),
		'main_head_5' => '',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_post' => 'luther_events_mini',
		'pre_sidebar' => 'main_blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'pre_sidebar_3' => '',
		'sidebar_2' => '',
		'sidebar_5' => get_luther_related_publication(3),
	),
	'luther2010_carousel' => array (
		'main_head' => 'luther_carousel',
		'main_head_5' => '',
	),
	'luther2010_giving' => array(
		'main_head' => 'luther_carousel',
		'main_head_5' => '',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_post' => array(
			'module'=>'children',
			'provide_images' => true,
		),
		'pre_sidebar' => 'main_blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'pre_sidebar_3' => 'luther_events_mini',
		'sidebar_2' => '',
		'sidebar_5' => get_luther_related_publication(3),
	),
	'luther2010_home' => array(
		'main_post'=>'',
		'main_head' => '',
		'main_head_2' => '',
		'main_head_3' => '',
		'main_head_4' => '',
		'main_head_5' => '',
		'main'=>'',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_5' => '',
		'main_post' => '',
		'main_post_2' => '',
		'main_post_3' => '',
		'pre_nav' => '',
		'navigation' => 'luther_carousel',
		'sub_nav' => 'luther_image_quote',
		'sub_nav_2' => '',
		'sub_nav_3' => '',
		'sub_nav_4' => '',
		'sub_nav_5' => '',
		'banner_xtra' => 'nav_search_logo',
		'pre_sidebar' => 'luther_events_mini',
		'pre_sidebar_3' => '',
		'sidebar'=> array( // News  
			'module' => 'luther_other_publication_news',
			'max_num_to_show' => 3,
		),
		'sidebar_2' => '',
		'sidebar_4' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'post_sidebar' => array( // Spotlights
			'module' => 'publication',
			'related_publication_unique_names' => array( 'spotlight_archives' ),
			'related_mode' => 'true',
			'related_title' => '',
			'related_order' => 'random',
			'max_num_items' => 1,
			'markup_generator_info' =>array(
				'list_item' =>array (
					'classname' => 'SpotlightListItemMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php'
				),
			), 	
		),
		'post_sidebar_2' => 'luther_bannerad',
	),
	'luther2010_home_feature' => array(
			'main_post'=>'',
			'main_head' => '',
			'main_head_2' => '',
			'main_head_3' => '',
			'main_head_4' => '',
			'main_head_5' => '',
			'main'=>'',
			'main_2' => '',
			'main_3' => '',
			'main_4' => '',
			'main_5' => '',
			'main_post' => '',
			'main_post_2' => '',
			'main_post_3' => '',
			'pre_nav' => '',
			'navigation' => array(
					'module' => 'feature/feature',
					'shuffle' => false,
					'autoplay_timer' => 12,
					'width'=>716,
					'height'=>288
			),
			'sub_nav' => 'luther_image_quote',
			'sub_nav_2' => '',
			'sub_nav_3' => '',
			'sub_nav_4' => '',
			'sub_nav_5' => '',
			'banner_xtra' => 'nav_search_logo',
			'pre_sidebar' => 'luther_events_mini',
			'pre_sidebar_3' => '',
			'sidebar'=> array( // News
					'module' => 'luther_other_publication_news',
					'max_num_to_show' => 3,
			),
			'sidebar_2' => '',
			'sidebar_4' => array(
					'module' => 'luther_av',
					'full_size' => true,
			),
			'post_sidebar' => array( // Spotlights
					'module' => 'publication',
					'related_publication_unique_names' => array( 'spotlight_archives' ),
					'related_mode' => 'true',
					'related_title' => '',
					'related_order' => 'random',
					'max_num_items' => 1,
					'markup_generator_info' =>array(
							'list_item' =>array (
									'classname' => 'SpotlightListItemMarkupGenerator',
									'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php'
							),
					),
			),
			'post_sidebar_2' => 'luther_bannerad',
	),
	'luther2010_landing' => array(
		'main_head' => 'luther_carousel',
		'main_head_5' => 'luther_tab_widget',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_post' => get_luther_headlines(3),
		'main_post_2' => '',
		'pre_sidebar' => 'main_blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'sidebar_2' => '',
		'sidebar_3' => 'luther_events_mini',
		'sidebar_5' => get_luther_related_publication(3),
		'post_sidebar' => 'luther_flickr_slideshow',
	),
	'luther2010_landing_feature' => array(
		'main_head' => array(
			'module' => 'feature/feature',
			'shuffle' => false,
			'autoplay_timer' => 12,
			'width'=>716,
			'height'=>288
		),
		'main_head_5' => 'luther_tab_widget',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_post' => get_luther_headlines(3),
		'main_post_2' => '',
		'pre_sidebar' => 'main_blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'pre_sidebar_3' => '',
		'sidebar_2' => '',
		'sidebar_3' => 'luther_events_mini',
		'sidebar_5' => get_luther_related_publication(3),
		'post_sidebar' => 'luther_flickr_slideshow',
	),
	 'luther2010_landing_feature_sidebar_news' => array(
		'main_head' => array(
			'module' => 'feature/feature',
			'shuffle' => false,
			'autoplay_timer' => 12,
			'width'=>716,
			'height'=>288
		),
		'main_head_5' => 'luther_tab_widget',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_post' => '',
		'main_post_2' => '',
		'pre_sidebar' => 'main_blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
	 	'pre_sidebar_3' => '',
		'sidebar_2' => '',
		'sidebar_3' => 'luther_events_mini',
		'sidebar_5' => get_luther_related_publication(5),
		'post_sidebar' => 'luther_flickr_slideshow',
	),
	'luther2010_music' => array(
		'main_head' => array(
			'module' => 'feature/feature',
			'shuffle' => false,
			'autoplay_timer' => 12,
			'width'=>716,
			'height'=>288
		),
		'main_head_5' => '',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_post' => 'luther_events_mini',
		'main_post_2' => get_luther_spotlight(),
		'pre_sidebar' => 'main_blurb',
		'pre_sidebar_2' => '',
		'pre_sidebar_3' => '',
		'sidebar_2' => '',
		'sidebar_5' => get_luther_related_publication(3),
		'post_sidebar' => 'luther_flickr_slideshow',
	),
	'luther2010_public_information' => array(
		'main_head' => 'luther_carousel',
		'main_head_5' => '',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_post' => array(
			'module' => 'publication',
			'related_publication_unique_names' => array('headlinesarchive'),
			'related_mode' => 'true',
			'max_num_items' => 7,
		),
		'main_post_2' => '',
		'pre_sidebar' => 'twitter',
		'pre_sidebar_2' => get_luther_spotlight(),
		'sidebar_2' => '',
		'sidebar_3' => 'luther_events_mini',
		'sidebar_4' => '',
		'post_sidebar' => 'luther_flickr_slideshow',
		'post_sidebar_2' => 'main_blurb',
	),
	'luther2010_sports' => array(
		'main_head' => array(
				'module' => 'feature/feature',
				'shuffle' => false,
				'autoplay_timer' => 12,
				'width'=>716,
				'height'=>288
		),
		'main_head_5' => '',
		'main' => 'luther_sports_results_mini',
		'main_2' => '',
		'main_3' => '',
		'main_4' => '',
		'main_post' => array(
			'module' => 'publication',
			'related_publication_unique_names' => array(luther_sports_get_publication_unique_name("headlines")),
			'show_featured_items' => true,
			'related_mode' => 'false',
			'related_title' => '',
			'max_num_items' => 5,
		),
		'pre_nav' => 'luther_section_heading',
		'navigation' => 'navigation',
		'sub_nav' => 'luther_username',		
		'sub_nav_2' => 'luther_events_mini',
		'sub_nav_3' => 'luther_bannerad',
		'sub_nav_4' => 'contact_blurb',
		'sub_nav_5' => '',
		'pre_sidebar' => 'main_blurb',
		'pre_sidebar_3' => '',
		'sidebar_2' => '',
		'post_sidebar' => array( // Spotlights
			'module' => 'publication',
			'related_publication_unique_names' => array(luther_sports_get_publication_unique_name("spotlights")),
			'related_mode' => 'true',
			'related_title' => '',
			'related_order' => 'random',
			'max_num_items' => 1,
			'markup_generator_info' =>array(
				'list_item' =>array (
					'classname' => 'SpotlightListItemMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php'
				),
			),
		),
	),
	'luther_google_map' => array(
		'main_post' => 'luther_google_map',
	),
	'luther_tab_widget' => array(
		'main_post' => 'luther_tab_widget',
	),
	'mobile_admissions' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main_head' => '',
		'main' => 'content',
		'main_post' => 'mobile_admissions_home',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_blank' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'mobile_blank',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_caf_cam' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'mobile_caf_cam',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_caf_menu' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'mobile_caf_menu',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_directions' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'mobile_directions',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_directory' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'aaron_directory',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_event_cal' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'mobile_event_cal',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_home' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main_head' => '',
		'main' => 'content',
		'main_post' => 'mobile_icon_home',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_labstats' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'lab_stats',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_librarysearch' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'library_search',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_map' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'campus_map',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_map_home' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => 'content',
		'main_post' => 'mobile_map_home',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_news' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main' => '',
		'main_post' => 'publication',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'mobile_visitor' => array(
		'banner_xtra' => '',
		'post_banner' => '',
		'main_head' => '',
		'main' => 'content',
		'main_post' => 'mobile_visitor_home',
		'footer' => '',
		'post_foot' => 'mobile_footer',
	),
	'net_price_calculator' => array(
		'main_post' => 'net_price_calculator',
	),
	'norge_conference' => array(
		'main_post' => 'norge_form/norge_form',
	),
	'artwork_module' => array(
		'main_post' => 'artwork_module',
	),
	'norse_form' => array(
		'main_post' => 'norse_form',
	),
	'norsecard' => array(
		'main_post' => 'norsecard',
	),
	'onecard' => array(
		'main_post'=>'onecard_dashboard',
		'sidebar'=>'',
		'pre_sidebar' => '',
	),
	'open_id' => array(
		'main_post'=>'open_id',
	),
	'publication' => get_luther_publication(),
	'publication_section_nav' => get_luther_publication_section_nav(),
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
		// 'navigation' => '',
		// 'sub_nav' => '',	
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
		'main' => 'login',
		'sidebar' => 'blurb',
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
	'test_page' => array(
		'main' => 'content',
		'main_post' => 'test_module',
		'sub_nav_3'=> 'twitter',
	),
	'transcript_request' => array(
		'main_post' => 'transcript_request/transcript_request',
	),
	'webcams' => array(
		'main' => 'content',
		'main_post' => 'webcams',
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

function luther2010_audience_navigation()
{
	echo '<ul>'."\n";
	echo '<li><a href="/admissions">Prospective Students</a></li>'."\n";
	echo '<li><a href="/parents">Parents</a></li>'."\n";
	echo '<li><a href="/alumni">Alumni & Friends</a></li>'."\n";
	echo '<li><a href="/facultystaff">Faculty & Staff</a></li>'."\n";
	echo '<li><a href="/students">Current Students</a></li>'."\n";
	echo '</ul>'."\n";
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

function luther2010_global_navigation()
{
	echo '<ul>'."\n";
	echo '<li class="admissions"><a href="/admissions">Admissions</a></li>'."\n";
	echo '<li class="academics"><a href="/academics">Academics</a></li>'."\n";
	echo '<li class="library-Technology"><a href="/lis">Library & Technology</a></li>'."\n";
	echo '<li class="student-life"><a href="/studentlife">Student Life</a></li>'."\n";
	echo '<li class="athletics"><a href="/sports">Athletics</a></li>'."\n";
	echo '<li class="music"><a href="/music">Music</a></li>'."\n";
	echo '<li class="giving"><a href="/giving">Giving</a></li>'."\n";
	echo '<li class="decorah"><a href="/decorah">Decorah</a></li>'."\n";
	echo '<li class="about-luther"><a href="/about">About Luther</a></li></ul>'."\n";
}

function luther_google_search()
{
	echo '<form id="search" action="//find.luther.edu/search" method="get" name="gs">'."\n";
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
function luther2010_google_search()
{
	echo '<gcse:searchbox-only style="width:30%"></gcse:searchbox-only>';
}

function luther_mobile_google_search()
{
	echo '<gcse:searchbox-only></gcse:searchbox-only>';
}

function admissions_main_navigation()
{
	echo '<div class="main-nav">'."\n";
	echo '<div class="wrap clearfix">'."\n";
	echo '<ul class="nav">'."\n";
	echo '<li class="home"><a href="/admissions/">Home</a></li>'."\n";
	echo '<li class="fastFacts"><a href="/admissions/fastfacts/">Fast Facts</a>'."\n";
	echo '<ul>'."\n";
	echo '<li><a href="/about/facts/">Quick Facts</a></li>'."\n";
	echo '<li><a href="/admissions/fastfacts/profiles/">Class Profiles</a></li>'."\n";
	echo '<li><a href="/admissions/fastfacts/profile/">Student Body Profile</a></li>'."\n";
	echo '<li><a href="/admissions/fastfacts/directions/">Driving Directions</a></li>'."\n";
	echo '<li><a href="/about/campus/map/">Campus Map</a></li>'."\n";
	echo '<li><a href="/decorah/">Decorah Area</a></li>'."\n";
	echo '<li><a href="/admissions/fastfacts/counselors/">Meet Your Counselor</a></li>'."\n";
	echo '</ul></li>'."\n";
	echo '<li class="academics"><a href="/admissions/academics/">Academics</a>'."\n";
	echo '<ul>'."\n";
	echo '<li><a href="/academics/majors/">Majors & Minors</a></li>'."\n";
	echo '<li><a href="/registrar/calendar/">Academic Calendars</a></li>'."\n";
	echo '<li><a href="/admissions/academics/requirements/">Admissions Requirements</a></li>'."\n";        	
	echo '<li><a href="/catalog/">Curriculum & Graduation Requirements</a></li>'."\n";
	echo '<li><a href="/academics/dean/honors/">Honors Program</a></li>'."\n";
	//echo '<li><a href="/admissions/academics/studyabroad/">Study Abroad</a></li>'."\n";
	echo '<li><a href="/global_learning/">Center for Global Learning</a></li>'."\n";
	echo '<li><a href="/lis">Library & Information Services</a></li>'."\n";
	echo '<li><a href="/sasc/">Student Academic Support Center</a></li>'."\n";
	echo '<li><a href="/sss/">Student Support Services</a></li>'."\n";
	echo '<li><a href="/admissions/academics/undergrad/">Undergraduate Research</a></li>'."\n";
	echo '</ul></li>'."\n";        	
	echo '<li class="lifeAtLuther"><a href="/admissions/lifeatluther/">Life at Luther</a>'."\n";
	echo '<ul>'."\n";
	echo '<li><a href="/admissions/academics/">Academics</a></li>'."\n";
	echo '<li><a href="/sports/">Athletics</a></li>'."\n";
	echo '<li><a href="/music/">Music</a></li>'."\n";	
	echo '<li><a href="/admissions/lifeatluther/blogs/">Student Blogs</a></li>'."\n";
	echo '<li><a href="/video/">Luther Videos</a></li>'."\n";
	echo '<li><a href="/ministries/">College Ministries</a></li>'."\n";        	
	echo '<li><a href="/diversity/">Diversity Center</a></li>'."\n";
	echo '<li><a href="/reslife/">Residence Life</a></li>'."\n";
	echo '<li><a href="/dining/">Dining Services</a></li>'."\n";
	echo '<li><a href="/counseling/">Counseling Service</a></li>'."\n";
	echo '<li><a href="/recservices/">Recreational Services</a></li>'."\n";
	echo '<li><a href="/wellness/">Wellness Program</a></li>'."\n";
	echo '<li><a href="/healthservice/">Health Service</a></li>'."\n";
	echo '<li><a href="/safety/">Safety & Security</a></li>'."\n";
	echo '<li><a href="/studentlife/activities/">Student Activities & Organizations</a></li>'."\n";
	echo '</ul></li>'."\n";
	echo '<li class="lifeAfterLuther"><a href="/admissions/lifeafterluther/">Life after Luther</a>'."\n";
	echo '<ul>'."\n";
	echo '<li><a href="/admissions/lifeafterluther/careercenter/">Career Center</a></li>'."\n";
	echo '<li><a href="/admissions/lifeafterluther/major/">Choosing a Major</a></li>'."\n";
	echo '<li><a href="/admissions/lifeafterluther/internships/">Internships</a></li>'."\n";
	echo '<li><a href="/admissions/lifeafterluther/jobs/">Jobs, Graduate School, & Volunteering</a></li>'."\n";
	echo '<li><a href="/admissions/lifeafterluther/outcomes/">Outcomes&mdash;Class of 2011</a></li>'."\n";
	echo '<li><a href="/admissions/lifeafterluther/reports/">Reports by Class</a></li>'."\n";
	echo '</ul></li>'."\n";
	echo '<li class="financialAid"><a href="/financialaid/">Financial Aid</a>'."\n";
	echo '<ul>'."\n";
	echo '<li><a href="/financialaid/applying/">How to Apply for Aid</a></li>'."\n";
	echo '<li><a href="/financialaid/prospective/scholarships/">Scholarship & Awards</a></li>'."\n";
	echo '<li><a href="/financialaid/prospective/need/">Need-Based Assistance</a></li>'."\n";
	echo '<li><a href="/financialaid/current/workstudy/">Work-Study Opportunities</a></li>'."\n";
	echo '<li><a href="/financialaid/tuition/">Tuition & Fees</a></li>'."\n";
	echo '<li><a href="/financialaid/news/">What\'s New</a></li>'."\n";
	echo '<li><a href="http://www.fafsa.gov/">Free Application for Federal Student Aid (FAFSA)</a></li>'."\n";
	echo '<li><a href="/financialaid/forms/">Forms</a></li>'."\n";
 	echo '<li><a href="/financialaid/consumer/">Consumer Information</a></li>'."\n";
	echo '<li><a href="/financialaid/faq/">FAQ</a></li>'."\n";
	echo '<li><a href="/financialaid/staff/">Staff</a></li>'."\n";
	echo '</ul></li>'."\n";
	echo '<li class="howToApply"><a href="/admissions/fastfacts/counselors">Staff</a></li>'."\n";
	echo '</ul>'."\n";

	luther_google_search();

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
	echo '<li id="photoTour"><a href="/campus/virtualtour">Virtual Tour</a></li>'."\n";
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
	echo '<div class="logo">'."\n";
	echo '<h1><a href="/">Luther College</a></h1>'."\n";
	echo '<h2><a href="/admissions/">Admissions</a></h2>'."\n";
	echo '</div>'."\n";
}    

function google_analytics()
{
	echo '<script type="text/javascript">'."\n";

  	echo 'var _gaq = _gaq || [];'."\n";
  	echo "_gaq.push(['_setAccount', 'UA-129020-8']);"."\n";
  	echo "_gaq.push(['_setDomainName', 'luther.edu']);"."\n";
  	echo "_gaq.push(['_setAllowLinker', true]);"."\n";
  	echo "_gaq.push(['_trackPageview']);"."\n";

  	echo '(function() {'."\n";
    echo "var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;"."\n";
    echo "ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';"."\n";
    echo "var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);"."\n";
  	echo '})();'."\n";

	echo '</script>'."\n";
}

function luther_social_media()
// links to luther social media pages (facebook, twitter, delicious, etc.)	
{
	echo '<!-- Luther Social Media BEGIN -->'."\n";
	echo '<div class="luther-social-media">'."\n";
	echo '<a href="/socialmedia/facebook" title="Luther Facebook pages"><img src="/images/facebook_32.png"/></a>'."\n";
	echo '<a href="/socialmedia/twitter" title="Luther Twitter feeds"><img src="/images/twitter_32.png"/></a>'."\n";
	echo '<a href="/socialmedia/flickr_photobureau" title ="Luther Flickr galleries"><img src="/images/flickr_32.png"/></a>'."\n";
	echo '<a href="/socialmedia/youtube" title="Luther YouTube videos"><img src="/images/youtube_32.png"/></a>'."\n";
	echo '<a href="/socialmedia/linkedin" title="Luther LinkedIn info"><img src="/images/linkedin_32.png"/></a>'."\n";
	echo '</div>'."\n";
	echo '<!-- Luther Social Media END -->'."\n";
}

function luther_is_mobile_device()
// returns true if browsing with mobile device, otherwise false
// see http://detectmobilebrowsers.com/ for a list of recent mobile browsers
{
	return (preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$_SERVER['HTTP_USER_AGENT'])
		|| preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',substr($_SERVER['HTTP_USER_AGENT'],0,4)));
}

function luther_shorten_string($text, $length, $append)
// shorten a string called $text to a word boundary if longer than $length.
// append a string to the end (like " ..." or "Read more...")
{
	if (strlen($text) > $length)
	{
		for ($i = $length; $text[$i] != ' '; $i--);
		$text = substr($text, 0, $i) . $append;
	}
	return $text;
}

function luther_is_local_ip()
// determine if ip address is luther college or Decorah area
// used for ReachLocal remarketing pixel on admissions site
{
	if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1'   // localhost
		// private
		|| preg_match("/^(10\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(172\.(1[6-9]|2[0-9]|3[01])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(192\.168\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Luther Campus
		|| preg_match("/^(192\.203\.196\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(198\.133\.77\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(209\.56\.59\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(74\.207\.(3[2-9]|4[0-9]|5[0-9]|6[0-3])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Decorah: Go to http://www.my-ip-address-is.com/city/Iowa/Decorah-IP-Addresses
		|| preg_match("/^(65\.116\.8[89]\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(65\.166\.58\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(66\.43\.231\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(66\.43\.252\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(67\.54\.189\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(67\.128\.219\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(69\.66\.77\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(72\.166\.100\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(75\.175\.212\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.17\.36\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.19\.[49]6\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.19\.232\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(66\.43\.252\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(199\.120\.71\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(204\.248\.125\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.243\.127\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.246\.174\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(207\.165\.178\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(207\.177\.54\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(209\.152\.65\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(216\.51\.150\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(216\.161\.207\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(216\.248\.94\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Calmar: Go to http://www.my-ip-address-is.com/city/Iowa/Calmar-IP-Addresses
		|| preg_match("/^(4\.252\.133\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(199\.201\.208\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.221\.68\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(207\.28\.22\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(207\.165\.228\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Cresco: Go to http://www.my-ip-address-is.com/city/Iowa/Cresco-IP-Addresses
		|| preg_match("/^(4\.158\.16\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(4\.158\.28\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(63\.86\.22\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(67\.224\.57\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(69\.66\.22\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(71\.7\.44\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.19\.105\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.22\.137\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(204\.248\.127\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(208\.161\.56\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Ossian: Go to http://www.my-ip-address-is.com/city/Iowa/Ossian-IP-Addresses
		|| preg_match("/^(207\.28\.13\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Waukon: Go to http://www.my-ip-address-is.com/city/Iowa/Waukon-IP-Addresses
		|| preg_match("/^(75\.167\.203\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(216\.51\.201\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// West Union: Go to http://www.my-ip-address-is.com/city/Iowa/West+Union-IP-Addresses
		|| preg_match("/^(205\.221\.67\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Harmony: Go to http://www.my-ip-address-is.com/city/Minnesota/Harmony-IP-Addresses
		|| preg_match("/^(12\.157\.197\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(204\.248\.121\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Mabel: Go to http://www.my-ip-address-is.com/city/Minnesota/Mabel-IP-Addresses
		|| preg_match("/^(204\.248\.126\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.243\.117\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Spring Grove: Go to http://www.my-ip-address-is.com/city/Minnesota/Spring+Grove-IP-Addresses
		|| preg_match("/^(204\.248\.117\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(204\.248\.124\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.243\.121\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(208\.74\.240\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR']))
	{
		return true;
	}
	return false;
}

function get_luther_publication()
// return array containing publication directives
{
	if (preg_match("/story_id\=\d+/", get_current_url()))
	{
		return array(
			'pre_banner' => '',
			'post_banner' => '',
			'main_head_4' => 'publication/luther_title',
			'main_head_5' => '',
			'main'=> array(
				'module' => 'publication/description',
				'hide_on_item' => true
			),
			'main_2' => '',
			'main_3' => '',
			'main_4' => '',
			'main_post' => 'publication',
			'main_post_2' => '', 
			'pre_sidebar_3' => '',		
			'sidebar'=> '',
			'sidebar_2' => 'luther_publication_image_sidebar',
			'sidebar_4' => '',
		);
	}
	else 
	{
		return array(
			'pre_banner' => '',
			'post_banner' => '',
			'main_head_4' => 'publication/luther_title',
			'main'=> array(
				'module' => 'publication/description',
				'hide_on_item' => true
			),
			'main_post'=>'publication',		
			'sidebar_2'=>'luther_image_sidebar',
		);
	}

}

function get_luther_publication_section_nav()
// return array containing publication directives
{
	if (preg_match("/story_id\=\d+/", get_current_url()))
	{
		return array(
			'pre_banner' => '',
			'post_banner' => '',
			'main_head_4' => 'publication/luther_title',
			'main_head_5' => '',
			'main'=> array(
				'module' => 'publication/description',
				'hide_on_item' => true
			),
			'main_2' => '',
			'main_3' => '',
			'main_4' => '',
			'main_post' => 'publication',
			'main_post_2' => '', 
			'navigation' => 'publication/sections',
			'pre_sidebar_3' => '',		
			'sidebar'=> '',
			'sidebar_2' => 'luther_publication_image_sidebar',
			'sidebar_4' => '',
		);
	}
	else 
	{
		return array(
			'pre_banner' => '',
			'post_banner' => '',
			'main_head_4' => 'publication/luther_title',
			'main'=> array(
				'module' => 'publication/description',
				'hide_on_item' => true
			),
			'main_post'=>'publication',		
			'navigation' => 'publication/sections',
			'sidebar_2'=>'luther_image_sidebar',
		);
	}

}

function get_luther_related_publication($max_num_items = 3)
// set up the related publication template for landing pages
{
	return array(
		'module' => 'publication',
		'markup_generator_info' => array(
			'list_item' => array(
				'classname' => 'MinimalListItemMarkupGenerator',
				'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
			)
		),
		//'related_publication_unique_names' => luther_get_publication_unique_name("headlines"),
		'related_mode' => true,
		'related_title' => '',
		'max_num_items' => $max_num_items,
	);
	
}

function get_luther_headlines($max_num_items)
// return array containing headlines information or '' if headlines don't exist on this minisite
{
	$headlines = luther_get_publication_unique_name("headlines");
	if (id_of($headlines, true, false))
	{
		return array(
			'module' => 'publication',
			'related_publication_unique_names' => $headlines,
			'related_mode' => 'true',
			'max_num_items' => $max_num_items
		);
	}
	else 
	{
		return '';
	}
}

function get_luther_spotlight()
// return array containing spotlight information or '' if spotlight doesn't exist on this minisite
{
	$spotlight = luther_get_publication_unique_name("spotlights");
	if (id_of($spotlight, true, false))
	{
		return array( // Spotlights
			'module' => 'publication',
			'related_publication_unique_names' => $spotlight,
			'related_mode' => 'true',
			'related_title' => '',
			'related_order' => 'random',
			'max_num_items' => 1,
			'markup_generator_info' =>array(
				'list_item' =>array (
					'classname' => 'SpotlightListItemMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php'
				),
			),
		);
	}
	else 
	{
		return '';
	}
}

function luther_get_publication_unique_name($s)
// allows another minisite to use a popular template like music, alumni, or giving
// by filling in an appropriate headline or spotlight unique publication name
// given the url for a particular minisite landing page (e.g. /music, /kwlc).
// The landing page must be at the root level of the luther site.
// $s is either "headlines" or "spotlights"
// e.g. /reslife becomes "headlines_reslife" or "spotlights_reslife"
{
	$url = get_current_url();
	if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/([A-Za-z0-9_]+)\/?/", $url, $matches))
	{
		return $s . "_" . $matches[1];
	}
	return '';
}

function luther_sports_get_publication_unique_name($s)
// fill in appropriate headline or spotlight unique publication name
// given the url for a particular sports landing page
// $s is either "headlines" or "spotlights"
// e.g. /sports/men/football becomes "headlines_football_men" or "spotlights_football_men"
{
	$url = get_current_url();
	if (preg_match("/sports\/?$/", $url))
	{
		return $s . "_sports";
	}
	else if (preg_match("/\/([A-Za-z0-9_]+)\/([A-Za-z0-9_]+)\/?$/", $url, $matches))
	{
		return $s . "_" . $matches[2] . "_" . $matches[1];
	}
}


?>