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
 *  Second page of the application
 *
 *  Personal Information
 *      Name
 *      Address
 *      Citizenship/Ethnicity
 *      Faith
 */

class ApplicationPageTwo extends FormStep {

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
            'text' => '<h3>Address Information</h3>',
        ),
        'permanent_address' => 'text',
        'permanent_apartment_number' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => 'Apt. #'
        ),
        'permanent_city' => array(
            'type' => 'text',
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
        'mailing_address' => 'text',
        'mailing_apartment_number' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => 'Apt. #'
        ),
        'mailing_city' => array(
            'type' => 'text',
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
                        <div id="faith_dialog" title="Your Faith">We are able to offer
                        <a href="/financialaid/prospective/scholarships/epic/" target=__blank>EPIC Scholarship</a>...blah, blah, blah.'
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
        )
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
                'display_name' => 'U.S. Social Security Number',
                'comments' => '<div id="ssn"><a href="#ssn_dialog">Why is this important?</a></div>
                        <div id="ssn_dialog" title="Social Security Info">
                        Your Social Security number allows us to .... blah, blah, blah</div>')
        ),
    );

    /* 	var $required = array(
      'first_name',
      'middle_name',
      'last_name',
      'permanent_address',
      'permanent_city',
      'permanent_state_province',
      'permanent_zip_postal',
      'permanent_country',
      'home_phone',
      'email',
      ); */
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
        //add element groups
        foreach ($this->element_group_info as $name => $info) {
            $this->add_element_group($info['type'], $name, $info['elements'], $info['args']);
        }

        $this->move_element('name_group', 'before', 'preferred_first_name');
        $this->move_element('ssn_group', 'after', 'date_of_birth');
        $this->pre_fill_form();
    }

    function pre_fill_form() {

        //use this to fill form pages for users returning to fill out the form
        //
//		if( $userid = reason_check_authentication() )
//		{
//			$person = get_individual_alum_info( $userid );
//			//pray($person);
//			foreach($this->form_to_person_key as $element_name=>$rules)
//			{
//				$value = '';
//				if(!$this->get_value($element_name))
//				{
//					if(empty($rules['function']))
//					{
//						$src = $rules['src'];
//						$field = $rules['field'];
//						if(!empty($person[$src][$field]))
//						{
//							if (is_array($person[$src][$field]))
//								$value = $person[$src][$field][0];
//							else
//								$value = $person[$src][$field];
//						}
//					}
//					else
//					{
//						$function = $rules['function'];
//						//echo $function.'<br />';
//						$value = $this->$function( $person );
//					}
//					if(!empty($value))
//					{
//						$this->set_value($element_name, $value);
//					}
//				}
//			}
//			if(!$this->get_value('country'))
//			{
//				$this->set_value('country','United States');
//			}
//		}
    }

    function get_ldap_address_element($person, $element) {
        static $elements;
        static $has_run = false;
        if (!$has_run) {
            if (!empty($person['ldap_info']['edupersonprimaryaffiliation'][0]) && $person['ldap_info']['edupersonprimaryaffiliation'][0] == 'student') {
                $ldap_address_field = 'carlstudentpermanentaddress';
            } else {
                $ldap_address_field = 'homepostaladdress';
            }
            if (!empty($person['ldap_info'][$ldap_address_field][0])) {
                $address_array = explode('$', $person['ldap_info'][$ldap_address_field][0]);
                $last_line = array_pop($address_array);
                if (preg_match('/^[A-Z ]+$/', trim($last_line))) { // stings of 1 or more capital letters plus spaces -- probably a country piece
                    $elements['country'] = trim($last_line);
                    $last_line = array_pop($address_array);
                }
                $elements['street_address'] = implode("\n", $address_array);
                $last_line_array = explode(' ', $last_line);
                $zip_array = array();
                $continue = true;
                while ($continue) {
                    $possible_zip = array_pop($last_line_array);
                    if (preg_match('/[0-9]/', $possible_zip)) {
                        $zip_array[] = $possible_zip;
                    } else {
                        $last_line_array[] = $possible_zip;
                        $continue = false;
                    }
                }
                if (!empty($zip_array)) {
                    $elements['zip'] = implode(' ', $zip_array);
                }
                $possible_state_province = array_pop($last_line_array);
                if (preg_match('/^[A-Z]+$/', trim($possible_state_province))) { // looks like a state code
                    $elements['state_province'] = $possible_state_province;
                } else /* probably part of the city name*/ {
                    $last_line_array[] = $possible_state_province; // put back into city name section
                    $elements['state_province'] = 'XX';
                }
                $elements['city'] = implode(' ', $last_line_array);
                //pray($elements);
                $has_run = true;
            }
        }
        if (!empty($elements[$element])) {
            return $elements[$element];
        }
    }

    function get_street_address($person) {
        $advance_fields = array('HOME_STREET1', 'HOME_STREET2', 'HOME_STREET3');
        $ldap_field = 'homepostaladdress';
        if (!empty($person['advance_info']['HOME_STREET1'])) {
            $line_array = array();
            foreach ($advance_fields as $field) {
                if (!empty($person['advance_info'][$field])) {
                    $line_array[] = $person['advance_info'][$field];
                }
            }
            $ret = implode("\n", $line_array);
        } else {
            $ret = $this->get_ldap_address_element($person, 'street_address');
        }
        return $ret;
    }

    function get_home_city($person) {
        $ret = '';
        if (!empty($person['advance_info']['HOME_CITY'])) {
            $ret = $person['advance_info']['HOME_CITY'];
        } elseif (!empty($person['advance_info']['HOME_FOREIGN_CITYZIP'])) {
            $ret = trim($person['advance_info']['HOME_FOREIGN_CITYZIP']);
        } else {
            $ret = $this->get_ldap_address_element($person, 'city');
        }
        return $ret;
    }

    function get_home_state($person) {
        $ret = '';
        if (!empty($person['advance_info']['HOME_STATE_CODE'])) {
            $ret = $person['advance_info']['HOME_STATE_CODE'];
        } else {
            $ret = $this->get_ldap_address_element($person, 'state_province');
        }
        if (empty($ret)) {
            $ret = 'XX';
        }
        return $ret;
    }

    function get_home_zip($person) {
        $ret = '';
        if (!empty($person['advance_info']['ZIPCODE'])) {
            $ret = $person['advance_info']['ZIPCODE'];
        } else {
            $ret = $this->get_ldap_address_element($person, 'zip');
        }
        return $ret;
    }

    function get_home_country($person) {
        $ret = '';
        if (!empty($person['advance_info']['HOME_COUNTRY'])) {
            $ret = $person['advance_info']['HOME_COUNTRY'];
        } else {
            $ret = $this->get_ldap_address_element($person, 'country');
        }
        return $ret;
    }

    function get_home_phone($person) {
        if (!empty($person['ldap_info']['homephone'][0])) {
            return str_replace(array('+1 ', ' '), array('', '-'), $person['ldap_info']['homephone'][0]);
        } else {
            return false;
        }
    }

    function get_business_phone($person) {
        $ret = '';
        if (!empty($person['advance_info']['BUS_PHONE'])) {
            $ret = $person['advance_info']['BUS_PHONE'];
        } elseif (!empty($person['ldap_info']['telephonenumber'][0])) {
            $ret = str_replace(array('+1 ', ' '), array('', '-'), $person['ldap_info']['telephonenumber'][0]);
        }
        return $ret;
    }

    function get_email_address($person) {
        $ldap_fields = array('mail', 'carlhomeemail');
        $ret = '';
        foreach ($ldap_fields as $field) {
            if (!empty($person['ldap_info'][$field][0])) {
                $ret = $person['ldap_info'][$field][0] . "\n";
                break;
            }
        }
        return $ret;
    }

    function get_affiliations($person) {
        $ret = array();
        $aff_term_map = array('alum' => 'Alumnus', 'staff' => 'Staff', 'faculty' => 'Faculty', 'student' => 'Student');
        if (!empty($person['ldap_info']['edupersonaffiliation'])) {
            if (is_array($person['ldap_info']['edupersonaffiliation'])) {
                foreach ($person['ldap_info']['edupersonaffiliation'] as $ldap_aff) {
                    if (!empty($aff_term_map[$ldap_aff])) {
                        $ret[] = $aff_term_map[$ldap_aff];
                    }
                }
            } else {
                $ldap_aff = $person['ldap_info']['edupersonaffiliation'];
                if (!empty($aff_term_map[$ldap_aff])) {
                    $ret[] = $aff_term_map[$ldap_aff];
                }
            }
        }
        return $ret;
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageTwo">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

       function process() {
        parent::process();

        connectDB('admissions_applications_connection');

        $first_name = $this->get_value('first_name');
        $middle_name = $this->get_value('middle_name');
        $last_name = $this->get_value('last_name');
        $preferred_first_name = $this->get_value('preferred_first_name');
        $gender = $this->get_value('gender');
        $date_of_birth = $this->get_value('date_of_birth');
        $ssn = '';
        if (!($this->get_value('ssn_1'))||($this->get_value('ssn_2'))||($this->get_value('ssn_3'))){
            $ssn = addslashes($this->get_value('ssn_1'));
            $ssn .= addslashes('-');
            $ssn .= addslashes($this->get_value('ssn_2'));
            $ssn .= addslashes('-');
            $ssn .= addslashes($this->get_value('ssn_3'));
        }
        $email = $this->get_value('email');
        $home_phone = $this->get_value('home_phone');
        $cell_phone = $this->get_value('cell_phone');
        $permanent_address = $this->get_value('permanent_address');
        $permanent_apartment_number = $this->get_value('permanent_apartment_number');
        $permanent_city = $this->get_value('permanent_city');
        $permanent_state_province = $this->get_value('permanent_state_province');
        $permanent_zip_postal = $this->get_value('permanent_zip_postal');
        $permanent_country = $this->get_value('permanent_country');
        $different_mailing_address = $this->get_value('different_mailing_address');
        $mailing_address = $this->get_value('mailing_address');
        $mailing_apartment_number = $this->get_value('mailing_apartment_number');
        $mailing_city = $this->get_value('mailing_city');
        $mailing_state_province = $this->get_value('mailing_state_province');
        $mailing_zip_postal = $this->get_value('mailing_zip_postal');
        $mailing_country = $this->get_value('mailing_country');
        $heritage = $this->get_value('heritage');
        if ($this->get_value('race')){
            $race_string = implode(',', $this->get_value('race'));
        }
        $church_name = $this->get_value('church_name');
        $church_city = $this->get_value('church_city');
        $church_state = $this->get_value('church_state');
        $religion = $this->get_value('religion');


        $qstring = "INSERT INTO `applicants` SET
                first_name='" . addslashes($first_name) . "',
                middle_name='" . addslashes($middle_name) . "',
                last_name='" . addslashes($last_name) . "',
                preferred_first_name='" . ((!empty ($preferred_first_name)) ? addslashes($preferred_first_name) : 'NULL') . "',
                gender='" . addslashes($gender) . "',
                date_of_birth='" . addslashes($this->get_value('date_of_birth')) . "',
                ssn='" . ((!empty ($ssn)) ? addslashes($ssn) : 'NULL') . "',
		email='" . addslashes($this->get_value('email')) . "',
		home_phone='" . addslashes($this->get_value('home_phone')) . "',
		cell_phone='" . addslashes($this->get_value('cell_phone')) . "',
		permanent_address='" . addslashes($permanent_address) . "',
		permanent_apartment_number='" . addslashes($this->get_value('permanent_apartment_number')) . "',
                permanent_city='" . addslashes($this->get_value('permanent_city')) . "',
                permanent_state_province='" . addslashes($this->get_value('permanent_state_province')) . "',
                permanent_zip_postal='" . addslashes($this->get_value('permanent_zip_postal')) . "',
                permanent_country='" . addslashes($this->get_value('permanent_country')) . "',
                different_mailing_address='" . addslashes($this->get_value('different_mailing_address')) . "',
                mailing_address='" . ((!empty ($mailing_address)) ? addslashes($mailing_address) : 'NULL') . "',
		mailing_apartment_number='" . ((!empty ($mailing_apartment_number)) ? addslashes($mailing_apartment_number) : 'NULL') . "',
                mailing_city='" . ((!empty ($mailing_city)) ? addslashes($mailing_city) : 'NULL') . "',
                mailing_state_province='" . ((!empty ($mailing_state_province)) ? addslashes($mailing_state_province) : 'NULL') . "',
                mailing_zip_postal='" . ((!empty ($mailing_zip_postal)) ? addslashes($mailing_zip_postal) : 'NULL') . "',
                mailing_country='" . ((!empty ($mailing_country)) ? addslashes($mailing_country) : 'NULL') . "',
                heritage='" . ((!empty ($heritage)) ? addslashes($heritage) : 'NULL')  . "',
                race='" . ((!empty ($race_string)) ? addslashes($race_string) : 'NULL') . "',
                church_name='" . ((!empty ($church_name)) ? addslashes($church_name) : 'NULL') . "',
		church_city='" . ((!empty ($church_city)) ? addslashes($church_city) : 'NULL') . "',
		church_state='" . ((!empty ($church_state)) ? addslashes($church_state) : 'NULL') . "',
		religion='" . ((!empty ($religion)) ? addslashes($religion) : 'NULL') . "' ";

        $qresult = db_query($qstring);

        //connect back with the reason DB
        connectDB(REASON_DB);
    }

    function run_error_checks() {
//		// Taken from http://us2.php.net/eregi
//		if( !eregi('^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,6}$',$this->get_value('email')))
//		{
//			$this->set_error('email','The email address you entered does not appear to be valid.  Please check to make sure you entered it correctly');
//		}
    }
}

function get_individual_ldap_info($userid) {
    include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
    $dir = new directory_service();
    if ($dir->search_by_attribute('ds_username', $userid,
                    array('carlnetid', 'carladvanceid', 'ds_fullname', 'mail', 'carlhomeemail', 'edupersonaffiliation', 'edupersonprimaryaffiliation',
                        'telephonenumber', 'homephone', 'carlstudentpermanentaddress', 'homepostaladdress', 'carlcohortyear'))) {
        return $dir->get_first_record();
    } else {
        return false;
    }
}

function get_individual_alum_info($userid) {
    $person = array();
    $person['ldap_info'] = get_individual_ldap_info($userid);
    if (!empty($person['ldap_info']['carladvanceid'][0])) {
        connectDB('alumni_directory');
        $dbs = new DBSelector;
        $dbs->add_table('ad', 'alumni_dir');
        $dbs->add_relation('ID_NUMBER = "' . $person['ldap_info']['carladvanceid'][0] . '"');
        $result = $dbs->run('Querying for info in alumni_directory for ' . $userid);
        connectDB(REASON_DB);
        reset($result);
        $person['advance_info'] = current($result);
    }
    return $person;
}

?>