<?php

/**
 * Code here represents simple templated snippets of html to share across 
 * ThorCore, the Reason form tools, and the Events modules.
 */

/**
 * Get event title/name string in the format "Name, Date" to list
 * events
 * 
 * @param int $eventId event entity id
 * @return string string with the event name & date, or empty string if
 *     no valid id passed
 */
function get_pretty_ticketed_event_name($eventId)
{
	$name = "";

	if ($eventId) {
		$event = new Entity($eventId);
		$date = date("l, M j Y, g:ia", strtotime($event->get_value('datetime')));
		$name = "{$event->get_value('name')}, $date";
	}

	return $name;
}

/**
 * Create a link to a ticketed event registration form
 * 
 * Mixes together the pretty title & current event capacity info
 * and makes an active link or a message that says "sold out" or the appropriate
 * condition for the event instead
 * 
 * @param int $eventId event entity id
 * @param array $eventInfo array of thor form info + live event status calculations,
 *     see ThorFormModel::event_tickets_get_all_event_seat_info().
 *     Info here is used to determine if to use $link or provide a event closed message instead
 * @param string $link the full url to where a clickable event title should go
 * @return array html link for the event given its current status
 */
function get_ticket_status_html_link($eventId, $eventInfo, $link)
{
	$html = "";
	$eventState = $eventInfo['eventState'];
	$eventTitle = get_pretty_ticketed_event_name($eventId);

	if ($eventState == 'open' || (!is_array($eventInfo) || empty($eventInfo))) {
		$html .= "Tickets for <a href='$link'>$eventTitle</a><br>\n";
	} else {
		$key_for_closed = $eventInfo['eventStateReason'];
		if ($key_for_closed == "max_tickets_reached") {
			$reason_for_closed = "SOLD OUT";
		} else if ($key_for_closed == "close_date_passed") {
			$reason_for_closed = "CLOSED";
		}
		$html .= "Tickets for $eventTitle<strong>&mdash;$reason_for_closed</strong><br>\n";
	}

	return $html;
}
