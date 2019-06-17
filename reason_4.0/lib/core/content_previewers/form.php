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
			if(!empty($data['messages']))
			{
				$messages = '<ul><li>' . implode( '</li><li>', $data['messages'] ) . '</li></ul>';
			}
			else
			{
				$messages = 'No messages. Import should be clean!';
			}
			$this->show_item_default( 'gravity_forms_export_messages', $messages );
		}

		function show_item_thor_content( $field , $value )
		{
			$this->show_item_default( $field, htmlspecialchars($value) );
		}
		
	}
?>
