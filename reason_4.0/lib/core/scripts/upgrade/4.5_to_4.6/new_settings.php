<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.5_to_4.6']['new_settings'] = 'ReasonUpgrader_46_NewSettings';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_46_NewSettings extends reasonUpgraderDefault implements reasonUpgraderInfoInterface
{   
    /**
     * Return information about new settings
     * @return string HTML report
     */
    public function run()
    {
        $str = '<h3>Reason 4.6 has some new changes:</h3>';
        $str .= '<p>in settings/reason_settings.php</p>';
        $str .= '<h4>Page Title Settings</h4>';
        $str .= '<ul>';
        $str .= '<li>REASON_SHOW_META_KEYWORDS</li>';
        $str .= '<li>REASON_HOME_TITLE_PATTERN</li>';
        $str .= '<li>REASON_SECONDARY_TITLE_PATTERN</li>';
        $str .= '<li>REASON_ITEM_TITLE_PATTERN</li>';
        $str .= '</ul>';
        $str .= '<h4>PDF open action</h4>';
        $str .= '<ul>';
        $str .= '<li>REASON_PDF_DOWNLOAD_DISPOSITION_DEFAULT</li>';
        $str .= '</ul>';
        $str .= '<h4>Minimum image size settings</h4>';
        $str .= '<ul>';
        $str .= '<li>REASON_STANDARD_MIN_IMAGE_HEIGHT</li>';
        $str .= '<li>REASON_STANDARD_MIN_IMAGE_WIDTH</li>';
        $str .= '</ul>';
        $str .= '<h4>Forms apply akismet spam filtering by default</h4>';
        $str .= '<ul>';
        $str .= '<li>REASON_FORMS_THOR_DEFAULT_AKISMET_FILTER</li>';
        $str .= '</ul>';

        return $str;
    }
}
