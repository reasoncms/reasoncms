<?php
require_once(SETTINGS_INC.'media_integration/vimeo_settings.php');
reason_include_once('classes/media/interfaces/media_work_previewer_modifier_interface.php');

/**
* A class that modifies the given Media Work content previewer for Vimeo-integrated 
* Media Works.
*/
class VimeoMediaWorkPreviewerModifier implements MediaWorkPreviewerModifierInterface
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
		reason_include_once( 'classes/media/vimeo/displayer_chrome/size_switch.php' );
		$this->displayer_chrome = new VimeoSizeSwitchDisplayerChrome();
		$this->displayer_chrome->set_media_work($previewer->_entity);
	}
	
	/**
	* Adds head items such as javascript and css to the previewer.
	* @param $head_items
	*/
	function set_head_items($head_items)
	{
		$head_items->add_javascript(JQUERY_URL, true);
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/vimeo/media_work_previewer.js');
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
		
		$this->previewer->show_all_values( $this->previewer->_entity->get_values() );
	}
	
	/**
	* Adds the preview for the media work to the page.
	*/
	private function _add_file_preview($entity)
	{	
		$this->displayer_chrome->set_media_height('small');
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
		reason_include_once( 'classes/media/vimeo/media_work_displayer.php' );
		$displayer = new VimeoMediaWorkDisplayer();
		$displayer->set_media_work($entity);
		
		$displayer->set_height('small');
		$embed_markup_small = $displayer->get_display_markup();
		
		$displayer->set_height('medium');
		$embed_markup_medium = $displayer->get_display_markup();
		
		$displayer->set_height('large');
		$embed_markup_large = $displayer->get_display_markup();

		if (!empty($embed_markup_small))
		{
			$this->_show_embed_item('Small Embedding Code', $embed_markup_small);
		}
		if (!empty($embed_markup_medium))
		{
			$this->_show_embed_item('Medium Embedding Code', $embed_markup_medium);
		}
		if (!empty($embed_markup_large))
		{
			$this->_show_embed_item('Large Embedding Code', $embed_markup_large);
		}
	}
	
	/**
	* Displays an embed field.
	*/
	private function _show_embed_item($field, $value)
	{
		$this->previewer->show_item_default( $field , '<input id="'.$field.'Element" type="text" readonly="readonly" size="50" value="'.htmlspecialchars($value).'">' );
	}
}