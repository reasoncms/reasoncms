<?php
/**
 * This is the settings file for general settings that all integration libaries should use.
 */

/**
 * Specify integration libraries used for creation of new media work entities. If Reason
 * is using no integration libraries for media, this array should be empty.
 * 
 * The 'default' library is implicit and shouldn't be included in this array.
 */
$GLOBALS['NEW_MEDIA_WORK_INTEGRATION_LIBRARIES'] = array();

/**
 * Specify integration libraries with which users have the ability to upload videos.
 * This array is a subset (or full set) of the NEW_MEDIA_WORK_INTEGRATION_LIBRARIES array.
 */
$GLOBALS['UPLOADABLE_MEDIA_WORK_INTEGRATION_LIBRARIES'] = array();

/**
 * These are the three default heights for the video transcodings.
 */
if(!defined('MEDIA_WORK_SMALL_HEIGHT')) define('MEDIA_WORK_SMALL_HEIGHT', 240);
if(!defined('MEDIA_WORK_MEDIUM_HEIGHT')) define('MEDIA_WORK_MEDIUM_HEIGHT', 360);
if(!defined('MEDIA_WORK_LARGE_HEIGHT')) define('MEDIA_WORK_LARGE_HEIGHT', 480);

/**
 * The "optimal" bitrate for audio on the web is about 128 kbps.
 */
if(!defined('SUGGESTED_AUDIO_BITRATE')) define('SUGGESTED_AUDIO_BITRATE', 128);

/**
 * These are the preferred bitrates for videos of different sizes.
 */
if(!defined('MEDIA_WORK_SMALL_HEIGHT_BITRATE')) define('MEDIA_WORK_SMALL_HEIGHT_BITRATE', 300);
if(!defined('MEDIA_WORK_MEDIUM_HEIGHT_BITRATE')) define('MEDIA_WORK_MEDIUM_HEIGHT_BITRATE', 600);
if(!defined('MEDIA_WORK_LARGE_HEIGHT_BITRATE')) define('MEDIA_WORK_LARGE_HEIGHT_BITRATE', 1050);

?>