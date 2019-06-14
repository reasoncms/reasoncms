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
		function display_entity()
		{
			$this->show_all_values( $this->_entity->get_values() );
			
			$data = $this->_entity->get_gravity_forms_json_and_messages();
			$this->show_item_default( 'gravity_forms_json', '<textarea>'.htmlspecialchars($data['json']).'</textarea>' );
			$this->show_item_default( 'gravity_forms_error_messages', implode( '<br />', $data['messages'] ) );
		}

		function show_item_thor_content( $field , $value )
		{
			$this->show_item_default( $field, htmlspecialchars($value) );
		}
		
	}
?>
