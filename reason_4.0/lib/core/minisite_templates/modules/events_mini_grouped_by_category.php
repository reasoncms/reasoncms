<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	reason_include_once('classes/calendar.php');
	reason_include_once('classes/page_types.php');
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'miniEventsGroupedByCategoryModule';

/**
 * A minisite module that lists upcoming events grouped into their categories.
 *
 * Note that this module needs a calendar on the same site, so links can go somewhere.
 *
 * This module has a large number of parameters:
 *
 * mode: "site" (default) uses all categories on the site; "page" uses the categories attached to the page.
 *
 * show_individual_occurrences: true (default) shows every occurrence of repeating events; false does not show repeats
 *
 * max_num_per_category: how many events should be listed under each category? Defaults to 3.
 *
 * show_archive_links: true (default) adds a link to the archive of events under each category; false does not
 *
 * category_unique_names: overrides the mode to specify a set of categories to use, defined by unique names
 *
 * show_empty_categories: false (default) hides categories with no upcoming events; true shows all categories
 *
 * link_to_more_info_url: false (default) only links to the item in the site's calendar; true will link to the contents of the URL for more info field on the event instead
 *
 * item_separator: The string to plce between events; defaults to " &#8226; " (a bullet)
 *
 * link_to_related_pages: false is default; if true, categories find a related page in the site and link to it instead of linking to the category on the calendar
 *
 * use_related_page_name: false is the default; if true, categories find a related page on the site and use its link name instead
 *
 * @author Matt Ryan
 */
