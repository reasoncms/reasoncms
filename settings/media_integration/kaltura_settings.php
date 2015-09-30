<?php

/*
* Set to true if your instance of Reason is integrated with Kaltura.  This is the master switch.
*/
if(!defined('KALTURA_REASON_INTEGRATED')) define('KALTURA_REASON_INTEGRATED', false);

/*
* This is the base url of your Kaltura instance.  Example: "http://kaltura.its.carleton.edu"
*/
if(!defined('KALTURA_SERVICE_URL')) define('KALTURA_SERVICE_URL', '');

/*
 * This defines whether Kaltura has HTTPS on with a valid cert
 */
if(!defined('KALTURA_HTTPS_ENABLED')) define('KALTURA_HTTPS_ENABLED', false);

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
* Setting up Transcoding Profiles // TODO: still need to document the whole process of setting this up...
*/
if(!defined('KALTURA_DEFAULT_TRANSCODING_PROFILE')) define('KALTURA_DEFAULT_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_AUDIO_TRANSCODING_PROFILE')) define('KALTURA_AUDIO_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_SMALL_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_SMALL_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_SMALL_LOW_BANDWIDTH_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_SMALL_LOW_BANDWIDTH_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_MEDIUM_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_MEDIUM_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_MEDIUM_LOW_BANDWIDTH_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_MEDIUM_LOW_BANDWIDTH_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_LARGE_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_LARGE_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_LARGE_LOW_BANDWIDTH_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_LARGE_LOW_BANDWIDTH_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_VIDEO_LARGE_VERY_LOW_BANDWIDTH_TRANSCODING_PROFILE')) define('KALTURA_VIDEO_LARGE_VERY_LOW_BANDWIDTH_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_AUDIO_MP3_SOURCE_TRANSCODING_PROFILE')) define('KALTURA_AUDIO_MP3_SOURCE_TRANSCODING_PROFILE', 0);
if(!defined('KALTURA_AUDIO_OGG_SOURCE_TRANSCODING_PROFILE')) define('KALTURA_AUDIO_OGG_SOURCE_TRANSCODING_PROFILE', 0);

?>