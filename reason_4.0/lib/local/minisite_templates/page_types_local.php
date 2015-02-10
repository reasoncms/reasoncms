<?php

$GLOBALS['_reason_page_types_local'] = array(
	'cloak_home' => array(
		'pre_bluebar' => '',
		'main_head' => 'page_title',
		'post_banner' => array(
			'module' => 'feature/feature',
			'shuffle' => false,
			'autoplay_timer' => 3,
			'width' => 1680,
			'height' => 651,
		),
		'main' => array(
			'module' => 'events_mini',
			'ideal_count' => 5,
		),
		'main_post' => array(
			'module' => 'publication',
			'related_mode' => 'true',
			'related_title' => 'Muush',
			'show_featured_items' => true,
			// 'related_markup_generator_info' =>
			// 	array('list_item' => array(
			// 		'classname' => 'ListItemNoImageMarkupGenerator', 
			// 		'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/no_image.php',
			// 		),
			//  	),
			'max_num_items' => 4,
		),
		'main_post_2' => '',
		'pre_sidebar' => array(
			'module' => 'blurb',
			'num_to_display' => 4,
		),
		'main_post_3' => '',
		'edit_link' => 'login_link',
		'pre_banner' => 'announcements',
		'banner_xtra' => 'search',
		//'post_banner' => 'navigation_top',
		'sidebar' => array(
			'module' => 'blurb',
			'num_to_display' => 1,
		),
		'post_sidebar' => 'content',
		'navigation' => '',
		'footer' => 'maintained',
		'sub_nav' => '',
		'sub_nav_2' => '',
		'sub_nav_3' => '',
		'post_foot' => '',
	),
);
?>