class miniEventsGroupedByCategoryModule extends DefaultMinisiteModule
{
	/**
	 * The modules that represent an events page that we can link to
	 * @var array
	 */
	var $_events_modules = array('events','event_registration','event_signup','event_slot_registration','events_archive','events_hybrid','events_verbose',);
	/**
	 * Acceptable parameters
	 * @var array
	 */
	var $acceptable_params = array(
			'mode' => 'site', // 'page', 'site'
			'show_individual_occurrences' => true,
			'max_num_per_category' => 3,
			'show_archive_links' => true,
			'category_unique_names' => array(),
			'show_empty_categories' => false,
			'link_to_more_info_url' => false,
			'item_separator' => ' &#8226; ',
			'link_to_related_pages' => false,
			'use_related_page_name' => false,
	);
	/**
	 * The url for the events page
	 * @var string
	 */
	var $_events_page_url;
	/**
	 * The categories found
	 * @var array
	 */
	var $_categories;
	/**
	 * The date & time of the most recently modified item
	 * @var string MySQL datetime
	 */
	 var $_last_modified = '0000-00-00 00:00:00';
	/**
	 * get the url of the events page
	 * @return string
	 */
	function _get_events_page_url() // {{{
	{
		if(is_null($this->_events_page_url))
		{
			// reason_include_once( 'minisite_templates/nav_classes/default.php' );
			$ps = new entity_selector($this->site_id);
			$ps->add_type( id_of('minisite_page') );
			$rels = array();
			$page_types = array();
			$rpts =& get_reason_page_types();
			$page_types = $rpts->get_page_type_names_that_use_module($this->_events_modules);
			$page_types = array_map('reason_sql_string_escape',array_unique($page_types));
			$ps->add_relation('page_node.custom_page IN ("'.implode('","', $page_types).'")');
			$ps->set_num(1);
			$page_array = $ps->run_one();
			$this->events_page = current($page_array);
			if (!empty($this->events_page))
			{
				$this->_events_page_url = $this->_pages->get_full_url($this->events_page->id());
			}
			else
			{
				$this->_events_page_url = false;
			}
		}
		return $this->_events_page_url;
	} // }}}
	/**
	 * Get the categories to use
	 * @return array of entities
	 */
	function _get_categories()
	{
		if(is_null($this->_categories))
		{
			$this->_categories = array();
			if(!empty($this->params['category_unique_names']))
			{
				foreach($this->params['category_unique_names'] as $uname)
				{
					$id = id_of($uname);
					if($id)
					{
							$this->_categories[$id] = new entity($uname);
					}
				}
			}
			else
			{
				switch($this->params['mode'])
				{
					case 'page':
						$es = new entity_selector();
						$es->add_type(id_of('category_type'));
						$es->add_right_relationship($this->page_id,relationship_id_of('page_to_category'));
						$es->add_rel_sort_field( $this->page_id, relationship_id_of('page_to_category') );
						$es->set_order('rel_sort_order');
						$this->_categories = $es->run_one();
						break;
					case 'site':
						$es = new entity_selector($this->site_id);
						$es->add_type(id_of('category_type'));
						$this->_categories = $es->run_one();
						break;
					default:
						trigger_error('Mode not "page" or "site"; not able to get categories');
						$this->_categories = array();
				}
			}
		}
		
		return $this->_categories;
	}
	/**
	 * Get the event entities for a given category
	 *
	 * This is the method to use when the param show_individual_occurrences is false
	 *
	 * @param entity $category
	 * @return array of dates to entities
	 */
	function _get_events_for_category($category)
	{
		$site = new entity($this->site_id);
		
		/* We have to get all future events to make sure we don't fail to grab enough event entities with future ocurrences... */
		$cal = new reasonCalendar(array('site'=>$site,'view'=>'all', 'automagic_window_snap_to_nearest_view'=>false,'categories'=>array($category->id()=>$category)));
		$cal->run();
		$event_days = $cal->get_all_days();
		$event_entities = $cal->get_all_events();
		$ret = array();
		$hits = array();
		$count = 0;
		foreach($event_days as $day=>$ids)
		{
			if($count >= $this->params['max_num_per_category'])
				break;
			foreach($ids as $id)
			{
				if($count >= $this->params['max_num_per_category'])
					break;
				if(!in_array($id, $hits))
				{
					$hits[] = $id;
					$ret[$day][$id] = $event_entities[$id];
					$count++;
				}
			}
		}
		return $ret;
	}
	/**
	 * Get the event ocurrences for a given category
	 *
	 * This is the method to use when the param show_individual_occurrences is true
	 *
	 * @param entity $category
	 * @return array of dates to entities
	 */
	function _get_event_ocurrences_for_category($category)
	{
		$site = new entity($this->site_id);
		
		$cal = new reasonCalendar(array('site'=>$site,'ideal_count'=>$this->params['max_num_per_category'], 'automagic_window_snap_to_nearest_view'=>false,'categories'=>array($category->id()=>$category)));
		$cal->run();
		$event_days = $cal->get_all_days();
		$event_entities = $cal->get_all_events();
		$ret = array();
		foreach($event_days as $day=>$ids)
		{
			foreach($ids as $id)
			{
				$ret[$day][$id] = $event_entities[$id];
			}
		}
		return $ret;
	}
	/**
	 * Get a page on the site for a given category
	 *
	 * @param entity $category
	 * @return entity $page
	 */
	function _get_page_for_category($category)
	{
		$ps = new entity_selector($this->site_id);
		$ps->add_type( id_of('minisite_page') );
		$ps->add_left_relationship($category->id(), relationship_id_of('page_to_category'));
		$ps->add_relation('entity.id != "'.reason_sql_string_escape($this->page_id).'"');
		$ps->set_num(1);
		$pages = $ps->run_one();
		if(!empty($pages))
			return current($pages);
		else
			return false;
	}
	/**
	 * Run the module
	 *
	 * @return null
	 */
	function run()
	{
		$url = $this->_get_events_page_url();
		if(empty($url))
		{
			trigger_error('No events page URL found. Unable to run module');
			return;
		}
		echo '<div class="eventsMiniByCategory">'."\n";
		echo '<ul>'."\n";
		foreach($this->_get_categories() as $category)
		{
			$output = $this->_get_event_output($category,$url);
				
			if(!empty($output))
			{
				echo '<li>'."\n";
				echo $output;
				echo '</li>'."\n";
				$this->_last_mod_reg($category);
			}
		}
		echo '</ul>'."\n";
		echo '</div>'."\n";
	}
	
	
	/**
	 * Get a the markup for an individual category
	 *
	 * @param entity $category
	 * @param string $event_page_url
	 * @return string $markup
	 */
	function _get_event_output($category, $event_page_url)
	{
		$ret = '';
		if($this->params['show_individual_occurrences'])
			$days = $this->_get_event_ocurrences_for_category($category);
		else
			$days = $this->_get_events_for_category($category);
		if($this->params['show_empty_categories'] || !empty($days))
		{
			$name = $category->get_value('name');
			$url = $event_page_url.'?category='.$category->id();
			if($this->params['link_to_related_pages'] || $this->params['use_related_page_name'])
			{
				$page = $this->_get_page_for_category($category);
				if(!empty($page))
				{
					if($this->params['link_to_related_pages'])
						$url = $this->_pages->get_full_url($page->id());
					if($this->params['use_related_page_name'])
						$name = $page->get_value('link_name') ? $page->get_value('link_name') : $page->get_value('name');
				}
			}
			$ret .= '<h3>';
			if($url)
				$ret .= '<a href="'.$url.'"><span class="categoryName">'.$name.'</span></a>';
			else
				$ret .= '<span class="categoryName">'.$name.'</span>';
			$ret .= '</h3>'."\n";
			$items = array();
			foreach($days as $day=>$events)
			{
				foreach($events as $event)
				{
					if($this->params['link_to_more_info_url'] && $event->get_value('url'))
						$event_url = reason_htmlspecialchars($event->get_value('url'));
					else
						$event_url = $event_page_url.'?event_id='.$event->id();
					$items[] = '<a href="'.$event_url.'">'.$event->get_value('name').'</a>'."\n";
					$this->_last_mod_reg($event);
				}
			}
			if($this->params['show_archive_links'])
			{
				$items[] = '<a href="'.$event_page_url.'?view=all&amp;start_date=1970-01-01&amp;category='.$category->id().'" class="archive">Archive</a>'."\n";
			}
			$ret .= '<div class="items">'.implode($this->params['item_separator'],$items).'</div>'."\n";
		}
		return $ret;
	}
	/**
	 * Register an item in the last modified store
	 *
	 * If the item is more recently modified than the current value of @var $_last_modified,
	 * this method will update @var $_last_modfified to match the item's value.
	 *
	 * @param entity object
	 * @return void 
	 */
	function _last_mod_reg($entity)
	{
		if($entity->get_value('last_modified') > $this->_last_modified)
		{
			$this->_last_modified = $entity->get_value('last_modified');
		}
	}
	
	/**
	 *  Template calls this function to figure out the most recently last modified item on page
	 * This function uses the most recently modified category or event
	 * @return mixed last modified value or false
	 */
	function last_modified() // {{{
	{
		if($this->_last_modified == '0000-00-00 00:00:00')
			return false;
		return $this->_last_modified;
	} // }}}
}
