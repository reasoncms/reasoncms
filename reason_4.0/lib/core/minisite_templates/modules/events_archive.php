<?php 
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'EventsArchiveModule';

/**
 * A minisite module that shows a chronological listing of all events on the site, 
 * beginning with the oldeest event on the site
 *
 * This module is useful for short-term sites that deal with defined events; when the time frame is
 * over this module acts as an archive of the events that were listed on the site.
 */
class EventsArchiveModule extends EventsModule
{
	var $default_list_chrome_markup = 'minisite_templates/modules/events_markup/archive/archive_events_list_chrome.php';
	
	function make_reason_calendar_init_array($start_date, $end_date = '', $view = '')
	{
		$array = parent::make_reason_calendar_init_array($start_date, $end_date, $view);
		$array['start_date'] = '1970-01-01';
		$array['view'] = 'all';
		return $array;
	}
	function get_today_link()
	{
	}
	function get_archive_toggler()
	{
	}
}
?>
