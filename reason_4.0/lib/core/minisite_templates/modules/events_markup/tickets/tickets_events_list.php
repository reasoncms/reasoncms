<?php

/**
 * @package reason
 * @subpackage events_markup
 */
/**
 * Include dependencies & register the class
 */
reason_include_once('minisite_templates/modules/events_markup/mini/mini_events_list.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/tickets/tickets_events_list.php'] = 'ticketsEventListMarkup';

/**
 * Class that generates a list markup for events associated to a form for selling/obtaining tickets
 */
class ticketsEventListMarkup extends miniEventsListMarkup
{

	public function get_markup()
	{
		$html = "";
		$events = $this->bundle->events($this->get_ongoing_display_type());
		if ($events) {
			$monthTracker = array();
			foreach ($events as $day => $times) {
				$month = date("F Y", strtotime($day));
				
				if (!array_key_exists($month, $monthTracker)) {
					$html .= "<h4>$month</h4>";
					$monthTracker[$month] = true;
				}

				foreach ($times as $time => $events) {
					foreach ($events as $event) {
						$html .= '<div class="event_with_tickets">';
						$html .= $this->bundle->list_item_markup($event, $day, $time);
						$html .= '</div>';
					}
				}
			}
		} else {
			$html .= "<p>No events to show right now.</p>";
		}
		
		return $html;
	}

}
