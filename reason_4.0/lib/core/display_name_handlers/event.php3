<?php
/**
 * @package reason
 * @subpackage display_name_handlers
 */
	
/**
 * Register display name handler with Reason
 */
$display_handler = 'event_display_handler';
$GLOBALS['display_name_handlers']['event.php3'] = 'event_display_handler';

if( !defined( 'DISPLAY_HANDLER_EVENT_PHP3' ) )
{
	define( 'DISPLAY_HANDLER_EVENT_PHP3',true );

	reason_include_once( 'classes/entity.php' );

	/**
	 * A display name handler for events
	 *
	 * This seems to be Carleton-specific code. Why is it in the core?
	 */
	function event_display_handler( $id )
	{
		if( !is_object( $id ) )
			$e = new entity( $id );
		else $e = $id;
		
		return $e->get_value( 'opponent' ).' at '.$e->get_value( 'site' ).' on '.$e->get_value( 'event_start' );
	}
}

?>
