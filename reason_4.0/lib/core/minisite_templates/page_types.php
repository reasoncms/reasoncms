<?php
/**
 * The core Reason page types
 *
 * NOTE: This is a very fragile file.  
 * If there is a parse error in this file, all of Reason will go down.
 * So tread lightly, and fully test changes on a development/testing
 * instance before moving them into production.
 *
 * @package reason
 * @subpackage page_types
 */
 	/**
 	 * Include the Reason header
 	 */
	include_once('reason_header.php');

	/**
	 * Define the reason page types array.
	 *
	 * *Form*
	 *
	 * array(
	 *	'page_type_1'=>array('page_location_1'=>'module_1','page_location_2'=>'module_2'),
	 *  'page_type_2'=>array('page_location_1'=>'module_2','page_location_2'=>'module_1'),
	 * );
	 *
	 * *Alternate syntax for specifying parameters*
	 *
	 * array(
	 *	'page_type_1'=>array(
	 *		'page_location_1'=>array('module=>'module_1','param_key'=>'param_value'),
	 *		'page_location_2'=>'module_2'
	 *	),
	 *  'page_type_2'=>array('page_location_1'=>'module_2','page_location_2'=>'module_1'),
	 * );
	 *
	 * *Adding notes to a page type*
	 *
	 * Instead of a page location name, you can use the special keyword "_meta" to add an array of
	 * extra information to a page type. Currently "note" is implemented, and any html you provide
	 * will show up underneath the page type field in the page content manager.
	 *
	 * array(
	 *	'page_type_1'=>array(
	 *		'page_location_1'=>'module_1',
	 *		'_meta'=>array('note' => '<p>A note about this page type presented to the user</p>'),
	 *	),
	 * );
	 *
	 * *Customizations*
	 *
	 * Do not customize this array. Instead, define $GLOBALS['_reason_page_types_local']
	 * in lib/local/minisite_templates/page_types_local.php .
	 *
	 * Page types defined in that array will be automatically merged with the contents of
	 * this array; where keys exist in both arrays, Reason will use the page type defined
	 * in page_types_local.
	 */
	$GLOBALS['_reason_page_types'] = array(
		'default' => array(
			'pre_bluebar' => '',
			'main' => 'content',
			'main_head' => 'page_title',
			'main_post' => '',
			'main_post_2' => '',
			'main_post_3' => '',
			'edit_link' => 'login_link',
			'pre_banner' => 'announcements',
			'banner_xtra' => 'search',
			'post_banner' => 'navigation_top',
			'pre_sidebar' => 'assets',
			'sidebar' => 'image_sidebar',
			'post_sidebar' => '',
			'navigation' => 'navigation',
			'footer' => 'maintained',
			'sub_nav' => 'blurb',
			'sub_nav_2' => '',
			'sub_nav_3' => '',
			'post_foot' => '',
		),
		'a_to_z' => array(
			'banner_xtra' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_head' => '',
			'main_post' => 'atoz',
			'sidebar' => 'blurb',
		),	
		'image_sidebar_100x100' => array(
			'sidebar' => array(
				'module' => 'image_sidebar',
				'thumbnail_width' => 100,
				'thumbnail_height' => 100,
				)
		),	
		'image_sidebar_150x150' => array(
			'sidebar' => array(
				'module' => 'image_sidebar',
				'thumbnail_width' => 150,
				'thumbnail_height' => 150,
				)
		),	
		'image_sidebar_200x200' => array(
			'sidebar' => array(
				'module' => 'image_sidebar',
				'thumbnail_width' => 200,
				'thumbnail_height' => 200,
				)
		),
		'image_sidebar_200x' => array(
			'sidebar' => array(
				'module' => 'image_sidebar',
				'thumbnail_width' => 200,
				)
		),
		'image_sidebar_skip_first' => array(
			'sidebar' => array(
				'module' => 'image_sidebar',
				'num_to_skip' => 1,
				)
		),
		'all_related_policies' => array(
			'main_post' => 'policy_related_all',
		),
		'assets' => array(
			'pre_sidebar' => '',
			'main_post' => 'assets',
		),
		'assets_with_author_and_date' => array(
			'pre_sidebar' => '',
			'main_post' => array(
				'module'=>'assets',
				'show_fields'=> array('name','file_size','file_type','description','datetime','author'),
				'order'=>'dated.datetime DESC, chunk.author ASC',
				),
		),
		'assets_by_date' => array(
			'pre_sidebar' => '',
			'main_post' => array(
				'module' => 'assets',
				'order' => 'dated.datetime DESC',
				),
		),
		'assets_by_category' => array(
			'pre_sidebar' => '',
			'main_post' => array(
				'module'=>'assets',
				'organize_by_page_categories' => true,
				),
		),
		'assets_by_category_sorted_by_date' => array(
			'pre_sidebar' => '',
			'main_post' => array(
				'module'=>'assets',
				'organize_by_page_categories' => true,
				'order' => 'dated.datetime DESC, entity.name ASC',
				),
		),
		'assets_by_author' => array(
			'pre_sidebar' => '',
			'main_post' => array(
				'module'=>'assets',
				'order' => 'chunk.author ASC',
				),
		),
		'audio_video' => array(
			'main_post' => 'av',
		),
		'audio_video_unpaginated' => array(
			'main_post' => array(
				'module'=>'av',
				'num_per_page'=>9999,
			),
		),
		'audio_video_reverse_chronological' => array(
			'main_post' => array(
				'module'=>'av',
				'relationship_sort'=>false,
			),
		),
		'audio_video_100x100_thumbnails' => array(
			'main_post' => array(
				'module' =>'av',
				'thumbnail_width' => 100,
				'thumbnail_height' => 100,
				),
		),
		'audio_video_100x100_thumbnails_reverse_chronological' => array(
			'main_post' => array(
				'module' =>'av',
				'thumbnail_width' => 100,
				'thumbnail_height' => 100,
				'relationship_sort'=>false,
				),
		),
		'audio_video_150x150_thumbnails' => array(
			'main_post' => array(
				'module' =>'av',
				'thumbnail_width' => 150,
				'thumbnail_height' => 150,
				),
		),
		'audio_video_150x150_thumbnails_reverse_chronological' => array(
			'main_post' => array(
				'module' =>'av',
				'thumbnail_width' => 150,
				'thumbnail_height' => 150,
				'relationship_sort'=>false,
				),
		),
		'audio_video_200x200_thumbnails' => array(
			'main_post' => array(
				'module' =>'av',
				'thumbnail_width' => 200,
				'thumbnail_height' => 200,
				),
		),
		'audio_video_200x200_thumbnails_reverse_chronological' => array(
			'main_post' => array(
				'module' =>'av',
				'thumbnail_width' => 200,
				'thumbnail_height' => 200,
				'relationship_sort'=>false,
				),
		),
		'audio_video_sidebar_blurbs' => array(
			'main_post' => 'av',
			'pre_sidebar' => 'image_sidebar',
			'sidebar' => 'blurb',
			'sub_nav' => 'assets',
		),
		'audio_video_sidebar_blurbs_reverse_chronological' => array(
			'main_post' => array(
				'module'=>'av',
				'relationship_sort'=>false,
				),
			'pre_sidebar' => 'image_sidebar',
			'sidebar' => 'blurb',
			'sub_nav' => 'assets',
		),
		'audio_video_with_filters' => array(
			'main_post' => array(
				'module'=>'av_with_filters',
			),
		),
		'audio_video_with_filters_reverse_chronological' => array(
			'main_post' => array(
				'module'=>'av_with_filters',
				'relationship_sort'=>false,
			),
		),
		'audio_video_chronological' => array(
			'main_post' => array(
				'module'=>'av',
				'sort_direction'=>'ASC',
				'relationship_sort'=>false,
			),
		),
		'audio_video_on_current_site' => array(
			'main_post' => array(
				'module'=>'av',
				'limit_to_current_page'=>false,
			),
		),
		'audio_video_on_current_site_no_nav' => array(
			'main_post' => array(
				'module'=>'av',
				'limit_to_current_page'=>false,
			),
			'navigation' => '',
			'sub_nav' => '',
			'sub_nav_2' => '',
			'sub_nav_3' => '',
		),
		'audio_video_on_current_site_with_filters' => array(
			'main_post' => array(
				'module'=>'av_with_filters',
				'limit_to_current_page'=>false,
			),
		),
		'audio_video_sidebar' => array(
			'pre_sidebar' => 'image_sidebar',
			'sidebar' => 'av',
		),
		'audio_video_sidebar_reverse_chronological' => array(
			'pre_sidebar' => 'image_sidebar',
			'sidebar' => array(
				'module'=>'av',
				'relationship_sort'=>false,
				),
		),
		'audio_video_sidebar_show_children' => array(
			'pre_sidebar' => 'av',
			'sidebar' => 'assets',
			'main_post' => 'children',
		),
		'audio_video_sidebar_show_children_reverse_chronological' => array(
			'pre_sidebar' => array(
				'module'=>'av',
				'relationship_sort'=>false,
				),
			'sidebar' => 'assets',
			'main_post' => 'children',
		),
		'audio_video_offer_original_download' => array(
			'main_post' => array(
				'module' => 'av',
				'offer_original_download_link' => true,
			),
		),
		'audio_video_simple' => array(
			'main_post' => 'av_simple',
		),
		'audio_video_simple_360_wide' => array(
			'main_post' => array(
				'module' => 'av_simple',
				'width' => 360,
			),
		),
		'audio_video_simple_640_wide' => array(
			'main_post' => array(
				'module' => 'av_simple',
				'width' => 640,
			),
		),
		'audio_video_simple_860_wide' => array(
			'main_post' => array(
				'module' => 'av_simple',
				'width' => 860,
			),
		),
		'audio_video_simple_sidebar' => array(
			'sidebar' => array(
				'module' => 'av_simple',
				'width' => 240,
			),
		),
		'audio_video_media_above_description' => array(
			'main_post' => array(
				'module'=>'av',
				'show_media_first'=>true,
			),
		),
		'kaltura_import' => array(
			'main_post' => 'kaltura_import',
		),
		'kaltura_import_default_hidden' => array(
			'main_post' => array(
				'module' => 'kaltura_import',
				'default_values' => array('show_hide' => 'hide'),
			),
		),
		'basic_tabs' => array(
			'main_head' => 'basic_tabs',
		),
		'basic_tabs_parent' => array(
			'main_head' => array(
				'module'=>'basic_tabs',
				'mode'=>'parent',
			),
		),
		'publication' => array(
			'main_post'=>'publication',
			'main_head' => 'publication/title',
			'main'=>'publication/description',
			'sidebar'=>'',
			'pre_sidebar' => '',
		),
		'publication_no_dates' => array(
			'main_post'=> array('module' => 'publication',
								'use_dates_in_list' => false,
								'markup_generator_info' =>
									array('item' => array(
										'classname' => 'NoDateItemMarkupGenerator', 
										'filename' => 'minisite_templates/modules/publication/item_markup_generators/no_date.php',
										)
					 				),
							   ),
			'main_head' => 'publication/title',
			'main'=>'publication/description',
			'sidebar'=>'',
			'pre_sidebar' => '',
		),
		'publication_listnav' => array(
			'main_post'=>array(
				'module'=>'publication',
				'filter_displayer'=>'listnav.php',
			),
			'main_head' => 'publication/title',
			'main'=>'publication/description',
			'sidebar'=>'',
			'pre_sidebar' => '',
		),
		'publication_no_nav' => array(
			'main_post'=>'publication',
			'main_head' => 'publication/title',
			'main'=>'publication/description',
			'navigation' => '',
			'sidebar'=>'',
			'pre_sidebar' => '',
		),
		'publication_related' => array(
			'main_post'=>array(
				'module'=>'publication',
				'related_mode'=>'true',
			),
		),
		'publication_related_via_category' => array(
			'main_post'=>array(
				'module'=>'publication',
				'related_mode'=>'true',
				'limit_by_page_categories'=>'true',
			),
		),
		'publication_related_7_headlines' => array(
			'main_post'=>array(
				'module'=>'publication',
				'related_mode'=>'true',
				'markup_generator_info' =>
					array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 7,
			),
		),
		'publication_section_nav' => array(
			'main_post'=>'publication',
			'main_head' => 'publication/title',
			'main'=>'publication/description',
			'sidebar'=>'',
			'pre_sidebar' => '',
			'navigation'=>'publication/sections',
		),
		'publication_with_events_sidebar' => array(
			'main_head' => 'publication/title',
			'main'=>'publication/description',
			'main_post' => 'publication',
			'sidebar'=>'events_mini',
			'pre_sidebar' => '',
		),
		'publication_with_events_sidebar_and_content' => array(
			'main_head' => 'publication/title',
			'main_post' => 'publication',
			'sidebar'=>'events_mini',
			'pre_sidebar' => '',
		),
		'publication_sidebar_via_categories' => array(
			'sidebar' => array(	'module' => 'publication',
								'related_mode' => 'true',
								'related_order' => 'random',
								'limit_by_page_categories' => true,
								'max_num_items' => 3),
        ),
        'publication_with_full_images_on_listing' => array(
        	'main_post' => array('module' => 'publication',
        						 'use_filters' => false,
        						 'show_login_link' => false,
        						 'markup_generator_info' => 
        						 array('list_item' => array('classname' => 'FullImageListItemMarkupGenerator',
        						 							'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/full_image.php',
        						 	  					   ),
        							  ),
        						),
        	'main_head' => 'publication/title',
        	'main' => 'publication/description',
        	'sidebar'=>'',
        	'pre_sidebar' => '',
        ),
		'publication_with_image_sidebar' => array(
			'main_post'=>'publication',
			'main_head' => 'publication/title',
			'main'=>'publication/description',
		),	
        'publication_and_events_sidebar' => array(
			'pre_sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
			'sidebar' => array(
				'module' => 'events_mini',
			),
		),
        'events_and_publication_sidebar' => array(
			'pre_sidebar' => array(
				'module' => 'events_mini',
			),
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
		),
        'events_and_publication_sidebar_show_children' => array(
			'pre_sidebar' => array(
				'module' => 'events_mini',
			),
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
			'main_post' => 'children',
		),
        
		'blurb' => array(
			'main_post' => 'blurb',
			'sub_nav' => '',
		),
		'blurb_first_under_nav_rest_below_content' => array(
			'sub_nav' => array(
				'module'=>'blurb',
				'num_to_display' => 1,
			),
			'main_post' => 'blurb',
		),
		'blurb_first_sidebar_others_under_navigation' => array(
			'pre_sidebar' => array(
				'module' => 'blurb',
				'num_to_display' => 1,
			),
			'sub_nav' => 'blurb',
		),	
		'blurb_no_demotion_of_headings' => array(
			'main_post' => array(
				'module' => 'blurb',
				'demote_headings' => 0,
			),
			'sub_nav' => '',
		),
		'blurb_no_nav' => array(
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_post'=>'blurb',
		),
		'blurb_under_nav_and_below_content' => array(
			'main_post' => 'blurb',
		),
		'blurb_with_children' => array(
			'main' => 'blurb',
			'main_post' => 'children',
			'sub_nav' => '',
		),
		'blurb_with_siblings' => array(
			'main' => 'blurb',
			'main_post' => 'siblings',
			'sub_nav' => '',
		),
		'blurb_with_children_sidebar' => array(
			'main_post' => 'blurb',
			'sidebar' => 'children',
			'sub_nav' => '',
		),
		'blurb_with_siblings_sidebar' => array(
			'main_post' => 'blurb',
			'sidebar' => 'siblings',
			'sub_nav' => '',
		),
		'blurb_2_columns' => array(
			'main_post' => array(
				'module' => 'blurb',
				'demote_headings' => 0,
			),
			'sub_nav' => array(
				'module' => 'head_items_include',
				'css' => array(array(REASON_HTTP_BASE_PATH.'css/blurb_grids/blurb_2_columns.css')),
			),
		),
		'blurbs_with_events_and_publication_sidebar_by_page_categories' => array(
			'pre_sidebar'=> array(
				'module'=>'events_mini',
				'limit_to_page_categories'=>true,
			),
			'sidebar'=>array(
				'module'=>'publication',
				'related_mode'=>'true',
				'limit_by_page_categories'=>'true',
				'max_num_items' => 3,
			),
			'main_post'=>'blurb',
			'sub_nav'=>'',
		),
		'blurbs_with_events_sidebar_by_page_categories' => array(
			'pre_sidebar'=> array(
				'module'=>'events_mini',
				'limit_to_page_categories'=>true,
			),
			'main_post'=>'blurb',
			'sub_nav'=>'',
		),
		'child_sites' => array(
			'main_post' => 'child_sites',
		),
		'child_sites_with_top_pages' => array(
			'main_post' => 'child_sites_top_pages',
		),
		'child_sites_with_top_pages_nonav' => array(
			'main_post' => 'child_sites_top_pages',
			'banner_xtra' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'children_and_grandchildren' => array(
			'main_post' => array(
				'module' => 'children_and_grandchildren',
				'max_depth' => 2
			),
		),
		'children_and_grandchildren_no_page_title' => array(
			'main_head' => '',
			'main_post' => array(
				'module' => 'children_and_grandchildren',
				'max_depth' => 2
			),
		),
		'children_and_grandchildren_no_nav' => array(
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_post' => array(
				'module' => 'children_and_grandchildren',
				'max_depth' => 2
			),
		),
		'children_and_grandchildren_no_nav_no_page_title' => array(
			'main_head' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_post' => array(
				'module' => 'children_and_grandchildren',
				'max_depth' => 2
			),
		),
		'children_and_grandchildren_full_names' => array(
			'main_post' => array(
				'module' => 'children_and_grandchildren',
				'max_depth' => 2,
				'use_link_name' => false,
			),
		),
		'children_and_grandchildren_full_names_h3' => array(
			'main_post' => array(
				'module' => 'children_and_grandchildren',
				'max_depth' => 2,
				'use_link_name' => false,
				'depth_to_tag_map' => array(1=>'h3',2=>'h4',),
			),
		),
		'children_and_grandchildren_full_names_sidebar_blurbs_no_title' => array(
			'main_post' => array(
				'module' => 'children_and_grandchildren',
				'max_depth' => 2,
				'use_link_name' => false,
				'depth_to_tag_map' => array(1=>'h3',2=>'h4',),
			),
			'pre_sidebar' => 'image_sidebar',
			'sidebar' => 'blurb',
			'sub_nav' => 'assets',
			'main_head' => '',
		),
		'children_and_grandchildren_sidebar_blurbs' => array(
			'main_post' => array(
				'module' => 'children_and_grandchildren',
				'max_depth' => 2
			),
			'pre_sidebar' => 'image_sidebar',
			'sidebar' => 'blurb',
			'sub_nav' => 'assets',
		),
		'children_and_siblings' => array(
			'main_post' => 'children',
			'sidebar'=>'siblings',
		),
		'children_and_sidebar_blurbs' => array(
			'main_post' => 'children',
			'sidebar' => 'blurb',
			'sub_nav' => '',
		),
		'children_and_sidebar_blurbs_no_nav' => array(
			'main_post' => 'children',
			'sidebar' => 'blurb',
			'navigation' => '',
			'sub_nav' => '',
			'sub_nav_2' => '',
			'sub_nav_3' => '',
		),
		'children_before_content' => array(
			'main_post' => 'content',
			'main'=>'children',
		),
		'children_before_content_sidebar_blurbs' => array(
			'main_post' => 'content',
			'main'=>'children',
			'sidebar'=>'blurb',
			'sub_nav'=>'',
		),
		'children_no_nav' => array(
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_post'=>'children',
		),
		'children_no_nav_no_title' => array(
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_head' => '',
			'main_post'=>'children',
		),
		'department_listing' => array(
			'banner_xtra' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_head' => '',
			'main_post' => 'department_list',
			'sidebar' => 'blurb',
		),
		'event_registration' => array(
			'main_post' => 'event_registration',
			'sidebar' => '',
		),
		'event_signup' => array(
			'main_post' => 'event_signup',
			'sidebar' => '',
		),
		'event_slot_registration' => array(
			'main_post' => array(
				'module' => 'events',
				'list_item_markup' => 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php',
			),
			'sidebar' => '',
		),
		'event_slot_registration_cache_1_hour' => array(
			'main_post' => array(
				'module' => 'events',
				'list_item_markup' => 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php',
				'cache_lifespan' => '3600',
				'cache_lifespan_meta' => '7400',
			),
			'sidebar' => '',
		),
		'events' => array(
			'main_post' => 'events',
			'sidebar' => '',
		),
		'events_cache_1_hour' => array(
			'main_post' => array(
				'module' => 'events',
				'cache_lifespan' => '3600',
				'cache_lifespan_meta' => '7400',
			),
			'sidebar' => '',
		),
		'events_gallery_archive' => array(
			'main_post' => array(
				'module' => 'events_gallery',
				'template' => 'big_list_no_temporal',
				'show' => array('archived'),
				'order' => '`datetime` DESC',
			),
		),
		'events_and_images_sidebar_show_children' => array(
			'sidebar' => 'events_mini',
			'main_post' => 'children',
			'pre_sidebar' => 'image_sidebar',
		),
		'publication_related_and_events_sidebar_show_children' => array(
			'sidebar' => 'events_mini',
			'pre_sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
					array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
				),
				'max_num_items' => 4,
			),
			'main_post' => 'children',
		),
		'publication_related_and_events_sidebar_show_children_no_title' => array(
			'sidebar' => 'events_mini',
			'pre_sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
					array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
				),
				'max_num_items' => 4,
			),
			'main_post' => 'children',
			'main_head' => '',
		),
		'events_archive' => array(
			'main_post' => 'events_archive',
			'sidebar' => '',
		),
		'events_hybrid' => array(
			'main_post' => 'events_hybrid',
			'sidebar' => '',
		),
		'events_hybrid_verbose' => array(
			'main_post' => array(
				'module'=>'events_hybrid',
				'list_item_markup' => 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php',
			),
			'sidebar' => '',
		),
		'events_archive_verbose' => array(
			'main_post' => array(
				'module'=>'events_archive',
				'list_item_markup' => 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php',
			),
			'sidebar' => '',
		),
		'events_archive_nav_below' => array(
			'main' => 'events_archive',
			'main_post'=>'navigation',
			'sidebar' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'events_nonav' => array(
			'main_post' => 'events',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'sidebar' => '',
		),
		'events_sidebar' => array(
			'sidebar' => 'events_mini',
		),
		'events_sidebar_by_page_categories' => array(
			'sidebar'=> array(
				'module'=>'events_mini',
				'limit_to_page_categories'=>true,
			),
		),
		'events_and_publication_sidebar_by_page_categories' => array(
			'sidebar'=> array(
				'module'=>'events_mini',
				'limit_to_page_categories'=>true,
			),
			'post_sidebar'=>array(
				'module'=>'publication',
				'related_mode'=>'true',
				'limit_by_page_categories'=>'true',
				'max_num_items' => 3,
			),
		),
		'events_sidebar_grouped_by_category' => array(
			'sidebar' => 'events_mini_grouped_by_category',
		),
		'events_sidebar_grouped_by_page_categories' => array(
			'sidebar' => array(
				'module' => 'events_mini_grouped_by_category',
				'mode' => 'page',
			),
		),
		'events_sidebar_show_children' => array(
			'sidebar' => 'events_mini',
			'main_post' => 'children',
		),
		'events_sidebar_show_nav_children' => array(
			'sidebar' => 'events_mini',
			'main_post' => array(
				'module'=>'children',
				'show_only_pages_in_nav' => true,
			),
		),
		'events_sidebar_show_children_random_images_in_subnav' => array(
			'sidebar' => 'events_mini',
			'main_post' => 'children',
			'sub_nav' => array('module' => 'image_sidebar', 'num_to_display' => 2, 'rand_flag' => true),
		),
		'events_sidebar_more_show_children' => array(
			'sidebar' => 'events_mini_more',
			'main_post' => 'children',
		),
		'events_instancewide' => array(
			'main_post' => 'events_instancewide',
			'sidebar' => '',
		),
		'events_verbose' => array(
			'main_post' => array(
				'module' => 'events',
				'list_item_markup' => 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php',
			),
			'sidebar' => '',
		),	
		'events_schedule' => array(
			'main_post' => array(
				'module' => 'events',
				'list_markup' => 'minisite_templates/modules/events_markup/schedule/schedule_events_list.php',
				'list_item_markup' => 'minisite_templates/modules/events_markup/schedule/schedule_events_list_item.php',
			),
			'sidebar' => '',
		),	
		'events_verbose_nonav' => array(
			'main_post' => array(
				'module' => 'events',
				'list_item_markup' => 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php',
			),
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'sidebar' => '',
		),	
		'faculty' => array(
			'main_post' => 'faculty',
		),
		'faculty_and_children' => array(
			'main_post' => 'faculty',
			'sidebar' => 'children',
		),
		'faculty_first' => array(
			'main' => 'faculty',
			'main_post' => 'content',
		),
		'faculty_no_nav' => array(
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_post' => 'faculty',
			'sidebar' => 'blurb',
		),
		'faculty_with_sidebar_blurbs' => array(
			'main_post' => 'faculty',
			'sidebar' => 'blurb',
			'sub_nav' => '',
		),
		'faculty_sidebar_children' => array(
			'main_post' => 'faculty',
			'sidebar' => 'children',
		),
		'faqs' => array(
			'main_post' => 'faqs',
		),
		'faqs_ordered_by_keywords' => array(
			'main_post' => array(
				'module' => 'faqs',
				'order_field' => 'keywords',
				'order_direction' => 'ASC',
			),
		),
		'feature' => array(
			'main_post' => 'feature/feature',
		),
		'feature_shuffled' => array(
			'main_post' => array(
				'module' => 'feature/feature',
				'shuffle' => true,
				'width'=>500,
				'height'=>300
			),
		),
		'feature_av' => array(
			'main_post' => array(
				'module' => 'feature/feature',
				'shuffle' => false,
				'width'=>300,
				'height'=>200,
				'view'=>'av_view'
			),
		),		
		'feature_autoplay' => array(
			'main_post' => array(
				'module' => 'feature/feature',
				'shuffle' => false,
				'autoplay_timer' => 3,
				'width'=>500,
				'height'=>300
			),
		),
		'feature_before_content_sidebar_events_news' => array(
			'sub_nav' => array(
				'module' => 'blurb',
				'num_to_display' => 1,
			),
			'main' => array(
				'module' => 'feature/feature',
				'shuffle' => false,
				'autoplay_timer' => 3,
				'width'=>500,
				'height'=>300
			),
			'main_post' => 'content',
			'pre_sidebar'=> array(
				'module' => 'events_mini',
				'ideal_count' => 4,
			),	
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
			'post_sidebar' => 'blurb',
		),
		'feature_before_content_sidebar_events' => array(
			'sub_nav' => array(
				'module' => 'blurb',
				'num_to_display' => 1,
			),
			'main' => array(
				'module' => 'feature/feature',
				'shuffle' => false,
				'autoplay_timer' => 3,
				'width'=>500,
				'height'=>300
			),
			'main_post' => 'content',
			'pre_sidebar'=>'events_mini',
			'post_sidebar' => 'blurb',
		),
		'feature_after_content_sidebar_events' => array(
			'sub_nav' => array(
				'module' => 'blurb',
				'num_to_display' => 1,
			),
			'main_post' => array(
				'module' => 'feature/feature',
				'shuffle' => false,
				'autoplay_timer' => 3,
				'width'=>500,
				'height'=>300
			),
			'pre_sidebar'=>'events_mini',
			'post_sidebar' => 'blurb',
		),
		'feature_slow_after_content_sidebar_news_events' => array(
			'sub_nav' => 'blurb',
			'main_post' => array(
				'module' => 'feature/feature',
				'shuffle' => false,
				'autoplay_timer' => 5,
				'width'=>500,
				'height'=>300
			),	
			'pre_sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
			'sidebar'=> array(
				'module' => 'events_mini',
				'ideal_count' => 4,
			),
		),
		'feature_before_content_sidebar_blurbs_first_under_subnav' => array(
			'sub_nav' => array(
				'module' => 'blurb',
				'num_to_display' => 1,
			),
			'main' => array(
				'module' => 'feature/feature',
				'shuffle' => false,
				'autoplay_timer' => 4,
				'width'=>500,
				'height'=>300
			),
			'main_post' => 'content',
			'pre_sidebar' => 'blurb'
		),
		'feature_after_content_sidebar_blurbs_first_under_subnav' => array(
			'sub_nav' => array(
				'module' => 'blurb',
				'num_to_display' => 1,
			),
			'main_post' => array(
				'module' => 'feature/feature',
				'shuffle' => false,
				'autoplay_timer' => 4,
				'width'=>500,
				'height'=>300
			),
			'pre_sidebar' => 'blurb'
		),
		'feedback' => array(
			'main_post' => 'feedback',
		),
		'feedbackNoNavNoSearch' => array(
			'banner_xtra' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main' => 'content',
			'main_post' => 'feedback',
		),
		'form' => array(
			'main' => 'form_content',
			'main_post' => 'form'
		),
		'form_force_login' => array( // @deprecated remove in reason 4 beta 8
			'main' => 'form_content',
			'main_post' => array(
				'module'=>'form',
				'force_login'=>true,
				),
		),
		'formNoNavNoSearch' => array(
			'banner_xtra' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main' => 'form_content',
			'main_post' => 'form'
		),
		'form_sidebar_blurbs' => array(
			'main' => 'form_content',
			'main_post' => 'form',
			'sidebar' => 'blurb',
			'sub_nav' => 'assets',
			'pre_sidebar' => 'image_sidebar',
		),
		'form_sidebar_blurbs_if_logged_in' => array(
			'main' => 'form_content',
			'main_post' => 'form',
			'sidebar' => 'blurbs_if_logged_in',
			'sub_nav' => 'assets',
			'pre_sidebar' => 'image_sidebar',
		),
		'gallery' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
			),
			'sidebar' => '',
		),
		'gallery_above_assets' => array(
			'main' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
			),
			'main_post' => 'assets',
			'pre_sidebar' => '',
			'sidebar' => '',
			'sub_nav' => 'content',
		),
		'gallery_above_blurbs' => array(
			'main' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
			),
			'main_post'=>'blurb',
			'sidebar' => '',
			'sub_nav' => 'content',
		),
		'gallery_above_content' => array(
			'main' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',
			),
			'main_post'=>'content',
			'sidebar' => '',
		),
		'gallery_chronological' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'dated.datetime ASC, entity.id ASC',
			),
			'sidebar' => '',
		),
		'gallery_reverse_chronological' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'dated.datetime DESC, entity.id DESC',
			),
			'sidebar' => '',
		),
		'gallery_entire_site' => array(
			'main_post' => array(
				'module' => 'gallery2',
				'entire_site' => true,
				),
		),
		'gallery_entire_site_no_nav' => array(
			'main_post' => array(
				'module' => 'gallery2',
				'entire_site' => true,
				),
			'navigation' => '',
		),
		'gallery_entire_site_no_nav_no_title' => array(
			'main_post' => array(
				'module' => 'gallery2',
				'entire_site' => true,
				),
			'navigation' => '',
			'main_head' => '',
			'banner_xtra' => '',
		),
		'gallery_first_nav_below' => array(
			'main' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',
			),
			'main_post'=>'navigation',
			'sidebar' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'gallery_no_nav' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',
			),
			'sidebar' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'gallery_single_page' => array(
			'main_post' => array(
				'module' => 'gallery2',
				'use_pagination' => false,
				'sort_order'=>'rel',
				),
			'sidebar' => '',
		),
		'gallery_vote' => array(
			'main_post' => 'gallery_vote',
			'sidebar' => '',
		),
		'gallery_24_per_page' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
				'number_per_page' => 24,
			),
			'sidebar' => '',
		),
		'gallery_24_per_page_sidebar_blurb' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
				'number_per_page' => 24,
			),
			'sub_nav' => '',
			'sidebar' => 'blurb',
		),
		'gallery_100x100_thumbnails' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
				'thumbnail_width'=>100,
				'thumbnail_height'=>100,
			),
			'sidebar' => '',
		),
		'gallery_150x150_thumbnails' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
				'thumbnail_width'=>150,
				'thumbnail_height'=>150,
			),
			'sidebar' => '',
		),
		'gallery_200x200_thumbnails' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
				'thumbnail_width'=>200,
				'thumbnail_height'=>200,
			),
			'sidebar' => '',
		),
		'gallery_640px' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
				'width'=>640,
				'height'=>640,
				'crop' => 'fit',
			),
			'sidebar' => '',
		),
		'gallery_720px' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
				'width'=>720,
				'height'=>720,
				'crop' => 'fit',
			),
			'sidebar' => '',
		),
		'gallery_800px' => array(
			'main_post' => array(
				'module'=>'gallery2',
				'sort_order'=>'rel',//'meta.description ASC',
				'width'=>800,
				'height'=>800,
				'crop' => 'fit',
			),
			'sidebar' => '',
		),
		'GoModule' => array( 
			'sub_nav_2' => '',
			'banner_xtra' => '',
			'sidebar' => '',
			'pre_sidebar' => '',
			'navigation' => '',
			'main_post' => 'go',
		),
		'google_map' => array(
			'main_post' => 'google_map',
		),
		'images_under_nav' => array(
			'sidebar' => 'blurb',
			'sub_nav' => 'image_sidebar',
		),
		'image_left_no_nav' => array(
			'navigation' => 'image_sidebar',
			'sub_nav_2' => '',
			'sidebar' => '',
		),
		'image_slideshow' => array(
			'main_post' => 'image_slideshow',
			'sidebar' => '',
		),
		'image_slideshow_500x375' => array(
			'main_post' => array(
				'module' => 'image_slideshow',
				'width' => 500,
				'height' => 375,
				),
			'sidebar' => '',
		),
		'image_slideshow_640x480' => array(
			'main_post' => array(
				'module' => 'image_slideshow',
				'width' => 640,
				'height' => 480,
				),
			'sidebar' => '',
		),
		'image_slideshow_720x540' => array(
			'main_post' => array(
				'module' => 'image_slideshow',
				'width' => 720,
				'height' => 540,
				),
			'sidebar' => '',
		),
		'image_slideshow_800x600' => array(
			'main_post' => array(
				'module' => 'image_slideshow',
				'width' => 800,
				'height' => 600,
				),
			'sidebar' => '',
		),
		'image_slideshow_before_content' => array(
			'main' => 'image_slideshow',
			'main_post' => 'content',
			'sidebar' => '',
		),
		'image_slideshow_before_content_one_blurb_subnav_others_sidebar' => array(
			'main' => 'image_slideshow',
			'main_post' => 'content',
			'sub_nav' => array('module' => 'blurb', 'num_to_display' => 1),
			'sidebar' => 'blurb',
		),
		'image_slideshow_before_content_no_nav' => array(
			'main' => 'image_slideshow',
			'main_post' => 'content',
			'navigation' => '',
			'sub_nav_2' => '',
			'sidebar' => '',
		),
		'image_slideshow_before_content_publication_sidebar' => array(
			'main' => 'image_slideshow',
			'main_post' => 'content',
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 5,
			),
		),
		'image_slideshow_before_content_events_sidebar' => array(
			'main' => 'image_slideshow',
			'main_post' => 'content',
			'sidebar' => 'events_mini',
		),
		'image_slideshow_manual' => array(
			'main_post' => array('module'=>'image_slideshow','slideshow_type'=>'manual'),
			'sidebar' => '',
		),
		'image_slideshow_manual_500x375' => array(
			'main_post' => array(
				'module'=>'image_slideshow',
				'slideshow_type'=>'manual',
				'width' => 500,
				'height' => 375,
			),
			'sidebar' => '',
		),
		'images_full_size' => array(
			'main_post' => 'images',
			'sidebar' => '',
		),
		'images_800w' => array(
			'main_post' => array(
				'module' => 'images',
				'width' => 800,
			),
			'sidebar' => '',
		),
		'images_640w' => array(
			'main_post' => array(
				'module' => 'images',
				'width' => 640,
			),
			'sidebar' => '',
		),
		'images_640x480' => array(
			'main_post' => array(
				'module' => 'images',
				'width' => 640,
				'height' => 480,
			),
			'sidebar' => '',
		),
		'images_full_size_before_content' => array(
			'main' => 'images',
			'main_post' => 'content',
			'sidebar' => '',
		),
		'images_full_size_before_content_randomized_single' => array(
			'main' => array(
				'module'=>'images',
				'max_num'=>1,
				'sort_order' => 'RAND()',
			),
			'main_post' => 'content',
			'sidebar' => '',
		),
		'images_full_size_one_at_a_time' => array(
			'main_post' => array(
				'module'=>'images',
				'num_per_page'=> 1,
			),
			'sidebar' => '',
		),
		'images_full_size_single_page' => array(
			'main_post' => array(
				'module'=>'images',
				'num_per_page'=> 99999,
			),
			'sidebar' => '',
		),
		'jobs' => array(
			'main_post' => 'jobs',
			'sub_nav' => '',
			'sidebar'=>'blurb',
		),
		'live_sites' => array(
			'main_post' => 'live_sites',
		),
		'minutes' => array(
			'main_post' => 'minutes',
		),
		'nav_below_content' => array(
			'banner_xtra' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_post'=>'navigation',
		),
		'publication_sidebar' => array(
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
		),
		'publication_sidebar_7_headlines' => array(
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 7,
			),
		),
		'publication_sidebar_images_above' => array(
			'pre_sidebar' => 'image_sidebar',
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
		),
		'publication_sidebar_two_month_expiration' => array(
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
				'minimum_date_strtotime_format' => '-2 months',
			),
		),
		'publication_and_blurbs_sidebar' => array(
			'pre_sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
			'sidebar' => 'blurb',
			'sub_nav' => '',
		),
		'no_sub_nav' => array(
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'no_title' => array(
			'main_head' => '',
		),
		'no_nav' => array(
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'noNavNoSearch' => array(
			'banner_xtra' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'policy' => array(
			'main_post' => 'policy',
		),
		'poll' => array(
			'main_post' => array('module' => 'form', 'form_view' => 'minisite_templates/modules/poll/poll_form_view.php'),
			'main_post_2' => array('module' => 'poll/polling_graph', 'custom_colors' => array('#cb4b4b', '#edc240', '#2f90dc', '#9440ed', '#4da74d')),
		),
		'poll_sidebar' => array(
			'pre_sidebar' => array('module' => 'form', 'form_view' => 'minisite_templates/modules/poll/poll_form_view.php'),
			'sidebar' => array('module' => 'poll/polling_graph_sidebar', 'custom_colors' => array('#cb4b4b', '#edc240', '#2f90dc', '#9440ed', '#4da74d')),
		),
		'projects' => array(
			'main_post' => 'projects',
		),
		'random_blurb_and_sidebar_image' => array(
			'sub_nav' => array('module' => 'blurb', 'num_to_display' => 1, 'rand_flag' => true),
			'sidebar' => array('module' => 'image_sidebar', 'num_to_display' => 3, 'caption_flag' => false, 'rand_flag' => true)
		),
		'random_sidebar_images' => array(
			'sidebar' => array('module' => 'image_sidebar', 'num_to_display' => 3, 'caption_flag' => false, 'rand_flag' => true)
		),
		'reason_college_home' => array(
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
				'module' => 'publication',
					'show_featured_items' => true,
					'related_mode' => 'true',
					'markup_generator_info' => array(
						'list' => array(
							'classname' => 'CloakRelatedListMarkupGenerator', 
							'filename' => 'minisite_templates/modules/publication/publication_list_markup_generators/cloak_related_list.php',
						),
						'list_item' => array(
							'classname' => 'CloakMinimalWithImageListItemMarkupGenerator',
							'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/cloak_minimal_with_image.php',
						),
						'featured_item' => array(
							'classname' => 'CloakWithImageListItemMarkupGenerator', 
							'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/cloak_with_image.php',
						),
					),
				'max_num_items' => 4,
			),
			'main_post' => array(
				'module' => 'events_mini',
				'ideal_count' => 5,
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
		'related_policies' => array(
			'main_post' => 'policy_related',
		),
		'related_policies_and_children' => array(
			'main_post' => 'policy_related',
			'sidebar' => 'children',
		),
		'related_policies_and_siblings' => array(
			'main_post' => 'policy_related',
			'sidebar' => 'siblings',
		),
		'resource' => array(
			'main_post' => 'resource',
		),
		'show_children' => array(
			'main_post' => 'children',
		),
		'show_children_and_publication_sidebar' => array(
			'main_post' => 'children',
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
		),
		'show_children_hide_non_nav' => array(
			'main_post' => array(
				'module'=>'children',
				'show_only_pages_in_nav' => true,
			),
		),
		'show_children_hide_non_nav_sidebar_blurbs' => array(
			'main_post' => array(
				'module'=>'children',
				'show_only_pages_in_nav' => true,
			),
			'sidebar' => 'blurb',
			'sub_nav' => 'image_sidebar',
		),
		'show_children_images_replace_nav' => array(
			'main_post' => 'children',
			'sidebar' => '',
			'navigation' => 'image_sidebar',
		),
		'show_children_no_title' => array(
			'main_head' => '',
			'main_post' => 'children',
		),
		'show_children_no_nav' => array(
			'main_post' => 'children',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'show_children_no_nav_hide_non_nav' => array(
			'main_post' => array(
				'module'=>'children',
				'show_only_pages_in_nav' => true,
			),
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'show_children_no_nav_no_title' => array(
			'main_post' => 'children',
			'main_head' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
		),
		'show_children_with_az_list' => array(
			'main_post' => array(
				'module'=>'children',
				'provide_az_links' => true,
			),
		),
		'show_children_with_first_images' => array(
			'main_post' => array(
				'module'=>'children',
				'provide_images' => true,
			),
		),
		'show_children_with_first_images_100x100' => array(
			'main_post' => array(
				'module'=>'children',
				'provide_images' => true,
				'thumbnail_height' => 100,
				'thumbnail_width' => 100,
			),
		),
		'show_children_with_first_images_150x150' => array(
			'main_post' => array(
				'module'=>'children',
				'provide_images' => true,
				'thumbnail_height' => 150,
				'thumbnail_width' => 150,
			),
		),
		'show_children_with_first_images_200x200' => array(
			'main_post' => array(
				'module'=>'children',
				'provide_images' => true,
				'thumbnail_height' => 200,
				'thumbnail_width' => 200,
			),
		),
		'show_children_with_first_images_no_nav' => array(
			'navigation' => '',
			'main_post' => array(
				'module'=>'children',
				'provide_images' => true,
			),
		),
		'show_children_with_random_images' => array(
			'main_post' => array(
				'module'=>'children',
				'provide_images' => true,
				'randomize_images' => true,
			),
		),
		'show_children_with_random_images_100x100' => array(
			'main_post' => array(
				'module'=>'children',
				'provide_images' => true,
				'randomize_images' => true,
				'thumbnail_height' => 100,
				'thumbnail_width' => 100,
			),
		),
		'show_children_with_random_images_150x150' => array(
			'main_post' => array(
				'module'=>'children',
				'provide_images' => true,
				'randomize_images' => true,
				'thumbnail_height' => 150,
				'thumbnail_width' => 150,
			),
		),
		'show_children_with_random_images_200x200' => array(
			'main_post' => array(
				'module'=>'children',
				'provide_images' => true,
				'randomize_images' => true,
				'thumbnail_height' => 200,
				'thumbnail_width' => 200,
			),
		),
		'siblings_and_children' => array(
			'main_post' => 'siblings',
			'sidebar'=>'children',
		),
		'sidebar_children' => array(
			'sidebar' => 'children',
		),
		'show_siblings' => array(
			'main_post' => 'siblings',
		),
		'show_siblings_hide_non_nav' => array(
			'main_post' => array(
				'module'=>'siblings',
				'show_only_pages_in_nav' => true,
			),
		),
		'show_siblings_hide_external_links' => array(
			'main_post' => array(
				'module'=>'siblings',
				'show_external_links' => false,
			),
		),
		'show_siblings_with_parent_title' => array(
			'main_post' => array(
				'module' => 'siblings',
				'use_parent_title_as_header' => true,
			),
		),
		'show_siblings_with_first_images' => array(
			'main_post' => array(
				'module'=>'siblings',
				'provide_images' => true,
			),
		),
		'show_siblings_with_first_images_100x100' => array(
			'main_post' => array(
				'module'=>'siblings',
				'provide_images' => true,
				'thumbnail_height' => 100,
				'thumbnail_width' => 100,
			),
		),
		'show_siblings_with_first_images_150x150' => array(
			'main_post' => array(
				'module'=>'siblings',
				'provide_images' => true,
				'thumbnail_height' => 150,
				'thumbnail_width' => 150,
			),
		),
		'show_siblings_with_first_images_200x200' => array(
			'main_post' => array(
				'module'=>'siblings',
				'provide_images' => true,
				'thumbnail_height' => 200,
				'thumbnail_width' => 200,
			),
		),
		'show_siblings_with_random_images' => array(
			'main_post' => array(
				'module'=>'siblings',
				'provide_images' => true,
				'randomize_images' => true,
			),
		),
		'show_siblings_with_random_images_100x100' => array(
			'main_post' => array(
				'module'=>'siblings',
				'provide_images' => true,
				'randomize_images' => true,
				'thumbnail_height' => 100,
				'thumbnail_width' => 100,
			),
		),
		'show_siblings_with_random_images_150x150' => array(
			'main_post' => array(
				'module'=>'siblings',
				'provide_images' => true,
				'randomize_images' => true,
				'thumbnail_height' => 150,
				'thumbnail_width' => 150,
			),
		),
		'show_siblings_with_random_images_200x200' => array(
			'main_post' => array(
				'module'=>'siblings',
				'provide_images' => true,
				'randomize_images' => true,
				'thumbnail_height' => 200,
				'thumbnail_width' => 200,
			),
		),
		'show_siblings_previous_next' => array(
			'main_post' => array(
				'module' => 'siblings',
				'previous_next' => true,
			),
		),
		'siblings_no_nav' => array(
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_post'=>'siblings',
		),
		'siblings_and_sidebar_blurbs' => array(
			'sidebar' => 'blurb',
			'sub_nav' => '',
			'main_post'=>'siblings',
		),
		'sidebar_blurb' => array(
			'sub_nav' => '',
			'sidebar' => 'blurb',
		),
		'sidebar_blurb_no_title' => array(
			'sub_nav' => '',
			'sidebar' => 'blurb',
			'main_head' => '',
		),
		'sidebar_blurb_and_children_no_title' => array(
			'sub_nav' => '',
			'sidebar' => 'blurb',
			'main_head' => '',
			'main_post' => 'children',
		),
		'sidebar_blurb_and_children_with_images' => array(
			'sub_nav' => '',
			'pre_sidebar' => 'image_sidebar',
			'sidebar' => 'blurb',
			'main_post' => 'children',
		),
		'sidebar_blurb_with_related_publication' => array(
			'pre_sidebar' => 'blurb',
			'sidebar' => array(
				'module' => 'publication',
				'related_mode' => 'true',
				'markup_generator_info' =>
				array('list_item' => array(
									'classname' => 'MinimalListItemMarkupGenerator', 
									'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
									)
					 ),
				'max_num_items' => 4,
			),
			'sub_nav' => '',
		),
		'sidebar_images_alpha_by_keywords' => array(
			'sidebar' => array(
				'module'=>'image_sidebar',
				'order_by'=>'keywords ASC',
			),
		),
		'sidebar_images_alpha_by_name' => array(
			'sidebar' => array(
				'module'=>'image_sidebar',
				'order_by'=>'name ASC',
			),
		),
		'sidebar_images_chronological' => array(
			'sidebar' => array(
				'module'=>'image_sidebar',
				'order_by'=>'datetime ASC',
			),
		),
		'sidebar_images_reverse_chronological' => array(
			'sidebar' => array(
				'module'=>'image_sidebar',
				'order_by'=>'datetime DESC',
			),
		),
		'site_map' => array(
			'banner_xtra' => '',
			'navigation' => '',
			'sub_nav_2' => '',
			'sub_nav' => '',
			'main_head' => '',
			'main_post' => array(
				'module' => 'sitemap',
				/* You can customize headings and page types with an array like this */
				/* 'site_types_unique_names' => array(
					'Gateways'=>array('gateway_site_type'),
					'Departments and Concentrations'=>array('department_site_type',
													'concentration_site_type'),
					'Offices'=>array('office_site_type'),
					'Other Sites'=>array('other_site_type'))), */
			),
			'sidebar' => 'blurb',
		),
		'social_accounts' => array(
			'main_post' => 'social_account/social_account',
		),
		'standalone_login_page' => array(
			'main_post' => 'login',
		),
		'standalone_login_page_stripped' => array(
			'main_head' => '',
			'edit_link' => '',
			'banner_xtra' => '',
			'navigation' => '',
			'sub_nav' => '',
			'sub_nav_2' => '',
			'main_post' => 'login',
			'sidebar' => 'blurb',
		),
		'tasks' => array(
			'main_post' => 'tasks',
		),
		'one_blurb_subnav_others_sidebar' => array(
			'sub_nav' => array('module' => 'blurb', 'num_to_display' => 1),
			'sidebar' => 'blurb',
		),
		'children_with_one_blurb_subnav_others_sidebar' => array(
			'main_post' => 'children',
			'sub_nav' => array('module' => 'blurb', 'num_to_display' => 1),
			'sidebar' => 'blurb',
			'pre_sidebar' => 'image_sidebar',
		),
		'editor_demo' => array(
			'main_post'=>'editor_demo',
		),
		'publication' => array(
			'main_post'=>'publication',
			'main_head' => 'publication/title',
			'main'=>'publication/description',
			'sidebar'=>'',
			'pre_sidebar' => '',
		),
		'show_children_and_random_images' => array(
			'main_post' => 'children',
			'sidebar' => array('module' => 'image_sidebar', 'num_to_display' => 4, 'caption_flag' => true, 'rand_flag' => true),
		),
		'feed_display_full' => array(
                       'main_post' => array(
                            'module' => 'magpie/magpie_feed_display',
                            'show_entries_lacking_description'=>true,
                            ),
					   'pre_sidebar' => 'magpie/magpie_feed_search',
                       'sidebar' => 'magpie/magpie_feed_nav',
        ),
		'feed_display_sidebar' => array(
                       'sidebar' => array(
					   		'module'=>'magpie/magpie_feed_display',
							'num_per_page'=>999,
							'desc_char_limit'=>25,
                            'show_entries_lacking_description'=>true,
						),
        ),
		'feed_display_sidebar_with_search' => array(
					   'pre_sidebar' => 'magpie/magpie_feed_search',
                       'sidebar' => 'magpie/magpie_feed_display',
        ),
        'classified' => array(
        	'main_post' => array(
        	'module' => 'classified/classified',
        	'filter_displayer' => 'listnav.php'),
        ),
        'quote_sidebar_random_no_page_title' => array(
        	'main_head' => '',
        	'sub_nav' => array(
        		'module'=> 'quote',
        		'enable_javascript_refresh' => true,
        		'prefer_short_quotes' => true,
        		'num_to_display' => 1,
        		'rand_flag' => true,
        	),
        ),
        'quote_sidebar_random' => array(
        	'sub_nav' => array(
        		'module'=> 'quote',
        		'enable_javascript_refresh' => true,
        		'prefer_short_quotes' => true,
        		'num_to_display' => 1,
        		'rand_flag' => true,
        	),
        ),
        'quote_by_category' => array(
        	'main_post' => array(
        		'module' => 'quote',
        		'page_category_mode' => true,
        	)
        ),
        'quote' => array(
        	'main_post' => 'quote',
        ),
        'timeline' => array(
        	'main_post' => 'timeline',
        ),
        'user_settings' => array(
        	'main_post' => 'user_settings/user_settings'
        ),
		'course_catalog_page' => array(
			'main' => 'catalog/subject_page'
			),
		);
	
	$GLOBALS['_reason_deprecated_modules'] = array(
				'form_custom/form_custom',
				'gallery_single_page',
				'gallery_entire_site',
	);
	
	if (reason_file_exists('minisite_templates/page_types_local.php'))
	{
		reason_include_once('minisite_templates/page_types_local.php');
		if(!empty($GLOBALS['_reason_page_types_local']))
		{
			$GLOBALS['_reason_page_types'] = array_merge($GLOBALS['_reason_page_types'],$GLOBALS['_reason_page_types_local']);
		}
		if(!empty($GLOBALS['_reason_deprecated_modules_local']))
		{
			$GLOBALS['_reason_deprecated_modules'] = array_merge($GLOBALS['_reason_deprecated_modules'],$GLOBALS['_reason_deprecated_modules_local']);
		}
	}
?>
