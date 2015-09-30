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
			echo '<tr>';
			$this->_row = $this->_row%2;
			$this->_row++;

			echo '<td class="listRow' . $this->_row . ' col1" >' . prettify_string( $field );
			if( $field != '&nbsp;' ) echo ':';
			echo '</td>';
			$value = htmlspecialchars($value);
			echo '<td class="listRow' . $this->_row . ' col2">' . ( ($value OR (strlen($value) > 0)) ? $value : '<em>(No value)</em>' ). '</td>';

			echo '</tr>';
		}
	}
?>
