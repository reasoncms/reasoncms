<?php
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/event_helper.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsUpcomingModule';
	
/**
 * This module displays a minimal calendar view of upcoming events, drawn from a cache for performance
 *
 * @package reason
 * @subpackage minisite_modules
 * 
 * @author Mark Heiman
 */
class EventsUpcomingModule extends DefaultMinisiteModule
{
	var $events;
	var $events_by_date;
	var $eh;
	var $acceptable_params = array (
					'cache_lifespan' => 0,
					'num_to_display' => NULL,
					'limit_to_audience' => NULL,
					'limit_to_category' => NULL,
					'lookahead_minutes' => 60,
					'set_or_categories' => false,
					'show_today_header' => true,
					'ongoing_display' => 'below', // or below or inline
					'ongoing_show_ends' => true,
					'all_day_display' => 'top', // or below
					'demote_all_day_events' => false,
					'title' => '',
					'foot' => '',
					);
	
	var $cleanup_rules = 	array(	
		'date' => array( 'function' => 'turn_into_date'),
		'time' => array( 'function' => 'turn_into_string'),
				);
	var $link_str = '';

	
	
	function init( $args = array() )
	{	
		if( isset( $this->parent->textonly ) && $this->parent->textonly == 1 )
		{
			$this->link_str = '&textonly=1';
		}
	
		$this->eh = new EventHelper($this->site_id, $this->page_id);
		if ($this->params['cache_lifespan'] > 0) $this->eh->set_cache_lifespan($this->params['cache_lifespan']);
		if ($this->params['set_or_categories']) $this->eh->set_or_categories($this->params['set_or_categories']);
		if ($this->params['num_to_display']) 
		{
			if ($this->params['demote_all_day_events']) 
				$this->eh->set_optimal_event_count($this->params['num_to_display']*2);
			else
				$this->eh->set_optimal_event_count($this->params['num_to_display']);
		}
		if ($this->params['lookahead_minutes']) $this->eh->set_lookahead_minutes($this->params['lookahead_minutes']);
		if ($this->params['limit_to_audience']) 
		{
			if (is_array($this->params['limit_to_audience']))
				$this->eh->set_audience_limit($this->params['limit_to_audience']);
			else
				$this->eh->set_audience_limit(array($this->params['limit_to_audience']));
		}
		if ($this->params['limit_to_category']) 
		{
			if (is_array($this->params['limit_to_category']))
				$this->eh->set_category_limit($this->params['limit_to_category']);
			else
				$this->eh->set_category_limit(array($this->params['limit_to_category']));
		}

		if (isset($this->request['date'])) 
		{
			$this->eh->set_startdate($this->request['date']);
			$this->eh->set_cache_lifespan(0);
		}
		if (isset($this->request['time'])) 
		{
			$this->eh->set_starttime($this->request['time']);
			$this->eh->set_cache_lifespan(0);
		}
				
		if('inline' == $this->params['ongoing_display'])
		{
			$init_array['ongoing_count_all_occurrences'] = true;
		}
		elseif('above' == $this->params['ongoing_display'])
		{
			$init_array['ongoing_count_all_occurrences'] = false;
			$init_array['ongoing_count_pre_start_dates'] = true;
			$init_array['ongoing_count_ends'] = $this->params['ongoing_show_ends'];
		}
		elseif('below' == $this->params['ongoing_display'])
		{
			$init_array['ongoing_count_all_occurrences'] = false;
			$init_array['ongoing_count_pre_start_dates'] = false;
			$init_array['ongoing_count_ends'] = $this->params['ongoing_show_ends'];
		}
		
		$this->eh->init();
		$this->eh->set_init_array($init_array);
		$this->events =& $this->eh->get_events();
		$this->events_by_date = $this->eh->calendar->get_all_days();
	}
	
	
	function has_content()
	{
		if( !empty($this->events) )
			return true;
		else
			return false;
	}
	
