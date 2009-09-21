<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'IndividualVisitForm';

/**
 * IndividualVisitForm adds visit request info to Thor form
 * that gets personal info
 *
 * @author Steve Smith
 */


class IndividualVisitForm extends DefaultThorForm
{
	
	var $elements = array(
/*
	'visit_date_comments' => array(
		'type' => 'comment',
		'text' => '<h3>Please use the calendar to select a date to visit. Available dates are in green. Please include an arrival time so we know when to expect you.</h3>',
		),
	'visit_date_and_time' => array(
		'type' => 'textdatetime_js',
		'script_url' => 'http://www.luther.edu/scripts/datetime.js',
		),

	'first_name' => 'text',
	'last_name' => 'text',
	'gender' => array(
		'type' => 'radio_inline',
		'options' => array('Female'=>'Female','Male'=>'Male',),
		),
	'address' => 'text',
	'city' => 'text',
	'state/province' => 'state_province',
	'zip' => 'text',
	'email' => 'text',
	'home_phone' => 'text',
	'cell_phone' => 'text',
*/
	'high_school' => array(
		'type' => 'text',
		'display_style' => 'normal',
		),
	'graduation_year' => array(
		'type' => 'year',
		'num_years_after_today' => 3,
		'num_years_before_today' => 4,
		),
	'transfer' => array(
		'type' => 'radio_inline_no_sort',
		'display_name' => 'Are you a transfer student?',
		'options' => array('Yes' => 'Yes', 'No' => 'No',),
		),
	'transfer_college' => array(
		'type' => 'textarea',
		'display_name' => 'If yes, what is the name and address of the school you previously attended?'
		),
	'visit_activities' => array(
		'type' => 'comment',
		'text' => '<h3>Please check any of the following activities 
					that you would like to do as part of your campus visit.
					We will try to accommodate as many of your requests as 
					possible.</h3>',
		),
	'meet_counselor' => array(
		'type' => 'checkboxfirst',
		'colspan' => 1,
		'display_name' => 'Meet with an Admissions Counselor',
		'display_style'=>'normal',
 		'comments' => '<small>  (30 min)</small>', 
		),
	'tour' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Take a campus tour',
		'display_style'=>'normal',
		'comments' => '<small>  (60 min)</small>',
		),
	'meet_faculty' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Meet with a faculty member',
		'display_style' => 'normal',
		'comments' => '<small>  (30 min)</small>',
		),
	'meet_faculty_details' => array(
		'type' => 'select',
		'add_null_value_to_top' => true,
		'display_name' =>'Select Department',
		'options' => array(
			'Accounting'=>'Accounting',
			'Africana Studies'=>'Africana Studies',
			'Art'=>'Art',
			'Athletic Training'=>'Athletic Training',
			'Biblical Languages'=>'Biblical Languages',
			'Biology'=>'Biology',
			'Business'=>'Business',
			'Chemistry'=>'Chemistry',
			'Classical Studies'=>'Classical Studies',
			'Classics'=>'Classics',
			'Communication Studies'=>'Communication Studies',
			'Computer Science'=>'Computer Science',
			'Economics'=>'Economics',
			'Education'=>'Education',
			'English'=>'English',
			'Environmental Studies'=>'Environmental Studies',
			'French'=>'French',
			'German'=>'German',
			'Health'=>'Health',
			'History'=>'History',
			'International Studies'=>'International Studies',
			'Management'=>'Management',
			'Management Information Systems'=>'Management Information Systems',
			'Mathematics'=>'Mathematics',
			'Mathematics/Statistics'=>'Mathematics/Statistics',
			'Museum Studies'=>'Museum Studies',
			'Music'=>'Music',
			'Nursing'=>'Nursing',
			'Philosophy'=>'Philosophy',
			'Physical Education'=>'Physical Education',
			'Physics'=>'Physics',
			'Political Science'=>'Political Science',
			'Psychology'=>'Psychology',
			'Religion'=>'Religion',
			'Russian Studies'=>'Russian Studies',
			'Scandinavian Studies'=>'Scandinavian Studies',
			'Social Welfare'=>'Social Welfare',
			'Social Work'=>'Social Work',
			'Sociology'=>'Sociology',
			'Spanish'=>'Spanish',
			'Speech and Theatre'=>'Speech and Theatre',
			'Theatre/Dance'=>'Theatre/Dance',
			'Women\'s and Gender Studies'=>'Women\'s and Gender Studies',
			),
		),
		'meet_second_faculty' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Meet with a second faculty member',
		'display_style' => 'normal',
		'comments' => '<small>  (30 min)</small>',
		),
	'meet__second_faculty_details' => array(
		'type' => 'select',
		'add_null_value_to_top' => true,
		'display_name' =>'Select Department',
		'options' => array(
			'Accounting'=>'Accounting',
			'Africana Studies'=>'Africana Studies',
			'Art'=>'Art',
			'Athletic Training'=>'Athletic Training',
			'Biblical Languages'=>'Biblical Languages',
			'Biology'=>'Biology',
			'Business'=>'Business',
			'Chemistry'=>'Chemistry',
			'Classical Studies'=>'Classical Studies',
			'Classics'=>'Classics',
			'Communication Studies'=>'Communication Studies',
			'Computer Science'=>'Computer Science',
			'Economics'=>'Economics',
			'Education'=>'Education',
			'English'=>'English',
			'Environmental Studies'=>'Environmental Studies',
			'French'=>'French',
			'German'=>'German',
			'Health'=>'Health',
			'History'=>'History',
			'International Studies'=>'International Studies',
			'Management'=>'Management',
			'Management Information Systems'=>'Management Information Systems',
			'Mathematics'=>'Mathematics',
			'Mathematics/Statistics'=>'Mathematics/Statistics',
			'Museum Studies'=>'Museum Studies',
			'Music'=>'Music',
			'Nursing'=>'Nursing',
			'Philosophy'=>'Philosophy',
			'Physical Education'=>'Physical Education',
			'Physics'=>'Physics',
			'Political Science'=>'Political Science',
			'Psychology'=>'Psychology',
			'Religion'=>'Religion',
			'Russian Studies'=>'Russian Studies',
			'Scandinavian Studies'=>'Scandinavian Studies',
			'Social Welfare'=>'Social Welfare',
			'Social Work'=>'Social Work',
			'Sociology'=>'Sociology',
			'Spanish'=>'Spanish',
			'Speech and Theatre'=>'Speech and Theatre',
			'Theatre/Dance'=>'Theatre/Dance',
			'Women\'s and Gender Studies'=>'Women\'s and Gender Studies',
			),
		),	
	'observe_class' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Sit in on a class',
		'display_style' => 'normal',
		'comments' => '<small>  Seniors Only - MWF (60 min) T Th (90 min)</small>',
		),
	'observe_class_details' => array(
		'type' => 'select',
		'add_null_value_to_top' => true,
		'display_name' => 'Select Department',
		'options' => array(
			'Accounting'=>'Accounting',
			'Africana Studies'=>'Africana Studies',
			'Art'=>'Art',
			'Athletic Training'=>'Athletic Training',
			'Biblical Languages'=>'Biblical Languages',
			'Biology'=>'Biology',
			'Business'=>'Business',
			'Chemistry'=>'Chemistry',
			'Classical Studies'=>'Classical Studies',
			'Classics'=>'Classics',
			'Communication Studies'=>'Communication Studies',
			'Computer Science'=>'Computer Science',
			'Economics'=>'Economics',
			'Education'=>'Education',
			'English'=>'English',
			'Environmental Studies'=>'Environmental Studies',
			'French'=>'French',
			'German'=>'German',
			'Health'=>'Health',
			'History'=>'History',
			'International Studies'=>'International Studies',
			'Management'=>'Management',
			'Management Information Systems'=>'Management Information Systems',
			'Mathematics'=>'Mathematics',
			'Mathematics/Statistics'=>'Mathematics/Statistics',
			'Museum Studies'=>'Museum Studies',
			'Music'=>'Music',
			'Nursing'=>'Nursing',
			'Philosophy'=>'Philosophy',
			'Physical Education'=>'Physical Education',
			'Physics'=>'Physics',
			'Political Science'=>'Political Science',
			'Psychology'=>'Psychology',
			'Religion'=>'Religion',
			'Russian Studies'=>'Russian Studies',
			'Scandinavian Studies'=>'Scandinavian Studies',
			'Social Welfare'=>'Social Welfare',
			'Social Work'=>'Social Work',
			'Sociology'=>'Sociology',
			'Spanish'=>'Spanish',
			'Speech and Theatre'=>'Speech and Theatre',
			'Theatre/Dance'=>'Theatre/Dance',
			'Women\'s and Gender Studies'=>'Women\'s and Gender Studies',
			),		
		),
	'chapel' => array(
		'type' => 'checkboxfirst',
		'colspan' => 2,
		'display_style' => 'normal',
		'comments' => '<small>  (30 min) daily at 10:30</small>',
		),
	'lunch' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Lunch',
		'display_style' => 'normal',
		'comments' => '<small>  (30-60 min)</small>',
		),
	'meet_coach' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Conversation with a coach',
		'display_style' => 'normal',
		'comments' => '<small>  (30 min)</small>',
		),
	'meet_coach_details' => array(
		'type' => 'select',
		'display_name' => 'Select Sport',
		'add_null_value_to_top' => true,
		'options' => array(
			'Baseball'=>'Baseball',
			'Basketball'=>'Basketball',
			'Cross Country'=>'Cross Country',
			'Football'=>'Football',
			'Golf'=>'Golf',
			'Soccer'=>'Soccer',
			'Softball'=>'Softball',
			'Swimming & Diving'=>'Swimming & Diving',
			'Tennis'=>'Tennis',
			'Track & Field'=>'Track & Field',
			'Volleyball'=>'Volleyball',		
			'Wrestling'=>'Wrestling',
			),
		),
	'choir' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Observe a choir rehearsal, if available',
		'display_style' => 'normal',
		'comments' => '<small>  MWF 1:30 (60 min)</small>',
		),
	'band' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Observe a band rehearsal, if available',
		'display_style' => 'normal',
		'comments' => '<small>  MWF 12:15 (60 min)</small>',
		),
	'orchestra' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Observe a orchestra rehearsal, if available',
		'display_style' => 'normal',
		'comments' => '<small>  MTWTHF 4:00 (60 min)</small>',
		),
	'music_audition' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'Perform a music audition for scholarship',
		'display_style' => 'normal',
		'comments' => '<small>  Seniors Only (30 min)</small>',
		),
	'music_audition_details' => array(
		'type' => 'select_no_sort_js',
		'display_name' => 'Select Instrument/Voice',
		'add_null_value_to_top' => true,
		'options' => array(
			'Flute'=>'Flute',
			'Oboe'=>'Oboe',
			'Clarinet'=>'Clarinet',
			'Saxophone'=>'Saxophone',
			'Bassoon'=>'Bassoon',
			'Horn'=>'Horn',
			'Trumpet'=>'Trumpet',
			'Trombone'=>'Trombone',
			'Euphonium'=>'Euphonium',
			'Tuba'=>'Tuba', 
			'Percussion'=>'Percussion',
			'Piano'=>'Piano',
			'Harp'=>'Harp',
			'Soprano'=>'Soprano',
			'Alto'=>'Alto',
			'Tenor'=>'Tenor',
			'Bass'=>'Bass',
			'Violin'=>'Violin',
			'Viola'=>'Viola',
			'Cello'=>'Cello',
			'Double Bass'=>'Double Bass',
			),
		), 	
	'meet_music_faculty' => array(
		'type' => 'checkboxfirst',
		'display_style' => 'normal',
		'display_name' => 'Conversation with music faculty',
		'comments' => '<small>  (30 min)</small>',
		),
	'meet_music_faculty_details' => array(
		'type' => 'select_no_sort',
		'display_name' => 'Select Discipline',
		'display_style' => 'right',
		'add_null_value_to_top' => true,
		'options' => array(
			'Band'=>'Band',
			'Choir'=>'Choir',
			'Composition'=>'Composition',
			'Early Music'=>'Early Music',
			'Jazz'=>'Jazz',
			'Music Education'=>'Music Education',
			'Orchestra'=>'Orchestra',
			'Theory'=>'Theory',
			'Flute'=>'Flute',
			'Oboe'=>'Oboe',
			'Clarinet'=>'Clarinet',
			'Saxophone'=>'Saxophone',
			'Bassoon'=>'Bassoon',
			'Horn'=>'Horn',
			'Trumpet'=>'Trumpet',
			'Trombone'=>'Trombone',
			'Euphonium'=>'Euphonium',
			'Tuba'=>'Tuba', 
			'Percussion'=>'Percussion',
			'Piano'=>'Piano',
			'Harp'=>'Harp',
			'Soprano'=>'Soprano',
			'Alto'=>'Alto',
			'Tenor'=>'Tenor',
			'Bass'=>'Bass',
			'Violin'=>'Violin',
			'Viola'=>'Viola',
			'Cello'=>'Cello',
			'Double Bass'=>'Double Bass',
			),
		),
	'additional_request' => array( 	
		'type' => 'textarea',
		'rows' => 2,
		'cols' => 35,
		'display_name' =>'Additional Request',
//		'comments' => '<small>  30 min</small>',
		),
	'housing_note' => array(
		'type' => 'comment',
		'text' => '<h3>Overnight Housing</h3> (Seniors Only - Please provide two weeks notice)',
		),
	'overnight_housing' => array(
		'type' => 'checkboxfirst',
		'display_name' => 'I would like to request overnight housing 
						with a current Luther student',
//		'display_style' => 'normal',
		),
	'overnight_note' => array(
		'type' => 'comment',
		'text' => '<strong>Please indicate arrival date. If requesting the night prior to your visit day, please indicate arrival time as well</strong>',
		),
	'overnight_date_and_time' => array(
		'type' => 'textdatetimenoseconds',
		//'script_url' => 'http://reasondev.luther.edu/javascripts/individual_visit.js',
		), 	
	);
	

	var $required = array(
//		'first_name',
//		'last_name',
//		'gender',
		'high_school',
		'graduation_year',
//		'email',
//		'visit_date_and_time'
//		'arrival_time'
	);


	// if defined none of the default actions will be run (such as email_form_data) and you need to define the custom method and a
	// should_custom_method in the view (if they are not in the model).
