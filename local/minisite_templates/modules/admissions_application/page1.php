<?php

include_once 'application_utils.php';

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

    var $openid_id;
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
            'comments' => '<div id="transfer_dialog" title="Transfer Students">
                <strong>Note:</strong> If you are a transfer student and filled out an application last year, then we\'ve got you covered.
                Contact the <a href="mailto:admissions@luther.edu?Subject=Previous%20Transfer%20Student%20Applicant">Admissions Office</a> (800-4-LUTHER) to
                restart the process.</div>',
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
            'comments' => '<div id="citizenship_dialog" title="International Students">
                <strong>Note:</strong> International Students should apply using
                the <a href="http://www.commonapp.org" target=_blank>Common App</a>.</div>',
            'options' => array(
                'citizen' => 'U.S. Citizen',
                'dual' => 'U.S./Dual Citizen',
                'resident' => 'Permanent Resident',
                'not a citizen' => 'Not a U.S. citizen or permanent resident')
        ),
    );
    var $required = array('student_type', 'enrollment_term', 'citizenship_status');
    var $display_name = 'Enrollment Info';
    var $error_header_text = 'Please check your form.';

    ///////////////
    // style up the form and add comments et al
    function on_every_time() {
        $this->show_form = true;

        $date = getdate();

        $cur_date = date("Y-m-d");

        $fa_deadline = date($date['year'] . "-08-01");
        $jt_deadline = date($date['year'] . "-12-15");
        $sp_deadline = date($date['year'] . "-01-15");

        if ($cur_date > $fa_deadline) {
            ($fa_year = date('Y') + 1);
        } else {
            $fa_year = date('Y');
        }

        if ($cur_date > $jt_deadline && $cur_date <= (date('Y') . "-12-31")) {
            $jt_year = date('Y') + 2;
        } else {
            $jt_year = date('Y') + 1;
        }

        if ($cur_date > $sp_deadline) {
            $sp_year = date('Y') + 1;
        } else {
            $sp_year = date('Y');
        }


        // if passed fall deadline but not jterm one
        if ($cur_date > $fa_deadline && $cur_date < $jt_deadline) {
            $this->enroll_term = array(
                $jt_year . 'JT' => 'J-term ' . $jt_year,
                $sp_year . 'SP' => 'Spring ' . $sp_year,
                $fa_year . 'FA' => 'Fall ' . $fa_year);
        }
        
        // if not passed fall deadline
        if ($cur_date < $fa_deadline && $cur_date > $sp_deadline) {
            $this->enroll_term = array(
                $fa_year . 'FA' => 'Fall ' . $fa_year,
                $jt_year . 'JT' => 'J-term ' . $jt_year,
                $sp_year . 'SP' => 'Spring ' . $sp_year);
        }
        
        //if passed fall and jterm deadline
        if ($cur_date < $sp_deadline && $cur_date > $jt_deadline) {
            $this->enroll_term = array(
                $sp_year . 'SP' => 'Spring ' . $sp_year,
                $fa_year . 'FA' => 'Fall ' . $fa_year,
                $jt_year . 'JT' => 'J-term ' . $jt_year);
        }

        $this->change_element_type('enrollment_term', 'radio_no_sort', array(
            'options' => $this->enroll_term));

        $this->pre_fill_form();
    }

    function no_show_form() {
        echo(check_login(get_current_url(), $this));
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageOne">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

    function pre_fill_form() {
        // check if the open_id has is set
        $o_id = check_open_id($this);
        if ($o_id) {
            // get an existing users data from the db based on openid_id and the form
            get_applicant_data($o_id, $this);
        } else {
            // no show form, invite to login
            $this->show_form = false;
        }
    }

    function process() {
        set_applicant_data($this->openid_id, $this);
    }

    function run_error_checks() {
        parent::run_error_checks();

        if ($this->get_value('citizenship_status') == 'not a citizen') {
            $this->set_error('citizenship_status', 'International Students - Please apply using the <a href="http://www.commonapp.org" target=_blank>Common App</a>.');
        }
    }

}

?>