	function run()
	{
		// If any of the events occur on dates other than today, we need to show the dates
		$date = (isset($this->request['date'])) ? $this->request['date'] : date('Y-m-d');
		$show_dates = (count($this->events) > 1);

		echo '<div id="upcomingEvents">'."\n";
		if(!empty($this->params['title']))
		{
			echo '<h3>'.$this->params['title'].'</h3>'."\n";
		}

		echo '<div class="list">'."\n";

		if($this->params['ongoing_display'] == 'above')
		{
			$this->show_ongoing_events( $this->get_ongoing_event_ids('above') );
		}
		
		foreach($this->events as $event_date=>$events)
		{
			$count = 0;
			ob_start();
			$first = reset($events);
			if ($first->get_value('date') == $date)
			{
				if ($show_dates || $this->params['show_today_header'])
					echo '<h4>Today ('.$first->get_value('display_date').')</h4>'."\n";
			} else if ($show_dates) {
				echo '<h4>'.$first->get_value('display_date').'</h4>'."\n";
			}
			echo '<ul>'."\n";
			
			$all_day_events = $regular_events = array();
			
			foreach($events as $event)
			{
				if ($this->eh->calendar->event_is_ongoing($event))
				{
					$ongoing_type = $this->get_event_ongoing_type_for_day($event,$event_date);
					if ( 'middle' != $ongoing_type )
						$regular_events[] = $event;
				}
				else if ($this->eh->calendar->event_is_all_day_event($event))
				{
					$all_day_events[] = $event;
				}
				else
				{
					$regular_events[] = $event;
				}
			}
			
			// If we have an event count limit, and we've chosen to demote all day events, fill the count
			// with regular events, and then remove all day events until we have enough to fill in (with
			// a minimum of one)
			if ($this->params['demote_all_day_events'] && $this->params['num_to_display'] 
				&& (count($all_day_events) + count($regular_events) > $this->params['num_to_display']))
			{
				shuffle($all_day_events);
				
				while (count($regular_events) + count($all_day_events) > $this->params['num_to_display'] && count($all_day_events) > 1)
					array_shift($all_day_events);
			}
			
			if (count($all_day_events))
			{
				if ($this->params['all_day_display'] == 'top')
					$events = array_merge($all_day_events,$regular_events);
				else
					$events = array_merge($regular_events,$all_day_events);
			} else {
				$events = $regular_events;
			}

			foreach($events as $event)
			{
				
				echo '<li>'."\n";
				$this->show_event_list_item($event);
				echo '</li>'."\n";
				$count++;
			}
			echo '</ul>'."\n";
			if ($count) 
				ob_end_flush();
			else
				ob_end_clean();
		}

		if($this->params['ongoing_display'] == 'below')
		{
			$this->show_ongoing_events( $this->get_ongoing_event_ids('below') );
		}

		echo '</div>'."\n";
		if(!empty($this->params['foot']))
		{
			echo '<div class="foot">'.$this->params['foot'].'</div>'."\n";
		}
		echo '</div>'."\n";
	}
			
	/**
	 * Output standard HTML for an event in the list
	 *
	 * @param object $event
	 * @param string $ongoing_type What method of display are we using for ongoing events? Values: '','starts','ends'
	 * @return void
	 */
	function show_event_list_item( $event, $ongoing_type = '' ) // {{{
	{
		if ($time = $this->get_event_time_html($event))
			echo $time.' ';
		echo $this->get_event_name_html($event);

		switch($ongoing_type)
		{
			case 'starts':
				echo ' <span class="begins">begins</span>';
			case 'through':
				echo ' <em class="through">(through '.$this->_get_formatted_end_date($event).')</em> ';
				break;
			case 'ends':
				echo ' <span class="ends">ends</span>';
				break;
		}		
		if($location = $this->get_event_location_html($event))
			echo ' '.$location ."\n";
	} // }}}
	
