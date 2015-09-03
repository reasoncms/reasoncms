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
