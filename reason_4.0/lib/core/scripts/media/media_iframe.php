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
reason_include_once('classes/group_helper.php');
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
				/* &&
				$media_work->get_value('transcoding_status') == 'ready' */
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

function display_media($media_work)
{
	if($media_work->get_value('transcoding_status') != 'ready')
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
	$displayer = reason_iframe_get_displayer($media_work);
	$markup = $displayer->_get_embed_markup();
	if ($markup != false)
	{
		$class = $media_work->get_value('av_type').'Wrapper';
		echo '<div class="'.$class.'">'."\n";
		echo $markup;
		echo '</div>'."\n";
	}
	
	/* Mobile safari has a rendering glitch displaying audio in an iframe; the only way we've found
	   to fix this glitch is to embed a non-image as an image in the content. This causes mobile
	   safari to change its rendering mode and properly render the audio player. */
	if($media_work->get_value('av_type') == 'Audio')
		echo '<img src="'.REASON_HTTP_BASE_PATH.'modules/av/notanimage.txt" width="1" height="1" alt="" />';
}

$media_work = reason_iframe_get_media_work();
$valid_hash = reason_iframe_valid_hash($media_work);

$page_state = 'invalid_request';

if ($media_work != false && $valid_hash)
{
	$page_state = 'ok';
	$es = new entity_selector();
	$es->add_type(id_of('group_type'));
	$es->add_right_relationship($media_work->id(), relationship_id_of('av_restricted_to_group'));
	$group = current($es->run_one());

	if (!empty($group))
	{
		$gh = new group_helper();
		$gh->set_group_by_id($group->id());
		if ( $gh->requires_login() ) 
		{
			$username = reason_check_authentication();
			if ($username)
			{
				if (!$gh->is_username_member_of_group($username))
				{
					$page_state = 'unauthorized';
					header('HTTP/1.1 403 Forbidden');
				}
			}
			else
			{
				$page_state = 'authentication_required';
				header('HTTP/1.1 403 Forbidden');
			}
		}
	}
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
	echo '<style>body{margin:0;padding:0;}.AudioWrapper{position:relative;height:46px;}audio{width:100%;position:absolute;bottom:0;}</style>'."\n";
else
	echo '<style>body{background:#777;color:#eee;font-family:Verdana,Arial,Helvetica,sans-serif;font-size:0.8em;margin:0;padding:0.5em;}a{color:#fff}a.signIn{background:#555;padding:0.3em 0.67em;text-decoration:none;}p{margin-top:0;}</style>'."\n";
echo '</head>'."\n";
echo '<body>'."\n";
switch($page_state)
{
	case 'ok':
		display_media($media_work);
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
echo '<script src="'.JQUERY_URL.'"></script>'."\n";
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
});
</script>
';
echo '</body>'."\n";
echo '</html>'."\n";
?>