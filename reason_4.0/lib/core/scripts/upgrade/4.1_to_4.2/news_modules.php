<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.1_to_4.2']['news_modules'] = 'ReasonUpgrader_41_NewsModules';
include_once('reason_header.php');
reason_include_once('classes/page_types.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

/**
 * @todo this is pretty slow - might be best to have it be an actual upgrade script that just provides info.
 */
class ReasonUpgrader_41_NewsModules implements reasonUpgraderInterface 
{
	/**
	 * This includes all the core and local news based modules at the time of writing ... the Carleton ones are in the
	 * list because having a few extra doesn't hurt anything.
	 */
	protected $old_school_news_modules = array(
		'news_all',
		'news_by_category',
		'news_mini',
		'news_one_at_a_time',
		'news_parent',
		'news_proofing_multipage',
		'news_proofing',
		'news_rand.php',
		'news_via_categories',
		'news',
		'news2_mini_random',
		'news2_mini',
		'news2',
		'news_currently',
		'news_doc',
		'news_insideCarleton',
		'news_rand',
		'news_rand_aaf',
		'news_top',
		'athletics/recruit_center_profile');     
    
    /**
     * These are page types that used to be but are no longer in page_types.php - pages that used these are likely broken.
     */
    protected $deleted_page_type_names_to_check_for = array(
    	'blurbs_with_events_and_news_sidebar_by_page_categories',
    	'children_and_grandchildren_full_names_sidebar_blurbs_no_title_random_news_subnav',
    	'events_and_news_sidebar_by_page_categories',
    	'events_and_news_sidebar_show_children',
    	'news_and_events_sidebar_show_children',
    	'news_and_events_sidebar_show_children_no_title',
    	'events_and_news_sidebar',
    	'events_and_news_sidebar_weekly',
    	'news',
    	'news_all',
    	'news_by_category',
    	'news_mini',
    	'news_NoNav_sidebarBlurb',
    	'news_one_at_a_time',
    	'news_proofing_multipage',
    	'news_sidebar',
    	'news_random',
    	'news_proofing',
    	'news_via_categories',
    	'news_via_categories_with_children',
    	'news_via_categories_with_siblings',
    	'news_with_sidebar_blurbs',
    	'newsNoNavigation',
    	'newsNoNavigation_footer_blurb',
    	'noNavNoSearch_news_sidebar',
    	'random_news_sub_nav',
    	'show_children_and_news_sidebar',
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
		return 'Check Page Types and Pages for References to Deprecated News Modules';
	}
	
	/**
     * Get a description of what this upgrade script will do
     * @return string HTML description
     */
	public function description()
	{
		$str = "<p>The old news modules in Reason were deprecated while Reason was still in beta. As of Reason 4.2 the page types and modules have been removed from the core. ";
		$str .= "This upgrade script assesses whether or not you have page types (and pages) referencing deprecated news modules so that you can more easily fix them if needed.</p>";
		return $str;
	}
	
	protected function get_page_types_using_deprecated_news_modules()
	{
		static $retrieved;
		if (!isset($retrieved))
		{
			$rpts =& get_reason_page_types();
			$page_type_names = array();
			foreach ($this->old_school_news_modules as $module_name)
			{	
				if ($page_types = $rpts->get_page_type_names_that_use_module($module_name))
				{
					$page_type_names = array_merge($page_type_names, $page_types);
				}
			}
			if (isset($page_type_names))
			{
				// lets find pages using the page type if they exist and link to them.
				foreach ($page_type_names as $page_type_name)
				{
					$retrieved[$page_type_name] = array();
					
					$es = new entity_selector();
					$es->limit_tables('page_node');
					$es->limit_fields('custom_page');
					$es->add_type(id_of('minisite_page'));
					$es->add_relation('page_node.custom_page = "'.$page_type_name.'"');
					$pages_using_news_modules = $es->run_one();
					
					if (!empty($pages_using_news_modules))
					{
						foreach ($pages_using_news_modules as $k => $page_entity)
						{
							$retrieved[$page_type_name][$k] = $page_entity;
						}
					}
				}
			}
		}
		return (!empty($retrieved)) ? $retrieved : false;
	}
	
	/**
	 * We want to find pages where custom_page is in our array $deleted_page_type_names_to_check_for.
	 *
	 */
	protected function get_pages_using_now_missing_page_types()
	{
		static $retrieved;
		if (!isset($retrieved))
		{
			$es = new entity_selector();
			$es->limit_tables('page_node');
			$es->limit_fields('custom_page');
			$es->add_type(id_of('minisite_page'));
			$es->add_relation('page_node.custom_page IN ("'.implode('","', $this->deleted_page_type_names_to_check_for).'")');
			$retrieved = $es->run_one();
		}
		return $retrieved;
	}
	
	public function test()
	{
		return '<p>Would report on page types that reference and any pages that use deprecated news modules.</p>';
	}
	
    /**
     * Return information about new settings
     * @return string HTML report
     */
	public function run()
	{
		$str = '';
		if ($deprecated = $this->get_page_types_using_deprecated_news_modules())
		{
			$str .= '<h4>Page Types That Should Be Changed or Deleted</h4>';
			$str .= '<p>The following pages types (and reason pages) look to be using deleted news modules and should be updated or removed.</p>';
			$str .= '<ul>';
			foreach ($deprecated as $page_type_name => $pages)
			{
				$str .= '<li>'.$page_type_name;
				if (!empty($pages))
				{	
					$str .= '<ul>';
					foreach ($pages as $id => $page)
					{
						$url = @reason_get_page_url($id);
						$str .= '<li>';
						if ($url) 
						{
							$str .= '<a href="'.$url.'">' . $page->get_value('name') . '</a> (Page ID ' . $id . ')';
						}
						else
						{
							$str .= $page->get_value('name') . ' (Page ID ' . $id . ' - URL not available)';
						}
						$str .= '</li>';
					}
					$str .= '</ul>';
				}
				$str .= '</li>';
			}
			$str .= '</ul>';	
		}
		
		if ($pages_using_missing_page_types = $this->get_pages_using_now_missing_page_types())
		{
			$str .= '<h3>Pages Using Deleted News Page Types</h3>';
			$str .= '<ul>';
			foreach ($pages_using_missing_page_types as $id => $page)
			{
				$url = @reason_get_page_url($id);
				$name = $page->get_value('name');
				$str .= '<li><a href="'.$url.'">'.$name.'</a></li>';
			}
			$str .= '</ul>';
		}
		return $str;
	}
}
?>