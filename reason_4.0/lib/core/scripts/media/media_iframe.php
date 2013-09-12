<?php
/*
* Iframe displayer for media works. This is the generalized way of displaying media works in
* Reason. An iframe with the url for this script is given some parameters that tell it to
* display video or audio. Some access control is implemented in here, too. 
*
* Valid request params:
* 	media_work_id				int
*	media_file_id (legacy works)int
*	height						int or 'small', 'medium', 'large'
* 	width						int or 'small', 'medium', 'large'
* 	hash						string
*	autostart					string ('true', 'false')
*	show_controls				string ('true', 'false')
*	disable_google_analytics	string ('true', 'false')
*
* @package reason
* @subpackage scripts
*/

/**
 * Include dependencies
 */
include ('reason_header.php');
require_once(SETTINGS_INC.'media_integration/media_settings.php');
reason_include_once('classes/media/factory.php');
reason_include_once('classes/media_work_helper.php');
reason_include_once('function_libraries/user_functions.php');

/*
 * return the media work entity specified in the request, or false if there is something wrong
 */
function reason_iframe_get_media_work()
{
	if (!empty($_REQUEST['media_work_id']))
	{
		$id = (integer) $_REQUEST['media_work_id'];
		if ($id)
		{
			$media_work = new entity($id);
			if (
				$media_work->get_value('type') == id_of('av')
				&&
				(
					$media_work->get_value('state') == 'Live'
					||
					user_can_edit_site(get_user_id(reason_check_authentication()),get_owner_site_id($id))
				)
			)
			{
				return $media_work;
			}
		}
	}
	return false;
}

// return a MediaWorkDisplayer set to the specified settings.
function reason_iframe_get_displayer($media_work)
{
	$displayer = MediaWorkFactory::media_work_displayer($media_work->get_value('integration_library'));
	if ($displayer)
	{
		$displayer->set_media_work($media_work);
		if (!$media_work->get_value('integration_library') || $media_work->get_value('integration_library') == 'default' && isset($_REQUEST['media_file_id']))
		{
			$media_file_id = (integer) $_REQUEST['media_file_id'];
			$displayer->set_current_media_file($media_file_id);
		}
		if ( !empty($_REQUEST['height']) )
		{
			$size = $_REQUEST['height'];
			if ($size == 'small') $size = MEDIA_WORK_SMALL_HEIGHT;
			elseif ($size == 'medium') $size = MEDIA_WORK_MEDIUM_HEIGHT;
			elseif ($size == 'large') $size = MEDIA_WORK_LARGE_HEIGHT;
			else $size = (integer) $_REQUEST['height'];
			$displayer->set_height($size);
		}
		
		if ( !empty($_REQUEST['width']) )
		{
			$width = (integer) $_REQUEST['width'];
			$displayer->set_width($width);
		}
		
		if (!empty($_REQUEST['autostart']) && $_REQUEST['autostart'])
			$displayer->set_autostart(true);
		else
			$displayer->set_autostart(false);
			
		if (!empty($_REQUEST['show_controls']) && $_REQUEST['show_controls'] == 'false')
		{
			$displayer->set_show_controls(false);
		}
		else
			$displayer->set_show_controls(true);
	}
	return $displayer;
}

// check to see if the hash key in $_REQUEST is equal to the specified media work's hash.
function reason_iframe_valid_hash($displayer)
{
	if ( !empty($_REQUEST['hash']) && $displayer->get_hash() == $_REQUEST['hash'])
		return true;
	else	
		return false;
}

function google_analytics_enabled()
{
	if ( !empty($_REQUEST['disable_google_analytics']) && $_REQUEST['disable_google_analytics'] == 'true')
	{
		return false;
	}
	return true;
}

// echo the html markup used to display the media on this page (which is always inside an
// iframe).
function display_media($media_work, $displayer)
{
	if($media_work->get_value('transcoding_status') && $media_work->get_value('transcoding_status') != 'ready')
	{
		switch($media_work->get_value('transcoding_status'))
		{
			case 'converting':
				$msg = 'This media is being processed. Please check back later.';
				break;
			case 'error':
			default:
				$msg = 'This media cannot be displayed at this time.';
				break;
		}
		echo '<div class="statusMessage">'.$msg.'</div>'."\n";
		return;
	}
	$markup = $displayer->get_embed_markup();
	if ($markup != false)
	{
		$class = $media_work->get_value('av_type').'Wrapper';
		echo '<div class="'.$class.'">'."\n";
		echo $markup;
		/* Add canvas code to assist mobile safari - should only do this to videos*/
		if ($media_work->get_value('av_type') == 'Video') {
			echo '<canvas width="' . $displayer->get_embed_width() . '" height="' . $displayer->get_embed_height() . '"></canvas>';
			/*eg  <canvas width="    634                   " height="    264                    "></canvas>*/
		}
		echo '</div>'."\n";
	}
	
	/* Mobile safari has a rendering glitch displaying audio in an iframe; the only way we've found
	   to fix this glitch is to embed a non-image as an image in the content. This causes mobile
	   safari to change its rendering mode and properly render the audio player. */
	if($media_work->get_value('av_type') == 'Audio')
		echo '<img src="'.REASON_HTTP_BASE_PATH.'modules/av/notanimage.txt" width="1" height="1" alt="" class="nonImage" />';
}

