<?php
/**
 * @package reason
 * @subpackage display_name_handlers
 */
	
	/**
	 * Include dependencies and register display name handler with Reason
	 */
	reason_include_once( 'classes/entity.php' );
	$display_handler = 'default_display_handler';
	$GLOBALS['display_name_handlers']['default.php3'] = 'default_display_handler';

	/**
	 * The default display name handler
	 *
	 * Display names are a snippet of HTML that code can call to get a "canonical" representation
	 * on a entity's name. These are not commonly used in minisite templates or modules, where
	 * greater control is desired over presentation, but they are used quite often in the Reason
	 * administrative interface. For example, images have a display name handler registered that
	 * includes the thumbnail image along with the name of the entity.
	 *
	 * You can register a display name handler by choosing the file name when editing a type in
	 * reason. Just create a function that takes an entity or ID as its only parameter, register it
	 * as above, and return the display name of the type.
	 *
	 * @param mixed $id A reason ID or entity
	 * @return string
	 */
	function default_display_handler( $id )
	{
		if( !is_object( $id ) )
			$e = new entity( $id );
		
		return $e->get_value( 'name' );
	}
?>
