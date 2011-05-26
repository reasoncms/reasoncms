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
        'Stuff' => 'Stuff',
        'Stuff2' => 'Stuff2',
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
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageFive">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }
}

?>