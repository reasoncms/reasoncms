<?php
/**
 * Events markup class -- list markup for schedule-style display
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('minisite_templates/modules/events_markup/interfaces/events_list_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/schedule/schedule_events_list.php'] = 'scheduleEventsListMarkup';
/**
 * Class that generates a list markup for the events module, using schedule-style display
 *
 * The class groups events that happen at the same time under a common time heading.
 */
class scheduleEventsListMarkup implements eventsListMarkup
{
	/**
	 * The function bundle
	 * @var object
	 */
	protected $bundle;
	
	/**
	 * Modify the page's head items, if desired
	 * @param object $head_items
	 * @return void
	 */
	public function modify_head_items($head_items)
	{
	}
	/**
	 * Set the function bundle for the markup to use
	 * @param object $bundle
	 * @return void
	 */
	public function set_bundle($bundle)
	{
		$this->bundle = $bundle;
	}
	/**
	 * Tell the module what kind of ongoing event display this list markup does
	 *
	 * Note that this does not change the location of the ongoing events -- it is simply
	 * informative for the module/model. When you make a new markup object you need to make sure
	 * that get_markup() and this function agree.
	 *
	 * @return string 'above', 'below', or 'inline'
	 */
	public function get_ongoing_display_type()
	{
		return 'above';
	}
	/**
	 * Get the list markup
	 *
	 * @return string markup
	 */
	public function get_markup()
	{
		if(empty($this->bundle))
		{
			trigger_error('Call set_bundle() before calling get_markup()');
			return '';
		}
		$ret = '';
		if($events = $this->bundle->events($this->get_ongoing_display_type()))
		{
			$prev_month = '';
			$prev_year = '';
			$show_months = true;
			$calendar = $this->bundle->calendar();
			if(!empty($calendar) && ($calendar->get_view() == 'daily' || $calendar->get_view() == 'weekly') )
			{
				$show_months = false;
			}
			
			if(!empty($events['ongoing']))
			{
				$ret .=  '<div class="ongoingblock">'."\n";
				$ret .=  '<h3>Ongoing</h3>'."\n";
				$ret .=  '<ul class="ongoingEvents">'."\n";
				foreach($events['ongoing'] as $time => $ongoing_events)
				{
					foreach($ongoing_events as $event)
					{
						$ret .=  '<li class="event">';
						$ret .= $this->bundle->list_item_markup($event, 'ongoing', $time);
						$ret .= '</li>'."\n";
					}
				}
				$ret .=  '</ul>'."\n";
				$ret .=  '</div>'."\n";
			}
			
			foreach($events as $day => $times)
			{
				if('ongoing' == $day)
					continue;
				
				if($show_months && ($prev_month != substr($day,5,2) || $prev_year != substr($day,0,4) ) )
				{
					$ret .= '<h3 class="month">'.prettify_mysql_datetime( $day, 'F Y' ).'</h3>'."\n";
					$prev_month = substr($day,5,2);
					$prev_year = substr($day,0,4);
				}
				$today = ($day == $this->bundle->today()) ? ' (Today)' : '';
				$ret .= '<div class="dayblock" id="dayblock_'.$day.'">'."\n";
				$ret .= '<h4 class="day"><a name="'.$day.'"></a>'.prettify_mysql_datetime( $day, 'l, F jS' ).$today.'</h4>'."\n";
				$ret .= '<ul class="dayEvents scheduleDisplay">';
				foreach($times as $time => $events)
				{
					$ret .= '<li class="time_block">'."\n";
					if('all_day' == $time)
						$time_str = 'All Day';
					else
						$time_str = prettify_mysql_datetime( $day.' '.$time, 'g:i a');
					$ret .= '<h5 class="time">'.$time_str.'</h5>'."\n";
					$ret .= '<ul class="time_events">'."\n";
					foreach($events as $event)
					{
						$ret .= '<li class="event">';
						$ret .= $this->bundle->list_item_markup($event, $day, $time);
						$ret .= '</li>'."\n";
					}
					$ret .= '</ul>'."\n";
					$ret .= '</li>'."\n";
				}
				$ret .= '</ul>'."\n";
				$ret .= '</div>'."\n";
			}
		}
		return $ret;
	}
}