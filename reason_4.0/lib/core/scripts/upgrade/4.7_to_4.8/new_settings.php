<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.7_to_4.8']['new_settings'] = 'ReasonUpgrader_48_NewSettings';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_48_NewSettings extends reasonUpgraderDefault implements reasonUpgraderInfoInterface
{   
    /**
     * Return information about new settings
     * @return string HTML report
     */
    public function run()
    {
        $str = '<h3>Reason 4.8 has some new changes:</h3>';
        $str .= '<h4>in settings/reason_settings.php</h4>';
        $str .= '<ul>';
        $str .= '<li>REASON_DEFAULT_CONTENT_LANGUAGE';
	$str .= '<pre>';
	$str .= "define('REASON_DEFAULT_CONTENT_LANGUAGE', 'en-US');";
	$str .= '</pre>';
	$str .= '</li>';
        $str .= '<li>REASON_DEFAULT_INTERFACE_LANGUAGE';
	$str .=	'<pre>';
	$str .= "define('REASON_DEFAULT_INTERFACE_LANGUAGE', 'en-US');";
	$str .=	'</pre>';
	$str .= '</li>';
        $str .= '</ul>';

        return $str;
    }
}
