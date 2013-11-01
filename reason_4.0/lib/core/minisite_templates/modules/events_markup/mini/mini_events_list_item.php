<?php
/**
 * Events markup class -- the default list item markup for feed/sidebar display
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('minisite_templates/modules/events_markup/interfaces/events_list_item_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/default/events_list_item.php'] = 'miniEventsListItemMarkup';
/**
 * Class that generates a list item markup optimized for feed/sidebar display for the events module
 *
 * This class takes an event and produces markup meant to be used in the events listing
 */
class miniEventsListItemMarkup implements eventsListItemMarkup
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
		$ret = '';
		$link = '';
		$link = $this->bundle->event_link($event->id(), $day);
		$ret .= $this->bundle->teaser_image( $event->id(), $link );
		if($time && 'all_day' != $time)
			$ret .= prettify_mysql_datetime($event->get_value('datetime'), 'g:i a') . ' - ';
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
		return $ret;
	}
}