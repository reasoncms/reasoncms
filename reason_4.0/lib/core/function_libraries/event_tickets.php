<?php

/**
 * Code here represents simple templated snippets of html to share across 
 * ThorCore, the Reason form tools, and the Events modules.
 * 
 */
function get_pretty_ticketed_event_name($eventId)
{
	$name = "";

	if ($eventId) {
		$event = new Entity($eventId);
		$date = date("M j Y, g:ia", strtotime($event->get_value('datetime')));
		$name = "{$event->get_value('name')}, $date";
	}

	return $name;
}

function get_ticket_status_html_link($eventId, $eventInfo, $link)
{
	$html = "";
	$eventState = $eventInfo['eventState'];
	$eventTitle = get_pretty_ticketed_event_name($eventId);

	if ($eventState == 'open' || !is_array($eventInfo)) {
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
