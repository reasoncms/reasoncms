<?php
/**
 * Events markup class -- the list markup for a hybrid view of events
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('minisite_templates/modules/events_markup/interfaces/events_list_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/hybrid/hybrid_events_list.php'] = 'hybridEventsListMarkup';
/**
 * Class that generates a hybrid list markup for the events module
 *
 * This list markup class splits event occurrences in 'upcoming' and 'archived' sets, and displays
 * each under its own heading.
 */
class hybridEventsListMarkup implements eventsListMarkup
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
	 * Get the list chrome markup
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
			
			$upcoming = array();
			$archive = array();
			foreach($events as $day => $times)
			{
				if('ongoing' == $day)
					$upcoming[$day] = $times;
				elseif($day < $this->bundle->today())
					$archive[$day] = $times;
				else
					$upcoming[$day] = $times;
			}
			if(!empty($upcoming))
			{
				$ret .='<div class="upcoming">'."\n";
				if(!empty($archive))
					$ret .= '<h3 class="upcoming">Upcoming</h3>'."\n";
				if(!empty($upcoming['ongoing']))
				{
					$ret .=  '<div class="ongoingblock">'."\n";
					$ret .=  '<h4>Ongoing</h4>'."\n";
					$ret .=  '<ul class="ongoingEvents">'."\n";
					foreach($upcoming['ongoing'] as $time => $events)
					{
						foreach($events as $event)
						{
							$ret .=  '<li class="event">';
							$ret .= $this->bundle->list_item_markup($event, 'ongoing', $time);
							$ret .= '</li>'."\n";
						}
					}
					$ret .=  '</ul>'."\n";
					$ret .=  '</div>'."\n";
				}
				foreach($upcoming as $day => $times)
				{
					if('ongoing' == $day)
						continue;
					if($prev_month != substr($day,5,2) || $prev_year != substr($day,0,4) )
					{
						$ret .= '<h4 class="month">'.prettify_mysql_datetime( $day, 'F Y' ).'</h4>'."\n";
						$prev_month = substr($day,5,2);
						$prev_year = substr($day,0,4);
					}
					$today = ($day == $this->bundle->today()) ? ' (Today)' : '';
					$ret .= '<div class="dayblock" id="dayblock_'.$day.'">'."\n";
					$ret .= '<h5 class="day"><a name="'.$day.'"></a>'.prettify_mysql_datetime( $day, 'l, F jS' ).$today.'</h5>'."\n";
					$ret .= '<ul class="dayEvents">';
					foreach($times as $time => $events)
					{
						foreach($events as $event)
						{
							$ret .= '<li class="event">';
							$ret .= $this->bundle->list_item_markup($event, $day, $time);
							$ret .= '</li>'."\n";
						}
					}
					$ret .= '</ul>'."\n";
					$ret .= '</div>'."\n";
				}
				$ret .= '</div>'."\n";
			}
			$prev_month = '';
			$prev_year = '';
			if(!empty($archive))
			{
				$ret .= '<div class="archive">'."\n";
				if(!empty($upcoming))
					$ret .= '<h3 class="archive">Archive</h3>'."\n";
				foreach(array_reverse($archive, true) as $day => $times)
				{
					if($prev_month != substr($day,5,2) || $prev_year != substr($day,0,4) )
					{
						$ret .= '<h4 class="month">'.prettify_mysql_datetime( $day, 'F Y' ).'</h4>'."\n";
						$prev_month = substr($day,5,2);
						$prev_year = substr($day,0,4);
					}
					$ret .= '<div class="dayblock" id="dayblock_'.$day.'">'."\n";
					$ret .= '<h5 class="day"><a name="'.$day.'"></a>'.prettify_mysql_datetime( $day, 'l, F jS' ).'</h5>'."\n";
					$ret .= '<ul class="dayEvents">';
					foreach($times as $time => $events)
					{
						foreach($events as $event)
						{
							$ret .= '<li class="event">';
							$ret .= $this->bundle->list_item_markup($event, $day, $time);
							$ret .= '</li>'."\n";
						}
					}
					$ret .= '</ul>'."\n";
					$ret .= '</div>'."\n";
				}
				$ret .= '</div>'."\n";
			}
		}
		return $ret;
	}
}