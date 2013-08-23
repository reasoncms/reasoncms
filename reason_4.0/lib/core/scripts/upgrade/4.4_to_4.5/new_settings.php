<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.4_to_4.5']['new_settings'] = 'ReasonUpgrader_45_NewSettings';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_45_NewSettings extends reasonUpgraderDefault implements reasonUpgraderInfoInterface
{	
    /**
     * Return information about new settings
     * @return string HTML report
     */
	public function run()
	{
		$str = '<h3>Reason 4.5 has some settings changes:</h3>';
		$str .= '<ul>';
		$str .= '<li>package_settings.php - LESSPHP_INC has been added.</li>';
		$str .= '</ul>';
		return $str;
	}
}
?>