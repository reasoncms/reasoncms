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
        'activities_1_header' => array(
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
        'hr' => 'hr',
        'activities_2_header' => array(
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
        'hr2' => 'hr',
        'activities_3_header' => array(
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
        'hr3' => 'hr',
        'activities_4_header' => array(
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
        'hr4' => 'hr',
        'activities_5_header' => array(
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
        'hr5' => 'hr',
        'activities_6_header' => array(
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
        'hr6' => 'hr',
        'activities_7_header' => array(
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
        'hr7' => 'hr',
        'activities_8_header' => array(
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
        'hr8' => 'hr',
        'activities_9_header' => array(
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
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageFive">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }
}

?>