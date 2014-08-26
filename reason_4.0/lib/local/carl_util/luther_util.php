<?php

reason_include_once( 'function_libraries/root_finder.php');

function get_luther_spotlight()
// return array containing spotlight information or '' if spotlight doesn't exist on this minisite
{
	$spotlight = luther_get_publication_unique_name("spotlights");
	if (id_of($spotlight, true, false))
	{
		return array( // Spotlights
			'module' => 'publication',
			'related_publication_unique_names' => $spotlight,
			'related_mode' => 'true',
			//'related_title' => '',
			'related_order' => 'random',
			'max_num_items' => 1,
			'markup_generator_info' =>array(
				'list_item' =>array (
					'classname' => 'SpotlightListItemMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/list_item_markup_generators/spotlight_related.php'
				),
				'list' =>array (
					'classname' => 'RelatedListHTML5SpotlightMarkupGenerator',
					'filename' =>'minisite_templates/modules/publication/publication_list_markup_generators/related_list_html5_spotlight.php'
				),
			),
		);
	}
	else
	{
		return '';
	}
}

function luther_get_publication_unique_name($s)
// allows another minisite to use a popular template like music, alumni, or giving
// by filling in an appropriate headline or spotlight unique publication name
// given the url for a particular minisite landing page (e.g. /music, /kwlc).
// The landing page must be at the root level of the luther site.
// $s is either "headlines" or "spotlights"
// e.g. /reslife becomes "headlines_reslife" or "spotlights_reslife"
{
	$url = get_current_url();
	$url = preg_replace("|\-|", "", $url);   // remove hypens
	if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/([A-Za-z0-9_]+)\/?/", $url, $matches))
	{
		return $s . "_" . $matches[1];
	}
	return '';
}

function luther_is_sports_page()
// checks if url has "/sports" at the root level
{
	$url = get_current_url();
	return preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/sports\/?/", $url);
}

function luther_video_audio_streaming($event_id, $imgVideo = null, $imgAudio = null)
// append video and audio images if video/audio streaming categories are present for an event
// by default uses fontawesome to display video and audio icons--pass in an image to override
{
	$es = new entity_selector();
	$es->description = 'Selecting categories for event';
	$es->add_type( id_of('category_type'));
	$es->add_right_relationship( $event_id, relationship_id_of('event_to_event_category') );
	$cats = $es->run_one();
	$vstream = '';
	$astream = '';
	foreach( $cats AS $cat )
	{
		if ($cat->get_value('name') == 'Video Streaming')
		{
			if ($imgVideo != null)
			{
				$vstream = '<a title="Video Streaming" href="http://client.stretchinternet.com/client/luther.portal"><img class="video_streaming" src="' . $imgVideo .'" alt="Video Streaming"></a>';
			}
			else
			{
				$vstream = '<a title="Video Streaming" href="http://client.stretchinternet.com/client/luther.portal"><i class="fa fa-video-camera fa-fw"></i></a>';
			}
		}
		if ($cat->get_value('name') == 'Audio Streaming')
		{
			if ($imgAudio != null)
			{
				$astream = '<a title="Audio Streaming" href="http://www.luther.edu/kwlc/"><img class="audio_streaming" src="' . $imgAudio .'" alt="Audio Streaming"></a>';
			}
			else
			{
				$astream = '<a title="Audio Streaming" href="http://www.luther.edu/kwlc/"><i class="fa fa-headphones fa-fw"></i></a>';
			}
		}
	}
	return $astream . $vstream;
}

function luther_get_event_title($include_site_name = true)
// event title is based on the current root level minisite
{
	if ($include_site_name)
	{
		$url = get_current_url();
		$url = preg_replace("|\-|", " ", $url); // replace hypen with space
		if (preg_match("/^https?:\/\/[A-Za-z0-9_\.]+\/([A-Za-z0-9_]+)\/?/", $url, $matches))
		{
			return 'Upcoming ' . ucfirst($matches[1]) . ' Events';
		}
	}
	return 'Upcoming Events';
}

function luther_get_related_publication($max_num_items = 3)
// set up the related publication template for landing pages
{
	return array(
		'module' => 'publication',
		'markup_generator_info' => array(
			'list_item' => array(
					'classname' => 'MinimalListItemMarkupGenerator',
					'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/minimal.php',
			)
		),
		'related_mode' => true,
		'related_title' => '',
		'max_num_items' => $max_num_items,
	);
}

function luther_get_image_url($image)
// if the image is not found on the local server at WEB_PHOTOSTOCK followed by the image name
// try to find the image url on www.luther.edu
{
	// if images not found locally try pulling from www
	if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $image))
	{
		$image = (on_secure_page()) ? "https://www.luther.edu" . $image : "http://www.luther.edu" . $image;
	}
	return $image;
}

