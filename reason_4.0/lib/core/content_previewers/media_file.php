<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
/**
 * Include dependencies & register previewer with Reason
 */
reason_include_once( 'classes/av_display.php' );
reason_include_once( 'function_libraries/image_tools.php' );

$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'av_file_previewer';

/**
 * A content previewer for media files
 *
 */
class av_file_previewer extends default_previewer
{
	function display_entity()
	{
		$this->start_table();
		
		// Embedded preview
		$avd = new reasonAVDisplay();
		$avd->disable_automatic_play_start();
		if($image_info = reason_get_media_placard_image_info($this->_entity))
		{
			$avd->set_placard_image($image_info['url']);
			$avd->set_placard_image_dimensions($image_info['width'], $image_info['height']);
		}
		$embed_markup = $avd->get_embedding_markup($this->_entity);
		if(!empty($embed_markup))
		{
			$this->show_item_default( 'File Preview' , $embed_markup );
			if($embed_markup == strip_tags($embed_markup, REASON_DEFAULT_ALLOWED_TAGS) )
			{
				$this->show_item_default( 'Embed Code', '<textarea rows="7">'.htmlspecialchars($embed_markup).'</textarea>' );
			}
			else
			{
				$this->show_item_default( 'Embed Code', 'Not available<div class="smallText">(The code used to embed '.$this->_entity->get_value('media_format').' files may not be accepted in a Reason content area.)</div>' );
			}
		}
		$link_url = REASON_HTTP_BASE_PATH.'displayers/av_display.php?id='.htmlspecialchars($this->_entity->id());
		
		$this->show_item_default( 'Link','<a href="'.$link_url.'" target="_blank">Link to video</a>');
		
		// Everything Else
		$this->show_all_values( $this->_entity->get_values() );
		
		$this->end_table();
	}
	function show_item_url( $field , $value )
	{
		$value = '<a href="'.reason_htmlspecialchars($value).'">'.$value.'</a>';
		$this->show_item_default( $field , $value );
	}
}
?>
