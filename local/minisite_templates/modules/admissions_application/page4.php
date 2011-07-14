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
 *  Fourth page of the application
 *
 *  Education Information
 *      Previous High Schools
 *      Previous Colleges
 *      Test Scores
 */
class ApplicationPageFour extends FormStep {

    var $openid_id;
    var $_log_errors = true;
    var $error;
    var $elements = array(
        'education_header' => array(
            'type' => 'comment',
            'text' => '<h3>Your Education</h3>',
        ),
        'current_high_school_header' => array(
            'type' => 'comment',
            'text' => '<h4>Current High School</h4>',
        ),
        'final_high_school_header' => array(
            'type' => 'comment',
            'text' => '<h4>Final High School</h4>',
        ),
        'hs_name' => array(
            'type' => 'text',
            'display_name' => 'Name, City, State (e.g. IA)',
        ),
        'hs_ceeb' => 'hidden',
//        'hs_address' => array(
//            'type' => 'text',
//            'display_name' => 'Address',
//            'size' => 20,
//            'comments' => '¿¿¿¿If we are pulling from CEEB, is this needed????'
//        ),
//        'hs_city' => array(
//            'type' => 'text',
//            'display_name' => 'City',
//            'size' => 15,
//        ),
//        'hs_state_province' => array(
//            'type' => 'state_province',
//            'display_name' => 'State/Province',
//        ),
//        'hs_zip_postal' => array(
//            'type' => 'text',
//            'display_name' => 'Zip/Postal Code',
//            'size' => 8,
//        ),
//        'hs_country' => array(
//            'type' => 'Country',
//            'display_name' => 'Country',
//        ),
        'hs_grad_year' => array(
            'type' => 'text',
            'display_name' => 'Year of graduation',
            'size' => 4,
        ),
        'college_1_header' => array(
            'type' => 'comment',
            'text' => '<h4>College/University</h4>',
        ),
        'college_1_name' => array(
            'type' => 'text',
            'display_name' => 'Name',
        ),
        'college_1_ceeb' => 'hidden',
//        'college_1_address' => array(
//            'type' => 'text',
//            'display_name' => 'Address',
//            'size' => 20,
//            'comments' => '¿¿¿¿If we are pulling from CEEB, is this needed????'
//        ),
//        'college_1_city' => array(
//            'type' => 'text',
//            'display_name' => 'City',
//            'size' => 15,
//        ),
//        'college_1_state_province' => array(
//            'type' => 'state_province',
//            'display_name' => 'State/Province',
//        ),
//        'college_1_zip_postal' => array(
//            'type' => 'text',
//            'display_name' => 'Zip/Postal Code',
//            'size' => 8,
//        ),
//        'college_1_country' => array(
//            'type' => 'Country',
//            'display_name' => 'Country',
//        ),
        'college_2_hr' => 'hr',
        'college_2_header' => array(
            'type' => 'comment',
            'text' => '<h4>College/University</h4>',
        ),
        'college_2_name' => array(
            'type' => 'text',
            'display_name' => 'Name',
        ),
        'college_2_ceeb' => 'hidden',
//        'college_2_address' => array(
//            'type' => 'text',
//            'display_name' => 'Address',
//            'size' => 20,
//            'comments' => '¿¿¿¿If we are pulling from CEEB, is this needed????'
//        ),
//        'college_2_city' => array(
//            'type' => 'text',
//            'display_name' => 'City',
//            'size' => 15,
//        ),
//        'college_2_state_province' => array(
//            'type' => 'state_province',
//            'display_name' => 'State/Province',
//        ),
//        'college_2_zip_postal' => array(
//            'type' => 'text',
//            'display_name' => 'Zip/Postal Code',
//            'size' => 8,
//        ),
//        'college_2_country' => array(
//            'type' => 'Country',
//            'display_name' => 'Country',
//        ),
        'college_3_hr' => 'hr',
        'college_3_header' => array(
            'type' => 'comment',
            'text' => '<h4>College/University</h4>',
        ),
        'college_3_name' => array(
            'type' => 'text',
            'display_name' => 'Name',
        ),
        'college_3_ceeb' => 'hidden',
//        'college_3_address' => array(
//            'type' => 'text',
//            'display_name' => 'Address',
//            'size' => 20,
//            'comments' => '¿¿¿¿If we are pulling from CEEB, is this needed????'
//        ),
//        'college_3_city' => array(
//            'type' => 'text',
//            'display_name' => 'City',
//            'size' => 15,
//        ),
//        'college_3_state_province' => array(
//            'type' => 'state_province',
//            'display_name' => 'State/Province',
//        ),
//        'college_3_zip_postal' => array(
//            'type' => 'text',
//            'display_name' => 'Zip/Postal Code',
//            'size' => 8,
//        ),
//        'college_3_country' => array(
//            'type' => 'Country',
//            'display_name' => 'Country',
//        ),
        'add_college_button' => array(
            'type' => 'comment',
            'text' => '<span id="addCollege" title="Add College" class="addButton">
                Add a College
                </span>'
        ),
        'remove_college_button' => array(
            'type' => 'comment',
            'text' => '<span id="removeCollege" title="Remove College" class="removeButton">
                Remove a College
                </span>'
        ),
        'tests_header' => array(
            'type' => 'comment',
            'text' => '<h3>Standardized Tests</h3>'
        ),
        'taken_tests_comment' => array(
            'type' => 'comment',
            'text' => 'Have you taken the SAT and/or ACT tests?'
        ),
        'taken_tests' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('Yes' => 'Yes', 'No' => 'No'),
        ),
        'sat_scores_comment' => array(
            'type' => 'comment',
            'text' => 'Please provide your best SAT scores.'
        ),
        'sat_math' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => '&nbsp;',
            'comments' => 'Math'
        ),
        'sat_critical_reading' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => '&nbsp;',
            'comments' => 'Critical Reading'
        ),
        'sat_writing' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => '&nbsp;',
            'comments' => 'Writing'
        ),
        'act_scores_comment' => array(
            'type' => 'comment',
            'text' => 'Please provide your best ACT score.'
        ),
        'act_composite' => array(
            'type' => 'text',
            'size' => 2,
            'display_name' => '&nbsp;',
            'comments' => 'Composite'
        ),
        'logout4' => array(
            'type' => 'hidden',
        ),
    );

    var $display_name = 'Education';
    var $error_header_text = 'Please check your form.';
    var $element_group_info = array(
        'college_button_group' =>array(
            'type' => 'inline',
            'elements' => array('add_college_button', 'remove_college_button'),
            'args' => array('use_element_labels' => false, 'display_name' => '&nbsp;'),
        ),
    );

    function no_show_form() {
        echo(check_login());
    }

     function on_every_time() {
         $this->openid_id = check_open_id($this);
         if (is_submitted($this->openid_id)){
            die('It appears that you\'ve already submitted your application. If you\'d like to amend your application or have questions
                regarding, please contact the Admissions Office at 800-4-LUTHER.');
        }else{
        $this->show_form = true;
        }
        foreach ($this->element_group_info as $name => $info) {
            $this->add_element_group($info['type'], $name, $info['elements'], $info['args']);
        }
        $this->move_element('college_button_group', 'after', 'college_3_name');

        if ($this->controller->get('student_type') == 'FR') {
            $this->change_element_type('final_high_school_header', 'hidden');
        } else {
            $this->change_element_type('current_high_school_header', 'hidden');
        }

        $this->pre_fill_form();
     }

     function pre_fill_form() {
        // check if the open_id has is set
        if ($this->openid_id) {
            // get an existing users data from the db based on openid_id and the form
            get_applicant_data($this->openid_id, $this);
        } else {
            // no show form, invite to login
            $this->show_form = false;
        }
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageFour">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

    function process() {
        set_applicant_data($this->openid_id, $this);
        check_logout($this);
    }
}
?>