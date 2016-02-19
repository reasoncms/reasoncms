<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.6_to_4.7']['publication_emailer_setting'] = 'ReasonUpgrader_47_PublicationEmailerSetting';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_47_PublicationEmailerSetting extends reasonUpgraderDefault implements reasonUpgraderInfoInterface
{	
    /**
     * Return information about new settings
     * @return string HTML report
     */
	public function run()
	{
		$str = '<h3>Add PUBLICATION_SOCIAL_SHARING_INCLUDES_EMAIL setting:</h3>';
		if(!defined(PUBLICATION_SOCIAL_SHARING_INCLUDES_EMAIL))
		{
			$str .= '<p>In reason_settings.php, make sure the following lines are present after PUBLICATION_SOCIAL_SHARING_DEFAULT is defined:</p>';
			$str .= '<pre>
/**
 * PUBLICATION_SOCIAL_SHARING_INCLUDES_EMAIL
 * This setting identifies whether social sharing on publications will 
 * include email sharing via the publication module\'s email sharing feature
 */
define( \'PUBLICATION_SOCIAL_SHARING_INCLUDES_EMAIL\', true);
</pre>';
			$str .= '<p>This will enable email sharing of publication posts. If you do not want this feature, change this setting to <code>false</code>.</p>';
		}
		else
		{
			$str .= '<p>This setting is already present.<p>';
		}
		return $str;
	}
}