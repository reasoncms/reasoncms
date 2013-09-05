<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.3_to_4.4']['new_settings'] = 'ReasonUpgrader_44_NewSettings';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_44_NewSettings extends reasonUpgraderDefault implements reasonUpgraderInfoInterface
{	
    /**
     * Return information about new settings
     * @return string HTML report
     */
	public function run()
	{
		$str = '<h3>Reason 4.4 has some file system location and settings changes:</h3>';
		$str .= '<p>We have moved the file system location of files in reason_4.0/www/ that Reason CMS needs write access to into the REASON_DATA_DIR. ';
		$str .= 'For people upgrading, the sized_images and sized_images_custom directories have been moved to the REASON_DATA_DIR. There are symlinks at the previous locations. ';
		$str .= 'Additionally, there is a new folder in REASON_DATA_DIR called www_tmp. The tmp folder in reason_4.0/www/tmp is a symlink to the new location in REASON_DATA_DIR.</p>';
		$str .= '<h4>New Files</h4>';
		$str .= '<ul>';
		$str .= '<li>twitter_api_settings.php</li>';
		$str .= '</ul>';
		$str .= '<h4>Changed Settings</h4>';
		$str .= '<ul>';
		$str .= '<li>package_settings.php - CURL_PATH has been removed</li>';
		$str .= '<li>package_settings.php - LIBCURLEMU_INC has been removed.</li>';
		$str .= '<li>reason_settings.php - REASON_DEFAULT_HTML_EDITOR has been changed to tiny_mce.</li>';
		$str .= '<li>reason_settings.php - REASON_ENABLE_ENTITY_SANITIZATION has been added (default FALSE will be true in reason 4.5).</li>';
		$str .= '<li>reason_settings.php - REASON_TINYMCE_CONTENT_CSS_PATH has been added.</li>';
		$str .= '<li>reason_settings.php - REASON_SIZED_IMAGE_CUSTOM_DIR has been added.</li>';
		$str .= '<li>reason_settings.php - REASON_SIZED_IMAGE_CUSTOM_DIR_WEB_PATH has been added.</li>';
		$str .= '<li>reason_settings.php - REASON_SIZED_IMAGE_DIR has been changed to REASON_DATA_DIR.\'sized_images/\'.</li>';
		$str .= '<li>kaltura_integration_settings.php - KALTURA_HTTPS_ENABLED has been added.</li>';
		$str .= '</ul>';
		return $str;
	}
}
?>