function luther_is_local_ip()
// determine if ip address is luther college or Decorah area
// used for ReachLocal remarketing pixel on admissions site
{
	if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' // localhost
		// private
		|| preg_match("/^(10\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(172\.(1[6-9]|2[0-9]|3[01])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(192\.168\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Luther Campus
		|| preg_match("/^(192\.203\.196\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(198\.133\.77\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(209\.56\.59\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(74\.207\.(3[2-9]|4[0-9]|5[0-9]|6[0-3])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Decorah: Go to http://www.my-ip-address-is.com/city/Iowa/Decorah-IP-Addresses
		|| preg_match("/^(65\.116\.8[89]\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(65\.166\.58\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(66\.43\.231\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(66\.43\.252\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(67\.54\.189\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(67\.128\.219\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(69\.66\.77\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(72\.166\.100\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(75\.175\.212\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.17\.36\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.19\.[49]6\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.19\.232\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(66\.43\.252\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(199\.120\.71\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(204\.248\.125\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.243\.127\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.246\.174\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(207\.165\.178\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(207\.177\.54\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(209\.152\.65\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(216\.51\.150\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(216\.161\.207\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(216\.248\.94\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Calmar: Go to http://www.my-ip-address-is.com/city/Iowa/Calmar-IP-Addresses
		|| preg_match("/^(4\.252\.133\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(199\.201\.208\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.221\.68\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(207\.28\.22\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(207\.165\.228\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Cresco: Go to http://www.my-ip-address-is.com/city/Iowa/Cresco-IP-Addresses
		|| preg_match("/^(4\.158\.16\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(4\.158\.28\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(63\.86\.22\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(67\.224\.57\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(69\.66\.22\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(71\.7\.44\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.19\.105\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(173\.22\.137\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(204\.248\.127\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(208\.161\.56\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Ossian: Go to http://www.my-ip-address-is.com/city/Iowa/Ossian-IP-Addresses
		|| preg_match("/^(207\.28\.13\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Waukon: Go to http://www.my-ip-address-is.com/city/Iowa/Waukon-IP-Addresses
		|| preg_match("/^(75\.167\.203\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(216\.51\.201\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// West Union: Go to http://www.my-ip-address-is.com/city/Iowa/West+Union-IP-Addresses
		|| preg_match("/^(205\.221\.67\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Harmony: Go to http://www.my-ip-address-is.com/city/Minnesota/Harmony-IP-Addresses
		|| preg_match("/^(12\.157\.197\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(204\.248\.121\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Mabel: Go to http://www.my-ip-address-is.com/city/Minnesota/Mabel-IP-Addresses
		|| preg_match("/^(204\.248\.126\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.243\.117\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		// Spring Grove: Go to http://www.my-ip-address-is.com/city/Minnesota/Spring+Grove-IP-Addresses
		|| preg_match("/^(204\.248\.117\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(204\.248\.124\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(205\.243\.121\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR'])
		|| preg_match("/^(208\.74\.240\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5]))$/", $_SERVER['REMOTE_ADDR']))
	{
		return true;
	}
	return false;
}

function luther_is_mobile_device()
// returns true if browsing with mobile device, otherwise false
// see http://detectmobilebrowsers.com/ for a list of recent mobile browsers
{
	return (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$_SERVER['HTTP_USER_AGENT'])
			|| preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($_SERVER['HTTP_USER_AGENT'],0,4)));
}

function get_directory_images($folder)
// given a folder, returns an array of all image files in the folder
{
	$extList = array();
	$extList['gif'] = 'image/gif';
	$extList['jpg'] = 'image/jpeg';
	$extList['jpeg'] = 'image/jpeg';
	$extList['png'] = 'image/png';

	$handle = opendir($folder);
	while (false !== ($file = readdir($handle)))
	{
		$file_info = pathinfo($file);
		if (isset($extList[strtolower($file_info['extension'])]))
		{
			$fileList[] = $file;
		}
	}
	closedir($handle);
	return $fileList;
}

function google_analytics()
{
	if (!preg_match("/^www.luther.edu$/", REASON_HOST, $matches))
	{
		echo '<!-- '. REASON_HOST.': google analytics code goes here on production servers -->'."\n";
		return;
	}

	echo '<script type="text/javascript">'."\n";

	echo 'var _gaq = _gaq || [];'."\n";
	echo "_gaq.push(['_setAccount', 'UA-129020-8']);"."\n";
	echo "_gaq.push(['_setDomainName', 'luther.edu']);"."\n";
	echo "_gaq.push(['_setAllowLinker', true]);"."\n";
	echo "_gaq.push(['_trackPageview']);"."\n";

	echo '(function() {'."\n";
	echo "var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;"."\n";
	echo "ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';"."\n";
	echo "var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);"."\n";
	echo '})();'."\n";

	echo '</script>'."\n";
}

function emergency_preempt()
// Display one or more site-wide preemptive emergency messages
// if one or more text blurbs are placed on the page /preempt
{

	$site_id = get_site_id_from_url("/preempt");
	$page_id = root_finder( $site_id );   // see 'lib/core/function_libraries/root_finder.php'

	$es = new entity_selector();
	$es->add_type(id_of('text_blurb'));
	$es->add_right_relationship($page_id, relationship_id_of('minisite_page_to_text_blurb'));
	$result = $es->run_one();

	if ($result == null)
	{
		return;
	}

	echo '<div id="emergencyPreempt" class="emergency flash-notice">'."\n";
	echo '<div class="callout callout-danger">'."\n";
	foreach( $result AS $id => $page )
	{
		echo $page->get_value('content')."\n";
	}
	echo '</div>'."\n";
	echo '</div>'."\n";
}

function handle_ie8()
// if browser is ie 6-8 or display no longer supported message
// change regex when version 16 comes along
{
	if(preg_match('/(?i)msie [6-8]/',$_SERVER['HTTP_USER_AGENT']))
	{
		echo '<div id="emergencyPreempt" class="flash-notice">'."\n";
		echo '<div class="callout callout-warning ie8-alert">'."\n";
		echo 'Browser support for this version of Internet Explorer is no longer supported. Please upgrade to IE 9 or newer.'."\n";
		echo '</div>'."\n";
		echo '</div>  <!-- class="flash-notice"-->'."\n";
	}

}

function luther_shorten_string($text, $length, $append)
// shorten a string called $text to a word boundary if longer than $length.
// append a string to the end (like " ..." or "Read more...")
{
	if (strlen($text) > $length)
	{
		for ($i = $length; $text[$i] != ' '; $i--);
		$text = substr($text, 0, $i) . $append;
	}
	return $text;
}
