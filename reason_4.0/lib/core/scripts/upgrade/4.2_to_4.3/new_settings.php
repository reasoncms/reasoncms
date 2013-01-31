<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.2_to_4.3']['new_settings'] = 'ReasonUpgrader_42_NewSettings';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_42_NewSettings implements reasonUpgraderInfoInterface
{
	protected $user_id;
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}
	
    /**
     * Return information about new settings
     * @return string HTML report
     */
	public function run()
	{
		$exists = (defined('REASON_MAINTENANCE_MODE'));
		$str = '<h3>Reason 4.3 has many settings changes:</h3>';
		$str .= '<p>For people upgrading, <strong>dbs.xml and dbs.xml.sample now use "reason" as the default database for thor_connection instead of "thor"</strong> - if you have never customized your dbs.xml file you may need to change the thor_connection entry database line back to "thor" to keep forms working.</p>';
		$str .= '<h4>New Files</h4>';
		$str .= '<ul>';
		$str .= '<li>google_api_settings.php</li>';
		$str .= '<li>kaltura_integration_settings.php</li>';
		$str .= '</ul>';
		$str .= '<h4>Changed Settings</h4>';
		$str .= '<ul>';
		$str .= '<li>package_settings.php - LOKI_INC and LOKI_HTTP_PATH have been removed</li>';
		$str .= '<li>reason_settings.php - REASON_VERSION has been removed.</li>';
		$str .= '<li>reason_settings.php - REASON_URL_FOR_ICAL_FEED_HELP has been added.</li>';
		$str .= '<li>reason_settings.php - REASON_IMAGE_GRAVEYARD has been added.</li>';
		$str .= '<li>reason_settings.php - REASON_DATA_DIR has been added.</li>';
		$str .= '<li>reason_settings.php - REASON_CSV_DIR has been changed to reference REASON_DATA_DIR.</li>';
		$str .= '<li>reason_settings.php - PHOTOSTOCK has been changed to reference REASON_DATA_DIR.</li>';
		$str .= '<li>reason_settings.php - REASON_TEMP_DIR has been changed to reference REASON_DATA_DIR.</li>';
		$str .= '<li>reason_settings.php - REASON_LOG_DIR has been changed to reference REASON_DATA_DIR.</li>';
		$str .= '<li>reason_settings.php - REASON_CACHE_DIR has been changed to reference REASON_DATA_DIR.</li>';
		$str .= '<li>reason_settings.php - ASSET_PATH has been changed to reference REASON_DATA_DIR.</li>';
		$str .= '<li>reason_settings.php - REASON_DATA_DIR has been added.</li>';
		$str .= '<li>reason_settings.php - REASON_DEFAULT_ALLOWED_TAGS has been updated with HTML5 tags.</li>';
		$str .= '<li>thor_settings.php - USE_JS_THOR has been added - set it to true to try out the JavaScript thor form builder (in beta)</li>';
		$str .= '</ul>';
		return $str;
	}
}
?>