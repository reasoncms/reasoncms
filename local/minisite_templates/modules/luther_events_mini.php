<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'miniEventsModule';

/**
 * A minisite module that creates a minimal "sidebar" style event listing, linking to the main events page on the site
 */
class miniEventsModule extends EventsModule
{
	//var $ideal_count = 6;
	var $luther_counter = 3;
	var $show_options = false;
	var $show_navigation = false;
	var $show_views = false;
	var $show_calendar_grid = true;
	var $show_months = false;
	var $snap_to_nearest_view = false;
	var $events_page;
	var $events_page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','event_slot_registration','events_archive','events_archive_verbose', 'sports_results');
	var $list_date_format = 'M d';
		
	function init( $args = array() )
	{
		parent::init( $args );
		$this->find_events_page();
		
	}
	
	function has_content()
	{
		if(!empty($this->events_page_url) && !empty($this->calendar)
		|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports' & !empty($this->calendar))
		{
			$events = $this->calendar->get_all_events();
			if(empty($events))
				return false;
			else
				return true;
		}
		return false;
	}
	function run()
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_live_at_luther'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			echo '<section class="events" role="group">'."\n";
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				echo '<header class="blue-stripe"><h1><span>Schedule</span></h1></header>'."\n";
			}
			else
			{
				if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving')
				{
					echo '<header class="blue-stripe"><h1><span>Browse Events</span></h1></header>'."\n";
					$this->show_calendar_grid();
				}
				echo '<header class="blue-stripe"><h1><span>Upcoming Events</span></h1></header>'."\n";
			}
		}
		
		echo '<ol class="hfeed">'."\n";
	
		//echo '<div id="'.$this->div_id.'">'."\n";
		if (empty($this->request['event_id']))
			$this->list_events();
		else
			$this->show_event();
		//echo '</div>'."\n";
		echo '</ol>'."\n";
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_music'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_alumni')
		{
			//echo '<nav id="calendar">'."\n";
			$this->show_calendar_grid();
			//echo '</nav>  <!-- id="calendar" -->'."\n";
		}
		$this->show_feed_link();
		
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_giving'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_live_at_luther'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_naa'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information'
			|| $this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			echo '</section> <!-- class="events" role="group" -->'."\n";
		}
	}

	function find_events_page()
	{
		reason_include_once( 'minisite_templates/nav_classes/default.php' );
		$ps = new entity_selector($this->parent->site_id);
		$ps->add_type( id_of('minisite_page') );
		$rels = array();
		foreach($this->events_page_types as $page_type)
		{
			$rels[] = 'page_node.custom_page = "'.$page_type.'"';
		}
		$ps->add_relation('( '.implode(' OR ', $rels).' )');
		$page_array = $ps->run_one();
		reset($page_array);
		$this->events_page = current($page_array);
		if (!empty($this->events_page))
		{
			$ret = $this->parent->pages->get_full_url($this->events_page->id());
		}
		if(!empty($ret))
			$this->events_page_url = $ret;
	}
	
	function show_feed_link()
	{
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_home')
		{
                        /**** Temp change - per Greg. Until Susan fills the programming calender
                         * with events for the coming year.
                         */
			//$viewAllLink = "/programming/events/";
			$viewAllLink = "/events/";
		}
		//else if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		//{ 
		//	$viewAllLink = get_current_url() . "schedule";
		//}
		else
		{
			$viewAllLink = $this->events_page_url;
		}
		echo '<nav class="button view-all">'."\n";
		echo '<ul>'."\n";
		if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
		{
			echo '<li><a href="'.$viewAllLink.'">Complete schedule &gt;</a></li>'."\n";
		}
		else
		{
			//echo '<li><a href="/programming/calendar">View all events &gt;</a></li>'."\n";
			echo '<li><a href="'.$viewAllLink.'">View all events &gt;</a></li>'."\n";
		}
		echo '</ul>'."\n";
		echo '</nav>'."\n";
	}

	function list_events()
	{
		if ($this->calendar->contains_any_events())
		{
			$this->events_by_date = $this->calendar->get_all_days();
			if (!empty($this->events_by_date))
			{
				$this->events = $this->calendar->get_all_events();
				foreach($this->events_by_date as $day => $val)
				{
					$this->show_daily_events($day);
					if ($this->luther_counter <= 0)
						break;
				}		
			}
		}
	}
	
	function show_daily_events($day)
	{
		foreach ($this->events_by_date[$day] as $event_id)
		{
			
			$this->show_event_list_item( $event_id, $day );
		}		
	}

	function show_event_list_item_standard( $event_id, $day )
	{

		
		$sd = substr($this->events[$event_id]->get_value('datetime'), 0, 10);
		if (substr($day, 0, 10) == $sd
			|| substr($day, 0, 10) == substr($this->today, 0, 10))
		{
			echo '<li class="vevent">'."\n";
			if (!empty($this->events_page_url))
			{
				echo '<a href="'.$this->events_page_url.'?event_id='.$this->events[$event_id]->id().'&date='.$day.'">'."\n";
			}
			
			echo '<div>'."\n";
			$d = mktime(0, 0, 0, substr($day, 5, 2), substr($day, 8, 2), substr($day, 0, 4));
			$lo = substr($this->events[$event_id]->get_value('last_occurence'), 0, 10);

			echo '<time class="dtstart" datetime="'.$day.'"><span class="month">'.date('M', $d).'</span><span class="day">'.date('d', $d).'</span></time>'."\n";
			
			echo '<h1 class="summary">'.$this->events[$event_id]->get_value( 'name' );
			if ($sd != $lo)
			{
				$s = mktime(0, 0, 0, substr($sd, 5, 2), substr($sd, 8, 2), substr($sd, 0, 4));
				$e = mktime(0, 0, 0, substr($lo, 5, 2), substr($lo, 8, 2), substr($lo, 0, 4));
				if (date('M', $d) == date('M', $e))
				{
					echo '<br />('.date('M', $s).' '.date('d', $s).'-'.date('d', $e).')';
				}
				else 
				{
					echo '<br />('.date('M', $s).' '.date('d', $s).'-'.date('M', $e).' '.date('d', $e).')';
				}
			}
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_sports')
			{
				echo '<br /><span class="location">'.$this->events[$event_id]->get_value( 'location' ).'</span>';
			}
			
			echo '</h1>'."\n";
			echo '</div>'."\n";
			if (!empty($this->events_page_url))
			{
				echo '</a>'."\n";
			}
			echo '</li>'."\n";
			$this->luther_counter--;
		}

		
		//print_r( $this->events[$event_id]->get_values())."\n";

		

	}

}
?>
