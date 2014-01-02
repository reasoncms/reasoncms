<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'VerboseEventsModule';

/**
 * A minisite module that includes additional information in listings (inc. description & location)
 *
 * Deprecated, as the events markup framework is the proper way to handle this now
 *
 * Add this to the page type:
 *
 * 'list_item_markup' => 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php'
 *
 * @deprecated 
 */
class VerboseEventsModule extends EventsModule
{
	var $default_list_item_markup = 'minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php';
	function init( $args = array() )
	{
		trigger_error('The events_verbose module is deprecated and will go away in future versions of Reason. Use the events module with "list_item_markup" => "minisite_templates/modules/events_markup/verbose/verbose_events_list_item.php" in the page type instead.');
		parent::init($args);
	}
}
?>
