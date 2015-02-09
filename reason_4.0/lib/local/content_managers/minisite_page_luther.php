<?php
reason_include_once( 'content_managers/minisite_page.php3' );

$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'MinisitePageLutherManager';

class MinisitePageLutherManager extends MinisitePageManager
{	
	function alter_page_type_section()
	{
		$basic_options = array( 
			"default" => "Normal Page",
			"gallery" => 'Photo Gallery <span class="smallText">(Shows associated images in a gallery format)</span>',
			'show_children' => 'Shows children <span class="smallText">(Shows child pages in a list with their descriptions. Note: this includes pages not shown in navigation.)</span>',
			'show_siblings' => 'Shows siblings <span class="smallText">(Shows this page\'s sibling pages after the content of the page. Note: this includes pages not shown in navigation.)</span>',
		);
				
		$giving_sites_page_types = array(
			'news_via_categories'=>'Related News Sidebar',
			'news_via_categories_with_children'=>'Related News Sidebar plus children pages',
			'news_via_categories_with_siblings'=>'Related News Sidebar plus sibling pages',
			'sidebar_blurb'=>'Blurbs in right sidebar',
			'children_and_sidebar_blurbs'=>'Blurbs in right sidebar plus children pages',
			'show_children_hide_non_nav_sidebar_blurbs'=>'Blurbs in right sidebar plus children pages (but hide child pages not in navigation)',
			'siblings_and_sidebar_blurbs'=>'Blurbs in right sidebar plus sibling pages',
		);
										
		$types_to_optional_pages = array(
			'form'=>array('form'=>'Form page <span class="smallText">(A form must be associated with page for this to work)</span>',),
			'alumni_adventure_type'=>array('adventure'=>'Home page for a particular adventure <span class="smallText">(adventure needs to be associated with page)</span>',),
			'publication_type'=>array('publication'=>'Blog/Publication page <span class="smallText">(A blog/publication must be associated with page for this to work)</span>',),
			'av'=>array('audio_video'=>'Media <span class="smallText">(Shows audio and/or video after the page content. At least one media work must be associated with page for this to work)</span>',),
			'external_url'=>array('feed_display_full'=>'Full-Page feed display <span class="smallText">Provides the contents of an RSS or Atom feed as the main content of the page. An external URL must be associated with the page for this to work.</span>','feed_display_sidebar'=>'Sidebar feed display <span class="smallText">Lists the contents of an RSS or Atom feed in the sidebar. An external URL must be associated with the page for this to work.</span>'),
			'text_blurb'=>array('sidebar_blurb'=>'Sidebar blurbs <span class="smallText">(Shows blurbs in the sidebar instead of images)</span>',),
		);
		
		$sites_to_optional_pages = array(
			'giving_to_carleton_site'=>$giving_sites_page_types,
			'planned_giving_site'=>$giving_sites_page_types,
			'faculty_workload_discussion_site'=>array('blurb_under_nav_and_below_content'=>'Blurbs placed both under navigation and below content of page',),
			'gould_library_site'=>array('children_and_grandchildren'=>'Children and Grandchildren',),
			'language_center_site'=>array('blurb'=>'Blurbs below the content',),
			'digital_commons_site'=>array('assets_with_author_and_date'=>'Assets below the content',),
			'institutional_research'=>array('basic_tabs'=>'Tabbed Page','basic_tabs_parent'=>'Parent of Tabbed Page',),
			'learning_and_teaching_center_site_new'=>array('audio_video_sidebar_blurbs'=>'Term Archive Page <span class="smallText">(Video in main area, blurb of poster/workshops/book groups/etc. in right sidebar)</span>'),
			'sustainability_2011_site'=>array(
				'sustainability_shows_children'=>'Shows Children, 50x50 thumbnail <span class="smallText">(Shows children pages with small thumbnail, and hides pages that are not displayed in the navigation.)</span>'
				),
			'trustees_site'=>array(
				'assets' => 'Assets',
				'assets_by_category' => 'Assets by Category',
				'assets_by_category_with_merge' => 'Assets by Category (with merged PDFs)',
				'assets_by_date' => 'Assets by Date'
			),
			'music_class_resources_site'=>array(
				'audio_video_unpaginated'=>'Media Unpaginated <span class="smallText">(Shows all attached media in a single list)</span>',
				'audio_video_with_filters'=>'Media with Search Box <span class="smallText">(Provides an interface for searching among attached media)</span>',
			),
			'theater_studies_site'=>array(
				'show_children_with_first_images_150x150'=>'Shows children with first images <span class="smallText">(Like shows children, but shows first attached image sized at 150 x 150px)</span>',
			),
			'aisling_quigley_test_site'=>array( // This is actually the Pathways site
				'pathways_profile'=>'Pathways Profile <span class="smallText">(Attached image above blurbs in sidebar)</span>',
				'sidebar_blurb_and_children_with_images' => 'Sidebar Blurb and Children With Images',
			),
		);
		
		$users_to_optional_pages = array(
			'dpape' => 
				array( 'image_slideshow' => 'Automatic Slideshow',
					   'audio_video' => 'Audio / Video',
					   'audio_video_sidebar' => 'Audio / Video Sidebar',
					   'carousel_video' => 'Carousel Video'),
		  	'jnelson' =>
				array( 'assets' => 'Assets',
					   'assets_by_category' => 'Assets by Category',
					   'assets_by_category_with_merge' => 'Assets by Category (with merged PDFs)',
					   'assets_by_date' => 'Assets by Date'),
		  	'tvick' =>
				array( 'show_children_with_first_images' => 'Show Children with First Images',
					   'show_children_with_random_images' => 'Show Children with Random Images',
					   'image_slideshow' => 'Automatic Slideshow',
					   'sidebar_children' => 'Sidebar Children'),
			'ehaberot' =>
				array( 'show_children_with_first_images' => 'Show Children with First Images',
					   'show_children_with_random_images' => 'Show Children with Random Images',
					   'image_slideshow' => 'Automatic Slideshow',
					   'sidebar_children' => 'Sidebar Children'),
			'scarpent' =>
				array( 'blurb_first_sidebar_others_under_navigation' => 'First Blurb Sidebar, Others Under Navigation'),
			'sherrick' => 
				array( 'bio_book' => 'Bio Book',
					   'bio_book_unique_new_photo' => 'Bio Book Unique New Photo <span class="smallText">(Uploaded new photos not shared with other bio books)</span>'),
			'cgardner' => 
				array( 'bio_book' => 'Bio Book',
					   'bio_book_unique_new_photo' => 'Bio Book Unique New Photo <span class="smallText">(Uploaded new photos not shared with other bio books)</span>'),
		);
										
		if(!empty($types_to_optional_pages))
		{
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_right_relationship( $this->get_value('site_id'), relationship_id_of('site_to_type') );
			$es->add_relation( 'entity.unique_name IN ("'.implode('","',array_keys($types_to_optional_pages)).'")' );
			$types = $es->run_one();
			
			foreach($types as $type)
			{
				if(!empty($types_to_optional_pages[$type->get_value('unique_name')]))
				{
					foreach($types_to_optional_pages[$type->get_value('unique_name')] as $page_type=>$desc)
					{
						$basic_options[$page_type] = $desc;
					}
				}
			}
		}
		
		$site = new entity($this->get_value('site_id'));
		$site_unique_name = $site->get_value('unique_name');
		if(!empty($sites_to_optional_pages[$site_unique_name]))
		{
			foreach($sites_to_optional_pages[$site_unique_name] as $page_type=>$desc)
			{
				$basic_options[$page_type] = $desc;
			}
		}
		
		if (!empty($users_to_optional_pages))
		{
			$user = new entity($this->admin_page->user_id);
			if ($user->get_values() && isset($users_to_optional_pages[$user->get_value('name')]))
			{
				foreach ($users_to_optional_pages[$user->get_value('name')] as $page_type=>$desc)
				{
					$basic_options[$page_type] = $desc;
				}
			}
		}
		
		if ( !$this->get_value('custom_page') )
		{
			$this->set_value( 'custom_page', 'default' ); // set as default if no value
		}
		
		if ( array_key_exists($this->get_value('custom_page'),$basic_options ) || reason_user_has_privs( $this->admin_page->user_id, 'assign_any_page_type') )
		{
			$this->change_element_type( 'custom_page' , 'radio_no_sort' , array( 'options' => $basic_options ) );
		}
		else
		{
			$this->change_element_type( 'custom_page', 'solidtext' );
		}	
	}
}
?>