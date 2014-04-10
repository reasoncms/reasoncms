<?php

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