	function get_event_name_html(&$event)
	{
		$html = '<span class="name">';
		if ($url = $event->get_value('url'))
		{
			if (strpos($url,'http') !== false)
				$html .= '<a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8') . $this->link_str . '">';
			else
				$html .= '<a href="//'.REASON_HOST.htmlspecialchars($url,ENT_QUOTES,'UTF-8') . $this->link_str . '">';
			$html .= $event->get_value('name') . '</a>';
		}
		else
			$html .= $event->get_value('name');
		$html .= '</span>';
		return $html;
	}
	
	function get_event_time_html(&$event)
	{
		if ($event->get_value('time') == '00:00:00') return false;
		$html = '<span class="time">';
		$html .= $event->get_value('display_time');
		$html .= '</span>';
		return $html;
	}
	
	function get_event_location_html(&$event)
	{
		if (!$event->get_value('location')) return false;
		$html = '<span class="location">';
		$html .= $event->get_value('location');
		$html .= '</span>';
		return $html;
	}
	
	function get_event_by_id($id)
	{
		foreach($this->events as $date => $events)
		{
			if(isset($events[$id]))
				return $events[$id];
		}	
	}
	
	/**
	 * Format a date to be shown in a "through [formatted date]" phrase
	 *
	 * @param object $event entity
	 * @return string formatted date
	 */
	function _get_formatted_end_date($event)
	{
		$full_month = prettify_mysql_datetime($event->get_value('last_occurence'),'F');
		$month = prettify_mysql_datetime($event->get_value('last_occurence'),'M');
		
		$ret = $month;
		if($full_month != $month)
			$ret .= '.';
		$ret .= ' ';
		$ret .= prettify_mysql_datetime($event->get_value('last_occurence'),'j');
		
		$start_year = max(substr($event->get_value('datetime'),0,4), substr($this->eh->calendar->get_start_date(),0,4));
		
		if($start_year != substr($event->get_value('last_occurence'),0,4))
			$ret .= ', '.substr($event->get_value('last_occurence'),0,4);
		
		return $ret;
	}

	/**
	 * For a given event and a given day, should
	 * the event be displayed as starting, ending, "ongoing", or not at all?
	 *
	 * @param object $event
	 * @param string $day YYY-MM-DD
	 * @return string Values: 'starts', 'ends', 'middle', or ''
	 */
	function get_event_ongoing_type_for_day($event,$day)
	{
		if($this->params['ongoing_display'] != 'inline' && $this->eh->calendar->event_is_ongoing($event))
		{
			if(substr($event->get_value( 'datetime' ), 0,10) == $day)
			{
				return 'starts';
			}
			elseif($this->params['ongoing_show_ends'] && $event->get_value( 'last_occurence' ) == $day)
			{
				return 'ends';
			}
			else
			{
				return 'middle';
			}
		}
		return '';
	}
	/**
	 * Output HTML list of ongoing events
	 * @param array $ids integers
	 * @return void
	 */
	function show_ongoing_events($ids)
	{
		if(!empty($ids))
		{
			echo '<h4>Ongoing</h4>'."\n";
			echo '<ul>'."\n";
			foreach($ids as $id)
			{
				echo '<li>';
				$this->show_event_list_item( $this->get_event_by_id($id), 'through' );
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
		}
	}	
	
	/**
	 * Get the ids of all ongoing events in the calendar
	 *
	 * @param string $ongoing_display 'inline', 'above', or 'below' -- to determine which events
	 *               should be considered ongoing
	 * @return array event objects
	 */
	function get_ongoing_event_ids($ongoing_display = '')
	{
		if(empty($ongoing_display))
			$ongoing_display = $this->params['ongoing_display'];
		
		$ongoing_ids = array();
		foreach($this->events_by_date as $day => $val)
		{
			if ( $this->eh->calendar->get_end_date() && $day > $this->eh->calendar->get_end_date() )
				break;
			$ongoing_ids = array_merge($ongoing_ids,$val);
		}
		$ongoing_ids = array_unique($ongoing_ids);
		if('above' == $ongoing_display)
		{
			foreach($ongoing_ids as $k => $id)
			{
				foreach($this->events as $date => $events)
				{
					if(!isset($events[$id]) || !$this->eh->calendar->event_is_ongoing($events[$id]) || $events[$id]->get_value('datetime') >= $this->eh->calendar->get_start_date())
						unset($ongoing_ids[$k]);
				}
			}
		}
		elseif('below' == $ongoing_display)
		{
			foreach($ongoing_ids as $k => $id)
			{
				foreach($this->events as $date => $events)
				{
					if(!isset($events[$id]) || !$this->eh->calendar->event_is_ongoing($events[$id]) || $events[$id]->get_value('datetime') >= $this->eh->calendar->get_start_date() || $events[$id]->get_value('last_occurence') <= $this->eh->calendar->get_end_date())
						unset($ongoing_ids[$k]);
				}
			}
		}
		else
		{
			trigger_error('Unrecognized string passed to get_ongoing_event_ids(): '.$ongoing_display.'. Should be "above" or "below".');
		}
		return $ongoing_ids;
	}

	/**
	 * This method will clear the event cache generated by this module for the site and page
	 * @todo implement something to call this
	 */
	function clear_cache($site_id = '', $page_id = '')
	{
		$site_id = ($site_id) ? $site_id : $this->site_id;
		$page_id = ($page_id) ? $page_id : $this->page_id;
		if ($site_id && $page_id)
		{
			$qh = new EventHelper($site_id, $page_id);
		}
		$qh->clear_cache();
	}
}
?>
