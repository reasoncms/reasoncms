<?php

	reason_include_once( 'classes/av_display.php' );

	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'av_file_previewer';

	class av_file_previewer extends default_previewer
	{
		function display_entity() // {{{
		{
			$this->start_table();
			
			// Embedded preview
			$avd = new reasonAVDisplay();
			$avd->disable_automatic_play_start();
			$embed_markup = $avd->get_embedding_markup($this->_entity);
			if(!empty($embed_markup))
			{
				$this->show_item_default( 'File Preview' , $embed_markup );
			}
			
			// Everything Else
			$this->show_all_values( $this->_entity->get_values() );
			
			$this->end_table();
		} // }}}
	}
?>
