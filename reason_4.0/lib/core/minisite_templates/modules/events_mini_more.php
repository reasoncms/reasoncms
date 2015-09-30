<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * include the base class and register the module with Reason
  */
	reason_include_once( 'minisite_templates/modules/events_mini.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'moreMiniEventsModule';

/**
 * A minisite module that creates an events sidebar, and attempts to show more than the basic mini_events module
 */
class moreMiniEventsModule extends miniEventsModule
{
	var $ideal_count = 9;
}
?>
