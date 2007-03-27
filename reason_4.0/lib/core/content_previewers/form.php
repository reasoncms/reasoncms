<?php

	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'formPreviewer';

	class formPreviewer extends default_previewer
	{
		function show_item_thor_content( $field , $value )
		{
			echo '<tr>';
			$this->_row = $this->_row%2;
			$this->_row++;

			echo '<td class="listRow' . $this->_row . ' col1" align="right">&nbsp;<strong>' . prettify_string( $field );
			if( $field != '&nbsp;' ) echo ':';
			echo '</strong></td>';
			echo '<td class="listRow' . $this->_row . ' col2">&nbsp;&nbsp;</td>';
			$value = htmlspecialchars($value);
			echo '<td class="listRow' . $this->_row . ' col3" align="left">' . ( ($value OR (strlen($value) > 0)) ? $value : '<em>(No value)</em>' ). '</td>';

			echo '</tr>';
		}
	}
?>
