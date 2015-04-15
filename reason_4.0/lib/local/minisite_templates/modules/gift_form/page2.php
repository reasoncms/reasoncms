<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Matt Ryan
//    2005-02-16
//
//    Work on the first page of the giving form
//
//    Modified for Luther - Steve Smith
//    2010-09-13
//
//
////////////////////////////////////////////////////////////////////////////////

class GiftPageTwoForm extends FormStep
{
	var $_log_errors = true;
	var $error;
	
	// the usual disco member data
	var $elements = array(
		'first_name' => array(
			'type' => 'text',
			'size'=>15,
		),
		'last_name' => array(
			'type' => 'text',
			'display_name' => 'Last Name or Family Name',
			'size'=>20,
		),
		'spouse_first_name' => array(
			'type' => 'text',
			'size' => 15,
		),
		'spouse_last_name' => array(
			'type' => 'text',
			'size' => 20,
			// 'comments' => 'if applicable',
		),
		'luther_affiliation' => array(
			'display_name' => 'I am a Luther',
			'type' => 'checkboxgroup_no_sort',
			'options' => array('Alumnus/Spouse'=>'Alumnus/a or Spouse','Parent'=>'Parent','Friend'=>'Friend','Student'=>'Student','Faculty/Staff'=>'Faculty/Staff',),
		),
		'class_year' => array(
			'type' => 'text',
			'size' => 4,
			'comments' => '<span class="smallText comment">(YYYY)</span><div class="smallText comment">Choose either your class or your spouse\'s class.</div>',
		),
		'address_note' => array(
			'type' => 'comment',
			'text' => '<h3>Address and Contact Information</h3>',
		),
		'address_1' => 'text',
		'address_2' => 'text',
		'city' => array(
			'type' => 'text',
			'size'=>35,
		),
		'state_province' => array(
			'type' => 'state_province',
			'display_name' => 'State/Province',
			'include_military_codes' => true,
		),
		'zip' => array(
			'type' => 'text',
			'display_name' => 'Zip/Postal Code',
			'size'=>35,
		),
		'country' => array(
			'type' => 'country',
		),
		'phone' => array(
			'type' => 'text',
			'size'=>20,
		),
		'phone_type' => array(
			'type' => 'select_no_sort',
			'options' => array('Home'=>'Home', 'Cell'=>'Cell','Business'=>'Business',),
			'default' => 'Home',
		),
		'email' => array(
			'type' => 'text',
			'size'=>35,
			'display_name' => 'Email',
		),
		'estate_header' => array(
			'type' => 'comment',
			'text' => '<h3>Estate Planning <em>(optional)</em></h3>',
		),
		'estate_plans' => array(
			'type' => 'checkboxgroup_no_sort',
			'options' => array('have_estate_plans'=>'I\'ve included Luther in my estate plans.',
				'send_estate_info'=>'Please send me information about including Luther in my estate plans.'),
			'display_name' => '',
		),
		// 'estate_info' => array(
		// 	'type' => 'checkboxfirst',
  //                       'display_name' => 'Please send me information about including Luther in my estate plans.',
		// ),
		// 'address_note' => array(
		// 	'type' => 'comment',
		// 	'text' => '<h3>Address and Contact Information</h3>',
		// ),
		// 'address_type' => array(
		// 	'type' => 'radio_no_sort',
		// 	'options' => array('Home'=>'Home','Business'=>'Business'),
		// 	'default' => 'Home',
		// ),
		// 'street_address' => 'textarea',
		// 'city' => array(
		// 	'type' => 'text',
		// 	'size'=>35,
		// ),
		// 'state_province' => array(
		// 	'type' => 'state_province',
		// 	'display_name' => 'State/Province',
		// 	'include_military_codes' => true,
		// ),
		// 'zip' => array(
		// 	'type' => 'text',
		// 	'display_name' => 'Zip/Postal Code',
		// 	'size'=>35,
		// ),
		// 'country' => array(
		// 	'type' => 'country',
		// ),
		// 'phone' => array(
		// 	'type' => 'text',
		// 	'size'=>20,
		// ),
		// 'phone_type' => array(
		// 	'type' => 'select_no_sort',
		// 	'options' => array('Home'=>'Home', 'Cell'=>'Cell','Business'=>'Business',),
		// 	'default' => 'Home',
		// ),
		// 'email' => array(
		// 	'type' => 'text',
		// 	'size'=>35,
		// 	'display_name' => 'E-mail',
		// ),
		'advance_id'=>array(
			'type' => 'hidden',
		),
		'user_id'=>array(
			'type'=>'hidden',
		),
	);

