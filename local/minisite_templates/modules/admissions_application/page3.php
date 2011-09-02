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
 *  Third page of the application
 *
 *  Family Information
 *      See common app for example
 *      Parent 1
 *      Parent 2
 *      Legal Guardian
 *      Siblings
 *
 */
class ApplicationPageThree extends FormStep {

    var $openid_id;
    var $_log_errors = true;
    var $error;
    var $elements = array(
        'family_comment' => array(
            'type' => 'comment',
            'text' => '<h3>Family</h3>
                <div id="family">
                <a class="why" href="#family_dialog">Why is this information important?</a></div>
                <div id="family_dialog" title="Family Information">Luther College collects this information for demographic purposes
                even if you are an adult or emancipated minor. Please list both parents below, even if one or more is deceased or no
                longer has legal responsibilities toward you. If you are a minor with a legal guardian (an individual or government entity),
                then please list that information below as well.</div>'
        ),
        'household_header' => array(
            'type' => 'comment',
            'text' => '<h3>Household</h3>',
        ),
        'parent_marital_status_comment' => array(
            'type' => 'comment',
            'text' => 'Parent\'s marital status (relative to each other)'
        ),
        'parent_marital_status' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array(
                'never married' => 'Never Married',
                'married' => 'Married',
                'civil union' => 'Civil Union/Domestic Partners',
                'widowed' => 'Widowed',
                'separated' => 'Separated',
                'divorced' => 'Divorced'
            ),
        ),
        'permanent_home_parent_comment' => array(
            'type' => 'comment',
            'text' => 'With whom do you make your permanent home?'
        ),
        'permanent_home_parent' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array(
                'parent1' => 'Parent 1',
                'parent2' => 'Parent 2',
                'both' => 'Both',
                'guardian' => 'Legal Guardian',
                'ward' => 'Ward of the Court/State',
                'other' => 'Other'
            )
        ),
        'parent_1_header' => array(
            'type' => 'comment',
            'text' => '<h3>Parent 1</h3>',
        ),
        'parent_1_type' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('mother' => 'Mother', 'father' => 'Father'),
        ),
        'parent_1_living' => array(
            'display_name' => 'Is Parent 1 living?',
            'type' => 'radio_inline_no_sort',
            'options' => array('yes' => 'Yes', 'no' => 'No')
        ),
        'parent_1_title' => array(
            'type' => 'select_no_label',
            'display_name' => '&nbsp;',
            'options' => array(
                'MRS' => 'Mrs.',
                'MR' => 'Mr.',
                'MS' => 'Ms.',
                'MI' => 'Miss',
                'DR' => 'Dr.',
                'RE' => 'Rev.',
                'BI' => 'Bishop',
                'CA' => 'Capt.',
                'CD' => 'Cdr.',
                'CHA' => 'Chaplain',
                'CO' => 'Col.',
                'CP' => 'CPT',
                'FA' => 'Father',
                'GO' => 'Gov.',
                'HO' => 'Hon',
                'JU' => 'Judge',
                'LC' => 'LCDR',
                'LT' => 'Lt.',
                'LTC' => 'Lt. Col.',
                'MA' => 'Major',
                //'MSG' => 'Monseigneur',
                'PA' => 'Pastor',
                'PR' => 'Pr.',
                'PRO' => 'Prof.',
                'RED' => 'Rev. Dr.',
                'REF' => 'Rev. Fr.',
                'SE' => 'Senator',
                'SG' => 'Sgt.',
                'SI' => 'Sister',
                'SR' => 'Sra.',
            )
        ),
        'parent_1_first_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'parent_1_middle_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'parent_1_last_name' => array(
            'type' => 'text',
            'size' => 20
        ),
        'parent_1_suffix' => array(
            'type' => 'text',
            'display_name' => 'Suffix',
            'size' => 5
        ),
        'parent_1_address' => array(
            'type' => 'text',
            'display_name' => 'Address'
        ),
        'parent_1_address_2' => array(
            'type' => 'text',
            'display_name' => 'Address Line 2'
        ),
        'parent_1_apartment_number' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => 'Apt. #'
        ),
        'parent_1_city' => array(
            'type' => 'text',
            'size' => 35,
            'display_name' => 'City'
        ),
        'parent_1_state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
            'include_military_codes' => true,
        ),
        'parent_1_zip_postal' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 8,
        ),
        'parent_1_country' => array(
            'type' => 'country',
            'display_name' => 'Country'
        ),
        'parent_1_phone_type' => array(
            'type' => 'select_no_sort',
            'add_null_value_to_top' => true,
            'options' => array('home' => 'Home', 'cell' => 'Cell', 'work' => 'Work'),
        ),
        'parent_1_phone' => array(
            'type' => 'text',
            'size' => 20
        ),
        'parent_1_email' => array(
            'type' => 'text',
            'display_name' => 'E-mail',
        ),
        'parent_1_occupation' => array(
            'type' => 'text',
            'display_name' => 'Occupation',
        ),
        'parent_1_employer' => array(
            'type' => 'text',
            'display_name' => 'Employer',
        ),
        'parent_2_header' => array(
            'type' => 'comment',
            'text' => '<h3>Parent 2</h3>',
        ),
        'parent_2_type' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('mother' => 'Mother', 'father' => 'Father'),
        ),
        'parent_2_living' => array(
            'display_name' => 'Is Parent 2 living?',
            'type' => 'radio_inline_no_sort',
            'options' => array('yes' => 'Yes', 'no' => 'No')
        ),
        'parent_2_title' => array(
            'type' => 'select_no_label',
            'display_name' => '&nbsp;',
            'options' => array(
                'MRS' => 'Mrs.',
                'MR' => 'Mr.',
                'MS' => 'Ms.',
                'MI' => 'Miss',
                'DR' => 'Dr.',
                'RE' => 'Rev.',
                'BI' => 'Bishop',
                'CA' => 'Capt.',
                'CD' => 'Cdr.',
                'CHA' => 'Chaplain',
                'CO' => 'Col.',
                'CP' => 'CPT',
                'FA' => 'Father',
                'GO' => 'Gov.',
                'HO' => 'Hon',
                'JU' => 'Judge',
                'LC' => 'LCDR',
                'LT' => 'Lt.',
                'LTC' => 'Lt. Col.',
                'MA' => 'Major',
                //'MSG' => 'Monseigneur',
                'PA' => 'Pastor',
                'PR' => 'Pr.',
                'PRO' => 'Prof.',
                'RED' => 'Rev. Dr.',
                'REF' => 'Rev. Fr.',
                'SE' => 'Senator',
                'SG' => 'Sgt.',
                'SI' => 'Sister',
                'SR' => 'Sra.',
            )
        ),
        'parent_2_first_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'parent_2_middle_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'parent_2_last_name' => array(
            'type' => 'text',
            'size' => 20
        ),
        'parent_2_suffix' => array(
            'type' => 'text',
            'display_name' => 'Suffix',
            'size' => 5
        ),
        'parent_2_address_same' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'Is Parent 2\'s address the same as the above parent?',
            'options' => array('yes' => 'Yes', 'no' => 'No'),
        ),
        'parent_2_address' => array(
            'type' => 'text',
            'display_name' => 'Address'
        ),
        'parent_2_address_2' => array(
            'type' => 'text',
            'display_name' => 'Address Line 2'
        ),
        'parent_2_apartment_number' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => 'Apt. #'
        ),
        'parent_2_city' => array(
            'type' => 'text',
            'size' => 35,
            'display_name' => 'City'
        ),
        'parent_2_state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
            'include_military_codes' => true,
        ),
        'parent_2_zip_postal' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 8,
        ),
        'parent_2_country' => array(
            'type' => 'country',
            'display_name' => 'Country'
        ),
        'parent_2_phone_type' => array(
            'type' => 'select_no_sort',
            'add_null_value_to_top' => true,
            'options' => array('home' => 'Home', 'cell' => 'Cell', 'work' => 'Work'),
        ),
        'parent_2_phone' => array(
            'type' => 'text',
            'size' => 20
        ),
        'parent_2_email' => array(
            'type' => 'text',
            'display_name' => 'E-mail',
        ),
        'parent_2_occupation' => array(
            'type' => 'text',
            'display_name' => 'Occupation',
        ),
        'parent_2_employer' => array(
            'type' => 'text',
            'display_name' => 'Employer',
        ),
        'guardian_header' => array(
            'type' => 'comment',
            'text' => '<h3>Legal Guardian</h3>',
        ),
        'guardian_relation' => array(
            'type' => 'text',
            'display_name' => 'Relationship to you',
            'size' => 20
        ),
        'guardian_title' => array(
            'type' => 'select_no_label',
            'display_name' => '&nbsp;',
            'options' => array(
                'MRS' => 'Mrs.',
                'MR' => 'Mr.',
                'MS' => 'Ms.',
                'MI' => 'Miss',
                'DR' => 'Dr.',
                'RE' => 'Rev.',
                'BI' => 'Bishop',
                'CA' => 'Capt.',
                'CD' => 'Cdr.',
                'CHA' => 'Chaplain',
                'CO' => 'Col.',
                'CP' => 'CPT',
                'FA' => 'Father',
                'GO' => 'Gov.',
                'HO' => 'Hon',
                'JU' => 'Judge',
                'LC' => 'LCDR',
                'LT' => 'Lt.',
                'LTC' => 'Lt. Col.',
                'MA' => 'Major',
                //'MSG' => 'Monseigneur',
                'PA' => 'Pastor',
                'PR' => 'Pr.',
                'PRO' => 'Prof.',
                'RED' => 'Rev. Dr.',
                'REF' => 'Rev. Fr.',
                'SE' => 'Senator',
                'SG' => 'Sgt.',
                'SI' => 'Sister',
                'SR' => 'Sra.',
            )
        ),
        'guardian_first_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'guardian_middle_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'guardian_last_name' => array(
            'type' => 'text',
            'size' => 20
        ),
        'guardian_suffix' => array(
            'type' => 'text',
            'size' => 5
        ),
        'guardian_address' => array(
            'type' => 'text',
            'display_name' => 'Address'
        ),
        'guardian_address_2' => array(
            'type' => 'text',
            'display_name' => 'Address Line 2'
        ),
        'guardian_apartment_number' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => 'Apt. #'
        ),
        'guardian_city' => array(
            'type' => 'text',
            'size' => 35,
            'display_name' => 'City'
        ),
        'guardian_state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
            'include_military_codes' => true,
        ),
        'guardian_zip_postal' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 8,
        ),
        'guardian_country' => array(
            'type' => 'country',
            'display_name' => 'Country'
        ),
        'guardian_phone_type' => array(
            'type' => 'select_no_sort',
            'add_null_value_to_top' => true,
            'options' => array('home' => 'Home', 'cell' => 'Cell', 'work' => 'Work'),
        ),
        'guardian_phone' => array(
            'type' => 'text',
            'size' => 20
        ),
        'guardian_email' => array(
            'type' => 'text',
            'display_name' => 'E-mail',
        ),
        'guardian_occupation' => array(
            'type' => 'text',
            'display_name' => 'Occupation',
        ),
        'guardian_employer' => array(
            'type' => 'text',
            'display_name' => 'Employer',
        ),
        'legacy_header' => array(
            'type' => 'comment',
            'text' => '<h3>Parent/Guardian College Information</h3>'
        ),
        'legacy_comment' => array(
            'type' => 'comment',
            'text' => 'Did either of your parents or your guardian graduate from a four-year college or university?',
        ),
        'legacy' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array(
                'Yes' => 'Yes',
                'No' => 'No',
            ),
        ),
        'parent_1_college' => array(
            'type' => 'text',
        ),
        'parent_1_college_ceeb' => 'hidden',
        'parent_2_college' => array(
            'type' => 'text',
        ),
        'parent_2_college_ceeb' => 'hidden',
        'guardian_college' => array(
            'type' => 'text',
        ),
        'guardian_college_ceeb' => 'hidden',
        'siblings_header' => array(
            'type' => 'comment',
            'text' => '<h3>Siblings</h3>',
        ),
        'sibling_1_header' => array(
            'type' => 'comment',
            'text' => '<h4>Sibling 1</h4>',
        ),
        'sibling_1_relation' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('brother' => 'Brother', 'sister' => 'Sister'),
        ),
        'sibling_1_first_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'sibling_1_last_name' => array(
            'type' => 'text',
            'size' => 20
        ),
        'sibling_1_age' => array(
            'type' => 'text',
            'size' => 2,
            'display_name' => 'Age',
        ),
        'sibling_1_grade' => array(
            'display_name' => 'Grade',
            'type' => 'text',
            'size' => 3
        ),
        'sibling_1_college' => array(
            'type' => 'text',
            'display_name' => 'College (if applicable)'
        ),
        'sibling_1_college_ceeb' => 'hidden',
        'sibling_2_hr' => 'hr',
        'sibling_2_header' => array(
            'type' => 'comment',
            'text' => '<h4>Sibling 2</h4>',
        ),
        'sibling_2_relation' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('brother' => 'Brother', 'sister' => 'Sister'),
        ),
        'sibling_2_first_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'sibling_2_last_name' => array(
            'type' => 'text',
            'size' => 20
        ),
        'sibling_2_age' => array(
            'type' => 'text',
            'size' => 2,
            'display_name' => 'Age',
        ),
        'sibling_2_grade' => array(
            'display_name' => 'Grade',
            'type' => 'text',
            'size' => 3
        ),
        'sibling_2_college' => array(
            'type' => 'text',
            'display_name' => 'College (if applicable)'
        ),
        'sibling_2_college_ceeb' => 'hidden',
        'sibling_3_hr' => 'hr',
        'sibling_3_header' => array(
            'type' => 'comment',
            'text' => '<h4>Sibling 3</h4>',
        ),
        'sibling_3_relation' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('brother' => 'Brother', 'sister' => 'Sister'),
        ),
        'sibling_3_first_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'sibling_3_last_name' => array(
            'type' => 'text',
            'size' => 20
        ),
        'sibling_3_age' => array(
            'type' => 'text',
            'size' => 2,
            'display_name' => 'Age',
        ),
        'sibling_3_grade' => array(
            'display_name' => 'Grade',
            'type' => 'text',
            'size' => 3
        ),
        'sibling_3_college' => array(
            'type' => 'text',
            'display_name' => 'College (if applicable)'
        ),
        'sibling_3_college_ceeb' => 'hidden',
        'sibling_4_hr' => 'hr',
        'sibling_4_header' => array(
            'type' => 'comment',
            'text' => '<h4>Sibling 4</h4>',
        ),
        'sibling_4_relation' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('brother' => 'Brother', 'sister' => 'Sister'),
        ),
        'sibling_4_first_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'sibling_4_last_name' => array(
            'type' => 'text',
            'size' => 20
        ),
        'sibling_4_age' => array(
            'type' => 'text',
            'size' => 2,
            'display_name' => 'Age',
        ),
        'sibling_4_grade' => array(
            'display_name' => 'Grade',
            'type' => 'text',
            'size' => 3
        ),
        'sibling_4_college' => array(
            'type' => 'text',
            'display_name' => 'College (if applicable)'
        ),
        'sibling_4_college_ceeb' => 'hidden',
        'sibling_5_hr' => 'hr',
        'sibling_5_header' => array(
            'type' => 'comment',
            'text' => '<h4>Sibling 5</h4>',
        ),
        'sibling_5_relation' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('brother' => 'Brother', 'sister' => 'Sister'),
        ),
        'sibling_5_first_name' => array(
            'type' => 'text',
            'size' => 15
        ),
        'sibling_5_last_name' => array(
            'type' => 'text',
            'size' => 20
        ),
        'sibling_5_age' => array(
            'type' => 'text',
            'size' => 2,
            'display_name' => 'Age',
        ),
        'sibling_5_grade' => array(
            'display_name' => 'Grade',
            'type' => 'text',
            'size' => 3
        ),
        'sibling_5_college' => array(
            'type' => 'text',
            'display_name' => 'College (if applicable)'
        ),
        'sibling_5_college_ceeb' => 'hidden',
        'add_sibling_button' => array(
            'type' => 'comment',
            'text' => '<span id="addSibling" title="Add a Sibling" class="addButton">
                Add a Sibling
                </span>'
        ),
        'remove_sibling_button' => array(
            'type' => 'comment',
            'text' => '<span id="removeSibling" title="Remove Sibling" class="removeButton">
                Remove a Sibling
                </span>'
        ),
        'logout3' => array(
            'type' => 'hidden',
        ),
    );
    /**
     * Stores all the information necessary to instantiate each element group.
     * Format: element_group_name => element info
     * @var array
     */
    var $element_group_info = array(
        'parent_1_name_group' => array(
            'type' => 'inline',
            'elements' => array('parent_1_title', 'parent_1_first_name', 'parent_1_middle_name', 'parent_1_last_name'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Name'),
        ),
        'parent_1_phone_group' => array(
            'type' => 'inline',
            'elements' => array('parent_1_phone_type', 'parent_1_phone'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Preferred Phone'),
        ),
        'sibling_button_group' => array(
            'type' => 'inline',
            'elements' => array('add_sibling_button', 'remove_sibling_button'),
            'args' => array('use_element_labels' => false, 'display_name' => '&nbsp;'),
        ),
        'parent_2_name_group' => array(
            'type' => 'inline',
            'elements' => array('parent_2_title', 'parent_2_first_name', 'parent_2_middle_name', 'parent_2_last_name'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Name'),
        ),
        'parent_2_phone_group' => array(
            'type' => 'inline',
            'elements' => array('parent_2_phone_type', 'parent_2_phone'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Preferred Phone'),
        ),
        'guardian_name_group' => array(
            'type' => 'inline',
            'elements' => array('guardian_title', 'guardian_first_name', 'guardian_middle_name', 'guardian_last_name'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Name'),
        ),
        'guardian_phone_group' => array(
            'type' => 'inline',
            'elements' => array('guardian_phone_type', 'guardian_phone'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Preferred Phone'),
        ),
        'sibling_1_name_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_1_first_name', 'sibling_1_last_name'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Name'),
        ),
        'sibling_1_age_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_1_age', 'sibling_1_grade'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Age and Grade'),
        ),
        'sibling_2_name_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_2_first_name', 'sibling_2_last_name'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Name'),
        ),
        'sibling_2_age_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_2_age', 'sibling_2_grade'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Age and Grade'),
        ),
        'sibling_3_name_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_3_first_name', 'sibling_3_last_name'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Name'),
        ),
        'sibling_3_age_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_3_age', 'sibling_3_grade'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Age and Grade'),
        ),
        'sibling_4_name_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_4_first_name', 'sibling_4_last_name'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Name'),
        ),
        'sibling_4_age_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_4_age', 'sibling_4_grade'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Age and Grade'),
        ),
        'sibling_5_name_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_5_first_name', 'sibling_5_last_name'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Name'),
        ),
        'sibling_5_age_group' => array(
            'type' => 'inline',
            'elements' => array('sibling_5_age', 'sibling_5_grade'),
            'args' => array('use_element_labels' => false, 'display_name' => 'Age and Grade'),
        ),
    );
    var $display_name = 'Family';
    var $error_header_text = 'Please check your form.';
    var $required = array('permanent_home_parent', 'parent_1_first_name', 'parent_1_last_name', 'parent_1_name_group', 'parent_1_address',
        'parent_1_city', 'parent_1_state_province', 'parent_1_zip_postal', 'parent_1_country', 'parent_1_phone_group',
        'parent_1_occupation', 'legacy');

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

    function no_show_form() {
        echo(check_login());
    }

    // style up the form and add comments et al
    function on_every_time() {
        $this->openid_id = check_open_id($this);
        if (is_submitted($this->openid_id)) {
            echo(already_submitted_message());
            $this->show_form = false;
        } else {
            $this->show_form = true;

            foreach ($this->element_group_info as $name => $info) {
                $this->add_element_group($info['type'], $name, $info['elements'], $info['args']);
            }

            $this->move_element('parent_1_name_group', 'after', 'parent_1_living');
            $this->move_element('parent_1_phone_group', 'after', 'parent_1_country');
            $this->move_element('parent_2_name_group', 'after', 'parent_2_living');
            $this->move_element('parent_2_phone_group', 'after', 'parent_2_country');
            $this->move_element('guardian_name_group', 'after', 'guardian_relation');
            $this->move_element('guardian_phone_group', 'after', 'guardian_country');
            $this->move_element('sibling_1_name_group', 'after', 'sibling_1_relation');
            $this->move_element('sibling_1_age_group', 'before', 'sibling_1_college');
            $this->move_element('sibling_2_name_group', 'after', 'sibling_2_relation');
            $this->move_element('sibling_2_age_group', 'before', 'sibling_2_college');
            $this->move_element('sibling_3_name_group', 'after', 'sibling_3_relation');
            $this->move_element('sibling_3_age_group', 'before', 'sibling_3_college');
            $this->move_element('sibling_4_name_group', 'after', 'sibling_4_relation');
            $this->move_element('sibling_4_age_group', 'before', 'sibling_4_college');
            $this->move_element('sibling_5_name_group', 'after', 'sibling_5_relation');
            $this->move_element('sibling_5_age_group', 'before', 'sibling_5_college');

            $this->pre_fill_form();
        }
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageThree">' . "\n";
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