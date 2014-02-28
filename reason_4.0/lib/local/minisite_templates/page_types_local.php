<?php

$GLOBALS['_reason_page_types_local'] = array(
	/* Core overrides */
	
	'default' => array(
		'global_header' => 'global/global_header',
		'global_navigation' => 'global/global_navigation',
			'pre_banner' => '',
			'banner_xtra' => '',
			'post_banner' => '',
			'pre_main_head' => array(
				'module' => 'feature/feature',
				'width' => '1134',
				'height' => '572',
			),
			'letterboard_image' => array(
				'module' => 'image_sidebar',
				'num_to_display' => 1,
				'thumbnail_width' => 1200,
				'thumbnail_height' => 400,
				'thumbnail_crop' => 'fill',
			),
			'main_head' => 'page_title',
			'main' => 'content',
			'main_post' => '',
			'main_post_2' => '',
			'main_post_3' => '',
			'pre_sidebar' => 'assets',
			/*'sidebar' => array(
				'module' => 'image_sidebar',
				'num_to_display' => 2,
				'thumbnail_width' => 600,
				'thumbnail_height' => 400,
				'thumbnail_crop' => 'fill',
			),*/
			/*''sidebar' => 'image_sidebar',*/
			'post_sidebar' => 'blurb',
			'navigation' => 'navigation',
			'sub_nav' => 'blurb',
			'sub_nav_2' => '',
			'sub_nav_3' => '',
			'edit_link' => 'login_link',
			'footer' => '',
			'post_foot' => '',
		'global_footer' => 'global/global_footer',
	),
	
	//'publication' => array(
		//'post_banner' => 'publication/title',
	//),
	
	'show_children' => array(
		'main_post' => array(
			'module'=>'children',
			'description_part_of_link' => true,
			'provide_images' => true,
			'randomize_images' => true,
			'thumbnail_height' => 200,
			'thumbnail_width' => 200,
			'html5' => true,
		),
	),
	
	/* Local page types */
	
	'luther_home_page_2014' => array (
		//'pre_sidebar' => 'global/social_media_box',
		//'sidebar' => 'global/callout',
		'post_sidebar' => '',
		'navigation' => '',
		'sub_nav' => '',
	),
	
);
?>