	/**
	* Stores all the information necessary to instantiate each element group.
	* Format: element_group_name => element info
	* @var array
	*/
	var $element_group_info = array(	
		'name_group' => array (	'type' => 'inline',
					'elements' =>  array( 'first_name', 'last_name' ),
					'args' => array('use_element_labels' => false,
							'display_name' => 'Name',),
			),
		'spouse_name_group' => array ( 'type' => 'inline',
					'elements' => array( 'spouse_first_name', 'spouse_last_name'),
					'args' => array('use_element_labels' => false,
							'display_name' => 'Spouse Name (if applicable)'),
					),
		'phone_group' => array ('type' => 'inline',
					'elements' =>  array( 'phone', 'phone_type' ),
					'args' => array('use_element_labels' => false,
							'display_name' => 'Phone',),
			),
		);

	var $required = array(
		'first_name',
		'last_name',
		'luther_affiliation',
		'address_1',
		'city',
		'state_province',
		'zip',
		'country',
		'phone', 
		'phone_type',
		'email',
	);
	var $error_checks = array(
		'phone' => array(
			'is_phone_number' => 'Invalid Phone Number',
		),
	);
	var $display_name = 'Personal Info';
	var $error_header_text = 'Please check your form.';
	// var $form_to_person_key = array(
	// 	'first_name'=>array('src'=>'ldap_info','field'=>'givenname'),
	// 	'last_name'=>array('src'=>'ldap_info','field'=>'sn'),
	// 	'luther_affiliation'=>array('function'=>'get_affiliations'),
	// 	'class_year'=>array('src'=>'ldap_info','field'=>'carlcohortyear'),
	// 	'street_address'=>array('function'=>'get_street_address'),
	// 	'city'=>array('function'=>'get_home_city'),
	// 	'state_province'=>array('function'=>'get_home_state'),
	// 	'zip'=>array('function'=>'get_home_zip'),
	// 	'country'=>array('function'=>'get_home_country'),
	// 	'phone'=>array('function'=>'get_home_phone'),
	// 	'email'=>array('function'=>'get_email_address'),
	// 	'advance_id'=>array('src'=>'ldap_info','field'=>'carladvanceid'),
	// 	'user_id'=>array('src'=>'ldap_info','field'=>'carlnetid'),
	// 							);
	function is_phone_number( $num )
	{
		return true;
	}
	// style up the form and add comments et al
	function on_every_time()
	{
		$this->box_class = 'StackedBox';
		//add element groups
		foreach($this->element_group_info as $name => $info)
		{
			$this->add_element_group( $info['type'], $name, $info['elements'], $info['args']);
		}
		$this->move_element('name_group','before','luther_affiliation');
		$this->move_element('spouse_name_group','before','luther_affiliation');
		$this->move_element('phone_group','before','email');
		$this->add_comments('email','<div class="smallText comment">A confirmation email will be sent to this address.</div>');
		// $this->change_element_type('class_year','numrange',array('start'=>1924,'end'=>(date('Y')+5)));
		// $this->pre_fill_form();
	}
	// function pre_fill_form()
	// {
	// 	//$userid = 'polgreet_1976'; // An alumnus
	// 	//$userid = 'adicoffc_1978'; // Another alumnus
	// 	//$userid = 'perkinsb_1995'; // int'l alumnus - Czech Republic
	// 	//$userid = 'mehtan_1936'; // int'l alumnus - India
	// 	//$userid = 'mryan'; // An alumnus and staff member
	// 	//$userid = 'jlawrenc'; // A staff member
	// 	//$userid = 'syoon'; // A faculty member
	// 	//$userid = 'blahat'; // A student
	// 	//$userid = 'peiz'; // An international student - China
	// 	//$userid = 'yadavp'; // int'l student - India
	// 	//$userid = 'nogawac'; // int'l student - Japan
	// 	//$userid = 'hongj'; // int'l student - South Korea
	// 	//$userid = 'mccullom'; // int'l student - Canada
	// 	//$userid = 'holmesw'; // int'l student - UK
	// 	//$userid = 'ngoa'; // int'l student - Vietnam
	// 	if( $userid = reason_check_authentication() )
	// 	{
	// 		$person = get_individual_alum_info( $userid );
	// 		//pray($person);
	// 		foreach($this->form_to_person_key as $element_name=>$rules)
	// 		{
	// 			$value = '';
	// 			if(!$this->get_value($element_name))
	// 			{
	// 				if(empty($rules['function']))
	// 				{
	// 					$src = $rules['src'];
	// 					$field = $rules['field'];
	// 					if(!empty($person[$src][$field]))
	// 					{
	// 						if (is_array($person[$src][$field]))
	// 							$value = $person[$src][$field][0];
	// 						else
	// 							$value = $person[$src][$field];
	// 					}
	// 				}
	// 				else
	// 				{
	// 					$function = $rules['function'];
	// 					//echo $function.'<br />';
	// 					$value = $this->$function( $person );
	// 				}
	// 				if(!empty($value))
	// 				{
	// 					$this->set_value($element_name, $value);
	// 				}
	// 			}
	// 		}
	// 		if(!$this->get_value('country'))
	// 		{
	// 			$this->set_value('country','United States');
	// 		}		
	// 	}
	// }
	// function get_ldap_address_element( $person, $element )
	// {
	// 	static $elements;
	// 	static $has_run = false;
	// 	if(!$has_run)
	// 	{
	// 		if(!empty($person['ldap_info']['edupersonprimaryaffiliation'][0]) && $person['ldap_info']['edupersonprimaryaffiliation'][0] == 'student')
	// 		{
	// 			$ldap_address_field = 'carlstudentpermanentaddress';
	// 		}
	// 		else
	// 		{
	// 			$ldap_address_field = 'homepostaladdress';
	// 		}
	// 		if(!empty($person['ldap_info'][$ldap_address_field][0]))
	// 		{
	// 			$address_array = explode('$',$person['ldap_info'][$ldap_address_field][0]);
	// 			$last_line = array_pop($address_array);
	// 			if(preg_match('/^[A-Z ]+$/', trim($last_line))) // stings of 1 or more capital letters plus spaces -- probably a country piece
	// 			{
	// 				$elements['country'] = trim($last_line);
	// 				$last_line = array_pop($address_array);
	// 			}
	// 			$elements['street_address'] = implode("\n",$address_array);
	// 			$last_line_array = explode(' ',$last_line);
	// 			$zip_array = array();
	// 			$continue = true;
	// 			while($continue)
	// 			{
	// 				$possible_zip = array_pop($last_line_array);
	// 				if(preg_match('/[0-9]/',$possible_zip))
	// 				{
	// 					$zip_array[] = $possible_zip;
	// 				}
	// 				else
	// 				{
	// 					$last_line_array[] = $possible_zip;
	// 					$continue = false;
	// 				}
					
