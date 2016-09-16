<?php

reason_include_once('function_libraries/event_tickets.php');
reason_include_once('minisite_templates/modules/events_markup/mini/mini_events_list_item.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/tickets/tickets_events_list_item.php'] = 'ticketsListItemMarkup';

class ticketsListItemMarkup extends miniEventsListItemMarkup
{

	public function get_markup($event, $day, $time)
	{
		$eventId = $event->id();
		$linkForOpenEvents = $this->bundle->event_link($event, $day /* , array("view" => "registration_form") */);
		$linkForOpenEvents .= "#jumpToForm";
		$html = $this->bundle->teaser_image($event, $linkForOpenEvents);

		$formInfo = $this->bundle->get_ticket_info_from_form($event);
		$thisEventsInfo = $formInfo[$eventId];

		$html .= get_ticket_status_html_link($eventId, $thisEventsInfo, $linkForOpenEvents);

		return $html;
	}

}
