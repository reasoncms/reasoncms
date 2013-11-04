<?php 
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsHybridModule';

/**
 * A minisite module that shows a chronological listing of all events on the site, 
 * with upcoming events in chronological order at the top of the page, and past events in reverse
 * chronological order after the upcoming events.
 *
 * This module is useful for situations that demand an archive to be immediately visible, but also
 * need to highlight the events that are in the future.
 *
 * Note that this module shows all events on the site, regardless of when they happened, which makes
 * it less-than-ideal for sites with an extensive history of events.
 *
 * @author Matt Ryan
 */
class EventsHybridModule extends EventsModule
{
	var $default_list_markup = 'minisite_templates/modules/events_markup/hybrid/hybrid_events_list.php';
	var $default_list_chrome_markup = 'minisite_templates/modules/events_markup/hybrid/hybrid_events_list_chrome.php';
	
	/* function handle_params( $params )
	{
		return parent::handle_params();
	} */
	function make_reason_calendar_init_array($start_date, $end_date = '', $view = '')
	{
		$array = parent::make_reason_calendar_init_array($start_date, $end_date, $view);
		$array['start_date'] = '1970-01-01';
		$array['view'] = 'all';
		return $array;
	}
	/*
	function list_events()
	{
		$msg = '';
		if($this->calendar->contains_any_events())
		{
			$this->events_by_date = $this->calendar->get_all_days();
			$this->events = $this->calendar->get_all_events();
			$this->show_view_options();
			$this->show_calendar_grid_and_options_bar();
			$this->display_list_title();
			if($this->calendar->get_view() == 'daily' || $this->calendar->get_view() == 'weekly')
				$this->show_months = false;
			if(!empty($this->events_by_date))
			{
				echo $msg;
				echo '<div id="events">'."\n";
				$upcoming = array();
				$archive = array();
				foreach($this->events_by_date as $day => $val)
				{
					if($day < $this->today)
					{
						$archive[$day] = $val;
					}
					else
					{
						$upcoming[$day] = $val;
					}
				}
				if(!empty($upcoming))
				{
					
					echo '<div class="upcoming">'."\n";
					if(!empty($archive))
						echo '<h3 class="upcoming">Upcoming</h3>'."\n";
					if($this->params['ongoing_display'] == 'above')
					{
						$this->show_ongoing_events( $this->get_ongoing_event_ids('above') );
					}
					foreach($upcoming as $day => $val)
					{
						$this->show_daily_events( $day );
					}
					if($this->params['ongoing_display'] == 'below')
					{
						$this->show_ongoing_events( $this->get_ongoing_event_ids('below') );
					}
					echo '</div>'."\n";
				}
				if(!empty($archive))
				{
					echo '<div class="archive">'."\n";
					if(!empty($upcoming))
						echo '<h3 class="archive">Archive</h3>'."\n";
					foreach(array_reverse($archive, true) as $day => $val)
					{
						$this->show_daily_events( $day );
					}
					echo '</div>'."\n";
				}
				echo '</div>'."\n";
			}
			else
			{
				$this->no_events_error();
			}
		}
		else
		{
			$this->no_events_error();
		}
		echo '<div class="foot">'."\n";
		if($this->show_icalendar_links)
			$this->show_list_export_links();
		$this->show_feed_link();
		echo '</div>'."\n";
	}
	*/
	function get_ongoing_event_ids($ongoing_display = '')
	{
		if(empty($ongoing_display))
			$ongoing_display = $this->params['ongoing_display'];
		
		$ongoing_ids = array();
		foreach($this->events_by_date as $day => $val)
		{
			if( $day < $this->today )
				continue;
			elseif( $this->calendar->get_end_date() && $day > $this->calendar->get_end_date() )
				break;
			$ongoing_ids = array_merge($ongoing_ids,$val);
		}
		$ongoing_ids = array_unique($ongoing_ids);
		if('above' == $ongoing_display)
		{
			foreach($ongoing_ids as $k => $id)
			{
				if(!$this->event_is_ongoing($this->events[$id]) || $this->events[$id]->get_value('datetime') >= $this->today)
					unset($ongoing_ids[$k]);
			}
		}
		elseif('below' == $ongoing_display)
		{
			foreach($ongoing_ids as $k => $id)
			{
				if(!$this->event_is_ongoing($this->events[$id]) || $this->events[$id]->get_value('datetime') >= $this->today || $this->events[$id]->get_value('last_occurence') <= $this->today)
					unset($ongoing_ids[$k]);
			}
		}
		else
		{
			trigger_error('Unrecognized string passed to get_ongoing_event_ids(): '.$ongoing_display.'. Should be "above" or "below".');
		}
		return $ongoing_ids;
	}
	
	function show_ongoing_events($ids)
	{
		if(!empty($ids))
		{
			echo '<h4>Ongoing</h4>'."\n";
			echo '<ul>'."\n";
			foreach($ids as $id)
			{
				echo '<li>'.$this->show_event_list_item( $id, '', 'through' ).'</li>'."\n";
			}
			echo '</ul>'."\n";
		}
	}
	function show_daily_events( $day ) // {{{
	{
		ob_start();
		foreach ($this->events_by_date[$day] as $event_id)
		{
			$ongoing_type = $this->get_event_ongoing_type_for_day($event_id,$day);
			if( 'middle' == $ongoing_type )
				continue;
			echo '<li class="event">';
			$this->show_event_list_item( $event_id, $day, $ongoing_type );
			echo '</li>'."\n";
		}
		$list_items = ob_get_clean();
		
		if(empty($list_items))
			return;
		
		if($this->show_months == true && ($this->prev_month != substr($day,5,2) || $this->prev_year != substr($day,0,4) ) )
		{
			echo '<h4 class="month">'.prettify_mysql_datetime( $day, 'F Y' ).'</h4>'."\n";
			$this->prev_month = substr($day,5,2);
			$this->prev_year = substr($day,0,4);
		}
		
		if($day == $this->today)
			$today = ' (Today)';
		else
			$today = '';
		echo '<h5 class="day">'.prettify_mysql_datetime( $day, $this->list_date_format ).$today.'</h5>'."\n";
		echo '<ul>';
		echo $list_items;
		echo '</ul>'."\n";
	} // }}}
	function get_today_link()
	{
	}
	function get_archive_toggler()
	{
	}
	function show_date_picker()
	{
	}
}
?>
