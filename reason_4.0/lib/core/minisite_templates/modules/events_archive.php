<?php 
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsArchiveModule';

/**
 * A minisite module that shows a chronological listing of all events on the site, 
 * beginning with the oldeest event on the site
 *
 * This module is useful for short-term sites that deal with defined events; when the time frame is
 * over this module acts as an archive of the events that were listed on the site.
 */
class EventsArchiveModule extends EventsModule
{
	var $show_calendar_grid = false;
	var $show_views = false;
	
	function make_reason_calendar_init_array($start_date, $end_date = '', $view = '')
	{
		$array = parent::make_reason_calendar_init_array($start_date, $end_date, $view);
		$array['start_date'] = '1970-01-01';
		$array['view'] = 'all';
		return $array;
	}
	function list_events()
	{
		$msg = '';
		if($this->calendar->contains_any_events())
		{
			$this->events_by_date = $this->calendar->get_all_days();
			/* if($this->rerun_if_empty && empty($this->pass_vars) && empty($this->events_by_date))
			{
				$this->rerun_calendar();
				$this->events_by_date = $this->calendar->get_all_days();
				if(count(current($this->events_by_date)) > 1)
				{
					$msg = '<p>This calendar has no events coming up. Here are the last events available:</p>'."\n";
				}
				else
				{
					$msg = '<p>This calendar has no events coming up. Here is the last event available:</p>'."\n";
				}
				
			} */
			$this->events = $this->calendar->get_all_events();
			$this->show_view_options();
			$this->show_calendar_grid_and_options_bar();
			//$this->show_options_bar();
			//$this->show_navigation();
			//$this->show_calendar_grid();
			//$this->show_focus();
			$this->display_list_title();
			if($this->calendar->get_view() == 'daily' || $this->calendar->get_view() == 'weekly')
				$this->show_months = false;
			if(!empty($this->events_by_date))
			{
				echo $msg;
				/* if($this->calendar->get_start_date() < $this->today && empty($this->request['search']))
				{
					echo '<p>Viewing archive. <a href="'.$this->construct_link(array('start_date'=>'')).'">Reset calendar to today</a></p>';
				} */
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
				$split = (!empty($upcoming) && !empty($archive));
				if(!empty($upcoming))
				{
					if($split)
						echo '<div class="upcoming">'."\n".'<h3 class="upcoming">Upcoming</h3>'."\n";
					foreach($upcoming as $day => $val)
					{
						if ( $this->calendar->get_end_date() && $day > $this->calendar->get_end_date() )
							break;
						$this->show_daily_events( $day );
					}
					if($split)
						echo '</div>'."\n";
				}
				
				if(!empty($archive))
				{
					if($split)
						echo '<div class="archive">'."\n".'<h3 class="archive">Archive</h3>'."\n";
					foreach($archive as $day => $val)
					{
						if ( $this->calendar->get_end_date() && $day > $this->calendar->get_end_date() )
							break;
						$this->show_daily_events( $day );
					}
					if($split)
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
		//$this->show_navigation();
		// $this->show_options_bar();
		if($this->show_icalendar_links)
			$this->show_list_export_links();
		$this->show_feed_link();
		echo '</div>'."\n";
	}
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
