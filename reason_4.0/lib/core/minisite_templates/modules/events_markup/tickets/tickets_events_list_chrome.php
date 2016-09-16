<?php

reason_include_once('minisite_templates/modules/events_markup/mini/mini_events_list_chrome.php');
$GLOBALS['events_markup']['minisite_templates/modules/events_markup/tickets/tickets_events_list_chrome.php'] = 'ticketsEventListChrome';

class ticketsEventListChrome extends miniEventsListChromeMarkup
{

	public function get_markup()
	{
		$ret = '';
		$ret .= $this->get_section_markup('list');
		return $ret;
	}

}
