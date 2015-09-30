<?php
/**
 * @package reason
 * @subpackage scripts
 */

/**
 * Include the entire publication migrator family of utilities
 */
reason_include_once('classes/head_items.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/page_types.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('scripts/developer_tools/publication_migrator/migrator_screen.php');
reason_include_once('scripts/developer_tools/publication_migrator/migrator_screen_1.php');
reason_include_once('scripts/developer_tools/publication_migrator/migrator_screen_2.php');
reason_include_once('scripts/developer_tools/publication_migrator/migrator_screen_3.php');
reason_include_once('scripts/developer_tools/publication_migrator/migrator_screen_4.php');
reason_include_once('scripts/developer_tools/publication_migrator/migrator_screen_5.php');
reason_include_once('scripts/developer_tools/publication_migrator/migrator_screen_6.php');
reason_include_once('scripts/developer_tools/publication_migrator/migrator_screen_7.php');
include_once(CARL_UTIL_INC.'basic/misc.php');

/**
 * The publication migrator helps transition sites using old style news to use
 * the publications module.
 *
 * It basically works as follows:
 * 
 * - Identify sites using page types from the old publications framework
 * - Screen 1: Allow selection of a site to "migrate."
 * - Screen 2: Allow association of news items, issues, and sections to an existing or new publication.
 * - Screen 3: Map known page types from old page type to new page type and relate publication.
 *
 * Not all page types can be known, and some sites will require some manual work to migrate.
 * Even for sites that require manual work, it may be worthwhile to extend this tool to handle them...
 *
 * @author Nathan White
 */
class PublicationMigratorHelper
{
	var $cleanup_rules = array('active_screen' => array('function' => 'check_against_array', 'extra_args' => array("1","2","3","4","5","6","7")),
							   'site_id' => array('function' => 'turn_into_int'));
	
	/**
	 * If defined, maps numeric keys to different MigratorScreen class name than the default MigratorScreen#
	 * Make sure your custom migrator screen class is included before the helper tries to instantiate it.
	 */
	var $custom_migrator_screen;
	
	/**
	 * Old style news modules
	 * @var array
	 */
	var $news_modules = array('news', 'news_mini', 'news_via_categories', 'news_by_category', 'news_rand', 'news_all',
	                          'news_one_at_a_time', 'news_proofing', 'news_proofing_multipage', 
	                          'news2', 'news2_mini', 'news2_mini_random');
	
	/**
	 * Custom news modules for your instance
	 */
	var $custom_news_modules;
	
	var $publication_modules = array('publication');
	
	/**
	 * Custom publication modules for your instance
	 * These would be items that extend the publcations module (hopefully there are not many, if any, of these)
	 */
	var $custom_publication_modules;
		
	/**
	 * Defines known suggested page type mappings from old style news to publication module
	 * @var array
	 */
	var $recommended_page_type_mapping = array('news' => 'publication', 
											   'events_and_news_sidebar' => 'events_and_publication_sidebar',
											   'show_children_and_news_sidebar' => 'show_children_and_publication_sidebar',
											   'events_and_news_sidebar_show_children_nagios_status' => 'events_and_publication_sidebar_show_children_nagios_status',
											   'news_sidebar' => 'publication_sidebar',
											   'children_and_grandchildren_full_names_sidebar_blurbs_no_title_random_news_subnav' => 'international_students_information_front_page',
											   'news_proofing' => 'publication',
											   'news_proofing_multipage' => 'publication', // news_proofing pages should be deleted
											   'news_and_events_sidebar_show_children' => 'publication_related_and_events_sidebar_show_children',
											   'news_and_events_sidebar_show_children_no_title' => 'publication_related_and_events_sidebar_show_children_no_title'); 
	
	/**
	 * Custom recommended page type mappings specific to your instance
	 */
	var $custom_recommended_page_type_mapping;
	
	/**
	 * If an old news page type corresponds to an item in this array, the comments will be displayed on the page that allows the page type
	 * to be changed. This is useful to create notes about obsolete page types, page types that will require more work, etc.
	 */
	var $page_type_comments = array('news_proofing' => 'This page type is obsolete - the publications module will show hidden issues for logged in site users. 
														You should probably delete this page in the Reason administrative interface.',
									'news_proofing_multipage' => 'This page type is obsolete - the publications module will show hidden issues for logged in 
																  site users. You should probably delete this page in the Reason administrative interface.');
	
	var $custom_page_type_comments;
	
	/**
	 * Grab request variables - merge custom page types and modules into the instance arrays.
	 */
	function init()
	{
		$this->request = carl_clean_vars(carl_get_request(), $this->cleanup_rules);
		if (!empty($this->custom_recommended_page_type_mapping))
		{
			$this->recommended_page_type_mapping = array_merge($this->recommended_page_type_mapping, $this->custom_recommended_page_type_mapping);
		}
		if (!empty($this->custom_news_modules))
		{
			$this->news_modules = array_merge($this->news_modules, $this->custom_news_modules);
		}
		if (!empty($this->custom_publication_modules))
		{
			$this->publication_modules = array_merge($this->publication_modules, $this->custom_publication_modules);
		}
		if (!empty($this->custom_page_type_comments))
		{
			$this->page_type_comments = array_merge($this->page_type_comments, $this->custom_page_type_comments);
		}
	}
	
	function run()
	{
		$html = $this->get_head_markup();
		if ($this->authenticate())
		{
			$form =& $this->get_form();
			ob_start();
			$form->run();
			$html .= ob_get_contents();
			ob_end_clean();
		}
		else
		{
			$html .= $this->get_unauthorized_markup();
		}
		$html .= $this->get_foot_markup();
		echo $html;
	}
	
	function get_site_id()
	{
		return (isset($this->request['site_id'])) ? $this->request['site_id'] : 0;
	}
	
	function get_user_id()
	{
		static $user_id;
		if (!isset($user_id))
		{
			$user_netid = reason_require_authentication();
			$user_id = get_user_id($user_netid);
		}
		return $user_id;
	}
	
	function get_site_name()
	{
		static $site_name;
		if (!isset($site_name))
		{
			$site_id = $this->get_site_id();
			if ($site_id)
			{
				$site = new entity($site_id);
				$site_name = $site->get_value('name');
			}
			else $site_name = '';
		}
		return $site_name;
	}
	
	function get_head_markup()
	{
		$head_items = new HeadItems();
		// add needed head items
		$head_items->add_head_item('title',array(),'Publication Migration Wizard',true);
		$head_items->add_stylesheet('//' . REASON_HOST . REASON_HTTP_BASE_PATH . 'css/publication_migrator/publication_migrator.css');
		$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
		$html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
		$html .= '<head>'."\n";
		$html .= $head_items->get_head_item_markup();
		$html .= '</head>'."\n";
		$html .= '<body>'."\n";
		return $html;
	}
	
	function get_foot_markup()
	{
		return '</body></html>';
	}
	
	function get_unauthorized_markup()
	{
		return '<h3>Unauthorized</h3><p>You must have Reason upgrade privileges to use this tool.</p>';
	}
	
	function does_site_have_issue_type()
	{
		$site_id = $this->get_site_id();
		$es = new entity_selector();
		$es->add_type(id_of('type'));
		$es->add_right_relationship($this->get_site_id(),relationship_id_of('site_to_type'));
		$es->add_relation('entity.id = "'.id_of('issue_type').'"');
		$es->set_num(1);
		$type = $es->run_one();
		return ($type);
	}
	
	function does_site_have_section_type()
	{
		$site_id = $this->get_site_id();
		$es = new entity_selector();
		$es->add_type(id_of('type'));
		$es->add_right_relationship($this->get_site_id(),relationship_id_of('site_to_type'));
		$es->add_relation('entity.id = "'.id_of('news_section_type').'"');
		$es->set_num(1);
		$type = $es->run_one();
		return ($type);	
	}
	
	function &get_page_type_comments()
	{
		return $this->page_type_comments;
	}
	
	function get_allowable_relationship_for_page_type($page_type)
	{
		$rpt =& get_reason_page_types();
		$pt = $rpt->get_page_type($page_type);
		if ($pt)
		{
			$regions = $pt->get_region_names();
			foreach ($regions as $region)
			{
				$region_info = $pt->get_region($region);
				if (isset($region_info['module_params']['related_mode']) && $region_info['module_params']['related_mode'] == true)
				{
					return 'page_to_related_publication';
				}
			}
			return 'page_to_publication';
		}
	}
	
	/**
	 * @return array site entities that appear to need migration
	 *
	 * @todo be more discriminating currently lists all sites with news items
	 */
	function &get_sites_that_need_migration()
	{	
		static $sites;
		if (!isset($sites))
		{
			$es = new entity_selector();
			$es->limit_tables();
			$es->limit_fields('entity.name');
			$es->add_type(id_of('site'));
			$es->add_left_relationship(id_of('news'), relationship_id_of('site_to_type'));
			$es->set_order('entity.name ASC');
			$sites = $es->run_one();
		}
		return $sites;
	}

	function get_site_names_by_id()
	{
		$sites = $this->get_sites_that_need_migration();
		if (!empty($sites))
		{
			foreach ($sites as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function &get_site_publications()
	{
		static $publications;
		$site_id = $this->get_site_id();
		if (!isset($publications))
		{
			$es = new entity_selector($site_id);
			$es->limit_tables();
			$es->limit_fields('entity.name');
			$es->add_type(id_of('publication_type'));
			$es->set_order('entity.name ASC');
			$publications = $es->run_one();
		}
		return $publications;
	}
	
	function get_site_publication_names_by_id()
	{
		$publications =& $this->get_site_publications();
		if (!empty($publications))
		{
			foreach ($publications as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function &get_unattached_news_items()
	{
		static $unattached_news_items;
		$site_id = $this->get_site_id();
		if (!isset($unattached_news_items))
		{
			$attached_news_items =& $this->get_attached_news_items();
			$es2 = new entity_selector($site_id);
			$es2->limit_tables(array('entity', 'press_release', 'status'));
			$es2->limit_fields(array('release_title'));
			$es2->add_type(id_of('news'));
			$es2->add_relation('status.status = "published"');
			if ($attached_news_items)
			{
				$es2->add_relation('entity.id NOT IN ('.implode(",",array_keys($attached_news_items)).')');
			}
			$unattached_news_items = $es2->run_one();
		}
		return $unattached_news_items;
	}
	
	function &get_attached_news_items()
	{
		static $attached_news_items;
		$site_id = $this->get_site_id();
		if (!isset($attached_news_items))
		{
			if ($site_pubs =& $this->get_site_publications())
			{
				$es = new entity_selector($site_id);
				$es->limit_tables();
				$es->limit_fields();
				$es->add_type(id_of('news'));
				$es->add_left_relationship_field('news_to_publication', 'entity', 'id', 'pub_id', array_keys($site_pubs));
				$attached_news_items = $es->run_one();
			}
			else $attached_news_items = false;
		}
		return $attached_news_items;
	}
	
	function get_unattached_news_item_names_by_id()
	{
		$unattached_news_items =& $this->get_unattached_news_items();
		if (!empty($unattached_news_items))
		{
			foreach ($unattached_news_items as $k=>$v)
			{
				$result[$k] = $v->get_value('release_title');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function get_attached_news_item_names_by_id()
	{
		$attached_news_items =& $this->get_attached_news_items();
		if (!empty($attached_news_items))
		{
			foreach ($attached_news_items as $k=>$v)
			{
				$result[$k] = $v->get_value('release_title');
			}
		}
		return (isset($result)) ? $result : '';
	}

	function &get_unattached_sections()
	{
		static $unattached_sections;
		$site_id = $this->get_site_id();
		if (!isset($unattached_sections))
		{
			$attached_sections =& $this->get_attached_sections();
			$es2 = new entity_selector($site_id);
			$es2->limit_tables();
			$es2->limit_fields();
			$es2->add_type(id_of('news_section_type'));
			if ($attached_sections)
			{
				$es2->add_relation('entity.id NOT IN ('.implode(",",array_keys($attached_sections)).')');
			}
			$unattached_sections = $es2->run_one();
		}
		return $unattached_sections;
	}
	
	function &get_attached_sections()
	{
		static $attached_sections;
		$site_id = $this->get_site_id();
		if (!isset($attached_sections))
		{
			if ($site_pubs =& $this->get_site_publications())
			{
				$es = new entity_selector($site_id);
				$es->limit_tables();
				$es->limit_fields();
				$es->add_type(id_of('news_section_type'));
				$es->add_left_relationship_field('news_section_to_publication', 'entity', 'id', 'pub_id', array_keys($site_pubs));
				$attached_sections = $es->run_one();
			}
			else $attached_sections = false;
		}
		return $attached_sections;
	}
	
	function get_unattached_section_names_by_id()
	{
		$unattached_sections =& $this->get_unattached_sections();
		if (!empty($unattached_sections))
		{
			foreach ($unattached_sections as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function get_attached_section_names_by_id()
	{
		$attached_sections =& $this->get_attached_sections();
		if (!empty($attached_sections))
		{
			foreach ($attached_sections as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}

	function &get_unattached_issues()
	{
		static $unattached_issues;
		$site_id = $this->get_site_id();
		if (!isset($unattached_issues))
		{
			$attached_issues =& $this->get_attached_issues();
			$es2 = new entity_selector($site_id);
			$es2->limit_tables();
			$es2->limit_fields();
			$es2->add_type(id_of('issue_type'));
			if ($attached_issues)
			{
				$es2->add_relation('entity.id NOT IN ('.implode(",",array_keys($attached_issues)).')');
			}
			$unattached_issues = $es2->run_one();
		}
		return $unattached_issues;
	}
	
	function &get_attached_issues()
	{
		static $attached_issues;
		$site_id = $this->get_site_id();
		if (!isset($attached_issues))
		{
			if ($site_pubs =& $this->get_site_publications())
			{
				$es = new entity_selector($site_id);
				$es->limit_tables();
				$es->limit_fields();
				$es->add_type(id_of('issue_type'));
				$es->add_left_relationship_field('issue_to_publication', 'entity', 'id', 'pub_id', array_keys($site_pubs));
				$attached_issues = $es->run_one();
			}
			else $attached_issues = false;
		}
		return $attached_issues;
	}
	
	function get_unattached_issue_names_by_id()
	{
		$unattached_issues =& $this->get_unattached_issues();
		if (!empty($unattached_issues))
		{
			foreach ($unattached_issues as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function get_attached_issue_names_by_id()
	{
		$attached_issues =& $this->get_attached_issues();
		if (!empty($attached_issues))
		{
			foreach ($attached_issues as $k=>$v)
			{
				$result[$k] = $v->get_value('name');
			}
		}
		return (isset($result)) ? $result : '';
	}
	
	function get_section_id_by_name( $name )
	{
		static $section_ids_by_name;
		echo 'given name ' .$name;
		if (!isset($section_ids_by_name[$name]))
		{
			$sections =& $this->get_sections( true );
			if ($sections)
			{
				foreach ($sections as $section)
				{
					$section_name = $section->get_value('name');
					$id = $section->id();
					$section_ids_by_name[$section_name] = $id;
				}
			}
		}
		return (isset($section_ids_by_name[$name])) ? $section_ids_by_name[$name] : false;
	}
	
	function &get_sections( $refresh = false )
	{
		static $sections;
		if (!isset($sections) || $refresh)
		{
			$site_id = $this->get_site_id();
			$es = new entity_selector($site_id);
			$es->add_type(id_of('news_section_type'));
			$sections = $es->run_one();
			$sections = ($sections) ? $sections : false;
		}
		return $sections;
	}
	
	function &get_pages_using_news_modules()
	{
		$rpts =& get_reason_page_types();
		static $pages_using_news_modules;
		if (!isset($pages_using_news_modules))
		{
			foreach ($this->news_modules as $module)
			{
				$valid_page_types = (isset($valid_page_types))
									? array_unique(array_merge($valid_page_types, $rpts->get_page_type_names_that_use_module($module)))
									: $rpts->get_page_type_names_that_use_module($module);
			}
			foreach (array_keys($valid_page_types) as $k) quote_walk($valid_page_types[$k], NULL);
		
			$site_id = $this->get_site_id();
			$es = new entity_selector($site_id);
			$es->add_type(id_of('minisite_page'));
			$es->add_relation('page_node.custom_page IN ('.implode(",", $valid_page_types).')');
			$pages_using_news_modules = $es->run_one();
		}
		return $pages_using_news_modules;
	}
	
	function &get_publication_module_page_types()
	{
		$rpts =& get_reason_page_types();
		static $publication_module_page_types;
		if (!isset($publication_module_page_types))
		{
			foreach ($this->publication_modules as $module)
			{
				$valid_page_types = (isset($valid_page_types))
									? array_unique(array_merge($valid_page_types, $rpts->get_page_type_names_that_use_module($module)))
									: array_unique($rpts->get_page_type_names_that_use_module($module));
			}
			foreach ($valid_page_types as $page_type)
			{
				$publication_module_page_types[$page_type] = $page_type;
			}
		}
		return $publication_module_page_types;
	}
	
	/**
	 * Support function for get_news_minisite_page
	 */
	function &get_page_types_with_main_post_news()
	{
		$rpts =& get_reason_page_types();
		static $page_types_with_main_post_news;
		if (!isset($page_types_with_main_post_news))
		{
			foreach ($this->news_modules as $module)
			{
				$candidate_page_types = (isset($candidate_page_types))
									  ? array_unique(array_merge($candidate_page_types, $rpts->get_page_type_names_that_use_module($module)))
									  : array_unique($rpts->get_page_type_names_that_use_module($module));
			}
			//!working
			foreach ($candidate_page_types as $page_type_name)
			{
				// we need to make sure one of the news modules is assigned to the main_post area
				$page_type = $rpts->get_page_type($page_type_name);
				if (in_array('main_post', $page_type->get_region_names()))
				{
					$region_info = $page_type->get_region('main_post');
					$main_post_module = $region_info['module_name'];
					if (in_array($main_post_module, $this->news_modules))
						$page_types_with_main_post_news[$page_type_name] = $page_type_name;
				}
			}
		}
		return $page_types_with_main_post_news;
	}
	
	function &get_news_minisite_page()
	{
		static $news_minisite_page;
		if (!isset($news_minisite_page))
		{
			$pub_module_page_types =& $this->get_publication_module_page_types();
			$page_types_with_main_post_news =& $this->get_page_types_with_main_post_news();
			$page_types = implode('","', $page_types_with_main_post_news);
			$site_id = $this->get_site_id();
			$es = new entity_selector($site_id);
			$es->add_type(id_of('minisite_page'));
			$es->add_relation('page_node.custom_page IN ("'.$page_types.'")');
			$es->set_num(1);
			$result = $es->run_one();
			if (!empty($result))
			{
				$news_minisite_page = current($result);
			}
			else
			{
				$news_minisite_page = false;
			}
		}
		return $news_minisite_page;
	}
	
	/**
	 * @return object disco form
	 */
	function &get_form()
	{
		$active_form_num = (isset($this->request['active_screen'])) ? $this->request['active_screen'] : "1";
		$migrator_form_name = (isset($this->custom_migrator_screen[$active_form_num])) ?
							  $this->custom_migrator_screen[$active_form_num] :
							  "MigratorScreen" . $active_form_num;
		$form = new $migrator_form_name;
		$form->helper = $this;
		return $form;
	}
	
	function authenticate()
	{
		$reason_user_id = $this->get_user_id();
		return reason_user_has_privs( $reason_user_id, 'upgrade' );
	}
	
	function ensure_type_is_on_site($type_id)
	{
		$site_id = $this->get_site_id();
		if ($site_id)
		{
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_right_relationship($this->get_site_id(),relationship_id_of('site_to_type'));
			$es->add_relation('entity.id = "'.$type_id.'"');
			$es->set_num(1);
			$type = $es->run_one();
			if(empty($type))
			{
				create_relationship( $site_id, $type_id, relationship_id_of('site_to_type'));
			}
			return true;
		}
		return false;
	}
	
	function ensure_nobody_group_is_on_site()
	{
		$site_id = $this->get_site_id();
		if ($site_id)
		{
			if(!(site_borrows_entity( $site_id, id_of('nobody_group')) || site_owns_entity( $site_id, id_of('nobody_group'))))
			{
				// borrow it
				create_relationship( $site_id, id_of('nobody_group'), get_borrows_relationship_id(id_of('group_type')));
			}
			return true;
		}
		return false;
	}
	
	function &get_recommended_page_type_mapping()
	{
		return $this->recommended_page_type_mapping;
	}
	
	/**
	 * GUESS METHODS - return best guess for a variety of things and an empty guess if nothing makes sense
	 */
	function guess_desired_publication_name()
	{
		$page =& $this->get_news_minisite_page();
		return ($page) ? $this->get_minisite_page_value($page, 'name') : '';
	}

	function guess_desired_publication_posts_per_page()
	{
		return "12";
	}
	
	function guess_desired_publication_description()
	{
		$page =& $this->get_news_minisite_page();
		return ($page) ? strip_tags($this->get_minisite_page_value($page, 'content')) : '';
	}
	
	function guess_desired_publication_rss_feed_url()
	{
		$page =& $this->get_news_minisite_page();
		$page_name = ($page) ? $this->get_minisite_page_value($page, 'name') : '';
		$rss_feed_url = ($page_name) ? strtolower(str_replace(" ", "_", $page_name)) : '';
		return $rss_feed_url;
	}
	
	function get_minisite_page_value(&$page, $value)
	{
		return $page->get_value($value);
	}
	
	function report()
	{
	}
}
?>