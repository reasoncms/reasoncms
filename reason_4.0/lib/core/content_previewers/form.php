<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Register previewer with Reason
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'formPreviewer';

	/**
	 * A previewer for Reason forms
	 */
	class formPreviewer extends default_previewer
	{
		function show_item_thor_content( $field , $value )
		{
			$this->show_item_default( $field, htmlspecialchars($value) );
		}
	}
?>
