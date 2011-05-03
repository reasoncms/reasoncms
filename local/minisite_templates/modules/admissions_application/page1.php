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
 *  First page of the application
 *
 *  Enrollment Term
 *  Student Type
 * 
 */
class ApplicationPageOne extends FormStep {

    var $_log_errors = true;
    var $error;
    var $elements = array(
        'header' => array(
            'type' => 'comment',
            'text' => '<h3>Enrollment Information</h3>',
        ),
        'student_type_comment' => array(
            'type' => 'comment',
            'text' => 'What type of student will you be enrolling as?',
        ),
        'student_type' => array(
            'type' => 'radio_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('FR' => 'First Year', 'TR' => 'Transfer'),
        ),
        'enrollment_term_comment' => array(
            'type' => 'comment',
            'text' => 'When do you wish to enroll at Luther?',
        ),
        'enrollment_term' => array(
            'type' => 'text',
            'display_name' => '&nbsp;',
        ),
        'citizenship_status_comment' => array(
            'type' => 'comment',
            'text' => 'What is your citizenship status?',
        ),
        'citizenship_status' => array(
            'type' => 'radio_no_sort',
            'display_name' => '&nbsp;',
            'options' => array(
                'citizen' => 'U.S. Citizen',
                'dual' => 'U.S./Dual Citizen',
                'resident' => 'Permanent Resident',
                'not a citizen' => 'Not a U.S, citizen or permanent resident')
        ),
        'submitter_ip' => 'hidden',
    );
    var $required = array('student_type', 'enrollment_term', 'citizenship_status');
    var $display_name = 'Enrollment Info';
    var $error_header_text = 'Please check your form.';

    // style up the form and add comments et al
    function on_every_time() {
        $this->set_value('submitter_ip', $_SERVER['REMOTE_ADDR']);


        $date = getdate();
        $jt_year = $date['year'];
        $sp_year = $date['year'];
        $fa_year = $date['year'];

        if ($date['mon'] <= 3) {
            $year = $date['year'];
        } else {
            $year = $date['year'] + 1;
        }

        $this->change_element_type('enrollment_term', 'radio_no_sort', array(
            'options' => array(
                $year . 'FA' => 'Fall 2011',
                $year . 'JT' => 'J-term 2012',
                $year . 'SP' => 'Spring 2012')));
            //'comments' => '<em>Show some year logic here</em>'));
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageOne">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

}
?>