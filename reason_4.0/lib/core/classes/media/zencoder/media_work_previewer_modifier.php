<?php
require_once(SETTINGS_INC.'media_integration/zencoder_settings.php');
reason_include_once('classes/media/interfaces/media_work_previewer_modifier_interface.php');

/**
 * A class that modifies the given Media Work content previewer for Zencoder-integrated 
 * Media Works.
 */
class ZencoderMediaWorkPreviewerModifier implements MediaWorkPreviewerModifierInterface
{
	/**
	 * The previewer this modifier class will modify.
	 */
	protected $previewer;
	
	/**
	 * The displayer chrome used for the media work preview.
	 */
	protected $displayer_chrome;

	/**
	 * Sets the media work previewer instance.
	 * @param $previewer
	 */
	function set_previewer($previewer)
	{
		$this->previewer = $previewer;
		// set up the displayer chrome
		reason_include_once( 'classes/media/zencoder/displayer_chrome/size_switch.php' );
		$this->displayer_chrome = new ZencoderSizeSwitchDisplayerChrome();
		$this->displayer_chrome->set_media_work($previewer->_entity);
	}
	
	/**
	 * Adds head items such as javascript and css to the previewer.
	 * @param $head_items
	 */
	function set_head_items($head_items)
	{
		$head_items->add_javascript(JQUERY_URL, true);
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/zencoder/media_work_previewer.js');
		$this->displayer_chrome->set_head_items($head_items);
	}
	
	/**
	 * Adds rows of content to the previewer.
	 */
	function display_entity()
	{
		$this->previewer->start_table();
		
		$this->_add_file_preview($this->previewer->_entity);
		$this->_add_embed_code($this->previewer->_entity);
		$vals = $this->previewer->_entity->get_values();
		unset($vals['salt']);
		$this->previewer->show_all_values($vals);
		$this->previewer->end_table();
	}
	
	/**
	 * Adds the preview for the media work to the page.
	 */
	private function _add_file_preview($entity)
	{	
		$this->displayer_chrome->set_media_height('small');
		$this->displayer_chrome->set_google_analytics(false);
		$html_markup = $this->displayer_chrome->get_html_markup();
		if(!empty($html_markup))
		{
			$this->previewer->show_item_default( 'preview', $html_markup);
		}
	}
	
	/**
	 * Adds the embed code field to the page.
	 * @param $entity
	 * @param $displayer
	 */
	private function _add_embed_code($entity)
	{							
		reason_include_once( 'classes/media/zencoder/media_work_displayer.php' );
		$displayer = new ZencoderMediaWorkDisplayer();
		$displayer->set_media_work($entity);
		
		if ($entity->get_value('av_type') == 'Video')
		{
			$displayer->set_height('small');
			$embed_markup_small = $displayer->get_display_markup();
			
			$displayer->set_height('medium');
			$embed_markup_medium = $displayer->get_display_markup();
			
			$displayer->set_height('large');
			$embed_markup_large = $displayer->get_display_markup();

			if (!empty($embed_markup_small))
			{
				$this->_show_embed_item('Small Embedding Code', $embed_markup_small);
				$this->_show_embed_item('Medium Embedding Code', $embed_markup_medium);
				$this->_show_embed_item('Large Embedding Code', $embed_markup_large);
			}
		}
		else
		{
			$displayer->set_height('small');
			$embed_markup_small = $displayer->get_display_markup();
			if (!empty($embed_markup_small))
			{
				$this->_show_embed_item('Audio Embedding Code', $embed_markup_small);
			}
		}
	}
	
	/**
	 * Displays an embed field.
	 */ 
	private function _show_embed_item($field, $value)
	{
		echo '<tr id="'.str_replace(' ', '_', $field).'_Row">';
		$this->previewer->_row = $this->previewer->_row%2;
		$this->previewer->_row++;

		echo '<td class="listRow' . $this->previewer->_row . ' col1">';
		if($lock_str = $this->previewer->_get_lock_indication_string($field))
			echo $lock_str . '&nbsp;';
		echo prettify_string( $field );
		if( $field != '&nbsp;' ) echo ':';
		echo '</td>';
		echo '<td class="listRow' . $this->previewer->_row . ' col2"><input id="'.$field.'Element" type="text" readonly="readonly" size="50" value="'.htmlspecialchars($value).'"></td>';

		echo '</tr>';
	}
}
?>