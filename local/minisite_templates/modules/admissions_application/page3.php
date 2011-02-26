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
class ApplicationPageThree extends FormStep
{
	var $_log_errors = true;
	var $error;

	var $elements = array(
            'family_comment' => array(
                'type' => 'comment',
                'text' => '¿¿¿¿Include text from the common app????'
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
                    'neverMarried' => 'Never Married',
                    'married' => 'Married',
                    'widowed' => 'Widowed',
                    'separated' => 'Separated',
                    'divorced' => 'Divorced'
                ),
                'comments' => '¿¿¿¿Do we need to collect divorce date????'
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
                    'guardian' => 'Legal Guadian',
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
                    'options' => array('mother'=>'Mother','father'=>'Father','unknown'=>'Unknown'),
            ),
            'parent_1_living' => array(
                    'display_name' => 'Is Parent 1 living?',
                    'type' => 'radio_inline_no_sort',
                    'options' => array('yes' => 'Yes', 'no' => 'No')
            ),
            'parent_1_deceased_date' => array(
                    'display_name' => 'date_deceased',
                    'type' => 'textdate',
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
                    'MSG' => 'Monseigneur',
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
            'parent_1_birth_country' => array(
                'type' => 'text',
                'size' => 15,
                'display_name' => 'Country of Birth',
            ),
            'parent_1_home_address' => array(
                'type' => 'textarea',
            ),
            'parent_1_phone_type' => array(
                'type' => 'select_no_sort',
                'add_null_value_to_top' => true,
                'options' => array('home' => 'Home', 'cell' =>'Cell', 'work' => 'Work'),
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
            'parent_1_college' => array(
                'type' => 'text',
                'display_name' => 'College (if any)'
            ),
            'parent_1_college_degree' => array(
                'type' => 'text',
                'display_name' => 'Degree',
                'size' => 20
            ),
            'parent_1_college_year' => array(
                'type' => 'text',
                'size' => 4,
                'display_name' => 'Year',
            ),
            'parent_1_grad' => array(
                'type' => 'text',
                'display_name' => 'Graduate School (if any)'
            ),
            'parent_1_grad_degree' => array(
                'type' => 'text',
                'display_name' => 'Degree',
                'size' => 20
            ),
            'parent_1_grad_year' => array(
                'type' => 'text',
                'size' => 4,
                'display_name' => 'Year',
            ),
            'parent_2_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Parent 2</h3>',
            ),
            'parent_2_type' => array(
                    'type' => 'radio_inline_no_sort',
                    'display_name' => '&nbsp;',
                    'options' => array('mother'=>'Mother','father'=>'Father','unknown'=>'Unknown'),
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
                    'MSG' => 'Monseigneur',
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
            'parent_2_birth_country' => array(
                'type' => 'text',
                'size' => 15,
                'display_name' => 'Country of Birth',
            ),
            'parent_2_home_address' => array(
                'type' => 'textarea',
            ),
            'parent_2_phone_type' => array(
                'type' => 'select_no_sort',
                'add_null_value_to_top' => true,
                'options' => array('home' => 'Home', 'cell' =>'Cell', 'work' => 'Work'),
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
            'parent_2_college' => array(
                'type' => 'text',
                'display_name' => 'College (if any)'
            ),
            'parent_2_college_degree' => array(
                'type' => 'text',
                'size' => 20,
                'display_name' => 'Degree',
            ),
            'parent_2_college_year' => array(
                'type' => 'text',
                'size' => 4,
                'display_name' => 'Year',
            ),
            'parent_2_grad' => array(
                'type' => 'text',
                'display_name' => 'Graduate School (if any)'
            ),
            'parent_2_grad_degree' => array(
                'type' => 'text',
                'display_name' => 'Degree',
                'size' => 20
            ),
            'parent_2_grad_year' => array(
                'type' => 'text',
                'size' => 4,
                'display_name' => 'Year',
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
                    'MSG' => 'Monseigneur',
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
            'guardian_birth_country' => array(
                'type' => 'text',
                'size' => 15,
                'display_name' => 'Country of Birth',
            ),
            'guardian_home_address' => array(
                'type' => 'textarea',
            ),
            'guardian_phone_type' => array(
                'type' => 'select_no_sort',
                'add_null_value_to_top' => true,
                'options' => array('home' => 'Home', 'cell' =>'Cell', 'work' => 'Work'),
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
            'guardian_college' => array(
                'type' => 'text',
                'display_name' => 'College (if any)'
            ),
            'guardian_college_degree' => array(
                'type' => 'text',
                'display_name' => 'Degree',
                'size' => 20
            ),
            'guardian_college_year' => array(
                'type' => 'text',
                'size' => 4,
                'display_name' => 'Year',
            ),
            'guardian_grad' => array(
                'type' => 'text',
                'display_name' => 'College (if any)'
            ),
            'guardian_grad_degree' => array(
                'type' => 'text',
                'display_name' => 'Degree',
                'size' => 20
            ),
            'guardian_grad_year' => array(
                'type' => 'text',
                'size' => 4,
                'display_name' => 'Year',
            ),
            'siblings_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Siblings</h3>',
            ),
            'siblings_1_header' => array(
                    'type' => 'comment',
                    'text' => '<h4>Sibling 1</h4>',
            ),
            'sibling_1_relation' => array(
                    'type' => 'radio_inline_no_sort',
                    'display_name' => '&nbsp;',
                    'options' => array('brother'=>'Brother','sister'=>'Sister'),
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
                'size' => 2
            ),
            'sibling_1_college' => array(
                'type' => 'text',
                'display_name' => 'College (if any)'
            ),
            'sibling_1_college_degree' => array(
                'type' => 'text',
                'size' => 20,
                'display_name' => 'Degree earned or expected',
            ),
            'sibling_1_college_start_month' => array(
                'type' => 'text',
                'size' => 2,
            ),
            'sibling_1_dateslash_1' => array(
                'type' => 'comment',
                'text' => '/'
            ),
            'sibling_1_college_start_year' => array(
                'type' => 'text',
                'size' => 4,
            ),
            'sibling_1_college_date_dash' => array(
                'type' => 'comment',
                'text' => '&mdash;'
            ),
            'sibling_1_college_end_month' => array(
                'type' => 'text',
                'size' => 2,
            ),
            'sibling_1_dateslash_2' => array(
                'type' => 'comment',
                'text' => '/'
            ),
            'sibling_1_college_end_year' => array(
                'type' => 'text',
                'size' => 4,
            ),
            'siblings_2_header' => array(
                    'type' => 'comment',
                    'text' => '<h4>Sibling 2</h4>',
            ),
            'sibling_2_relation' => array(
                    'type' => 'radio_inline_no_sort',
                    'display_name' => '&nbsp;',
                    'options' => array('brother'=>'Brother','sister'=>'Sister'),
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
                'size' => 2
            ),
            'sibling_2_college' => array(
                'type' => 'text',
                'display_name' => 'College (if any)'
            ),
            'sibling_2_college_degree' => array(
                'type' => 'text',
                'size' => 20,
                'display_name' => 'Degree earned or expected',
            ),
            'sibling_2_college_start_month' => array(
                'type' => 'text',
                'size' => 2,
            ),
            'sibling_2_dateslash_1' => array(
                'type' => 'comment',
                'text' => '/'
            ),
            'sibling_2_college_start_year' => array(
                'type' => 'text',
                'size' => 4,
            ),
            'sibling_2_college_date_dash' => array(
                'type' => 'comment',
                'text' => ' &mdash;',
            ),
            'sibling_2_college_end_month' => array(
                'type' => 'text',
                'size' => 2,
            ),
            'sibling_2_dateslash_2' => array(
                'type' => 'comment',
                'text' => '/'
            ),
            'sibling_2_college_end_year' => array(
                'type' => 'text',
                'size' => 4,
            ),
            'legacy_comment' => array(
                'type' => 'comment',
                'text' => 'Do you have any immediate family who have attended or currently attend Luther College?,'
            ),
            'legacy' => array(
                'type' => 'radio_inline_no_sort',
                'display_name' => '&nbsp;',
                'options' => array('yes'=>'Yes', 'no'=>'No'),
                'comments' => '¿¿¿¿Is this question redundant????'
            )
        );

	/**
	* Stores all the information necessary to instantiate each element group.
	* Format: element_group_name => element info
	* @var array
	*/
	var $element_group_info = array(
            'parent_1_name_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'parent_1_title', 'parent_1_first_name', 'parent_1_middle_name', 'parent_1_last_name' ),
                'args' => array('use_element_labels' => false ,'display_name' => 'Name'),
            ),
            'parent_1_phone_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'parent_1_phone_type', 'parent_1_phone' ),
                'args' => array('use_element_labels' => false, 'display_name' => 'Preferred Phone'),
            ),
            'parent_1_college_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'parent_1_college_degree' , 'parent_1_college_year' ),
                'args' => array('use_element_labels' => false, 'display_name' => 'Degree & Year'),
            ),
            'parent_1_grad_school_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'parent_1_grad_degree' , 'parent_1_grad_year' ),
                'args' => array('use_element_labels' => false, 'display_name' => 'Degree & Year'),
            ),
            'parent_2_name_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'parent_2_title', 'parent_2_first_name', 'parent_2_middle_name', 'parent_2_last_name' ),
                'args' => array('use_element_labels' => false ,'display_name' => 'Name'),
            ),
            'parent_2_phone_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'parent_2_phone_type', 'parent_2_phone' ),
                'args' => array('use_element_labels' => false, 'display_name' => 'Preferred Phone'),
            ),
            'parent_2_college_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'parent_2_college_degree' , 'parent_2_college_year' ),
                'args' => array('use_element_labels' => false, 'display_name' => 'Degree & Year'),
            ),
            'parent_2_grad_school_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'parent_2_grad_degree' , 'parent_2_grad_year' ),
                'args' => array('use_element_labels' => false, 'display_name' => 'Degree & Year'),
            ),
            'guardian_name_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'guardian_title', 'guardian_first_name', 'guardian_middle_name', 'guardian_last_name' ),
                'args' => array('use_element_labels' => false ,'display_name' => 'Name'),
            ),
            'guardian_phone_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'guardian_phone_type', 'guardian_phone' ),
                'args' => array('use_element_labels' => false, 'display_name' => 'Preferred Phone'),
            ),
            'guardian_college_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'guardian_college_degree' , 'guardian_college_year' ),
                'args' => array('use_element_labels' => false, 'display_name' => 'Degree & Year'),
            ),
            'guardian_grad_school_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'guardian_grad_degree' , 'guardian_grad_year' ),
                'args' => array('use_element_labels' => false, 'display_name' => 'Degree & Year'),
            ),
            'sibling_1_name_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'sibling_1_first_name', 'sibling_1_last_name'),
                'args' => array('use_element_labels' => false ,'display_name' => 'Name'),
            ),
            'sibling_1_age_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'sibling_1_age', 'sibling_1_grade'),
                'args' => array('use_element_labels' => false, 'display_name' => 'Age &  Grade'),
            ),
            'sibling_1_college_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'sibling_1_college_start_month', 'sibling_1_dateslash_1', 'sibling_1_college_start_year',
                    'sibling_1_college_date_dash', 'sibling_1_college_end_month', 'sibling_1_dateslash_2', 'sibling_1_college_end_year'),
                'args' => array('use_element_labels' => false ,'display_name' => 'Dates'),
            ),
            'sibling_2_name_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'sibling_2_first_name', 'sibling_2_last_name'),
                'args' => array('use_element_labels' => false ,'display_name' => 'Name'),
            ),
            'sibling_2_age_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'sibling_2_age', 'sibling_2_grade'),
                'args' => array('use_element_labels' => false, 'display_name' => 'Age &  Grade'),
            ),
            'sibling_2_college_group' => array(
                'type' => 'inline',
                'elements' =>  array( 'sibling_2_college_start_month', 'sibling_2_dateslash_1', 'sibling_2_college_start_year',
                    'sibling_2_college_date_dash', 'sibling_2_college_end_month', 'sibling_2_dateslash_2', 'sibling_2_college_end_year'),
                'args' => array('use_element_labels' => false ,'display_name' => 'Dates'),
            ),
   	);

	var $display_name = 'Family';
	var $error_header_text = 'Please check your form.';

	// style up the form and add comments et al
	function on_every_time()
	{
            foreach($this->element_group_info as $name => $info) {
                $this->add_element_group( $info['type'], $name, $info['elements'], $info['args']);
            }

            $this->move_element('parent_1_name_group','after','parent_1_type');
            $this->move_element('parent_1_phone_group','after','parent_1_home_address');
            $this->move_element('parent_1_college_group','after','parent_1_college');
            $this->move_element('parent_1_grad_school_group','after','parent_1_grad');
            $this->move_element('parent_2_name_group','after','parent_2_type');
            $this->move_element('parent_2_phone_group','after','parent_2_home_address');
            $this->move_element('parent_2_college_group','after','parent_2_college');
            $this->move_element('parent_2_grad_school_group','after','parent_2_grad');
            $this->move_element('guardian_name_group','after','guardian_relation');
            $this->move_element('guardian_phone_group','after','guardian_home_address');
            $this->move_element('guardian_college_group','after','guardian_college');
            $this->move_element('guardian_grad_school_group','after','guardian_grad');
            $this->move_element('sibling_1_name_group','after','sibling_1_relation');
            $this->move_element('sibling_1_age_group','before','sibling_1_college');
            $this->move_element('sibling_1_college_group','after','sibling_1_college_degree');
            $this->move_element('sibling_2_name_group','after','sibling_2_relation');
            $this->move_element('sibling_2_age_group','before','sibling_2_college');
            $this->move_element('sibling_2_college_group','after','sibling_2_college_degree');
            

	}

	function pre_show_form()
	{
            echo '<div id="giftForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
            echo '</div>'."\n";
	}
}
?>