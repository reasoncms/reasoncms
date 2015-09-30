<?php
/**
 * @package reason
 * @subpackage display_name_handlers
 */
	
/**
 * Register display name handler with Reason
 */
$display_handler = 'reason_field_display_name_handler';
$GLOBALS['display_name_handlers']['field.php'] = $display_handler;

if( !defined( 'DISPLAY_HANDLER_FIELD_PHP' ) )
{
	define( 'DISPLAY_HANDLER_FIELD_PHP',true );

	reason_include_once( 'classes/entity.php' );

	/**
	 * A display name handler for media works
	 *
	 * Includes a thumbnail of the work's placard image as part of the display name
	 *
	 * @param mixed $id Reason ID or entity
	 * @return string
	 */
	function reason_field_display_name_handler( $id )
	{
		if( !is_object( $id ) )
			$e = new entity( $id );
		else $e = $id;
		
		if($tables = $e->get_left_relationship('field_to_entity_table'))
		{
			$table = current($tables);
			return $table->get_value('name').'.'.$e->get_value('name');
		}
		return $e->get_value('name');
	}
}

?>
