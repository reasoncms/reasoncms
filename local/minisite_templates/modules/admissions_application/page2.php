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
 *  Second page of the application
 *
 *  Personal Information
 *      Name
 *      Address
 *      Citizenship/Ethnicity
 *      Faith
 */

class ApplicationPageTwo extends FormStep {

    var $openid_id;
    var $_log_errors = true;
    var $error;
    // the usual disco member data
    var $elements = array(
        'your_information_header' => array(
            'type' => 'comment',
            'text' => '<h3>Your Information</h3>'
        ),
        'name_comment' => array(
            'type' => 'comment',
            'text' => 'Please enter your name <strong>exactly</strong> as it appears on official documents.'
        ),
        'first_name' => array(
            'type' => 'text',
            'size' => 15,
        ),
        'middle_name' => array(
            'type' => 'text',
            'size' => 10,
        ),
        'last_name' => array(
            'type' => 'text',
            'display_name' => 'Last Name or Family Name',
            'size' => 15,
        ),
        'suffix' => array(
            'type' => 'text',
            'size' => 4
        ),
        'preferred_first_name' => array(
            'type' => 'text',
            'size' => 15,
        ),
        'gender' => array(
            'type' => 'radio_inline',
            'options' => array('F' => 'Female', 'M' => 'Male'),
        ),
        'date_of_birth' => array(
            'type' => 'textdate',
            'use_picker' => false
        ),
        'ssn_1' => array(
            'type' => 'text',
            'size' => 3,
        //'comments' => '<br><a href="#ssn">Why is this important?</a>'
        ),
        'ssn_dash_1' => array(
            'type' => 'comment',
            'text' => ' &ndash; '
        ),
        'ssn_2' => array(
            'type' => 'text',
            'size' => 2,
        ),
        'ssn_dash_2' => array(
            'type' => 'comment',
            'text' => ' &ndash; '
        ),
        'ssn_3' => array(
            'type' => 'text',
            'size' => 4,
        ),
        'email' => array(
            'type' => 'text',
            'size' => 35,
            'display_name' => 'E-mail Address',
        ),
        'home_phone' => array(
            'type' => 'text',
            'size' => 20,
        ),
        'cell_phone' => array(
            'type' => 'text',
            'size' => 20,
        ),
        'address_header' => array(
            'type' => 'comment',
            'text' => '<h3>Permanent Address Information</h3>',
        ),
        'permanent_address' => array(
            'type' => 'text',
            'display_name' => 'Address'
        ),
        'permanent_address_2' => array(
            'type' => 'text',
            'display_name' => 'Address Line 2'
        ),
        'permanent_apartment_number' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => 'Apt. #'
        ),
        'permanent_city' => array(
            'type' => 'text',
            'display_name' => 'City',
            'size' => 35,
        ),
        'permanent_state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
            'include_military_codes' => true,
        ),
        'permanent_zip_postal' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 8,
        ),
        'permanent_country' => array(
            'type' => 'country',
            'display_name' => 'Country',
        ),
        'mailing_address_comment' => array(
            'type' => 'comment',
            'text' => 'Is your mailing address different from your permanent address?'
        ),
        'different_mailing_address' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('Yes' => 'Yes', 'No' => 'No'),
        ),
        'mailing_address_header' => array(
            'type' => 'comment',
            'text' => '<h3>Mailing Address Information</h3>',
        ),
        'mailing_address' => array(
            'type' => 'text',
            'display_name' => 'Address'
        ),
        'mailing_address_2' => array(
            'type' => 'text',
            'display_name' => 'Address Line 2'
        ),
        'mailing_apartment_number' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => 'Apt. #'
        ),
        'mailing_city' => array(
            'type' => 'text',
            'display_name' => 'City',
            'size' => 35,
        ),
        'mailing_state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
            'include_military_codes' => true,
        ),
        'mailing_zip_postal' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 8,
        ),
        'mailing_country' => array(
            'type' => 'country',
            'display_name' => 'Country',
        ),
        'additional_information_header' => array(
            'type' => 'comment',
            'text' => '<h3>Additional Information</h3>'
        ),
        'heritage_comment' => array(
            'text' => 'If you wish to be identified with a particular ethnic group,
                        please select the choice that most accurately describes your heritge.',
            'type' => 'comment'
        ),
        'heritage' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'Are you Hispanic or Latino?',
            'options' => array('HI' => 'Yes', 'No' => 'No'),
        ),
        'race_comment' => array(
            'type' => 'comment',
            'text' => 'In addition, select one or more of the following racial categories to describe yourself.'
        ),
        'race' => array(
            'type' => 'checkboxgroup_no_sort',
            'display_name' => '&nbsp;',
            'options' => array(
                'AN' => 'American Indian or Alaska Native',
                'AS' => 'Asian',
                'BL' => 'Black or African American',
                'HI' => 'Hispanic',
                'HP' => 'Native Hawaiian or Other Pacific Islander',
                'WH' => 'White',
                'UN' => 'Unknown'
            ),
        ),
        'your_faith_header' => array(
            'type' => 'comment',
            'text' => '<h3>Your Faith</h3>
                         <div id="faith">
                        <a class="faith" href="#faith_dialog">Why is this important?</a></div>
                        <div id="faith_dialog" title="Your Faith">Luther College collects home congregation information solely for the purpose of awarding
                                <a href="/financialaid/prospective/scholarships/epic/" target=__blank>Education Partners in Covenant (EPIC) Matching Grants</a>.
                                EPIC grants represent a cooperative venture between Luther College and any church congregation that chooses to financially support
                                student(s) at Luther.'
        ),
        'church_name' => 'text',
        'church_city' => array(
            'type' => 'text',
            'size' => 15,
        ),
        'church_state' => 'state',
        'religion' => array(
            'type' => 'select',
            'add_null_value_to_top' => true,
            'options' => array(
                'CR' => 'Roman Catholic',
                'LE' => 'Lutheran ELCA',
                'LL' => 'Lutheran LC-MS',
                'LO' => 'Lutheran Other',
                'LU' => 'Lutheran Unknown',
                'LW' => 'Lutheran Wisconsin',
                'NB' => 'Buddhist',
                'NH' => 'Hindu',
                'NJ' => 'Jewish',
                'NM' => 'Muslim',
                'NO' => 'Non-Christian Other',
                'NU' => 'Non-Christian Unknown',
                'PA' => 'Assemblies of God',
                'PB' => 'Baptist',
                'PC' => 'Covenant',
                'PE' => 'Episcopal',
                'PK' => 'Christian Unknown',
                'PL' => 'Latter Day Saints/Mormon',
                'PM' => 'Methodist',
                'PO' => 'Christian Other',
                'PP' => 'Presbyterian',
                'PQ' => 'Quaker (Friends)',
                'PU' => 'United Church of Christ',
                'RN' => 'None',
                'RU' => 'Unreported',
                'UN' => 'Unitarian'
            )
        ),
        'logout2' => array(
            'type' => 'hidden',
        ),
    );
    /**
     * Stores all the information necessary to instantiate each element group.
     * Format: element_group_name => element info
     * @var array
     */
    var $element_group_info = array(
        'name_group' => array(
            'type' => 'inline',
            'elements' => array('first_name', 'middle_name', 'last_name'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Name')
        ),
        'church_group' => array(
            'type' => 'inline',
            'elements' => array('church_city', 'church_state'),
            'args' => array('use_element_labels' => false, 'display_name' => 'City/State')
        ),
        'ssn_group' => array(
            'type' => 'inline',
            'elements' => array('ssn_1', 'ssn_dash_1', 'ssn_2', 'ssn_dash_2', 'ssn_3'),
            'args' => array(
                'use_element_labels' => false,
                'display_name' => 'U.S.&nbsp;Social&nbsp;Security&nbsp;Number',
//                'comments' => '<div id="ssn"><a href="#ssn_dialog">Why is this important?</a></div>
//                        <div id="ssn_dialog" title="Social Security Info">
//                        Required for US Citizens and Permanent Residents applying for financial aid via FAFSA</div>')
                'comments' => '<div class="formComment">Required for US citizens and permanent residents applying for financial aid via FAFSA</div>')
        ),
    );
    var $no_session = array('ssn_1', 'ssn_2', 'ssn_3');
    var $required = array('first_name', 'middle_name', 'last_name', 'gender', 'date_of_birth',
        'email', 'home_phone', 'permanent_address', 'permanent_city',
        'permanent_state_province', 'permanent_state_province', 'permanent_zip_postal',
        'permanent_country', 'different_mailing_address', 'mailing_address', 'mailing_city',
        'mailing_state_province', 'mailing_zip_postal', 'mailing_country');
    var $error_checks = array(
        'phone' => array(
            'is_phone_number' => 'Invalid Phone Number',
        ),
    );
    var $display_name = 'Personal Info';
    var $error_header_text = 'Please check your form.';

    function is_phone_number($num) {
        return true;
    }

    // style up the form and add comments et al
    function on_every_time() {
        $this->openid_id = check_open_id($this);
        if (is_submitted($this->openid_id)) {
            echo(already_submitted_message());
            $this->show_form = false;
        } else {
            $this->show_form = true;

            //add element groups
            foreach ($this->element_group_info as $name => $info) {
                $this->add_element_group($info['type'], $name, $info['elements'], $info['args']);
            }

            $this->move_element('name_group', 'before', 'suffix');
            $this->move_element('ssn_group', 'after', 'date_of_birth');
            $this->pre_fill_form();
        }
    }

    function no_show_form() {
        echo(check_login());
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
        echo '<div id="admissionsApp" class="pageTwo">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

    function process() {
        set_applicant_data($this->openid_id, $this);
        check_logout($this);
    }

    function run_error_checks() {
        $this->_error_flag = false;
    }

}

?>