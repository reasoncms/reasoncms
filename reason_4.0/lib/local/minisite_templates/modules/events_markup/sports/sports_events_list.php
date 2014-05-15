<?php
/**
 * Events markup class -- the default list markup
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('minisite_templates/modules/events_markup/interfaces/events_list_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/sports/sports_events_list.php'] = 'sportsEventsListMarkup';
/**
 * Class that generates a list markup for the events module
 */
class sportsEventsListMarkup implements eventsListMarkup
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
		
		$ret .= '<table class="tablesorter">'."\n";
		
		$ret .= '<tr>'."\n";
		$ret .= '<th class="date">Date</th>'."\n";
		$ret .= '<th class="event">Opponent</th>'."\n";
		$ret .= '<th class="location">Location</th>'."\n";
		$ret .= '<th class="timeOrResults">Time/Results</th>'."\n";
		$ret .= '</tr>'."\n";
	
		if($events = $this->bundle->events($this->get_ongoing_display_type()))
		{			
			foreach($events as $day => $times)
			{				
				foreach($times as $time => $events)
				{
					foreach($events as $event)
					{						
						$ret .= $this->bundle->list_item_markup($event, $day, $time);						
					}
				}
			}
		}
		$ret .= '</table>'."\n";
		return $ret;
	}
}