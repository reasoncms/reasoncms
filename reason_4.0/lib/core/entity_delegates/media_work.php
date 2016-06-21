<?php

reason_include_once( 'entity_delegates/abstract.php' );

$GLOBALS['entity_delegates']['entity_delegates/media_work.php'] = 'mediaWorkDelegate';

class mediaWorkDelegate extends entityDelegate
{
	function get_display_name()
	{
		if($this->entity->get_value('transcoding_status') == 'converting' || $this->entity->get_value('transcoding_status') == 'finalizing')
			return '<img src="'.REASON_HTTP_BASE_PATH.'ui_images/spinner_16.gif" width="16" height="16" alt="Converting" /> '.$this->entity->get_value('name');
		if($this->entity->get_value('transcoding_status') == 'error')
			return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/error.png" width="16" height="16" alt="Error" /> '.$this->entity->get_value('name');
		if($this->entity->get_value('transcoding_status') && ($images = $this->entity->get_left_relationship('av_to_primary_image')))
		{
			$image = current($images);
			if($image_html = $image->get_image_html('thumbnail',true,true,0.5))
				return $image_html.$this->entity->get_value('name');
		}
		switch($this->entity->get_value('av_type'))
		{
			case 'Audio':
				return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/sound.png" width="16" height="16" alt="Audio" /> '.$this->entity->get_value('name');
			case 'Video':
				return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/television.png" width="16" height="16" alt="Video" /> '.$this->entity->get_value('name');
		}
		return $this->entity->get_value('name');
	}
}