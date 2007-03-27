<?php
$display_handler = 'event_display_handler';
$GLOBALS['display_name_handlers']['event.php3'] = 'event_display_handler';

if( !defined( 'DISPLAY_HANDLER_EVENT_PHP3' ) )
{
	define( 'DISPLAY_HANDLER_EVENT_PHP3',true );

	reason_include_once( 'classes/entity.php' );

	function event_display_handler( $id )
	{
		if( !is_object( $id ) )
			$e = new entity( $id );
		else $e = $id;
		
		return $e->get_value( 'opponent' ).' at '.$e->get_value( 'site' ).' on '.$e->get_value( 'event_start' );
	}
}

?>
