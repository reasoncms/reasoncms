<?php

/*
* Set to true if your instance of Reason is integrated with Kaltura.  This is the master switch.
*/
if(!defined('KALTURA_REASON_INTEGRATED')) define('KALTURA_REASON_INTEGRATED', false);

/*
* This is the base url of your Kaltura instance.  Example: "http://kaltura.its.carleton.edu"
*/
if(!defined('KALTURA_SERVICE_URL')) define('KALTURA_SERVICE_URL', "");

/*
* These three settings can be found in the Kaltura Management Console (KMC).  In 
* Settings->Integration Settings, there is an Account Info pane containing these
* fields.  Note that you want the partner id, not the sub partner id.
*
* KALTURA_PARTNER_ID	integer
* KALTURA_ADMIN_SECRET	string
* KALTURA_USER_SECRET	string
*/
if(!defined('KALTURA_PARTNER_ID')) define('KALTURA_PARTNER_ID', 0);
if(!defined('KALTURA_ADMIN_SECRET')) define('KALTURA_ADMIN_SECRET', "");
if(!defined('KALTURA_USER_SECRET')) define('KALTURA_USER_SECRET', "");


/*
* Setting up Transcoding Profiles
*
* Kaltura integration requires seven specific transcoding profiles to be set up. These settings
* map these profile IDs to PHP constants.
*
* The value of each of these constants should be an integer.
*
* @todo write a script that creates these profiles automatically
*/
if(!defined('KALTURA_DEFAULT_TRANSCODING_PROFILE')) define('KALTURA_DEFAULT_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_AUDIO_TRANSCODING_PROFILE')) define('KALTURA_AUDIO_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_SMALL_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_SMALL_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_MEDIUM_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_MEDIUM_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_LARGE_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_LARGE_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_AUDIO_MP3_SOURCE_TRANSCODING_PROFILE')) define('KALTURA_AUDIO_MP3_SOURCE_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_AUDIO_OGG_SOURCE_TRANSCODING_PROFILE')) define('KALTURA_AUDIO_OGG_SOURCE_TRANSCODING_PROFILE', 0);


/*
* These are the three default heights for the video transcodings.  They should match the three
* heights used when setting up the transcoding profiles in Kaltura.  240, 360, and 480 work well.
*/
if(!defined('MEDIA_WORK_SMALL_HEIGHT')) define('MEDIA_WORK_SMALL_HEIGHT', 240);
if(!defined('MEDIA_WORK_MEDIUM_HEIGHT')) define('MEDIA_WORK_MEDIUM_HEIGHT', 360);
if(!defined('MEDIA_WORK_LARGE_HEIGHT')) define('MEDIA_WORK_LARGE_HEIGHT', 480);

/*
* These bit rates are used to determine which transcoding flavors should be generated
* by kaltura.
*/
if(!defined('KALTURA_MEDIUM_VIDEO_BITRATE')) define('KALTURA_MEDIUM_VIDEO_BITRATE', 600);
if(!defined('KALTURA_LARGE_VIDEO_BITRATE')) define('KALTURA_LARGE_VIDEO_BITRATE', 1050);


?>