//	var $process_actions = array('my_custom_process');
	
	function custom_init()
	{
	
	}

	function on_every_time()
	{
		$visitdatetime_field = $this->get_element_name_from_label('Visit Date and Time');
		$this->change_element_type($visitdatetime_field, 'textdatetime_js');
		
		$gender = $this->get_element_name_from_label('Gender');
		$this->change_element_type($gender, 'radio_inline_no_sort');
		
		$state_field = $this->get_element_name_from_label('State/Province');
		$this->change_element_type($state_field, 'state_province');

//		$this->set_element_properties($grad_year, 'num_years_after_today' => 3, 'num_years_before_today' => 4);


		//$gender_field_name = $this->get_element_name_from_label('Gender');
		//$this->change_element_type($gender_field_name, 'radio_inline');
		
	}
	
	
	function email_form_data_to_submitter()
	{
		$model =& $this->get_model();
		
		// Figure out who would get an email confirmation (either through a 
		// Your Email field or by knowing the netid of the submitter
		if (!$recipient = $this->get_value_from_label('Email'))
		{
			if ($submitter = $model->get_email_of_submitter())
				$recipient = $submitter.'@luther.edu';
		}
		
		// If we're supposed to send a confirmation and we have an address...
		if ($recipient)
		{
			// Use the (first) form recipient as the return address if available
			if ($senders = $model->get_email_of_recipient())
			{
				list($sender) = explode(',',$senders, 1);
				if (strpos($sender, '@') === FALSE)
					$sender .= '@luther.edu';
			} else {
				$sender = 'auto-form-process@luther.edu';
			}
			
			$thank_you = $model->get_thank_you_message();
			
			$email_values = $model->get_values_for_email_submitter_view();
	
			if (!($subject = $this->get_value_from_label('Confirmation Subject')))
				$subject = 'Thank you for requesting a visit';
			
			$values = "\n";
			if ($model->should_email_data())
			{
				foreach ($email_values as $key => $val)
				{
					$values .= sprintf("\n%s:\n   %s\n", $val['label'], $val['value']);
				}
			}
			
			$html_body = $thank_you . nl2br($values);
			$txt_body = html_entity_decode(strip_tags($html_body));
			
			$mailer = new Email($recipient, $sender, $sender, $subject, $txt_body, $html_body);
			$mailer->send();
		}		
	}
	
	
	function run_error_checks()
	{
		//$val = $this->get_value('extra_field');
		//if (empty($val)) $this->set_error('extra_field', 'The field must have content');
	}
	
	function process()
	{
		// getting value from a disco field
		///$field_value = $this->get_value('extra_field');
		
		// getting disco field name from thor
		///$food_stuff_field_name = $this->get_element_name_from_label('Food Stuff');
		///$food_stuff_value = $this->get_value($food_stuff_field_name);
		///echo $food_stuff_value;
	}
	
/*
	function should_my_custom_process()
	{
		return true;
	}

	
	function my_custom_process()
	{
		echo 'hello';
	}
	
	function where_to()
	{
		return false;
	}
*/
}
?>
