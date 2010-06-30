<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the Lutheran Festival of Writing form
//
////////////////////////////////////////////////////////////////////////////////

class LFWFormPageOne extends FormStep
{
	var $_log_errors = true;
	var $error;
	
	var $elements = array(
		'amount' => 'cloaked',
		'your_information_header' => array(
			'type' => 'comment',
			'text' => 'Application deadline is <strong>October 22, 2010</strong>',
		),
		'title' => array(
			'type' => 'select_no_sort',
			'add_null_value_to_top' => true,
			'options' => array('Mr.'=>'Mr.', 'Ms.'=>'Ms.', 'Mrs.'=>'Mrs.', 'Prof.'=>'Prof.','Dr.'=>'Dr.','Pastor'=>'Pastor'
			)
		),
		'first_name' => array(
			'type' => 'text',
			'size' => 15,
		),
		'middle_initial' => array(
			'type' => 'text',
			'size' => 2,
		),
		'last_name' => array(
			'type' => 'text',
			'size' => 20,
		),
		'street_address' => array(
			'type' => 'text',
			'size' => 35,
		),
		'city' => array(
			'type' => 'text',
			'size' => 35,
		),
		'state_province' => array(
			'type' => 'state_province',
			'display_name' => 'State/Province',
		),
		'zip' => array(
			'type' => 'text',
			'display_name' => 'Zip/Postal Code',
			'size' => 35,
		),
		'e-mail' => array(
			'type' => 'text',
			'size'=> 35,
			'display_name' => 'E-mail Address'
		),
		'phone_note' => array(
			'type' => 'comment',
			'text' => '*Phone (please include at least one number of a daytime phone or phone with voicemail)'
		),
		'home_phone' => array(
			'type' => 'text',
			'size'=>20,
		),
		'office_phone' => array(
			'type' => 'text',
			'size'=>20,
		),
		'cell_phone' => array(
			'type' => 'text',
			'size'=>20,
		),
		'institution' => array(
			'type' => 'text',
			'comments' => '<br />college, church, company, etc. If applicable',
			'size' => 35,
		),
		'position_title' => array(
			'type' => 'text',
			'comments' => '<br />If applicable',
			'size' => 35,
		),
		'profession' => array(
			'type' => 'select_no_sort',
			'add_null_value_to_top' => true,
			'options' => array(
				'Faculty'=>'Faculty',
				'Staff'=>'Staff',
				'Student'=>'Student',
				'Clergy'=>'Clergy',
				'Other'=>'Other Rostered Church Leader',
			),
		),
		'registration_header' => array(
			'type' => 'comment',
			'text' => '<h3>Registration</h3><br />Registration includes admission to all plenary sessions, panels, readings, coffee breaks, and Friday evening reception)',
		),
		'conference_fee' => array(
			'type' => 'radio_no_sort',
			'options' => array(
				'95'=>'General Public, $95',
				'25'=>'Non-Luther Student, $25',
				'-'=>'Luther Faculty and Staff, $0',
				'--'=>'Luther Student, $0',
			),
		),
		'attend_banquet' => array(
			'type' => 'radio_inline_no_sort',
			'display_name' => 'Attend Saturday evening banquet?',
			'comments' => 'cost $25',
			'options' => array(
				'Yes'=>'Yes',
				'No'=>'No',
			),
		),
		'dietary_needs'=> array(
			'type' => 'textarea',
			'display_name' => 'Please list any special dietary needs',
		),
		'lodging_header' => array(
			'type' => 'comment',
			'text' => '<h3>Lodging Information</h3>',
		),
		'general_lodging_comment' => array(
			'type' => 'comment',
			'text' => '<strong>General Public:</strong>. please see the <a href="housing" target="_blank">Accomodations</a> page.',
		),
		'visiting_student_comment' => array(
			'type' => 'comment',
			'text' => '<strong>Visiting college students</strong> can choose to be housed with Luther students in non-smoking residence halls; they must bring their own sleeping bags, pillows, and towels. Student housing requests must be received by <strong>October 15, 2010.</strong>',
		),
		'student_housing' => array(
			'type' => 'radio_inline_no_sort',
			'display_name' => 'Will you need Festival-arranged housing on campus?',
			'comments' => '<br />students only',
			'options' => array(
				'Yes'=>'Yes',
				'No'=>'No',
			),
		),
		'housing_gender' => array(
			'type' => 'radio_inline_no_sort',
			'display_name' => 'Gender',
			'options' => array(
				'F'=>'Female',
				'M'=>'Male',
			),
		),
		'housing_student_type' => array(
			'type' => 'select_no_sort',
			'add_null_value_to_top' => true,
			'display_name' => 'Type of Student',
			'options' => array(
				'HS'=>'High School',
				'College'=>'College',
				'Grad'=>'Graduate Student',
				'Seminary' => 'Seminary'
			),
		),
		'housing_nights' => array(
			'type' => 'radio_inline_no_sort',
			'display_name' => 'Nights housing is needed',
			'options' => array(
				'Fri'=>'Friday night',
				'Sat'=>'Saturday night',
				'both'=>'both nights',
			),
		),	
	);
	
	var $required = array('first_name', 'last_name', 'street_address', 'city', 'state_province', 'zip', 'e-mail');
	var $display_name = 'Lutheran Festival of Writing Registration';
	var $error_header_text = 'Please check your form.';

	function pre_show_form()
	{
		echo '<div id="lfwForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
	
	function needs_payment()
	{
	  	$amount = 0;
	  	$conf_fee = $this->get_value('conference_fee');
	  	$banq_fee = $this->get_value('attend_banquet');
	  		  	
		if (isset($conf_fee))
	  	{
			$amount = $amount + $conf_fee;
		}
		
		if ($banq_fee == 'Yes')
	  	{
			$amount = $amount + 25;
		}
		
		$this->set_value('amount', $amount);
		
		if ($amount == 0)
		{
			return 'LFWRegistrationConfirmation';
		}else{
			return 'LFWFormPageTwo';
		}
	}
}

?>
