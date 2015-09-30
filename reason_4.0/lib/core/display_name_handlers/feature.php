<?php
/**
 * @package reason
 * @subpackage display_name_handlers
 */
	
/**
 * Register display name handler with Reason
 */
$display_handler = 'reason_feature_display_name_handler';
$GLOBALS['display_name_handlers']['feature.php'] = 'reason_feature_display_name_handler';

if( !defined( 'DISPLAY_HANDLER_FEATURE_PHP' ) )
{
	define( 'DISPLAY_HANDLER_FEATURE_PHP',true );

	reason_include_once( 'classes/entity.php' );
	reason_include_once('function_libraries/image_tools.php');

	/**
	 * A display name handler for features
	 *
	 * Includes a thumbnail of one of the feature's images as part of the display name
	 *
	 * @param mixed $id Reason ID or entity
	 * @return string
	 */
	function reason_feature_display_name_handler( $id )
	{
		if( !is_object( $id ) )
			$e = new entity( $id );
		else $e = $id;
		if($images = $e->get_left_relationship('feature_to_image'))
		{
			$image = reset($images);
			if($path = reason_get_image_path($image, 'thumbnail'))
			{
				if(file_exists($path))
				{
					if($size = getimagesize($path))
					{
						return '<img src="'.htmlspecialchars(reason_get_image_url($image, 'thumbnail'), ENT_QUOTES).'" width="'.round($size[0] / 2).'" height="'.round($size[1] /2 ).'" alt="'.reason_htmlspecialchars(strip_tags($image->get_value('description'))).'" /> '.$e->get_value('name');
					}
				}
			}
		}
		elseif($media = $e->get_left_relationship('feature_to_media_work'))
		{
			$m = reset($media);
			$images = $m->get_left_relationship('av_to_primary_image');
			if(!empty($images))
			{
				$image = reset($images);
				if($path = reason_get_image_path($image, 'thumbnail'))
				{
					if(file_exists($path))
					{
						if($size = getimagesize($path))
						{
							return '<img src="'.htmlspecialchars(reason_get_image_url($image, 'thumbnail'), ENT_QUOTES).'" width="'.round($size[0] / 2).'" height="'.round($size[1] /2 ).'" alt="'.reason_htmlspecialchars(strip_tags($image->get_value('description'))).'" /> '.$e->get_value('name');
						}
					}
				}
			}
			switch($m->get_value('av_type'))
			{
				case 'Audio':
					return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/sound.png" width="16" height="16" alt="Audio" /> '.$e->get_value('name');
				case 'Video':
					return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/television.png" width="16" height="16" alt="Video" /> '.$e->get_value('name');
			}
		}
		return $e->get_value('name');
	}
}

?>