	// 			}
	// 			if(!empty($zip_array))
	// 			{
	// 				$elements['zip'] = implode(' ',$zip_array);
	// 			}
	// 			$possible_state_province = array_pop($last_line_array);
	// 			if(preg_match('/^[A-Z]+$/', trim($possible_state_province))) // looks like a state code
	// 			{
	// 				$elements['state_province'] = $possible_state_province;
	// 			}
	// 			else // probably part of the city name
	// 			{
	// 				$last_line_array[] = $possible_state_province; // put back into city name section
	// 				$elements['state_province'] = 'XX';
	// 			}
	// 			$elements['city'] = implode(' ',$last_line_array);
	// 			//pray($elements);
	// 			$has_run = true;
	// 		}
	// 	}
	// 	if(!empty($elements[$element]))
	// 	{
	// 		return $elements[$element];
	// 	}
		
	// }
	// function get_street_address( $person )
	// {
	// 	$advance_fields = array('HOME_STREET1','HOME_STREET2','HOME_STREET3');
	// 	$ldap_field = 'homepostaladdress';
	// 	if(!empty($person['advance_info']['HOME_STREET1']))
	// 	{
	// 		$line_array = array();
	// 		foreach($advance_fields as $field)
	// 		{
	// 			if(!empty($person['advance_info'][$field]))
	// 			{
	// 				$line_array[] = $person['advance_info'][$field];
	// 			}
	// 		}
	// 		$ret = implode("\n",$line_array);
	// 	}
	// 	else
	// 	{
	// 		$ret = $this->get_ldap_address_element( $person, 'street_address' );
	// 	}
	// 	return $ret;
	// }
	// function get_home_city( $person )
	// {
	// 	$ret = '';
	// 	if(!empty($person['advance_info']['HOME_CITY']))
	// 	{
	// 		$ret = $person['advance_info']['HOME_CITY'];
	// 	}
	// 	elseif(!empty($person['advance_info']['HOME_FOREIGN_CITYZIP']))
	// 	{
	// 		$ret = trim($person['advance_info']['HOME_FOREIGN_CITYZIP']);
	// 	}
	// 	else
	// 	{
	// 		$ret = $this->get_ldap_address_element( $person, 'city' );
	// 	}
	// 	return $ret;
	// }
	// function get_home_state( $person )
	// {
	// 	$ret = '';
	// 	if(!empty($person['advance_info']['HOME_STATE_CODE']))
	// 	{
	// 		$ret = $person['advance_info']['HOME_STATE_CODE'];
	// 	}
	// 	else
	// 	{
	// 		$ret = $this->get_ldap_address_element( $person, 'state_province' );
	// 	}
	// 	if(empty($ret))
	// 	{
	// 		$ret = 'XX';
	// 	}
	// 	return $ret;
	// }
	// function get_home_zip( $person )
	// {
	// 	$ret = '';
	// 	if(!empty($person['advance_info']['ZIPCODE']))
	// 	{
	// 		$ret = $person['advance_info']['ZIPCODE'];
	// 	}
	// 	else
	// 	{
	// 		$ret = $this->get_ldap_address_element( $person, 'zip' );
	// 	}
	// 	return $ret;
	// }
	// function get_home_country( $person )
	// {
	// 	$ret = '';
	// 	if(!empty($person['advance_info']['HOME_COUNTRY']))
	// 	{
	// 		$ret = $person['advance_info']['HOME_COUNTRY'];
	// 	}
	// 	else
	// 	{
	// 		$ret = $this->get_ldap_address_element( $person, 'country' );
	// 	}
	// 	return $ret;
	// }
	// function get_home_phone( $person )
	// {
	// 	if(!empty($person['ldap_info']['homephone'][0]))
	// 	{
	// 		return str_replace(array('+1 ',' '),array('','-'),$person['ldap_info']['homephone'][0]);
	// 	}
	// 	else
	// 	{
	// 		return false;
	// 	}
	// }
	// function get_business_phone( $person )
	// {
	// 	$ret = '';
	// 	if(!empty($person['advance_info']['BUS_PHONE']))
	// 	{
	// 		$ret = $person['advance_info']['BUS_PHONE'];
	// 	}
	// 	elseif(!empty($person['ldap_info']['telephonenumber'][0]))
	// 	{
	// 		$ret = str_replace(array('+1 ',' '),array('','-'),$person['ldap_info']['telephonenumber'][0]);
	// 	}
	// 	return $ret;
	// }
	// function get_email_address( $person  )
	// {
	// 	$ldap_fields = array('mail','carlhomeemail');
	// 	$ret = '';
	// 	foreach($ldap_fields as $field)
	// 	{
	// 		if(!empty($person['ldap_info'][$field][0]))
	// 		{
	// 			$ret = $person['ldap_info'][$field][0]."\n";
	// 			break;
	// 		}
	// 	}
	// 	return $ret;
	// }
	// function get_affiliations( $person )
	// {
	// 	$ret = array();
	// 	$aff_term_map = array('alum'=>'Alumnus','staff'=>'Staff','faculty'=>'Faculty','student'=>'Student');
	// 	if(!empty($person['ldap_info']['edupersonaffiliation']))
	// 	{
	// 		if(is_array($person['ldap_info']['edupersonaffiliation']))
	// 		{
	// 			foreach($person['ldap_info']['edupersonaffiliation'] as $ldap_aff)
	// 			{
	// 				if(!empty($aff_term_map[$ldap_aff]))
	// 				{
	// 					$ret[] = $aff_term_map[$ldap_aff];
	// 				}
	// 			}
	// 		}
	// 		else
	// 		{
	// 			$ldap_aff = $person['ldap_info']['edupersonaffiliation'];
	// 			if(!empty($aff_term_map[$ldap_aff]))
	// 			{
	// 				$ret[] = $aff_term_map[$ldap_aff];
	// 			}
	// 		}
	// 	}
	// 	return $ret;
	// }
	function pre_show_form()
	{
		echo '<div id="giftForm" class="pageTwo">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
	function run_error_checks()
	{
		if($this->get_value('luther_affiliation') && in_array('Alumnus', $this->get_value('luther_affiliation')) && !$this->get_value('class_year') )
		{
			$this->set_error('class_year','Please enter your class year if you are an alumnus/a');
		}
		if($this->get_value('luther_affiliation') && in_array('Student', $this->get_value('luther_affiliation')) && !$this->get_value('class_year') )
		{
			$this->set_error('class_year','Please enter your class year if you are a current Luther student');
		}
		// Taken from http://us2.php.net/eregi
		if( !eregi('^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,6}$',$this->get_value('email')))
		{
			$this->set_error('email','The email address you entered does not appear to be valid.  Please check to make sure you entered it correctly');
		}
	}
}
// function get_individual_ldap_info( $userid )
// {
// 	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
// 	$dir = new directory_service();
// 	if ($dir->search_by_attribute('ds_username', $userid, 
// 		array('carlnetid','carladvanceid','ds_fullname','mail','carlhomeemail','edupersonaffiliation','edupersonprimaryaffiliation',
// 		'telephonenumber','homephone','carlstudentpermanentaddress','homepostaladdress','carlcohortyear'))) 
// 	{
// 		return $dir->get_first_record();
// 	} else {
// 		return false;
// 	}
// }

// function get_individual_alum_info( $userid )
// {
// 	$person = array();
// 	$person['ldap_info'] = get_individual_ldap_info( $userid );
// 	if( !empty($person['ldap_info']['carladvanceid'][0]) )
// 	{
// 		connectDB('alumni_directory');
// 		$dbs = new DBSelector;
// 		$dbs->add_table( 'ad', 'alumni_dir' );
// 		$dbs->add_relation( 'ID_NUMBER = "'.$person['ldap_info']['carladvanceid'][0].'"');
// 		$result = $dbs->run('Querying for info in alumni_directory for '.$userid);
// 		connectDB(REASON_DB);
// 		reset($result);
// 		$person['advance_info'] = current($result);
// 	}
// 	return $person;
// }
?>
