<?php

/**
 * Admissions Application Module
 *
 * @author Steve Smith
 * @author Lucas Welper
 * @since 2011-02-11
 * @package ControllerStep
 *
 */
/*
 *  Fifth page of the application
 *
 *  Activites and Honors
 *
 */
class ApplicationPageFive extends FormStep {

    var $_log_errors = true;
    var $error;
    var $activities_array = array(
        'ACHNR' => 'Academic Honors',
        'ART' => 'Art',
        'BKYD' => 'Backyard Wilderness',
        'CHER' => 'Cheerleading',
        'CMIN' => 'Campus Ministry',
        'CULT' => 'Multicultural Programs',
        'DANC' => 'Dance',
        'DRAM' => 'Drama',
        'ENVC' => 'Environmental Concerns',
        'HABH' => 'Habitat for Humanity',
        'IMSPT' => 'Intramural Sports',
        'MBAN' => 'Band',
        'MBPL' => 'Band Private Lessons',
        'MCHR' => 'Choir',
        'MCPL' => 'Choir Private Lessons',
        'MGUI' => 'Guitar',
        'MHAR' => 'Harp',
        'MJZB' => 'Jazz Band',
        'MMUS' => 'Music',
        'MOGP' => 'Organ Private Lessons',
        'MOPL' => 'Orchestra Private Lessons',
        'MORC' => 'Orchestra',
        'MORG' => 'Organ',
        'MPIA' => 'Piano',
        'MPPL' => 'Piano Private Lessons',
        'MSTR' => 'Strings',
        'NEWS' => 'Newspaper',
        'PHOT' => 'Photography',
        'PROGV' => 'Volunteer Programs',
        'RADI' => 'Radio',
        'RUGB' => 'Rugby',
        'STAB' => 'Study Abroad',
        'STGO' => 'Student Government',
        'TRIA' => 'Mock Trial',
        'UTFB' => 'Ultimate Frisbee',
        'VBASE' => 'Baseball',
        'VBASK' => 'Basketball',
        'VCC' => 'Cross Country',
        'VFOOT' => 'Football',
        'VGOLF' => 'Golf',
        'VSOC' => 'Soccer',
        'VSOFT' => 'Softball',
        'VSW' => 'Swimming',
        'VTEN' => 'Tennis',
        'VTRA' => 'Track',
        'VVOL' => 'Volleyball',
        'VWR' => 'Wrestling',
        'XASF' => 'Active School Flag',
        'YEAR' => 'Yearbook',
        'Other' => 'Other'
    );
    var $participation_years_array = array(
        '9' => '9th', '10' => '10th',
        '11' => '11th', '12' => '12th',
        'Luther' => 'Plan to participate at Luther College'
    );
    var $elements = array(
        'activity_header' => array(
            'type' => 'comment',
            'text' => '<h3>Activities & Honors</h3>'
        ),
        'activity_comment' => array(
            'type' => 'comment',
            'text' => 'Please list your high school activities in order of their importance to you
                    (sports, service projects, clubs, special interests, etc.)'
        ),
        'activity_1_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 1</h4>'
        ),
        'activity_1' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_1_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_1_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_1_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_1_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'activity_2_hr' => 'hr',
        'activity_2_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 2</h4>'
        ),
        'activity_2' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_2_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_2_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_2_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_2_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'activity_3_hr' => 'hr',
        'activity_3_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 3</h4>'
        ),
        'activity_3' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_3_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_3_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_3_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_3_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'activity_4_hr' => 'hr',
        'activity_4_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 4</h4>'
        ),
        'activity_4' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_4_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_4_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_4_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_4_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'activity_5_hr' => 'hr',
        'activity_5_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 5</h4>'
        ),
        'activity_5' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_5_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_5_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_5_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_5_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'activity_6_hr' => 'hr',
        'activity_6_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 6</h4>'
        ),
        'activity_6' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_6_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_6_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_6_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_6_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'activity_7_hr' => 'hr',
        'activity_7_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 7</h4>'
        ),
        'activity_7' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_7_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_7_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_7_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_7_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'activity_8_hr' => 'hr',
        'activity_8_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 8</h4>'
        ),
        'activity_8' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_8_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_8_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_8_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_8_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'activity_9_hr' => 'hr',
        'activity_9_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 9</h4>'
        ),
        'activity_9' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_9_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_9_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_9_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_9_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'activity_10_hr' => 'hr',
        'activity_10_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 10</h4>'
        ),
        'activity_10' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_10_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_10_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_10_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_10_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),        
        'add_activity_button' => array(
            'type' => 'comment',
            'text' => '<div id="addActivity" title="Add an Activity" class="addButton">
                Add an Activity
                </div>'
        ),
        'remove_activity_button' => array(
            'type' => 'comment',
            'text' => '<div id="removeActivity" title="Remove Activity" class="removeButton">
                Remove an Activity
                </div>'
        )
            //'hr' => 'hr',
    );
    var $display_name = 'Activities';
    var $error_header_text = 'Please check your form.';

    // style up the form and add comments et al
    function on_every_time() {
        $this->change_element_type('activity_1', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_1_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_2', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_2_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_3', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_3_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_4', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_4_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_5', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_5_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_6', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_6_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_7', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_7_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_8', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_8_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_9', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_9_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_10', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_10_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->set_value('activity_1', 'foosball');
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageFive">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

     function  process() {
        parent::process();
        
//        //        $qstring = "INSERT INTO `applicants` SET";
//        $form_elements = $this->get_element_names();
////        pray($form_elements);
//        $qstring = '';
//        foreach ($form_elements as $element){
////            echo $element . "\n";
////            echo $this->get_value($element) . '<br>';
//            if (!(strstr($element, '_header') || strstr($element, '_comment') || strstr($element, 'hr'))){
//                $qstring .= "INSERT INTO `applicants` SET " . $element . "='";
//                    if ($this->get_value($element)){
//                        $qstring .= addslashes($this->get_value($element));
//                    } else {
//                        $qstring .= 'NULL';
//                    }
//                $qstring .= "', ";
//                $qresult = db_query($qstring);
//            }
//
//            echo $qstring;
//        }
//          die('die ' . $qstring);
        $activity_1 = $this->get_value('activity_1');
        $activity_1_other = $this->get_value('activity_1_other');
        $activity_1_participation = $this->get_value('activity_1_participation');
        if ($this->get_value('activity_1_participation')){
            $activity_1_participation_string = implode(',', $this->get_value('activity_1_participation'));
        }
        $activity_1_honors = $this->get_value('activity_1_honors');
        $activity_2 = $this->get_value('activity_2');
        $activity_2_other = $this->get_value('activity_2_other');
        if ($this->get_value('activity_2_participation')){
            $activity_2_participation_string = implode(',', $this->get_value('activity_2_participation'));
        }
        $activity_2_honors = $this->get_value('activity_2_honors');
        $activity_3 = $this->get_value('activity_3');
        $activity_3_other = $this->get_value('activity_3_other');
        if ($this->get_value('activity_3_participation')){
            $activity_3_participation_string = implode(',', $this->get_value('activity_3_participation'));
        }
        $activity_3_honors = $this->get_value('activity_3_honors');
        $activity_4 = $this->get_value('activity_4');
        $activity_4_other = $this->get_value('activity_4_other');
        if ($this->get_value('activity_4_participation')){
            $activity_4_participation_string = implode(',', $this->get_value('activity_4_participation'));
        }
        $activity_4_honors = $this->get_value('activity_4_honors');
        $activity_5 = $this->get_value('activity_5');
        $activity_5_other = $this->get_value('activity_5_other');
        if ($this->get_value('activity_5_participation')){
            $activity_5_participation_string = implode(',', $this->get_value('activity_5_participation'));
        }
        $activity_5_honors = $this->get_value('activity_5_honors');
        $activity_6 = $this->get_value('activity_6');
        $activity_6_other = $this->get_value('activity_6_other');
        if ($this->get_value('activity_6_participation')){
            $activity_6_participation_string = implode(',', $this->get_value('activity_6_participation'));
        }
        $activity_6_honors = $this->get_value('activity_6_honors');
        $activity_7 = $this->get_value('activity_7');
        $activity_7_other = $this->get_value('activity_7_other');
        if ($this->get_value('activity_7_participation')){
            $activity_7_participation_string = implode(',', $this->get_value('activity_7_participation'));
        }
        $activity_7_honors = $this->get_value('activity_7_honors');
        $activity_8 = $this->get_value('activity_8');
        $activity_8_other = $this->get_value('activity_8_other');
        if ($this->get_value('activity_8_participation')){
            $activity_8_participation_string = implode(',', $this->get_value('activity_8_participation'));
        }
        $activity_8_honors = $this->get_value('activity_8_honors');
        $activity_9 = $this->get_value('activity_9');
        $activity_9_other = $this->get_value('activity_9_other');
        if ($this->get_value('activity_9_participation')){
            $activity_9_participation_string = implode(',', $this->get_value('activity_9_participation'));
        }
        $activity_9_honors = $this->get_value('activity_9_honors');
        $activity_10 = $this->get_value('activity_10');
        $activity_10_other = $this->get_value('activity_10_other');
        if ($this->get_value('activity_10_participation')){
            $activity_10_participation_string = implode(',', $this->get_value('activity_10_participation'));
        }
        $activity_10_honors = $this->get_value('activity_10_honors');

        connectDB('admissions_applications_connection');
        
        $qstring = "INSERT INTO `applicants` SET
                activity_1='" . ((!empty ($activity_1)) ? addslashes($activity_1) : 'NULL') . "',
                activity_1_other='" . ((!empty ($activity_1_other)) ? addslashes($activity_1_other) : 'NULL') . "',
                activity_1_participation='" . ((!empty ($activity_1_participation_string)) ? addslashes($activity_1_participation_string) : 'NULL') . "',
                activity_1_honors='" . ((!empty ($activity_1_honors)) ? addslashes($activity_1_honors) : 'NULL') . "',
                activity_2='" . ((!empty ($activity_2)) ? addslashes($activity_2) : 'NULL') . "',
                activity_2_other='" . ((!empty ($activity_2_other)) ? addslashes($activity_2_other) : 'NULL') . "',
                activity_2_participation='" . ((!empty ($activity_2_participation_string)) ? addslashes($activity_2_participation_string) : 'NULL') . "',
                activity_2_honors='" . ((!empty ($activity_2_honors)) ? addslashes($activity_2_honors) : 'NULL') . "',
                activity_3='" . ((!empty ($activity_3)) ? addslashes($activity_3) : 'NULL') . "',
                activity_3_other='" . ((!empty ($activity_3_other)) ? addslashes($activity_3_other) : 'NULL') . "',
                activity_3_participation='" . ((!empty ($activity_3_participation_string)) ? addslashes($activity_3_participation_string) : 'NULL') . "',
                activity_3_honors='" . ((!empty ($activity_3_honors)) ? addslashes($activity_3_honors) : 'NULL') . "',
                activity_4='" . ((!empty ($activity_4)) ? addslashes($activity_4) : 'NULL') . "',
                activity_4_other='" . ((!empty ($activity_4_other)) ? addslashes($activity_4_other) : 'NULL') . "',
                activity_4_participation='" . ((!empty ($activity_4_participation_string)) ? addslashes($activity_4_participation_string) : 'NULL') . "',
                activity_4_honors='" . ((!empty ($activity_4_honors)) ? addslashes($activity_4_honors) : 'NULL') . "',
                activity_5='" . ((!empty ($activity_5)) ? addslashes($activity_5) : 'NULL') . "',
                activity_5_other='" . ((!empty ($activity_5_other)) ? addslashes($activity_5_other) : 'NULL') . "',
                activity_5_participation='" . ((!empty ($activity_5_participation_string)) ? addslashes($activity_5_participation_string) : 'NULL') . "',
                activity_5_honors='" . ((!empty ($activity_5_honors)) ? addslashes($activity_5_honors) : 'NULL') . "',
                activity_6='" . ((!empty ($activity_6)) ? addslashes($activity_6) : 'NULL') . "',
                activity_6_other='" . ((!empty ($activity_6_other)) ? addslashes($activity_6_other) : 'NULL') . "',
                activity_6_participation='" . ((!empty ($activity_6_participation_string)) ? addslashes($activity_6_participation_string) : 'NULL') . "',
                activity_6_honors='" . ((!empty ($activity_6_honors)) ? addslashes($activity_6_honors) : 'NULL') . "',
                activity_7='" . ((!empty ($activity_7)) ? addslashes($activity_7) : 'NULL') . "',
                activity_7_other='" . ((!empty ($activity_7_other)) ? addslashes($activity_7_other) : 'NULL') . "',
                activity_7_participation='" . ((!empty ($activity_7_participation_string)) ? addslashes($activity_7_participation_string) : 'NULL') . "',
                activity_7_honors='" . ((!empty ($activity_7_honors)) ? addslashes($activity_7_honors) : 'NULL') . "',
                activity_8='" . ((!empty ($activity_8)) ? addslashes($activity_8) : 'NULL') . "',
                activity_8_other='" . ((!empty ($activity_8_other)) ? addslashes($activity_8_other) : 'NULL') . "',
                activity_8_participation='" . ((!empty ($activity_8_participation_string)) ? addslashes($activity_8_participation_string) : 'NULL') . "',
                activity_8_honors='" . ((!empty ($activity_8_honors)) ? addslashes($activity_8_honors) : 'NULL') . "',
                activity_9='" . ((!empty ($activity_9)) ? addslashes($activity_9) : 'NULL') . "',
                activity_9_other='" . ((!empty ($activity_9_other)) ? addslashes($activity_9_other) : 'NULL') . "',
                activity_9_participation='" . ((!empty ($activity_9_participation_string)) ? addslashes($activity_9_participation_string) : 'NULL') . "',
                activity_9_honors='" . ((!empty ($activity_9_honors)) ? addslashes($activity_9_honors) : 'NULL') . "',
                activity_10='" . ((!empty ($activity_10)) ? addslashes($activity_10) : 'NULL') . "',
                activity_10_other='" . ((!empty ($activity_10_other)) ? addslashes($activity_10_other) : 'NULL') . "',
                activity_10_participation='" . ((!empty ($activity_10_participation_string)) ? addslashes($activity_10_participation_string) : 'NULL') . "',
                activity_10_honors='" . ((!empty ($activity_10_honors)) ? addslashes($activity_10_honors) : 'NULL') . "',
                last_update=NOW()" ;

        $qresult = db_query($qstring);
        
        //connect back with the reason DB
        connectDB(REASON_DB);
    }

}

?>