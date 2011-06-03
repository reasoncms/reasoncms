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
        'activities_header' => array(
            'type' => 'comment',
            'text' => '<h3>Activities & Honors</h3>'
        ),
        'activities_comment' => array(
            'type' => 'comment',
            'text' => 'Please list your high school activities in order of their importance to you
                    (sports, service projects, clubs, special interests, etc.)'
        ),
        'activities_01_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 1</h4>'
        ),
        'activity_01' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_01_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_01_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_01_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_01_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'hr' => 'hr',
        'activities_02_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 2</h4>'
        ),
        'activity_02' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_02_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_02_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_02_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_02_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'hr2' => 'hr',
        'activities_03_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 3</h4>'
        ),
        'activity_03' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_03_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_03_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_03_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_03_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'hr3' => 'hr',
        'activities_04_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 4</h4>'
        ),
        'activity_04' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_04_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_04_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_04_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_04_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'hr4' => 'hr',
        'activities_05_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 5</h4>'
        ),
        'activity_05' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_05_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_05_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_05_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_05_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'hr5' => 'hr',
        'activities_06_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 6</h4>'
        ),
        'activity_06' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_06_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_06_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_06_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_06_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'hr6' => 'hr',
        'activities_07_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 7</h4>'
        ),
        'activity_07' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_07_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_07_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_07_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_07_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'hr7' => 'hr',
        'activities_08_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 8</h4>'
        ),
        'activity_08' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_08_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_08_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_08_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_08_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'hr8' => 'hr',
        'activities_09_header' => array(
            'type' => 'comment',
            'text' => '<h4>Activity 9</h4>'
        ),
        'activity_09' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array()
        ),
        'activity_09_other' => array(
            'type' => 'text',
            'display_name' => 'Other details'
        ),
        'activity_09_participation' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => 'Participation',
            'options' => array()
        ),
        'activity_09_honors_comment' => array(
            'type' => 'comment',
            'text' => 'Please describe any honors you earned that are associated with this activity',
        ),
        'activity_09_honors' => array(
            'type' => 'text',
            'display_name' => '&nbsp;'
        ),
        'hr9' => 'hr',
        'activities_10_header' => array(
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
            //'hr' => 'hr',
    );
    var $display_name = 'Activities';
    var $error_header_text = 'Please check your form.';

    // style up the form and add comments et al
    function on_every_time() {
        $this->change_element_type('activity_01', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_01_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_02', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_02_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_03', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_03_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_04', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_04_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_05', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_05_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_06', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_06_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_07', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_07_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_08', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_08_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_09', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_09_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->change_element_type('activity_10', 'select_no_sort', array('options' => $this->activities_array));
        $this->change_element_type('activity_10_participation', 'checkboxgroup_no_sort', array('options' => $this->participation_years_array));
        $this->set_value('activity_01', 'foosball');
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
        $activity_01 = $this->get_value('activity_01');
        $activity_01_other = $this->get_value('activity_01_other');
        $activity_01_participation = $this->get_value('activity_01_participation');
        if ($this->get_value('activity_01_participation')){
            $activity_01_participation_string = implode(',', $this->get_value('activity_01_participation'));
        }
        $activity_01_honors = $this->get_value('activity_01_honors');
        $activity_02 = $this->get_value('activity_02');
        $activity_02_other = $this->get_value('activity_02_other');
        if ($this->get_value('activity_02_participation')){
            $activity_02_participation_string = implode(',', $this->get_value('activity_02_participation'));
        }
        $activity_02_honors = $this->get_value('activity_02_honors');
        $activity_03 = $this->get_value('activity_03');
        $activity_03_other = $this->get_value('activity_03_other');
        if ($this->get_value('activity_03_participation')){
            $activity_03_participation_string = implode(',', $this->get_value('activity_03_participation'));
        }
        $activity_03_honors = $this->get_value('activity_03_honors');
        $activity_04 = $this->get_value('activity_04');
        $activity_04_other = $this->get_value('activity_04_other');
        if ($this->get_value('activity_04_participation')){
            $activity_04_participation_string = implode(',', $this->get_value('activity_04_participation'));
        }
        $activity_04_honors = $this->get_value('activity_04_honors');
        $activity_05 = $this->get_value('activity_05');
        $activity_05_other = $this->get_value('activity_05_other');
        if ($this->get_value('activity_05_participation')){
            $activity_05_participation_string = implode(',', $this->get_value('activity_05_participation'));
        }
        $activity_05_honors = $this->get_value('activity_05_honors');
        $activity_06 = $this->get_value('activity_06');
        $activity_06_other = $this->get_value('activity_06_other');
        if ($this->get_value('activity_06_participation')){
            $activity_06_participation_string = implode(',', $this->get_value('activity_06_participation'));
        }
        $activity_06_honors = $this->get_value('activity_06_honors');
        $activity_07 = $this->get_value('activity_07');
        $activity_07_other = $this->get_value('activity_07_other');
        if ($this->get_value('activity_07_participation')){
            $activity_07_participation_string = implode(',', $this->get_value('activity_07_participation'));
        }
        $activity_07_honors = $this->get_value('activity_07_honors');
        $activity_08 = $this->get_value('activity_08');
        $activity_08_other = $this->get_value('activity_08_other');
        if ($this->get_value('activity_08_participation')){
            $activity_08_participation_string = implode(',', $this->get_value('activity_08_participation'));
        }
        $activity_08_honors = $this->get_value('activity_08_honors');
        $activity_09 = $this->get_value('activity_09');
        $activity_09_other = $this->get_value('activity_09_other');
        if ($this->get_value('activity_09_participation')){
            $activity_09_participation_string = implode(',', $this->get_value('activity_09_participation'));
        }
        $activity_09_honors = $this->get_value('activity_09_honors');
        $activity_10 = $this->get_value('activity_10');
        $activity_10_other = $this->get_value('activity_10_other');
        if ($this->get_value('activity_10_participation')){
            $activity_10_participation_string = implode(',', $this->get_value('activity_10_participation'));
        }
        $activity_10_honors = $this->get_value('activity_10_honors');

        connectDB('admissions_applications_connection');
        
        $qstring = "INSERT INTO `applicants` SET
                activity_01='" . ((!empty ($activity_01)) ? addslashes($activity_01) : 'NULL') . "',
                activity_01_other='" . ((!empty ($activity_01_other)) ? addslashes($activity_01_other) : 'NULL') . "',
                activity_01_participation='" . ((!empty ($activity_01_participation_string)) ? addslashes($activity_01_participation_string) : 'NULL') . "',
                activity_01_honors='" . ((!empty ($activity_01_honors)) ? addslashes($activity_01_honors) : 'NULL') . "',
                activity_02='" . ((!empty ($activity_02)) ? addslashes($activity_02) : 'NULL') . "',
                activity_02_other='" . ((!empty ($activity_02_other)) ? addslashes($activity_02_other) : 'NULL') . "',
                activity_02_participation='" . ((!empty ($activity_02_participation_string)) ? addslashes($activity_02_participation_string) : 'NULL') . "',
                activity_02_honors='" . ((!empty ($activity_02_honors)) ? addslashes($activity_02_honors) : 'NULL') . "',
                activity_03='" . ((!empty ($activity_03)) ? addslashes($activity_03) : 'NULL') . "',
                activity_03_other='" . ((!empty ($activity_03_other)) ? addslashes($activity_03_other) : 'NULL') . "',
                activity_03_participation='" . ((!empty ($activity_03_participation_string)) ? addslashes($activity_03_participation_string) : 'NULL') . "',
                activity_03_honors='" . ((!empty ($activity_03_honors)) ? addslashes($activity_03_honors) : 'NULL') . "',
                activity_04='" . ((!empty ($activity_04)) ? addslashes($activity_04) : 'NULL') . "',
                activity_04_other='" . ((!empty ($activity_04_other)) ? addslashes($activity_04_other) : 'NULL') . "',
                activity_04_participation='" . ((!empty ($activity_04_participation_string)) ? addslashes($activity_04_participation_string) : 'NULL') . "',
                activity_04_honors='" . ((!empty ($activity_04_honors)) ? addslashes($activity_04_honors) : 'NULL') . "',
                activity_05='" . ((!empty ($activity_05)) ? addslashes($activity_05) : 'NULL') . "',
                activity_05_other='" . ((!empty ($activity_05_other)) ? addslashes($activity_05_other) : 'NULL') . "',
                activity_05_participation='" . ((!empty ($activity_05_participation_string)) ? addslashes($activity_05_participation_string) : 'NULL') . "',
                activity_05_honors='" . ((!empty ($activity_05_honors)) ? addslashes($activity_05_honors) : 'NULL') . "',
                activity_06='" . ((!empty ($activity_06)) ? addslashes($activity_06) : 'NULL') . "',
                activity_06_other='" . ((!empty ($activity_06_other)) ? addslashes($activity_06_other) : 'NULL') . "',
                activity_06_participation='" . ((!empty ($activity_06_participation_string)) ? addslashes($activity_06_participation_string) : 'NULL') . "',
                activity_06_honors='" . ((!empty ($activity_06_honors)) ? addslashes($activity_06_honors) : 'NULL') . "',
                activity_07='" . ((!empty ($activity_07)) ? addslashes($activity_07) : 'NULL') . "',
                activity_07_other='" . ((!empty ($activity_07_other)) ? addslashes($activity_07_other) : 'NULL') . "',
                activity_07_participation='" . ((!empty ($activity_07_participation_string)) ? addslashes($activity_07_participation_string) : 'NULL') . "',
                activity_07_honors='" . ((!empty ($activity_07_honors)) ? addslashes($activity_07_honors) : 'NULL') . "',
                activity_08='" . ((!empty ($activity_08)) ? addslashes($activity_08) : 'NULL') . "',
                activity_08_other='" . ((!empty ($activity_08_other)) ? addslashes($activity_08_other) : 'NULL') . "',
                activity_08_participation='" . ((!empty ($activity_08_participation_string)) ? addslashes($activity_08_participation_string) : 'NULL') . "',
                activity_08_honors='" . ((!empty ($activity_08_honors)) ? addslashes($activity_08_honors) : 'NULL') . "',
                activity_09='" . ((!empty ($activity_09)) ? addslashes($activity_09) : 'NULL') . "',
                activity_09_other='" . ((!empty ($activity_09_other)) ? addslashes($activity_09_other) : 'NULL') . "',
                activity_09_participation='" . ((!empty ($activity_09_participation_string)) ? addslashes($activity_09_participation_string) : 'NULL') . "',
                activity_09_honors='" . ((!empty ($activity_09_honors)) ? addslashes($activity_09_honors) : 'NULL') . "',
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