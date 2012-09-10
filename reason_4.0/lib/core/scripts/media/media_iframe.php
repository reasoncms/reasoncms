<?php
/*
* Iframe displayer for media work.
*
* Valid request params:
* 	media_work_id	int
*	height			int or 'small', 'medium', 'large'
* 	width			int or 'small', 'medium', 'large'
* 	hash			string
*	autostart		string ('true', 'false')
*	show_controls	string ('true', 'false')
*
* @package reason
* @subpackage scripts
*/

/**
 * Include dependencies
 */
include ('reason_header.php');
reason_include_once('classes/media_work_displayer.php');
reason_include_once('function_libraries/user_functions.php');


/*
 * return the media work entity specified in the request, or false if there is something wrong
 */
function reason_iframe_get_media_work()
{
	if ( !empty($_REQUEST['media_work_id']) ) 
	{
		$id = (integer) $_REQUEST['media_work_id'];
		if($id)
		{
			$media_work = new entity($id);
			if(
				$media_work->get_value('type') == id_of('av')
				&&
				(
					$media_work->get_value('state') == 'Live'
					||
					user_can_edit_site(get_user_id(reason_check_authentication()),get_owner_site_id($id))
				)
				&&
				$media_work->get_value('transcoding_status') == 'ready'
			)
			{
				return $media_work;
			}
		}
	}
	return false;
}

// return a MediaWorkDisplayer set to the appropriate height
function reason_iframe_get_displayer($media_work)
{
	$displayer = new MediaWorkDisplayer();
	$displayer->set_media_work($media_work);
	
	if ( !empty($_REQUEST['height']) )
	{
		$size = $_REQUEST['height'];
		if ($size == 'small') $size = MEDIA_WORK_SMALL_HEIGHT;
		elseif ($size == 'medium') $size = MEDIA_WORK_MEDIUM_HEIGHT;
		elseif ($size == 'large') $size = MEDIA_WORK_LARGE_HEIGHT;
		
		$displayer->set_height($size);
	}
	
	if ( !empty($_REQUEST['width']) )
	{
		$displayer->set_width($_REQUEST['width']);
	}
	
	
	if (!empty($_REQUEST['autostart']) && $_REQUEST['autostart'] == 'true')
		$displayer->set_autostart(true);
	else
		$displayer->set_autostart(false);
		
	if (!empty($_REQUEST['show_controls']) && $_REQUEST['show_controls'] == 'false')
	{
		$displayer->set_controls_display(false);
	}
	else
		$displayer->set_controls_display(true);
		
	return $displayer;
}

// check to see if the hash key in $_REQUEST is identical to this media work's hash
function reason_iframe_valid_hash($media_work)
{
	if ( !empty($_REQUEST['hash']) && MediaWorkDisplayer::get_hash($media_work) == $_REQUEST['hash'])
		return true;
	else	
		return false;
}


$media_work = reason_iframe_get_media_work();
$valid_hash = reason_iframe_valid_hash($media_work);

echo '<!DOCTYPE html>'."\n";
echo '<html>'."\n";
echo '<head>'."\n";
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
echo '<title>';
if ($media_work != false && $valid_hash)
	echo strip_tags($media_work->get_value('name'));
else
	echo 'Media Not Found';
echo '</title>'."\n";
echo '<style type="text/css">
body{margin:0;padding:0;}
.AudioWrapper{position:relative;height:46px;}
audio{width:100%;position:absolute;bottom:0;}
</style>'."\n";
echo '</head>'."\n";
echo '<body>'."\n";

if ($media_work != false && $valid_hash)
{
	$displayer = reason_iframe_get_displayer($media_work);
	$markup = $displayer->_get_embed_markup();
	if ($markup != false)
	{
		$class = $media_work->get_value('av_type').'Wrapper';
		echo '<div class="'.$class.'">'."\n";
		echo $markup;
		echo '</div>'."\n";
	}
	
	$media_files = $displayer->get_media_files();
	$first = current($media_files);
	$second = next($media_files);
	
	echo '<a class="flavor_info" mime_type="'.$first->get_value('mime_type').'" url="'.$first->get_value('url').'"></a>'."\n";
	echo '<a class="flavor_info" mime_type="'.$second->get_value('mime_type').'" url="'.$second->get_value('url').'"></a>'."\n";
	
	/* Mobile safari has a rendering glitch displaying audio in an iframe; the only way we've found
	   to fix this glitch is to embed a non-image as an image in the content. This causes mobile
	   safari to change its rendering mode and properly render the audio player. */
	if($media_work->get_value('av_type') == 'Audio')
		echo '<img src="'.REASON_HTTP_BASE_PATH.'modules/av/notanimage.txt" width="1" height="1" alt="" />';
}
else
{
	echo 'The requested media cannot be fetched.';
}

echo '</body>'."\n";
echo '</html>'."\n";
?>