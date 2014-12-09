<?php
reason_include_once( 'carl_util/luther_util.php' );

$GLOBALS['_reason_page_types_local'] = array(

	'default' => array(
		'global_header' => 'global/global_header',
		'global_navigation' => 'global/global_navigation',
			'pre_banner' => '',
			'banner_xtra' => '=',
			'post_banner' => '',
			'pre_main_head' => array(
				'module' => 'feature/feature',
				'width' => '1200',
				'height' => '575',
				'autoplay_timer' => 4,
				'autoplay' => true,
			),
			'post_main_head' => '',
			'main_head' => 'page_title',
			'main' => 'content',
			'main_post' => '',
			'main_post_2' => '',
			'main_post_3' => '',
			'call_to_action_blurb' => 'blurb_call_to_action',
			'pre_sidebar' => 'blurb',
			'pre_sidebar_2' => array(
				'module' => 'image_sidebar_luther', // default parameters set in alter_reason_pagetype in luther.php
				'thumbnail_width' => 600,
				'caption_flag' => true,
				//'thumbnail_height' => 400,
				//'thumbnail_crop' => 'fill',
				'num_to_display' => 3,
			),
			'sidebar' => array(
				'module' => 'luther_av',
				'full_size' => true,
				'num_per_page' => 2,
			),
			'sidebar_2' =>'assets',
			'post_sidebar' => 'twitter',
			'post_sidebar_2' => '',
			'post_sidebar_3' => '',
			'navigation' => 'navigation',
			'sub_nav' => 'social_account/social_account',
			'contact_blurb' => 'blurb_contact',
			'sub_nav_2' => '',
			'sub_nav_3' => '',
			'edit_link' => 'login_link',
			'pre_foot' => '',
			'footer' => 'maintained',
			'post_foot' => '',
		'global_footer' => 'global/global_footer',
	),

	/* Page types, in alphbetical order */

	// 'admissions_home' => array(
	// 	'banner_xtra' => '',
	// 	'post_banner' => '',
	// 	'sub_nav' => '',
	// 	'sub_nav_2' => 'admissions_sub_nav_2',
	// 	'sub_nav_3' => 'admissions_events_mini',
	// 	'main' => '',
	// 	'main_post' => array(
	// 		'module'=> 'quote',
	// 		'template' => '<blockquote><p><span class="openingQuote">&#8216;&#8216;</span>[[quote]]</p></blockquote><p class="cite">[[author]]</p>',
	// 		'prefer_short_quotes' => true,
	// 		'num_to_display' => 1,
	// 		'rand_flag' => true,
	// 	),
	// 	'pre_sidebar' => array( // Spotlights
	// 		'module' => 'publication',
	// 		'related_publication_unique_names' => array( 'spotlight_archives' ),
	// 		'related_mode' => 'true',
	// 		'related_title' => '',
	// 		'related_order' => 'random',
	// 		'max_num_items' => 1,
	// 		'markup_generator_info' => array(
	// 			'list_item' => array (
	// 				'classname' => 'SpotlightListItemMarkupGenerator',
	// 				'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/admissions_spotlight.php'
	// 			),
	// 		),
	// 	),
	// 	'sidebar' => array( // Highlights
	// 		'module' => 'publication',
	// 		'related_mode' => 'true',
	// 		'related_title' => '',
	// 		'related_order' => 'random',
	// 		'max_num_items' => 1,
	// 		'markup_generator_info' => array(
	// 			'list_item' => array (
	// 				'classname' => 'HeadlineListItemMarkupGenerator',
	// 				'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/admissions_headline.php'
	// 			),
	// 		),
	// 	),
	// 	'post_sidebar' => 'blurb',
	// ),
	'all_band' => array(
		'main_post' => 'all_band/all_band',
	),
	'assets' => array(
		'sidebar_2' => '',
		'main_post' => 'assets',
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
	'blurb_main_content' => array(
		'main_post' => array(
			'module' => 'blurb',
			'num_to_display' => '1',
			'exclude_shown_blurbs' => true,
		),
		'pre_sidebar' => array(
			'module' => 'blurb',
			'exclude_shown_blurbs' => true,
		),
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
		'main_post' => 'faculty_luther',
		'post_main_head' => array(
			'module' => 'image_sidebar',
			'num_to_display' => 1,
			'thumbnail_width' => 1200,
			'thumbnail_crop' => 'fit',
			'caption_flag' => false,
		),
		'pre_sidebar_2' => array(
			'module' => 'image_sidebar',
			'num_to_skip' => 1,
			'thumbnail_width' => 600,
			'thumbnail_height' => 400,
			'thumbnail_crop' => 'fill',
			'num_to_display' => 0,
		)
	),
	'faculty_first' => array(
		'main' => 'faculty_luther',
		'main_post' => 'content'
	),
	'flickr_slideshow' => array(
		'main_post_3' => 'luther_flickr_slideshow',
	),
	'flickr_slideshow_sidebar' => array(
		'post_sidebar_3' => 'luther_flickr_slideshow',
	),
	'gallery' => array(
		'main_post' => array(
			'module'=>'gallery2',
			'sort_order'=>'rel',
		),
		'pre_sidebar_2' => '',
	),
	'gift_page_engine' => array(
		'main_post' => 'gift_form/gift_form',
		'post_main_head' => array(
			'module' => 'image_sidebar',
			'num_to_display' => 1,
			'thumbnail_width' => 1200,
			'thumbnail_crop' => 'fit',
			'caption_flag' => false,
		),
		'pre_sidebar_2' => '',
	),
	'homecoming_registration' => array(
		'main_post' => 'homecoming_registration/homecoming_registration',
	),
	'homecoming_attendees' => array(
		'main_post' => 'homecoming_attendees',
	),
	'image_slideshow' => array(
		'main_post_2' => 'luther_image_slideshow',
		'pre_sidebar_2' => '',
	),
	'landing' => array(
		'pre_sidebar' => 'blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'sidebar' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'sidebar_2' => array(
			'module' => 'events_mini',
			'ideal_count' => 4,
		),
		'post_sidebar' => array(
			'module'=>'publication',
			'related_mode'=>'true',
			'max_num_items' => 3,
			'css' => '',
			'markup_generator_info' => array(
				'list_item' => array(
					'classname' => 'RelatedListItemNoDescriptionMarkupGenerator',
					'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item_no_description.php',
				),
			),
		),
		'post_sidebar_2' => 'luther_flickr_slideshow',
		'post_sidebar_3' => 'twitter',
	),
	'landing_blog' => array(
		'main_post' => array(
			'module'=>'publication',
			'related_mode'=>'true',
			'max_num_items' => 3,
			'css' => '',
		),
		'pre_sidebar' => 'blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'sidebar' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'sidebar_2' => array(
			'module' => 'events_mini',
			'ideal_count' => 4,
		),
		'post_sidebar' => 'luther_flickr_slideshow',
		'post_sidebar_2' => 'twitter',
	),
	'landing_children' => array(
		'main_post' => 'children',
		'pre_sidebar' => 'blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'sidebar' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'sidebar_2' => array(
			'module' => 'events_mini',
			'ideal_count' => 4,
		),
		'post_sidebar' => array(
			'module'=>'publication',
			'related_mode'=>'true',
			'max_num_items' => 3,
			'css' => '',
			'markup_generator_info' => array(
				'list_item' => array(
					'classname' => 'RelatedListItemNoDescriptionMarkupGenerator',
					'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item_no_description.php',
				),
			),
		),
		'post_sidebar_2' => 'luther_flickr_slideshow',
		'post_sidebar_3' => 'twitter',
	),
	'landing_events' => array(
		'main_post' => array(
			'module' => 'events_mini',
			'ideal_count' => 4,
		),
		'pre_sidebar' => 'blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'sidebar' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'sidebar_2' => array(
			'module'=>'publication',
			'related_mode'=>'true',
			'max_num_items' => 3,
			'css' => '',
			'markup_generator_info' => array(
				'list_item' => array(
					'classname' => 'RelatedListItemNoDescriptionMarkupGenerator',
					'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item_no_description.php',
				),
			),
		),
		//'sidbebar_2' => get_luther_related_publication(), // Do we need to use this module instead?  Will this keep spotlights out?
		'post_sidebar' => 'luther_flickr_slideshow',
		'post_sidebar_2' => 'twitter',
	),
	'landing_giving_temporary' => array( // @TODO: Remove this page type once related publications are sorted out.
		'main_post' => 'children',
		'pre_sidebar' => 'blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'sidebar' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'sidebar_2' => array(
			'module' => 'events_mini',
			'ideal_count' => 4,
		),
		'post_sidebar_2' => 'luther_flickr_slideshow',
		'post_sidebar_3' => 'twitter',
	),
	'landing_library' => array (
		'main' => 'luther_tab_widget',
		'main_post' => 'content',
	),
	'landing_news' => array(
		'pre_main_head' => '',
		'main' => '',
		'main_post' => array(
			'module'=>'publication',
			'related_mode'=>'true',
			'max_num_items' => 4,
			'related_publication_unique_names' => array(
				'headlinesarchive',
			),
			'css' => '',
			'markup_generator_info' => array(
				'list_item' => array(
					'classname' => 'RelatedListItemMarkupGenerator',
					'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item.php',
				),
			),
		),
		'main_post_2' => 'luther_flickr_slideshow',
		'pre_sidebar' => array(
			'module'=>'publication',
			'related_mode'=>'true',
			'max_num_items' => 3,
			'related_publication_unique_names' => array(
				'ideascreations',
			),
			'css' => '',
			'markup_generator_info' => array(
				'list_item' => array(
					'classname' => 'RelatedListItemNoDescriptionMarkupGenerator',
					'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item_no_description.php',
				),
			),
		),
		'pre_sidebar_2' => array(
			'module' => 'luther_av',
			'full_size' => true,
		),
		'sidebar' => array(
			'module'=>'publication',
			'related_mode'=>'true',
			'max_num_items' => 3,
			'related_publication_unique_names' => array(
				'luther_in_the_media_publication',
			),
			'css' => '',
			'markup_generator_info' => array(
				'list_item' => array(
					'classname' => 'RelatedListItemNoDescriptionMarkupGenerator',
					'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item_no_description.php',
				),
			),
		),
	),
	'landing_sports' => array(
		'main' => array(
			'module' => 'luther_sports_results_mini',
			'list_chrome_markup' => 'minisite_templates/modules/events_markup/sports/sports_events_list_chrome.php',
			'list_markup' => 'minisite_templates/modules/events_markup/sports/sports_results_list.php',
			'list_item_markup' => 'minisite_templates/modules/events_markup/sports/sports_events_list_item.php',
			'item_markup' => 'minisite_templates/modules/events_markup/sports/sports_events_item.php',
			'ideal_count' => 12,
		),
		'main_post' => array(
			'module' => 'publication',
			'related_mode' => 'true',
			'related_title' => 'Headlines',
			'max_num_items' => 5,
			'css' => '',
		),
		'pre_sidebar' => array(
			'module' => 'events_mini',
			'title' => 'Schedule',
			'calendar_link_text' => 'Complete Schedule',
			'ideal_count' => 7,
		),
		'pre_sidebar_2' => 'blurb',
		'sidebar' => array(
			'module' => 'image_sidebar', // default parameters set in alter_reason_pagetype in luther.php
			'thumbnail_width' => 600,
			'num_to_display' => 3,
		),
		'sidebar_2' => array(
			'module' => 'luther_av',
			'full_size' => true,
			'num_per_page' => 2,
		),
		'post_sidebar' => 'assets',
		'post_sidebar_2' => 'twitter',
		'post_sidebar_3' => '',

	),
	'landing_spotlight' => array(
		'pre_sidebar' => 'blurb',
		'pre_sidebar_2' => get_luther_spotlight(),
		'sidebar' => array(
				'module' => 'luther_av',
				'full_size' => true,
		),
		'sidebar_2' => array(
				'module' => 'events_mini',
				'ideal_count' => 4,
		),
		'post_sidebar' => '',
		'post_sidebar_2' => 'luther_flickr_slideshow',
		'post_sidebar_3' => 'twitter',
	),
	'luther_google_map' => array(
		'main_post' => 'luther_google_map',
	),
	'luther_homepage' => array(
		'pre_main_head' => array(
			'module' => 'feature/feature',
			'width' => '1660',
			'height' => '575',
			'autoplay_timer' => 6,
		),
		'main_head' => '',
		'main' => '',
		'pre_sidebar' => array(
			'module' => 'publication',
			'related_mode' => 'true',
			'markup_generator_info' =>
				array(
					'list_item' => array(
						'classname' => 'RelatedListItemNoDescriptionMarkupGenerator',
						'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item_no_description.php',
					),
				 ),
			'max_num_items' => 3,
			'related_title' => 'Headlines',
			'css' => '',
		),
		'sidebar' => array(
			'module' => 'events_mini',
			//'module' => 'events_upcoming_luther',
			'ideal_count' => 4,
			'title' => 'Campus Events',
			//'foot' => '<a href="/events" class="more">More events</a>',
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
		'contact_blurb' => '',
		'footer' => '',
		'edit_link' => '',
	),
	'net_price_calculator' => array(
		'main_post' => 'net_price_calculator',
	),
	'norsecard' => array(
		'main_post' => 'norsecard',
	),
	'norse_calendar' => array(
		'main_post_3' => 'norse_calendar',
	),
	'open_id' => array(
		'main_post'=>'open_id',
	),
	'outcomes_profile' => array(
		'pre_sidebar' => array(
				'module' => 'luther_av',
				'full_size' => true,
				'num_per_page' => 2,
		),
		'pre_sidebar_2' => 'blurb',
		'sidebar' => '',
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
		'main_post' => 'slate_form'
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
		'main' => array(
			'module' => 'luther_sports_results_mini',
			'list_chrome_markup' => 'minisite_templates/modules/events_markup/sports/sports_events_list_chrome.php',
			'list_markup' => 'minisite_templates/modules/events_markup/sports/sports_results_list.php',
			'list_item_markup' => 'minisite_templates/modules/events_markup/sports/sports_events_list_item.php',
			'item_markup' => 'minisite_templates/modules/events_markup/sports/sports_events_item.php',
		),
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
		'main_head' => 'publication/title',
		'main'=>'publication/description',
		'main_post' => array(
			'module' => 'publication',
			'markup_generator_info' =>array(
				'list' =>array (
					'classname' => 'SpotlightPublicationListMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/publication_list_markup_generators/spotlight.php'
				),
				'list_item' =>array (
					'classname' => 'SpotlightListItemMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight.php'
				),
			),
		),
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
	'show_children_top_image' => array(
		'main_post' => 'children',
		'post_main_head' => array(
			'module' => 'image_sidebar',
			'num_to_display' => 1,
			'thumbnail_width' => 1200,
			'thumbnail_height' => 575,
			'thumbnail_crop' => 'fit',
			'caption_flag' => false,
		),
		'pre_sidebar_2' => array(
			'module' => 'image_sidebar',
			'num_to_skip' => 1,
			'thumbnail_width' => 600,
			'thumbnail_height' => 400,
			'thumbnail_crop' => 'fill',
			'num_to_display' => 0,
		)
	),

	'tagboard_full_HeyNorse' =>array(
		'pre_main_head' => '',
		'main' => array(
			'module' => 'tagboard',
			'name_id'=>'HeyNorse/184201',
		),
		'main_head' => '',
		'call_to_action_blurb' => '',
		'pre_sidebar' => 'b',
		'pre_sidebar_2' => '',
		'sidebar' => '',
		'sidebar_2' =>'',
		'post_sidebar' => '',
		'navigation' => '',
		'sub_nav' => '',
		'contact_blurb' => '',
	),
	'tagboard_full_NorseSports' =>array(
		'pre_main_head' => '',
		'main' => array(
				'module' => 'tagboard',
				'name_id' => 'NorseSports/185650',
		),
		'main_head' => '',
		'call_to_action_blurb' => '',
		'pre_sidebar' => 'b',
		'pre_sidebar_2' => '',
		'sidebar' => '',
		'sidebar_2' =>'',
		'post_sidebar' => '',
		'navigation' => '',
		'sub_nav' => '',
		'contact_blurb' => '',
	),
	'tagboard_nav' =>array(
		'pre_main_head' => '',
		'main' => array(
				'module' => 'tagboard',
		),
		'main_head' => '',
		'call_to_action_blurb' => '',
		'pre_sidebar' => 'b',
		'pre_sidebar_2' => '',
		'sidebar' => '',
		'sidebar_2' =>'',
		'post_sidebar' => '',
	),
	'top_image' => array(
		'post_main_head' => array(
			'module' => 'image_sidebar',
			'num_to_display' => 1,
			'thumbnail_width' => 1200,
			'thumbnail_height' => 575,
			'thumbnail_crop' => 'fit',
			'caption_flag' => false,
		),
		'pre_sidebar_2' => array(
			'module' => 'image_sidebar',
			'num_to_skip' => 1,
			'thumbnail_width' => 600,
			'thumbnail_height' => 400,
			'thumbnail_crop' => 'fill',
			'num_to_display' => 0,
		)
	),
	'transcript_request' => array(
		'main_post' => 'policy_related',
		'main_post_2' => 'transcript_request/transcript_request',
	),
	'virtual_tour' => array(
		'post_main_head' => 'virtual_tour',
		'main_post_2' => 'luther_image_slideshow',
		'pre_sidebar_2' => '',
	),
	'virtual_tour_with_siblings_prev_next' => array(
		'post_main_head' => 'virtual_tour',
		'main_post_2' => 'luther_image_slideshow',
		'main_post_3' => array(
			'module' => 'siblings',
			'previous_next' => true,
		),
		'pre_sidebar_2' => '',
	),
	'volunteer_other' => array(	// basically a copy of faculty
		'main_post' => 'volunteer_other',
		'post_main_head' => array(
			'module' => 'image_sidebar',
			'num_to_display' => 1,
			'thumbnail_width' => 1200,
			'thumbnail_crop' => 'fit',
			'caption_flag' => false,
		),
		'pre_sidebar_2' => array(
			'module' => 'image_sidebar',
			'num_to_skip' => 1,
			'thumbnail_width' => 600,
			'thumbnail_height' => 400,
			'thumbnail_crop' => 'fill',
			'num_to_display' => 0,
		),
	),
	'webcams' => array(
		'main' => 'content',
		'main_post' => 'webcams',
	),
);
?>
