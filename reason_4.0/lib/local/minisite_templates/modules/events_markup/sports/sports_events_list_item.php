<?php
/**
 * Events markup class -- the default list item markup
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once( 'minisite_templates/modules/events.php' );
reason_include_once('minisite_templates/modules/events_markup/interfaces/events_list_item_interface.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/sports/sports_events_list_item.php'] = 'sportsEventsListItemMarkup';
/**
 * Class that generates a list item markup for the events module
 *
 * This class takes an event and produces markup meant to be used in the events listing
 */
class sportsEventsListItemMarkup extends EventsModule implements eventsListItemMarkup
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
		
		if (substr($day, 0, 10) == substr($event->get_value('datetime'), 0, 10)
			&& (luther_is_sports_page(false)
			|| (!luther_is_sports_page(false)
			&& preg_match("/post_to_results/", $event->get_value( 'contact_organization' )))))
		{
			$ret .= '<tr>'."\n";
			$d = mktime(0, 0, 0, substr($day, 5, 2), substr($day, 8, 2), substr($day, 0, 4));
			$lo = substr($event->get_value('last_occurence'), 0, 10);
			if (substr($day, 0, 10) != $lo)
			{
				$e = mktime(0, 0, 0, substr($lo, 5, 2), substr($lo, 8, 2), substr($lo, 0, 4));
				if (date('M', $d) == date('M', $e))
				{
					$ret .= '<td>'.date('M', $d).' '.date('d', $d).'-'.date('d', $e).'</td>'."\n";
				}
				else
				{
					$ret .= '<td>'.date('M', $d).' '.date('d', $d).'-'.date('M', $e).' '.date('d', $e).'</td>'."\n";
				}
			}
			else
			{
		
				$ret .= '<td>'.date('M', $d).' '.date('d', $d).'</td>'."\n";
			}
				
			if (!luther_is_sports_page(false))
			{
				$event_name = ucfirst(preg_replace("|(^.*?)\s\((w?o?m?en)\)$|", "\\2's \\1", $event->get_value('sponsor')))." - ".$event->get_value( 'name' );
			}
			else
			{
				$event_name = $event->get_value( 'name' );
			}
			if(!empty($link))
			{
				$ret .= '<td><a href="'.$this->events_page_url.'?event_id='.$event->id().'&date='.$day.'">'.$event_name.'</a></td>'."\n";
			}
			else
			{
				$ret .= '<td>'.$event_name.'</td>'."\n";
			}
				
			$ret .= '<td>'.$event->get_value( 'location' ).'</td>'."\n";
		
			$ret .= '<td>';
			if (preg_match("/https?:\/\/[A-Za-z0-9_\-\.\/]+/", $event->get_value( 'description' ), $matches))
			{
				$ret .= '<a title="Live stats" href="'. $matches[0] .'">Live stats</a>';
			}
			else if ($event->get_value( 'description' ) != '')
			{
				$ret .= $event->get_value( 'description' );
			}
			else if (substr($event->get_value('datetime'), 11) != '00:00:00')
			{
				$ret .= prettify_mysql_datetime($event->get_value('datetime'), "g:i a" );
			}
			$ret .= luther_video_audio_streaming($event->get_value('id'));
			$ret .= '</td>'."\n";
		
			//echo '<td>'.$event->get_value( 'recurrence' ).'</td>'."\n";
			//echo '<td>'.$event->get_value( 'last_occurence' ).'</td>'."\n";
			//echo '<td>'.$event->get_value( 'datetime' ).'</td>'."\n";
			$ret .= '</tr>'."\n";
					$this->luther_counter--;
		}
		
		return $ret;
	}
	
}