// begin the script
$media_work = reason_iframe_get_media_work();
$displayer = reason_iframe_get_displayer($media_work);
if ($displayer)
{
	$valid_hash = reason_iframe_valid_hash($displayer);
	$page_state = 'invalid_request';
	
	if ($media_work != false && $valid_hash)
	{
		$page_state = 'ok';
		$mwh = new media_work_helper($media_work);
		$username = reason_check_authentication();
		if ( !$mwh->user_has_access_to_media($username) )
		{
			if ($username)
			{
				$page_state = 'unauthorized';
				header('HTTP/1.1 403 Forbidden');
			}
			else
			{
				$page_state = 'authentication_required';
				header('HTTP/1.1 403 Forbidden');
			}
		}
	}
}
else
{
	$page_state = 'invalid_request';
}

echo '<!DOCTYPE html>'."\n";
echo '<html>'."\n";
echo '<head>'."\n";
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
echo '<title>';
switch($page_state)
{
	case 'ok':
		echo strip_tags($media_work->get_value('name'));
		break;
	case 'unauthorized':
		echo 'Access Denied';
		break;
	case 'authentication_required':
		echo 'Sign-In Required';
		break;
	case 'invalid_request':
		echo 'Media Not Found';
		break;
	default:
		echo 'Media';
}
echo '</title>'."\n";
if('ok' == $page_state)
	//echo '<style>body{margin:0;padding:0;}.AudioWrapper{position:relative;height:46px;}audio{width:100%;position:absolute;bottom:0;}video,img{width:100%;height:auto;}</style>'."\n";
	//echo '<style>body{margin:0;padding:0;}.AudioWrapper{position:relative;height:46px;}audio{width:100%;position:absolute;bottom:0;}.VideoWrapper{position:absolute;left:0;right:0;top:0;bottom:0;}video,img{width:100%;height:auto;max-height:100%;}</style>'."\n";
	//below css works
	echo <<<STYLE
<style>
body { margin: 0; 
       padding: 0; } 
.AudioWrapper { position: relative; 
                height: 46px; } 
audio { width: 100%; 
        position: absolute; 
        bottom: 0; } 
.VideoWrapper { position: absolute; 
                left: 0; 
                right: 0; 
                top: 0; 
                bottom: 0; } 
video, 
img { width: 100%; 
      height: auto; 
      max-height: 100%; }
video, 
canvas { top: 0; 
         left: 0; 
         width: 100%; 
         max-width: 100%; 
         height: auto; }
video { height: 100%; 
        position: absolute;
        z-index:10000; } 
body { margin: 0; 
       height: 100%; 
       width: 100%; }
img.nonImage {
	position:relative;
	left:-9999px;
	width:1px;
	height:1px;
}
</style>
STYLE;
else
	echo '<style>body{background:#777;color:#eee;font-family:Verdana,Arial,Helvetica,sans-serif;font-size:0.8em;margin:0;padding:0.5em;}a{color:#fff}a.signIn{background:#555;padding:0.3em 0.67em;text-decoration:none;}p{margin-top:0;}</style>'."\n";
echo '<script src="'.JQUERY_URL.'"></script>'."\n";
if ($media_work->get_value('integration_library') && $media_work->get_value('integration_library') != 'default')
{
	// media api
	echo '<script src="/reason_package/reason_4.0/lib/core/classes/media/api/media_api.js"></script>'."\n";
}
elseif ($media_work->get_value('av_type') == '')
{
	echo '<script src="/reason_package/reason_4.0/lib/core/classes/media/api/media_api_flv.js"></script>'."\n";
}

echo '</head>'."\n";
echo '<body>'."\n";
switch($page_state)
{
	case 'ok':
		display_media($media_work, $displayer);
		break;
	case 'unauthorized':
		echo '<p>Sorry. You do not have permission to view this media.</p>';
		break;
	case 'authentication_required':
		echo '<p>You must be signed in to view this media.';
		
		// Use the parent's URL, since this is just an iframe in all likelihood
		if(!empty($_SERVER['HTTP_REFERER']))
			$dest = $_SERVER['HTTP_REFERER'];
		else
			$dest = get_current_url();
		
		$link = REASON_LOGIN_URL . '?dest_page='.urlencode($dest);
		
		echo ' <a target="_parent" href="'.$link.'" class="signIn">Sign in</a></p>'."\n";
		break;
	case 'invalid_request':
		echo 'The requested media cannot be found.';
		break;
}
/*
echo '<![if gt IE 7]>'."\n";
echo '<script src="'.REASON_PACKAGE_HTTP_BASE_PATH.'fitvids/jquery.fitvids_outside.js"></script>'."\n";
 echo '<script>
 $(document).ready(function(){
 	match = /Android\s+(\d+\.?\d*)[^\d]/.exec(window.navigator.userAgent);
 	if(match)
 	{
 		version = Number(match[1])
 		if(version < 4)
 		{
 			$("video>*:not(object), audio>*:not(object)").remove();
 			$("video>object, audio>object").unwrap();
 			$("object a").attr("target","_blank");
 		}
 	}
 	else
 	{
 		$("body").fitVids();
 	}
 });
 </script>
 ';
echo '<![endif]>'."\n"; */
echo '</body>'."\n";
echo '</html>'."\n";
?>