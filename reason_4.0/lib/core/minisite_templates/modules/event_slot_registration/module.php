<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
	/**
	 * Include the parent module & Reason db update library
	 */
	reason_include_once( 'minisite_templates/modules/events_verbose.php' );
	reason_include_once( 'function_libraries/admin_actions.php');
	/**
	 * Register the module with Reason
	 */
	$GLOBALS[ '_module_class_names' ][ 'event_slot_registration' ] = 'EventSlotRegistrationModule';
/**
 * A minisite module that allows users to register for events via registration slots
 */
class EventSlotRegistrationModule extends EventsModule
{

	function init( $args = array() )
	{
		trigger_error('The Event Slot Registration module is deprecated -- its functionality is now native to the standard events module. Please update your page types to use the standard events module.');
 		parent::init($args);
	}

}	
	
?>
