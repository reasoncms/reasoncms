<?php
	reason_include_once( 'classes/entity.php' );
	$display_handler = 'default_display_handler';
	$GLOBALS['display_name_handlers']['default.php3'] = 'default_display_handler';

	function default_display_handler( $id )
	{
		if( !is_object( $id ) )
			$e = new entity( $id );
		
		return $e->get_value( 'name' );
	}
?>
