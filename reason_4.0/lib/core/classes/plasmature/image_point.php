<?php

/**
 * Reason image point selector plasmature class.
 * 
 * @package reason
 * @subpackage classes
 * @author Kiran Tomlinson
 */

reason_require_once('classes/sized_image.php');
reason_require_once('function_libraries/image_tools.php');
include_once(DISCO_INC.'plasmature/plasmature.php');


class reason_image_focal_pointType extends defaultType {
    var $type = 'reason_image_focal_point';
    
    var $type_valid_args = array('aspect_ratios');
    var $aspect_ratios = array(1, 2, 0.75);
    
    function grab() {
    }
    
    function get_display() {
        
        $html  = '<div id="focalPointContainer">';
        $html .=    '<script type="text/javascript" src="'.WEB_JAVASCRIPT_PATH.'content_managers/image_point.js"></script>';
        $html .=    '<div id="focalPointSelector">';
        $html .=        '<div class="formComment smallText">';
        $html .=            '<strong class="focalPointTitle">Select a Focal Point</strong></br>Click on the image to position the focal point.';
        $html .=        '</div>';
        $html .=        '<div id="focalPointClickArea">';
        $html .=            '<img id="focalPointImage">';
        $html .=            '<img src="'.REASON_HTTP_BASE_PATH.'ui_images/focal_point_icon.png" id="focalPointIcon">';
        $html .=        '</div>';
        $html .=    '</div>';
        $html .=    '<div id="focalPointSeparator"><div id="focalPointSeparatorCell"></div></div>';
        $html .=    '<div id="cropSamples">';
        $html .=        '<div class="formComment smallText">';
        $html .=            '<strong class="focalPointTitle">Preview your focal point</strong></br>How this focal point would look with common crop shapes.';
        $html .=        '</div>';
        
        foreach ($this->aspect_ratios as $aspect_ratio) {
            $html .=    '<div class="croppedImageContainer" id="'.$aspect_ratio.'">';
            $html .=        '<img class="croppedImage" id="'.$aspect_ratio.'-ratioImage">';
            $html .=    '</div>';
        }

        $html .=    '</div>';
        $html .= '</div>';
        $html .= '<div id="noImageMessage">No image loaded yet.</div>';


        return $html;
    }
}


