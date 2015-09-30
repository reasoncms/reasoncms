<?php
include_once('reason_header.php');
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'classes/object_cache.php' );
reason_include_once( 'classes/calendar.php' );
//reason_include_once( 'minisite_templates/nav_classes/default.php' );
reason_include_once( 'function_libraries/url_utils.php' );
reason_include_once( 'function_libraries/util.php' );
reason_include_once('classes/page_types.php');

/**
 * Event helper
 *
 * Retrieve and cache a set of upcoming events
 *
 * NOTE: The functionality of this class related to retrieving events from the database
 * is geared towards grabbing events from all live, event-sharing sites in a Reason instance.
 * It is not designed to grab events for a specific site or set of sites, despite the existence
 * of $site_id parameters and a set_site_id() method.
 *
 * Utilized by the events_upcoming module.
 *
 * Sample usage example:
 *
 *  <code>
 *  	$eh = new EventHelper();
 *		$eh->init();
 *		$events =& $eh->get_events();	
 *  </code>
 *
 * @package reason
 * @subpackage classes
 *
 * @author Mark Heiman
 */

 class EventHelper
 {
	var $site_id;
	var $page_id;
	var $audience_limit = array();
	var $category_limit = array();
	var $or_categories;
	var $cache_lifespan = 0;
	var $optimal_event_count = 5;
	var $lookahead_minutes = 60;
	var $startdate;
	var $starttime;
	var $init_array = array();
	
	var $calendar;
	var $events;
	var $events_page_types;
	var $events_modules = array('events','events_verbose','events_academic_calendar','events_workshops','event_slot_registration','event_registration','event_signup','events_archive','athletics/athletics_schedule');
	
 	function EventHelper($site_id = NULL, $page_id = NULL, $optimal_event_count = NULL, $lookahead_minutes = NULL, $cache_lifespan = NULL, $audience_limit = NULL, $category_limit = NULL)
 	{
 		if (isset($site_id)) $this->set_site_id($site_id);
 		if (isset($page_id)) $this->set_page_id($page_id);
 		if (isset($optimal_event_count)) $this->set_optimal_event_count($optimal_event_count);
 		if (isset($lookahead_minutes)) $this->set_lookahead_minutes($lookahead_minutes);
 		if (isset($audience_limit)) $this->set_audience_limit($audience_limit);
 		if (isset($category_limit)) $this->set_category_limit($category_limit);
 		if (isset($cache_lifespan)) $this->set_cache_lifespan($cache_lifespan);
		$this->set_startdate(date('Y-m-d'));
		$this->set_starttime(date('H:i:s'));
 	}
 	
 	function init()
 	{
 		$this->init_from_cache();
		if (empty($this->events) || empty($this->calendar)) $this->init_from_database(); // there is no cache
 	}
 	
 	function init_from_cache()
	{
		$cache_lifespan = $this->get_cache_lifespan();
		if ($cache_lifespan > 0)
		{
			$cache = new ReasonObjectCache($this->get_cache_id(), $this->get_cache_lifespan());
			$this->events =& $cache->fetch();
			$cache = new ReasonObjectCache($this->get_cache_id().'_cal', $this->get_cache_lifespan());
			$this->calendar =& $cache->fetch();
		}
	}
	
	function init_from_database()
	{
		$init_array = array_merge($this->init_array, array(
			'ideal_count'=>$this->optimal_event_count*3, 
			'start_date'=> $this->startdate,
			));
		if (count($this->audience_limit)) 
		{
			$aud_array = array();
			foreach($this->audience_limit as $audience)
			{
				$aud_id = id_of($audience);
				if ($aud_id) $aud_array[] = new entity($aud_id);
			}
			if (count($aud_array))
				$init_array['audiences'] = $aud_array;
		}
		if (count($this->category_limit)) 
		{
			$cat_array = array();
			foreach($this->category_limit as $category)
			{
				$cat_id = id_of($category);
				if ($cat_id) $cat_array[] = new entity($cat_id);
			}
			if (count($cat_array))
				if ($this->or_categories) {
					$init_array['or_categories'] = $cat_array;
				} else {
					$init_array['categories'] = $cat_array;
				}
			
		}
		
		$this->calendar = new reasonCalendar( $init_array );
		$this->calendar->run();
		
		$full_event_pool = $this->calendar->get_all_events();
		//$event_days = $this->calendar->get_all_days();
		$event_pool = array();
		$events = array();
		$event_count = 0;
		// relative definition of now allows testing of different days
		$now = strtotime($this->startdate . ' ' . $this->starttime);

		// For repeating events, we need to split the repeats up into the right dates
		foreach ($this->calendar->events_by_date as $date => $day)
		{
			foreach ($day as $event)
			{
				if ($full_event_pool[$event]->get_value('dates') != $full_event_pool[$event]->get_value('last_occurence'))
				{
					foreach (explode(',', $full_event_pool[$event]->get_value('dates')) as $occurence)
					{
						if ($occurence != $date && $occurence >= $this->startdate)
							$this->calendar->events_by_date[$occurence][] = $event;
					}	
				}
			}
		}
		ksort($this->calendar->events_by_date);	
		foreach ($this->calendar->events_by_date as $date => $day)
		{
			// for some reason, the calendar class returns duplicates in a single day, so we need to unique them away
			$sorted_events = array_unique($this->calendar->sort_event_ids_by_time_of_day($day));
			foreach ($sorted_events as $event)
			{
				// we need to copy the event object here in case it's a recurring event
				$event_pool[$date][$event] = carl_clone($full_event_pool[$event]);
				$event_pool[$date][$event]->set_value('date', $date);
				$event_pool[$date][$event]->set_value('display_date', date('l, F jS', strtotime($date)));
				list($d, $t) = explode(' ' , $event_pool[$date][$event]->get_value('datetime'));
				$event_pool[$date][$event]->set_value('time', $t); 
				$event_pool[$date][$event]->set_value('display_time', date('g:ia', strtotime($event_pool[$date][$event]->_values['datetime'])));
				$this->set_range_timestamps($event_pool[$date][$event]);
				$this->set_page_link($event_pool[$date][$event]);
				
				// If this event is still going on or is in the future...
				if ($event_pool[$date][$event]->get_value('end_stamp') > $now )
				{
					// If we have enough events, and this event starts more than the lookahead span from now, we're done
					if ($event_count >= $this->optimal_event_count && $event_pool[$date][$event]->get_value('start_stamp') > $now + ($this->lookahead_minutes * 60) + 60)
						break 2;
					// Otherwise, add this event to our list
					else
					{
						$events[$date][$event] = $event_pool[$date][$event];
						$event_count++;
					}
				}
			}
		}
		$this->events = $events;
		$this->set_cache();

	}
 	
 	function init_from_categories(&$already_selected)
	{
		if ($this->page_category_mode)
		{
			$cat_es = new entity_selector($this->site_id);
			$cat_es->add_type( id_of('category_type') );
			$cat_es->limit_tables();
			$cat_es->limit_fields();
			$cat_es->add_right_relationship ($this->page_id, relationship_id_of( 'page_to_category' ) );
			$cat_result = $cat_es->run_one();
			if (!empty($cat_result))
			{
				$es = new entity_selector($this->site_id);
				$es->add_type( id_of('quote_type') );
				$es->set_env('site', $this->site_id);
				$es->add_left_relationship_field( 'quote_to_category', 'entity', 'id', 'cat_id', array_keys($cat_result));
				if (!empty($already_selected)) $es->add_relation('entity.id NOT IN ('.implode(array_keys($already_selected)).')');
				$result = $es->run_one();
			}
		}
		return (!empty($result)) ? $result : array();
	}
 	
 	function &get_events()
 	{
 		if (isset($this->events))
		{
			return $this->events;
		}
 		else
 		{
 			trigger_error('You must initialize the helper using the init() method before accessing events.', FATAL);
 		}
 	}
 	
	function set_range_timestamps(&$event)
	{
		// Assumes that the extra date and time values have already been set on the event
		$start = strtotime($event->get_value('date') . ' ' . $event->get_value('time'));
		// If this is an all day event, it runs until 11:59pm
		if ($event->get_value('time') == '00:00:00')
			$duration = (24 * 60) - 1;
		else 
		{
			$duration = ($event->get_value('hours')) ? $event->get_value('hours') * 60 : 0;
			$duration += ($event->get_value('minutes')) ? $event->get_value('minutes') : 0;
			// If there's no duration, assume an hour
			if ($duration == 0) $duration = 60;
		}
		$end = $start + ($duration * 60);
		$event->set_value('start_stamp', $start);
		$event->set_value('end_stamp', $end);
		
	}
 
	function set_page_link(&$e)
	{
		$owner_site = $e->get_owner();
		if ($owner_site)
		{
			$ps = new entity_selector($owner_site->id());
			$ps->add_type( id_of('minisite_page') );
			$rels = array();
			foreach($this->get_events_page_types() as $page_type)
			{
				$rels[] = 'page_node.custom_page = "'.$page_type.'"';
			}
			$ps->add_relation('( '.implode(' OR ', $rels).' )');
			$page_array = $ps->run_one();
			reset($page_array);
			$events_page = current($page_array);
	
			if (!empty($events_page))
			{
				$ret = reason_get_page_url($events_page->id());
			}
			if(!empty($ret))
				$e->set_value('url', sprintf('%s?event_id=%d&date=%s', $ret, $e->id(), $e->get_value('date')));
		}
		else trigger_error('set_page_link called on an event (id '.$e->id().') that does not have an owner site id - a url could not be set');
	}

	function get_events_modules()
	{
		reason_include_once('classes/module_sets.php');
		$ms =& reason_get_module_sets();
		return array_unique(array_merge($ms->get('event_display'),$this->events_modules));
	}
	function get_events_page_types()
	{
		// Based on the list of modules that show events, figure out which page types use them
		if (empty($this->events_page_types))
		{
			$rpts =& get_reason_page_types();
			$this->events_page_types = $rpts->get_page_type_names_that_use_module($this->get_events_modules());
		}
		return $this->events_page_types;
	}
	
 	function set_site_id($site_id)
 	{
 		$this->site_id = $site_id;
 	}
 	
 	function set_page_id($page_id)
 	{
 		$this->page_id = $page_id;
 	}

 	function set_optimal_event_count($optimal_event_count)
 	{
 		$this->optimal_event_count = $optimal_event_count;
 	}
 	
 	function set_lookahead_minutes($lookahead_minutes)
 	{
 		$this->lookahead_minutes = $lookahead_minutes;
 	}
 	
 	function set_audience_limit($audience_limit)
 	{
 		$this->audience_limit = $audience_limit;
 	}

 	function set_category_limit($category_limit)
 	{
 		$this->category_limit = $category_limit;
 	}

 	function set_or_categories($or_categories)
 	{
 		$this->or_categories = $or_categories;
 	}

	function set_startdate($start)
 	{
 		$this->startdate = $start;
 	}

	function set_starttime($start)
 	{
 		$this->starttime = $start;
 	}

	function set_init_array($init)
 	{
 		$this->init_array = $init;
 	}

	function set_cache()
	{
		if ($this->get_cache_lifespan() > 0)
		{
			$cache = new ReasonObjectCache($this->get_cache_id());
			$cache->set($this->events);
			$cache = new ReasonObjectCache($this->get_cache_id().'_cal');
			$cache->set($this->calendar);
		}
	}
	
	function set_cache_lifespan($seconds)
	{
		$ls = turn_into_int($seconds);
		$this->cache_lifespan = $seconds;
	}
		
	function get_cache_id()
	{
		return md5('events_cache_site_' . $this->site_id . '_page_' . $this->page_id);
	}
	
	function get_cache_lifespan()
	{
		return $this->cache_lifespan;
	}
	
	function clear_cache()
	{
		$cache = new ReasonObjectCache($this->get_cache_id());
		$cache->clear();
		$cache = new ReasonObjectCache($this->get_cache_id().'_cal');
		$cache->clear();
	}
 }
 
?>
