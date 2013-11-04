<?php
/**
 * Events markup class -- list item markup for schedule view
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('minisite_templates/modules/events_markup/interfaces/events_list_item_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/schedule/schedule_events_list_item.php'] = 'scheduleEventsListItemMarkup';
/**
 * Class that generates a list item markup for the events module in schedule view
 *
 * Note that this view does not produce a time -- it is assumed that the list will be handing the time display.
 */
class scheduleEventsListItemMarkup implements eventsListItemMarkup
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
	 * Get the markup for a given event
	 *
	 * @param object $event
	 * @param string $day mysql-formatted date, e.g. '2013-05-14', or 'ongoing'
	 * @param string $time mysql-formatted time, e.g. '15:30:00', or 'all_day'
	 * @return string markup
	 */
	public function get_markup($event, $day, $time)
	{
		if(empty($this->bundle))
		{
			trigger_error('Call set_bundle() on this object before calling get_markup()');
			return '';
		}
		$ret = '';
		$link = '';
		$link = $this->bundle->event_link($event, $day);
		$ret .= $this->bundle->teaser_image( $event, $link );
		$name = $event->get_value('name');
		if(!empty($link))
			$name = '<a href="'.$link.'">'.$name.'</a>';
		$ret .= $name;
		if('ongoing' == $day)
		{
			if($event->get_value('_ongoing_through_formatted'))
				$ret .= ' <em class="through">(through '.$event->get_value('_ongoing_through_formatted').')</em>';
		}
		elseif($event->get_value('_ongoing_starts') == $day)
		{
			$ret .= ' <span class="begins">begins</span>';
			if($event->get_value('_ongoing_through_formatted'))
				$ret .= ' <em class="through">(through '.$event->get_value('_ongoing_through_formatted').')</em>';
		}
		elseif($event->get_value('_ongoing_ends') == $day)
			$ret .= ' <span class="ends">ends</span>';
		
		if($duration = $this->bundle->prettify_duration($event))
			$ret .= ' <span class="duration">('.$duration.')</span>';
			
		if($event->get_value( 'description' ) || $event->get_value( 'location' ) )
		{
			$ret .= '<ul>'."\n";
			if($event->get_value( 'description' ))
			{
				$ret .= '<li class="description">';
				$ret .= $event->get_value( 'description' );
				$ret .= '</li>'."\n";
			}
			if($event->get_value( 'location' ))
			{
				$ret .= '<li class="location">'.$event->get_value( 'location' ).'</li>'."\n";
			}
			$ret .= '</ul>'."\n";
		}
		
		return $ret;
	}
}