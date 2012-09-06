<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.2_to_4.3']['switch_existing_av_page_types'] = 'ReasonUpgrader_42_SwitchAVPageTypes';

class ReasonUpgrader_42_SwitchAVPageTypes implements reasonUpgraderInterface
{

	var $page_type_map = array(
		'audio_video' => 'audio_video_reverse_chronological',
		'audio_video_100x100_thumbnails' => 'audio_video_100x100_thumbnails_reverse_chronological',
		'audio_video_150x150_thumbnails' => 'audio_video_150x150_thumbnails_reverse_chronological',
		'audio_video_200x200_thumbnails' => 'audio_video_200x200_thumbnails_reverse_chronological',
		'audio_video_sidebar_blurbs' => 'audio_video_sidebar_blurbs_reverse_chronological',
		'audio_video_with_filters' => 'audio_video_with_filters_reverse_chronological',
		'audio_video_sidebar' => 'audio_video_sidebar_reverse_chronological',
		'audio_video_sidebar_show_children' => 'audio_video_sidebar_show_children_reverse_chronological',
		
		// These are local
		'voice_and_av' => 'voice_and_av_reverse_chronological',
		'carleton_news_audio_video' => 'carleton_news_audio_video_reverse_chronological',
		'audio_video_with_categories_and_search' => 'audio_video_with_categories_and_search_reverse_chronological',
		'choral_media' => 'choral_media_reverse_chronological',
		'giving_report_home_page' => 'giving_report_home_page_reverse_chronological',
		'giving_report_more_videos' => 'giving_report_more_videos_reverse_chronological',
		'carousel_video' => 'carousel_video_reverse_chronological',
		'carousel_video_above_content' => 'carousel_video_above_content_reverse_chronological',
		'carousel_video_above_content_no_swap' => 'carousel_video_above_content_no_swap_reverse_chronological',
	);
	
	

	protected $user_id;
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}
	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Switch AV Module Page Types';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		$str = "<p>Switches page types used by pages using the av module to a parallel page type to preserve the ordering of av items.</p>";
		return $str;
	}

	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{			
		$log = '';
		$pages = $this->get_pages();
		if (empty($pages))
		{
			$log .= '<p>All pages have been switched over to their new custom_page.</p>';
			return $log;
		}
		else
		{
			$log .= '<p>There are '.count($pages).' pages that need switching...</p>';
		}
		
		$new_page_types = array_values($this->page_type_map);
		
		foreach ($pages as $page)
		{
			$custom_page = $page->get_value('custom_page');
			if ( !in_array($custom_page, $new_page_types) )
			{
				$log .= '<p>Would switch '.$page->get_value('name').'\'s custom_page from '.$custom_page.' to '.$this->page_type_map[$custom_page].'.</p>';
			}
			else
			{
				$log .= '<p>Already switched '.$page->get_value('name').'\'s custom_page to '.$custom_page.'.</p>';
			}
		}
		
		return $log;
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		$log = '';
		
		$pages = $this->get_pages();
		if (empty($pages))
		{
			$log .= '<p>All pages have been switched over to their new custom_page.</p>';
			return $log;
		}
		else
		{
			$log .= '<p>About to switch '.count($pages).' pages...</p>';
		}
		
		$new_page_types = array_values($this->page_type_map);
		
		foreach ($pages as $page)
		{
			$custom_page = $page->get_value('custom_page');
			$new_page_type = $this->page_type_map[$custom_page];
			
			if ( !in_array($custom_page, $new_page_types) )
			{
				reason_update_entity($page->id(), $this->_user_id, array('custom_page' => $new_page_type));
				$log .= '<p>Switched '.$page->get_value('name').'\'s custom_page from '.$custom_page.' to '.$new_page_type.'.</p>';
			}
			else
			{
				$log .= '<p>Already switched '.$page->get_value('name').'\'s custom_page to '.$custom_page.'.</p>';
			}
		}
		
		return $log;
	}
	
	private function get_pages()
	{
		$es = new entity_selector();
		$es->add_type(id_of('minisite_page'));
		$es->add_relation('page_node.custom_page IN("'.implode('","', array_keys($this->page_type_map)).'")');
		$pages = $es->run_one();
		
		
		return $this->filter_pages($pages);
	}
	
	private function filter_pages($pages)
	{
		$filtered = array();
		foreach($pages as $page)
		{
			$es = new entity_selector();
			$es->add_type(id_of('av'));
			$es->add_right_relationship($page->id(), relationship_id_of('minisite_page_to_av'));
			if ($es->get_one_count() > 1)
			{
				$filtered[] = $page;
			}
		}
		return $filtered;
	}
	
}
?>