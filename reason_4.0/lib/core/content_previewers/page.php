<?php

	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'page_previewer';

	class page_previewer extends default_previewer
	{
		function display_entity() // {{{
		{
			$this->start_table();
			
			// iFrame Preview
			if( !$this->_entity->get_value( 'url' ) )
			{
				// iFrame Preview
				reason_include_once( 'function_libraries/URL_History.php' );
				$url = build_URL( $this->_entity->id() );
				if ($url) $this->show_item_default( 'Public View of Page' , '<iframe src="'.$url.'" width="100%" height="400"></iframe>' );
			}
			
			// Everything Else
			$this->show_all_values( $this->_entity->get_values() );
			
			$this->end_table();
		} // }}}
		function show_item_extra_head_content( $field , $value )
		{
			echo '<tr>';
			$this->_row = $this->_row%2;
			$this->_row++;

			echo '<td class="listRow' . $this->_row . ' col1" align="right">&nbsp;<strong>' . prettify_string( $field );
			if( $field != '&nbsp;' ) echo ':';
			echo '</strong></td>';
			echo '<td class="listRow' . $this->_row . ' col2">&nbsp;&nbsp;</td>';
			$value = nl2br(htmlspecialchars($value));
			echo '<td class="listRow' . $this->_row . ' col3" align="left">' . ( ($value OR (strlen($value) > 0)) ? $value : '<em>(No value)</em>' ). '</td>';

			echo '</tr>';
		}
	}
?>
