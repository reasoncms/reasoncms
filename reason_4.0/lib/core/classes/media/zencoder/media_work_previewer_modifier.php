<?php
require_once(SETTINGS_INC.'media_integration/zencoder_settings.php');
reason_include_once('classes/media/interfaces/media_work_previewer_modifier_interface.php');

/**
 * A class that modifies the given Media Work content previewer for Zencoder-integrated 
 * Media Works.
 */
class ZencoderMediaWorkPreviewerModifier implements MediaWorkPreviewerModifierInterface
{
	
	private static $storage_class;

	/**
	 * Zencoder integration has been designed to work with either Amazon S3 or Reason file
	 * storage.
	 */
	public static function get_storage_class()
	{
		if (!self::$storage_class)
		{
			self::$storage_class = self::_get_storage_class();
		}
		return self::$storage_class;
	}

	private static function _get_storage_class()
	{
		if (strcasecmp(ZENCODER_FILE_STORAGE_OPTION, 'reason') == 0) 
		{
			reason_include_once('classes/media/media_file_storage/reason_file_storage.php');
			return new ReasonMediaFileStorageClass();
		}
		elseif (strcasecmp(ZENCODER_FILE_STORAGE_OPTION, 's3') == 0)
		{
			reason_include_once('classes/media/media_file_storage/s3_file_storage.php');
			return new S3MediaFileStorageClass();
		}
		else
		{
			trigger_error('Invalid storage option for Zencoder: '.ZENCODER_FILE_STORAGE_OPTION);
			return false;
		}
	}	
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
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/zencoder/media_work_previewer.js?v=2');
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
		$this->_add_original_link($this->previewer->_entity);
		$this->_add_file_links($this->previewer->_entity);
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
	
	protected function check_for_url_access($entity)
	{
		if(empty($this->previewer->admin_page))
			return false;
		
		$owner = $entity->get_owner();
		
		if(empty($owner))
			return false;
		
		if($owner->id() != $this->previewer->admin_page->site_id)
			return false;
		
		return true;
	}
	
	/**
	 * Displays an embed field.
	 */ 
	private function _show_embed_item($field, $value)
	{
		echo '<tr id="'.strtolower(str_replace(' ', '_', $field)).'_preview_field">';
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

	private function _add_file_links($entity)
	{
		if(!$this->check_for_url_access($entity))
			return;
		
		$es = new entity_selector();
 		$es->add_type(id_of('av_file'));
 		$es->add_right_relationship($entity->get_value('id'), relationship_id_of('av_to_av_file'));
 		$media_files = $es->run_one();
 		
 		foreach($media_files as $media_file)
 		{
 			if($url = $media_file->get_value('url'))
 			{
 				$name_parts = explode(' ',$media_file->get_value('name'));
 				$element_name = array_pop($name_parts);
 				if($entity->get_value('av_type') == 'Video')
 				{
 					$element_name .= '_'.array_pop($name_parts);
 				}
 				$url = '<a href="'.reason_htmlspecialchars($url).'">'.$url.'</a>';
 				$this->previewer->show_item_default( $element_name, $url);
 			}
 		}
	}

	private function _add_original_link($entity)
	{
		if(!$this->check_for_url_access($entity))
			return;
		
		$storage_class = self::get_storage_class();
		$original = $entity->get_value('original_filename');		
		$url = $storage_class->get_base_url().$storage_class->get_path(false, $original, $entity, 'original');	
		$url = '<a href="'.reason_htmlspecialchars($url).'">'.$url.'</a>';
		$this->previewer->show_item_default( 'original_upload', $url);

	}
}
?>
