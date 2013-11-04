<?php
/**
 * Events markup class -- verbose list item markup
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('minisite_templates/modules/events_markup/interfaces/events_list_item_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php'] = 'verboseEventsListItemMarkup';
/**
 * Class that generates verbose list item markup for the events module
 *
 * This markup includes event descriptions and location information.
 */
class verboseEventsListItemMarkup implements eventsListItemMarkup
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
		
		$desc_str = '';
		$time_loc_str = '';
		
		if($event->get_value( 'description' ))
		{
			$desc_str = '<li class="description">'.$event->get_value( 'description' ).'</li>'."\n";
		}
		
		$time_loc = array();
		if($time && 'all_day' != $time)
			$time_loc[] = '<span class="time">'.prettify_mysql_datetime($event->get_value('datetime'), 'g:i a').'</span>';
		if($event->get_value( 'location' ))
			$time_loc[] = '<span class="location">'.$event->get_value( 'location' ).'</span>';
		if (!empty($time_loc))
		{
			$time_loc_str = '<li class="timeLocation">'.implode(', ',$time_loc).'</li>'."\n";
		}
		
		if($desc_str || $time_loc_str)
			$ret .= '<ul>'."\n".$desc_str.$time_loc_str.'</ul>'."\n";
		
		if($event->get_value('_inline_editable'))
		{
			$before = '<div class="editable"><div class="editRegion">'."\n";
			$after = ' <a href="'.$event->get_value('_inline_editable_link').'" class="editThis">Edit Event</a></div></div>'."\n";
			$ret = $before.$ret.$after;
		}
		
		return $ret;
	}
}