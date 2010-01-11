<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Register previewer with Reason
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'page_previewer';

	/**
	 * A content previewer for minisite pages
	 */
	class page_previewer extends default_previewer
	{
		function display_entity() // {{{
		{
			$this->start_table();
			
			// iFrame Preview
			if( !$this->_entity->get_value( 'url' ) && $this->_entity->get_value( 'state' ) == 'Live' )
			{
				// iFrame Preview
				reason_include_once( 'function_libraries/URL_History.php' );
				$url = reason_get_page_url( $this->_entity->id() );
				if ($url)
				{
					//$this->show_item_default( 'Public View of Page' , '<iframe src="'.$url.'" width="100%" height="400"></iframe>' );

					// iframe replacement method
					// http://intranation.com/test-cases/object-vs-iframe/
					//  classid="clsid:25336920-03F9-11CF-8FD0-00AA00686F13"
					$this->show_item_default( 'Public View of Page' , '<object type="text/html" data="'.$url.'" class="pageViewer"></object><p><a href="'.$url.'" target="_new">Open page in new window</a></p>');
					//$this->show_item_default( 'Public View of Page' , '<iframe src="'.$url.'" class="pageViewer"></iframe><p><a href="'.$url.'" target="_new">Open page in new window</a></p>');
				}
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

			echo '<td class="listRow' . $this->_row . ' col1">' . prettify_string( $field );
			if( $field != '&nbsp;' ) echo ':';
			echo '</td>';
			$value = nl2br(htmlspecialchars($value));
			echo '<td class="listRow' . $this->_row . ' col2">' . ( ($value OR (strlen($value) > 0)) ? $value : '<em>(No value)</em>' ). '</td>';

			echo '</tr>';
		}
	}
?>
