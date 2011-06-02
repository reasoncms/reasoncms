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
 *  Fourth page of the application
 *
 *  Education Information
 *      Previous High Schools
 *      Previous Colleges
 *      Test Scores
 */
class ApplicationPageFour extends FormStep {

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
            'display_name' => 'Name',
            'size' => 20,
        ),
        'hs_address' => array(
            'type' => 'text',
            'display_name' => 'Address',
            'size' => 20,
            'comments' => '¿¿¿¿If we are pulling from CEEB, is this needed????'
        ),
        'hs_city' => array(
            'type' => 'text',
            'display_name' => 'City',
            'size' => 15,
        ),
        'hs_state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
        ),
        'hs_zip_postal' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 8,
        ),
        'hs_country' => array(
            'type' => 'Country',
            'display_name' => 'Country',
        ),
        'hs_grad_year' => array(
            'type' => 'year',
            'display_name' => 'Year of graduation',
        ),
        'college_1_header' => array(
            'type' => 'comment',
            'text' => '<h4>College/University</h4>',
        ),
        'college_1_name' => array(
            'type' => 'text',
            'display_name' => 'Name',
            'size' => 20,
        ),
        'college_1_address' => array(
            'type' => 'text',
            'display_name' => 'Address',
            'size' => 20,
            'comments' => '¿¿¿¿If we are pulling from CEEB, is this needed????'
        ),
        'college_1_city' => array(
            'type' => 'text',
            'display_name' => 'City',
            'size' => 15,
        ),
        'college_1_state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
        ),
        'college_1_zip_postal' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 8,
        ),
        'college_1_country' => array(
            'type' => 'Country',
            'display_name' => 'Country',
        ),
        'college_2_hr' => 'hr',
        'college_2_header' => array(
            'type' => 'comment',
            'text' => '<h4>College/University</h4>',
        ),
        'college_2_name' => array(
            'type' => 'text',
            'display_name' => 'Name',
            'size' => 20,
        ),
        'college_2_address' => array(
            'type' => 'text',
            'display_name' => 'Address',
            'size' => 20,
            'comments' => '¿¿¿¿If we are pulling from CEEB, is this needed????'
        ),
        'college_2_city' => array(
            'type' => 'text',
            'display_name' => 'City',
            'size' => 15,
        ),
        'college_2_state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
        ),
        'college_2_zip_postal' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 8,
        ),
        'college_2_country' => array(
            'type' => 'Country',
            'display_name' => 'Country',
        ),
        'college_3_hr' => 'hr',
        'college_3_header' => array(
            'type' => 'comment',
            'text' => '<h4>College/University</h4>',
        ),
        'college_3_name' => array(
            'type' => 'text',
            'display_name' => 'Name',
            'size' => 20,
        ),
        'college_3_address' => array(
            'type' => 'text',
            'display_name' => 'Address',
            'size' => 20,
            'comments' => '¿¿¿¿If we are pulling from CEEB, is this needed????'
        ),
        'college_3_city' => array(
            'type' => 'text',
            'display_name' => 'City',
            'size' => 15,
        ),
        'college_3_state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
        ),
        'college_3_zip_postal' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 8,
        ),
        'college_3_country' => array(
            'type' => 'Country',
            'display_name' => 'Country',
        ),
        'add_college_button' => array(
            'type' => 'comment',
            'text' => '<div id="addCollege" title="Add College" class="addButton">
                Add College
                </div>'
        ),
        'remove_college_button' => array(
            'type' => 'comment',
            'text' => '<div id="removeCollege" title="Remove College" class="removeButton">
                Remove College
                </div>'
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
            'text' => 'Please provide your best SAT scores'
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
            'text' => 'Please provide your best ACT score'
        ),
        'act_composite' => array(
            'type' => 'text',
            'size' => 2,
            'display_name' => '&nbsp;',
            'comments' => 'Composite'
        ),
    );
    var $element_group_info = array(
        'college_button_group' =>array(
            'type' => 'inline',
            'elements' => array('add_college_button', 'remove_college_button'),
//            'args' => array('use_element_labels' => false, 'display_name' => '&nbsp;'),
            'args' => array('use_element_labels' => false, 'use_group_display_name' => false, 'span_columns' => true),
        )
    );

    var $display_name = 'Education';
    var $error_header_text = 'Please check your form.';

     function on_every_time() {
        foreach ($this->element_group_info as $name => $info) {
            $this->add_element_group($info['type'], $name, $info['elements'], $info['args']);
        }
        $this->move_element('college_button_group', 'after', 'college_3_country');
     }

    // style up the form and add comments et al
    function on_first_time() {
        if ($this->controller->get('student_type') == 'FR') {
            $this->change_element_type('final_high_school_header', 'hidden');
        } else {
            $this->change_element_type('current_high_school_header', 'hidden');
        }
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageFour">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

    function  process() {
        parent::process();

        connectDB('admissions_applications_connection');

        $hs_name = $this->get_value('hs_name');
        $hs_address = $this->get_value('hs_address');
        $hs_city = $this->get_value('hs_city');
        $hs_state_province = $this->get_value('hs_state_province');
        $hs_zip_postal = $this->get_value('hs_zip_postal');
        $hs_country = $this->get_value('hs_country');
        $hs_grad_year = $this->get_value('hs_grad_year');
        $college_1_name = $this->get_value('college_1_name');
        $college_1_address = $this->get_value('college_1_address');
        $college_1_city = $this->get_value('college_1_city');
        $college_1_state_province = $this->get_value('college_1_state_province');
        $college_1_zip_postal = $this->get_value('college_1_zip_postal');
        $college_1_country = $this->get_value('college_1_country');
        $college_2_name = $this->get_value('college_2_name');
        $college_2_address = $this->get_value('college_2_address');
        $college_2_city = $this->get_value('college_2_city');
        $college_2_state_province = $this->get_value('college_2_state_province');
        $college_2_zip_postal = $this->get_value('college_2_zip_postal');
        $college_2_country = $this->get_value('college_2_country');
        $college_3_name = $this->get_value('college_3_name');
        $college_3_address = $this->get_value('college_3_address');
        $college_3_city = $this->get_value('college_3_city');
        $college_3_state_province = $this->get_value('college_3_state_province');
        $college_3_zip_postal = $this->get_value('college_3_zip_postal');
        $college_3_country = $this->get_value('college_3_country');
        $taken_tests = $this->get_value('taken_tests');
        $sat_math = $this->get_value('sat_math');
        $sat_critical_reading = $this->get_value('sat_critical_reading');
        $sat_writing = $this->get_value('sat_writing');
        $act_composite = $this->get_value('act_composite');

        $qstring = "INSERT INTO `applicants` SET
                hs_name='" . ((!empty ($hs_name)) ? addslashes($hs_name) : 'NULL') . "',
                hs_address='" . ((!empty ($hs_address)) ? addslashes($hs_address) : 'NULL') . "',
                hs_city='" . ((!empty ($hs_city)) ? addslashes($hs_city) : 'NULL') . "',
                hs_state_province='" . ((!empty ($hs_state_province)) ? addslashes($hs_state_province) : 'NULL') . "',
		hs_zip_postal='" . ((!empty ($hs_zip_postal)) ? addslashes($hs_zip_postal) : 'NULL') . "',
		hs_country='" . ((!empty ($hs_country)) ? addslashes($hs_country) : 'NULL') . "',
                hs_grad_year='" . ((!empty ($hs_grad_year)) ? addslashes($hs_grad_year) : 'NULL') . "',
                college_1_name='" . ((!empty ($college_1_name)) ? addslashes($college_1_name) : 'NULL') . "',
                college_1_address='" . ((!empty ($college_1_address)) ? addslashes($college_1_address) : 'NULL') . "',
                college_1_city='" . ((!empty ($college_1_city)) ? addslashes($college_1_city) : 'NULL') . "',
                college_1_state_province='" . ((!empty ($college_1_state_province)) ? addslashes($college_1_state_province) : 'NULL')  . "',
                college_1_zip_postal='" . ((!empty ($college_1_zip_postal)) ? addslashes($college_1_zip_postal) : 'NULL') . "',
                college_1_country='" . ((!empty ($college_1_country)) ? addslashes($college_1_country) : 'NULL') . "',
                college_2_name='" . ((!empty ($college_2_name)) ? addslashes($college_2_name) : 'NULL') . "',
                college_2_address='" . ((!empty ($college_2_address)) ? addslashes($college_2_address) : 'NULL') . "',
                college_2_city='" . ((!empty ($college_2_city)) ? addslashes($college_2_city) : 'NULL') . "',
                college_2_state_province='" . ((!empty ($college_2_state_province)) ? addslashes($college_2_state_province) : 'NULL')  . "',
                college_2_zip_postal='" . ((!empty ($college_2_zip_postal)) ? addslashes($college_2_zip_postal) : 'NULL') . "',
                college_2_country='" . ((!empty ($college_2_country)) ? addslashes($college_2_country) : 'NULL') . "',
                college_3_name='" . ((!empty ($college_3_name)) ? addslashes($college_3_name) : 'NULL') . "',
                college_3_address='" . ((!empty ($college_3_address)) ? addslashes($college_3_address) : 'NULL') . "',
                college_3_city='" . ((!empty ($college_3_city)) ? addslashes($college_3_city) : 'NULL') . "',
                college_3_state_province='" . ((!empty ($college_3_state_province)) ? addslashes($college_3_state_province) : 'NULL')  . "',
                college_3_zip_postal='" . ((!empty ($college_3_zip_postal)) ? addslashes($college_3_zip_postal) : 'NULL') . "',
                college_3_country='" . ((!empty ($college_3_country)) ? addslashes($college_3_country) : 'NULL') . "',
                taken_tests='" . ((!empty ($taken_tests)) ? addslashes($taken_tests) : 'NULL') . "',
		sat_math='" . ((!empty ($sat_math)) ? addslashes($sat_math) : 'NULL') . "',
		sat_critical_reading='" . ((!empty ($sat_critical_reading)) ? addslashes($sat_critical_reading) : 'NULL') . "',
		sat_writing='" . ((!empty ($sat_writing)) ? addslashes($sat_writing) : 'NULL') . "',
		act_composite='" . ((!empty ($act_composite)) ? addslashes($act_composite) : 'NULL') . "' ";

        $qresult = db_query($qstring);

        //connect back with the reason DB
        connectDB(REASON_DB);
    }
}

?>
