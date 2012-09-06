<?php
/**
 * @package reason
 * @subpackage display_name_handlers
 */
	
/**
 * Register display name handler with Reason
 */
$display_handler = 'reason_media_work_display_name_handler';
$GLOBALS['display_name_handlers']['media_work.php'] = 'reason_media_work_display_name_handler';

if( !defined( 'DISPLAY_HANDLER_MEDIA_WORK_PHP' ) )
{
	define( 'DISPLAY_HANDLER_MEDIA_WORK_PHP',true );

	reason_include_once( 'classes/entity.php' );
	reason_include_once('function_libraries/image_tools.php');

	/**
	 * A display name handler for media works
	 *
	 * Includes a thumbnail of the work's placard image as part of the display name
	 *
	 * @param mixed $id Reason ID or entity
	 * @return string
	 */
	function reason_media_work_display_name_handler( $id )
	{
		if( !is_object( $id ) )
			$e = new entity( $id );
		else $e = $id;
		
		if($e->get_value('integration_library') == 'kaltura' && $e->get_value('transcoding_status') != 'ready')
		{
			if($e->get_value('transcoding_status') == 'converting')
				return '<img src="'.REASON_HTTP_BASE_PATH.'ui_images/spinner_16.gif" width="16" height="16" alt="Converting" /> '.$e->get_value('name');
			if($e->get_value('transcoding_status') == 'error')
				return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/error.png" width="16" height="16" alt="Error" /> '.$e->get_value('name');
		}
		if($images = $e->get_left_relationship('av_to_primary_image'))
		{
			$image = current($images);
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
		switch($e->get_value('av_type'))
		{
			case 'Audio':
				return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/sound.png" width="16" height="16" alt="Audio" /> '.$e->get_value('name');
			case 'Video':
				return '<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/television.png" width="16" height="16" alt="Video" /> '.$e->get_value('name');
			default:
				return $e->get_value('name');
		}
		
	}
}

?>
