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
		'deadline_note' => array(
			'type' => 'comment',
			'text' => 'Priority registration ended October 22; if you wish to submit a late registration, the late registration conference fee is $120 plus $ 25 (optional) for the banquet.',
		),
		'your_information_header' => array(
			'type' => 'comment',
			'text' => '<h2>Your Information</h2>',
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
			'text' => '*Phone</ br> (please include at least one number of a daytime phone or phone with voicemail)',
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
			'size' => 35,
		),
		'position_title' => array(
			'type' => 'text',
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
			'text' => '<h2>Registration</h2><p>Registration includes admission to all plenary sessions, panels, readings, coffee breaks, and Friday evening reception)</p>',
		),
		'conference_note' => array(
			'type' => 'comment',
			'text' => '<h3>Conference Fee*</h3>',
		),
		'conference_fee' => array(
			'type' => 'radio_no_sort',
			'display_name' => '&nbsp;',
			'options' => array(
				'120'=>'General Public, $120',
				'25'=>'Non-Luther Student, $25',
				'-'=>'Luther Faculty and Staff, $0',
				'--'=>'Luther Student, $0',
			),
		),
		'banquet_note' => array(
			'type' => 'comment',
			'text' => '<h3>Attend the Saturday evening banquet?</h3>',
		),
		'attend_banquet' => array(
			'type' => 'radio_inline_no_sort',
			'display_name' => 'Cost - $25',
			'options' => array(
				'Yes'=>'Yes',
				'No'=>'No',
			),
		),
		'dietary_note' => array(
			'type' => 'comment',
			'text' => 'If yes, please list any dietary needs.',
		),
		'dietary_needs'=> array(
			'type' => 'textarea',
			'rows' => 5,
			'cols' => 35,
			'display_name' => '&nbsp;',
		),
		'lodging_header' => array(
			'type' => 'comment',
			'text' => '<h2>Lodging Information</h2>',
		),
		'lodging_comment' => array(
			'type' => 'comment',
			'display_name' => '&nbsp;',
			'text' => '<ul>General Public - Please see the <a href="/about/decorah/lodging/decorah/" target="_blank">Accommodations</a> page.</ul>
						<ul>Visiting college students can choose to be housed with Luther students in non-smoking residence halls; they must bring their own sleeping bags, pillows, and towels. Student housing requests must be received by <strong>October 15, 2010.</strong></ul>',
		),
		'student_housing' => array(
			'type' => 'radio_inline_no_sort',
			'display_name' => 'Will you need Festival-arranged housing on campus?',
			'comments' => 'for students only',
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
	
	var $required = array('first_name', 'last_name', 'street_address', 'city', 'state_province', 'zip', 'e-mail',);
	var $display_name = 'Lutheran Festival of Writing Registration';
	var $error_header_text = 'Please check your form.';

	function on_every_time()
	{
		$this->set_comments('position_title',form_comment('If applicable'));
		$this->set_comments('institution',form_comment('college, church, company, etc. If applicable'));
	}
	function pre_show_form()
	{
		echo '<div id="lfwForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
	function run_error_checks()
	{
		if (!($this->get_value('home_phone')||$this->get_value('office_phone')||$this->get_value('cell_phone')))
		{
			$this->set_error('home_phone','Please include at least one number of a daytime phone or phone with voicemail');
		}
		if (!($this->get_value('conference_fee')))
		{
			$this->set_error('conference_note','Please select a conference fee');
		}
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
