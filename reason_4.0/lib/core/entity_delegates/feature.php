<?php

reason_include_once( 'entity_delegates/abstract.php' );

$GLOBALS['entity_delegates']['entity_delegates/feature.php'] = 'featureDelegate';

/**
 * @todo implement methods that help with ingestion of images
 */
class featureDelegate extends entityDelegate
{
	function get_display_name()
	{
		if($images = $this->entity->get_left_relationship('feature_to_image'))
		{
			$image = reset($images);
			if($path = $image->get_image_path('thumbnail') )
			{
				if(file_exists($path))
				{
					if($size = getimagesize($path))
					{
						return '<img src="' . htmlspecialchars($image->get_image_url('thumbnail'), ENT_QUOTES) . '" width="'.round($size[0] / 2).'" height="'.round($size[1] /2 ) . '" alt="' . $image->get_alt_text().'" /> ' . $this->entity->get_value('name');
					}
				}
			}
		}
		elseif($media = $this->entity->get_left_relationship('feature_to_media_work'))
		{
			$m = reset($media);
			$images = $m->get_left_relationship('av_to_primary_image');
			if(!empty($images))
			{
				$image = reset($images);
				if($path = $image->get_image_path('thumbnail'))
				{
					if(file_exists($path))
					{
						if($size = getimagesize($path))
						{
							return '<img src="'.$image->get_image_url(reason_get_image_url($image, 'thumbnail'), ENT_QUOTES).'" width="'.round($size[0] / 2).'" height="'.round($size[1] /2 ).'" alt="'.$image->get_alt_text().'" /> '.$this->entity->get_value('name');
						}
					}
				}
			}
			switch($m->get_value('av_type'))
			{
				case 'Audio':
					return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/sound.png" width="16" height="16" alt="Audio" /> '.$this->entity->get_value('name');
				case 'Video':
					return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/television.png" width="16" height="16" alt="Video" /> '.$this->entity->get_value('name');
			}
		}
		return $this->entity->get_value('name');
	}
}