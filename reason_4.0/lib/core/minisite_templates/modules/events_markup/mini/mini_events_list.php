<?php
/**
 * Events markup class -- the default list markup for feed/sidebar display
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('minisite_templates/modules/events_markup/interfaces/events_list_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/mini/mini_events_list.php'] = 'miniEventsListMarkup';
/**
 * Class that generates a list markup for feed/sidebar display in the the events module
 */
class miniEventsListMarkup implements eventsListMarkup
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
			
			if(!empty($events['ongoing']))
			{
				$ret .=  '<div class="ongoingblock">'."\n";
				$ret .=  '<h4>Ongoing</h4>'."\n";
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
				
				$today = ($day == $this->bundle->today()) ? ' (Today)' : '';
				$ret .= '<div class="dayblock" id="dayblock_'.$day.'">'."\n";
				$ret .= '<h4 class="day"><a name="'.$day.'"></a><span class="daySpan">'.prettify_mysql_datetime( $day, 'D' ).'</span><span class="monthSpan">'.prettify_mysql_datetime( $day, ' M' ).'</span><span class="numberSpan">'.prettify_mysql_datetime( $day, ' j' ).$today.'</span></h4>'."\n";
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
		}
		return $ret;
	}
}