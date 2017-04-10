<?php

if(!defined('VIMEO_UPLOADING_ENABLED'))
{
	if(isset($GLOBALS['UPLOADABLE_MEDIA_WORK_INTEGRATION_LIBRARIES']) && in_array('vimeo', $GLOBALS['UPLOADABLE_MEDIA_WORK_INTEGRATION_LIBRARIES']))
		define('VIMEO_UPLOADING_ENABLED', true);
	else
		define('VIMEO_UPLOADING_ENABLED', false);
}

/**
 * These four constants are only relevant if VIMEO_UPLOADING_ENABLED is set to true.
 *
 * These keys are used to access Vimeo's api. They can be found at 
 * https://developer.vimeo.com/apps/[DEV_ID]. It's under the "My Apps" tab.
 */
if(!defined('VIMEO_CLIENT_ID')) define('VIMEO_CLIENT_ID', '');
if(!defined('VIMEO_CLIENT_SECRET')) define('VIMEO_CLIENT_SECRET', '');
if(!defined('VIMEO_ACCESS_TOKEN')) define('VIMEO_ACCESS_TOKEN', '');
if(!defined('VIMEO_ACCESS_TOKEN_SECRET')) define('VIMEO_ACCESS_TOKEN_SECRET', '');

?>
