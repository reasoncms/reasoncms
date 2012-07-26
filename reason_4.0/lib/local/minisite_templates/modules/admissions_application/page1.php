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
            'text' => '<h4>What type of student will you be enrolling as?</h4>',
        ),
        'student_type' => array(
            'type' => 'radio_no_sort',
            'comments' => '<div id="transfer_dialog" title="Transfer Students">
                <strong>Note:</strong> If you are a transfer student and have filled out a Luther College Application for Admission
                within the past two years, you do not need to complete this application. Please contact
                the <a href="mailto:admissions@luther.edu?Subject=Previous%20Transfer%20Student%20Applicant">Admissions Office</a>
                (800-4-LUTHER, ext. 1287) to reopen your admissions file.</div>',
            'options' => array('FR' => 'First Year', 'TR' => 'Transfer'),
        ),
        'enrollment_term_comment' => array(
            'type' => 'comment',
            'text' => '<h4>When do you wish to enroll at Luther?</h4>',
        ),
		// hard coding terms since the logic isn't working
        'enrollment_term' => array(
            'type' => 'radio_no_sort',
			'options' => array(	'2013FA' => 'Fall 2013',
								'2014JT' => 'J-term 2014',
								'2014SP' => 'Spring 2014',
						)
        ),
        'citizenship_status_comment' => array(
            'type' => 'comment',
            'text' => '<h4>What is your citizenship status?</h4>',
        ),
        'citizenship_status' => array(
            'type' => 'radio_no_sort',
            'comments' => '<div id="citizenship_dialog" title="International Students">
                <strong>Note:</strong> This online application is designed for US citizens and permanent residents applying to Luther. 
                If you are an international student applicant, please apply using
                the <a href="http://www.commonapp.org" target=_blank>Common Application</a>.</div>',
            'options' => array(
                'citizen' => 'U.S. Citizen',
                'dual' => 'U.S./Dual Citizen',
                'resident' => 'Permanent Resident',
                'not a citizen' => 'Not a U.S. citizen or permanent resident')
        ),
        'logout1' => array(
            'type' => 'hidden',
        ),
    );
    var $required = array('student_type', 'enrollment_term', 'citizenship_status');
    var $display_name = 'Enrollment Info';
    var $error_header_text = 'Please check your form.';

    ///////////////
    // style up the form and add comments et al
    function on_every_time() {
        $this->openid_id = check_open_id($this);
        if (is_submitted($this->openid_id)) {
            echo(already_submitted_message());
            $this->show_form = false;
        } else {
            $this->show_form = true;
// Commenting out enrollment term logic as it wasn't working
//        $date = getdate();
//
//        $cur_date = date("Y-m-d");
//
//        $add_next_fall = date($date['year'] . "-06-01");
//        $fa_deadline = date($date['year'] . "-08-15");
//        $jt_deadline = date($date['year'] . "-12-15");
//        $sp_deadline = date($date['year'] . "-01-15");
//
//        if ($cur_date > $fa_deadline) {
//            ($fa_year = date('Y') + 1);
//        } else {
//            $fa_year = date('Y');
//        }
//
//        if ($cur_date > $jt_deadline && $cur_date <= (date('Y') . "-12-31")) {
//            $jt_year = date('Y') + 2;
//        } else {
//            $jt_year = date('Y') + 1;
//        }
//
//        if ($cur_date > $sp_deadline) {
//            $sp_year = date('Y') + 1;
//        } else {
//            $sp_year = date('Y');
//        }
//
//
//        // if passed fall deadline but not jterm one
//        if ($cur_date > $fa_deadline && $cur_date < $jt_deadline) {
//            $this->enroll_term = array(
//                $jt_year . 'JT' => 'J-term ' . $jt_year,
//                $sp_year . 'SP' => 'Spring ' . $sp_year,
//                $fa_year . 'FA' => 'Fall ' . $fa_year);
//        }
//
//        // if not passed fall deadline
//        if ($cur_date < $fa_deadline && $cur_date > $sp_deadline) {
//            $this->enroll_term = array(
//                $fa_year . 'FA' => 'Fall ' . $fa_year,
//                $jt_year . 'JT' => 'J-term ' . $jt_year,
//                $sp_year . 'SP' => 'Spring ' . $sp_year
//            );
//            if ($cur_date >
//                    $add_next_fall){
//                $this->enroll_term = array(
//                    $fa_year . 'FA' => 'Fall ' . $fa_year,
//                    $jt_year . 'JT' => 'J-term ' . $jt_year,
//                    $sp_year . 'SP' => 'Spring ' . $sp_year,
//                    ($fa_year + 1) . 'FA' => 'Fall ' . ($fa_year + 1)
//                );
//            }
//        }
//
//        //if passed fall and jterm deadline
//        if ($cur_date < $sp_deadline && $cur_date > $jt_deadline) {
//            $this->enroll_term = array(
//                $sp_year . 'SP' => 'Spring ' . $sp_year,
//                $fa_year . 'FA' => 'Fall ' . $fa_year,
//                $jt_year . 'JT' => 'J-term ' . $jt_year);
//        }
//
//        $this->change_element_type('enrollment_term', 'radio_no_sort', array(
//            'options' => $this->enroll_term));

        $this->pre_fill_form();
        }
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
        if ($this->openid_id){
            // get an existing users data from the db based on openid_id and the form
            get_applicant_data($this->openid_id, $this);
        } else {
            // no show form, invite to login
            $this->show_form = false;
        }
    }

    function process() {
        set_applicant_data($this->openid_id, $this);
        check_logout($this);
    }

    function run_error_checks() {
//        parent::run_error_checks();

        if ($this->get_value('citizenship_status') == 'not a citizen') {
            $this->set_error('citizenship_status', 'International Students - This online application is designed for US citizens and permanent
                residents applying to Luther. If you are an international student applicant, please apply using
                the <a href="http://www.commonapp.org" target=_blank>Common Application</a>.');
        }
        $this->_error_flag = false;
    }

}

?>