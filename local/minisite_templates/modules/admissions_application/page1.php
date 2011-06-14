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
        'submitter_ip' => 'hidden',
        'creation_date' => 'hidden'
    );
    var $required = array('student_type', 'enrollment_term', 'citizenship_status');
    var $display_name = 'Enrollment Info';
    var $error_header_text = 'Please check your form.';


    function on_first_time() {
//        $this->add_element('transfer_note', 'comment', array('text' => '<noscript>Hey there!</noscript>'));
//        $this->add_element('citizenship_note', 'comment', array('text' => '<noscript>Hey there!</noscript>'));
    }

    // style up the form and add comments et al
    function on_every_time() {
        pray($this->actions);
        $this->set_value('submitter_ip', $_SERVER['REMOTE_ADDR']);
        
        $this->show_form = true;

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
//        } else {
//            $this->show_form = true;
//            #######################
//        }
        $this->pre_fill_form();
    }

    function no_show_form() {
        echo '<br /> session name: ' . $this->sess->sess_name;
        echo "<br /> session id: " . session_id();
        echo '<br>' . $this->openid_id;

        $url = get_current_url();
        $parts = parse_url($url);
        $url = $parts['scheme'] . '://' . $parts['host'] . '/openid/?dest_page=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];

//        $txt = '<h3>Hi There!</h3>';
//        //$txt .= '<p>You are not currently logged in. Luther College students and alumni have access to this form. The contents will be displayed after you login.' . "\n";
//        $txt .= '<p>To begin or resume your application, please <a href="' . $url . '">log in</a> using an Open ID.</p>';
//        $txt .= '<div class = "loginlogout">';
//        $txt .= '<a href="' . $url . '">Log In</a>';
//        $txt .= '</div>';
//        return $txt;
        $url = get_current_url();
        try {
            $next_url = $_GET['next'];
        } catch (Exception $e) {
            $next_url = '';
        }
        if ($url) {
            $url = 'https://reasondev.luther.edu/reason/open_id/new_token.php?next=' . $url;
        } else {
            $url = 'https://reasondev.luther.edu/reason/open_id/new_token.php';
        }

        //$parts = parse_url($url);
        //$url = $parts['scheme'] . '://' . $parts['host'] . '/login/?dest_page=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        echo $url;

        echo '<iframe src="https://luthertest2.rpxnow.com/openid/embed?token_url=' . $url . '"
    scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe>';
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
        parent::process();
        set_applicant_data($this->openid_id, $this);

//        connectDB('admissions_applications_connection');
//
//        $qstring = "INSERT INTO `applicants` SET
//                submitter_ip='" . addslashes($this->get_value('submitter_ip')) . "',
//                open_id='" . addslashes($this->get_value('open_id')) . "',
//                creation_date='" . addslashes($this->get_value('creation_date')) . "',
//		student_type='" . addslashes($this->get_value('student_type')) . "',
//		enrollment_term='" . addslashes($this->get_value('enrollment_term')) . "',
//		citizenship_status='" . addslashes($this->get_value('citizenship_status')) . "' ";
//
//        $qresult = db_query($qstring);
//
//        //connect back with the reason DB
//        connectDB(REASON_DB);
    }

    function run_error_checks() {
        parent::run_error_checks();

        if ($this->get_value('citizenship_status') == 'not a citizen') {
            $this->set_error('citizenship_status', 'International Students - Please apply using the <a href="http://www.commonapp.org" target=_blank>Common App</a>.');
        }
    }

}

?>