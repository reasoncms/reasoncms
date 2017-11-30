<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('scripts/upgrade/reason_db_helper.php');

$GLOBALS['_reason_upgraders']['4.7_to_4.8']['add_focal_points_fields_to_images'] = 'ReasonUpgrader_48_AddImageFocalPoints';

class ReasonUpgrader_48_AddImageFocalPoints implements reasonUpgraderInterface 
{

    protected $user_id;
    public function user_id( $user_id = NULL)
    {
        if(!empty($user_id))
            return $this->user_id = $user_id;
        else
            return $this->user_id;
    }
    
    private $dbHelper;

    public function __construct() 
    {
        $this->dbHelper = new ReasonDbHelper();
        $this->dbHelper->setUsername(reason_check_authentication());
    }

    /**
     * Get the title of the upgrader
     * @return string
     */
    public function title()
    {
        return 'Add crop_style, focal_point_x, and focal_point_y fields to the image type';
    }
    
    /**
     * Get a description of this script's function
     * @return string HTML description
     */
    public function description()
    {
        return '<p>This script adds three new fields, crop_style, focal_point_x, and focal_point_y to the image entity table. focal_point_x and focal_point_y allow a point on the image to be marked as central. If the image is cropped, it will be done so that the focal point is still prominent. crop_style defines how the image should be cropped.</p>';
    }
    
    
    private function echoYesNo($test) {
        $fontColor = $test ? "green" : "red";
        $wording = $test ? "YES" : "NO";
        return '<span style="color:'.$fontColor.';">'.$wording.'</span>';
    }
    
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
    public function test()
    {
        return '<p>' . 
            'Image type has crop_style field: ' . $this->echoYesNo($this->dbHelper->columnExistsOnTable('image', 'crop_style')) . '<br>' .
            'Image type has focal_point_x field: ' . $this->echoYesNo($this->dbHelper->columnExistsOnTable('image', 'focal_point_x')) . '<br>' .
            'Image type has focal_point_y field: ' . $this->echoYesNo($this->dbHelper->columnExistsOnTable('image', 'focal_point_y')) . '<br>' .
        '</p>';
    }
    
    private function addFieldToImage($field_name, $field_details) {
        if ($this->dbHelper->columnExistsOnTable('image', $field_name)) {
            echo $field_name . ' field already exists. <br>';
        } else {
            $this->dbHelper->addFieldsToEntity('image', $field_details);
        }
    }

    /**
     * Run the upgrader
     * @return string HTML report
     */
    public function run()
    {
        $this->addFieldToImage('crop_style', array('crop_style' => 'enum("center","custom") DEFAULT "center"'));
        $this->addFieldToImage('focal_point_x', array('focal_point_x' => 'float'));
        $this->addFieldToImage('focal_point_y', array('focal_point_y' => 'float'));
    }
}