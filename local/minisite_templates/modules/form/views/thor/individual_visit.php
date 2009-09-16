<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'IndividualVisitForm';

/**
 * IndividualVisitForm adds visit request info to Thor form
 * that gets personal info
 *
 * @author Steve Smith
 */

class IndividualVisitForm extends DefaultThorForm
{
	//$box = new Box;
	//$box->head();
	/*
	 $box = new Box;
 $box->head();
 //To display a normal (two-column) element:
 $box->row($label, $content, $required, $error, $key);
 //Alternative way to display a two-column element:
 $box->row_open($label, $required, $error, $key);
 echo 'Content';
 $box->row_close();
 //To display a spanning element:
 $box->row_text_span($content, $colspan, $error, $key);
 //To end the box class:
 $box->foot( $buttons );

	//$box = new Box;
	//$box -> head();
	//$box->row_text_span();
	*/
	
	var $elements = array(
	'academic_interests_comment' => array(
		'type' => 'comment',
		'text' => '<h3>Please choose up to 3 academic areas you are interested in studying.</h3>',
		),
	'academic_interests' => array(
		'type' => 'select',
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
			'Undecided' => 'Undecided',
			'Women\'s and Gender Studies'=>'Women\'s and Gender Studies',
			'Arts Management'=>'Arts Management (Preprofessional Program)',
			'International Management Studies'=>'International Management Studies (Preprofessional Program)',
			'Predentistry'=>'Predentistry (Preprofessional Program)',
			'Preengineering'=>'Preengineering (Preprofessional Program)',
			'Prelaw'=>'Prelaw (Preprofessional Program)',
			'Premedicine'=>'Premedicine (Preprofessional Program)',
			'Preoptometry'=>'Preoptometry (Preprofessional Program)',
			'Prepharmacy'=>'Prepharmacy (Preprofessional Program)',
			'Prephysical Therapy'=>'Prephysical Therapy (Preprofessional Program)',
			'Preseminary'=>'Preseminary (Preprofessional Program)',
			'Preveterinary Medicine'=>'Preveterinary Medicine (Preprofessional Program)',
			'Sports Management'=>'Sports Management (Preprofessional Program)',
			),
		),
		'academic_interests_2' => array(
		'type' => 'select',
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
			'Arts Management'=>'Arts Management (Preprofessional Program)',
			'International Management Studies'=>'International Management Studies (Preprofessional Program)',
			'Predentistry'=>'Predentistry (Preprofessional Program)',
			'Preengineering'=>'Preengineering (Preprofessional Program)',
			'Prelaw'=>'Prelaw (Preprofessional Program)',
			'Premedicine'=>'Premedicine (Preprofessional Program)',
			'Preoptometry'=>'Preoptometry (Preprofessional Program)',
			'Prepharmacy'=>'Prepharmacy (Preprofessional Program)',
			'Prephysical Therapy'=>'Prephysical Therapy (Preprofessional Program)',
			'Preseminary'=>'Preseminary (Preprofessional Program)',
			'Preveterinary Medicine'=>'Preveterinary Medicine (Preprofessional Program)',
			'Sports Management'=>'Sports Management (Preprofessional Program)',
			),
		),	
		'academic_interests_3' => array(
		'type' => 'select',
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
			'Arts Management'=>'Arts Management (Preprofessional Program)',
			'International Management Studies'=>'International Management Studies (Preprofessional Program)',
			'Predentistry'=>'Predentistry (Preprofessional Program)',
			'Preengineering'=>'Preengineering (Preprofessional Program)',
			'Prelaw'=>'Prelaw (Preprofessional Program)',
			'Premedicine'=>'Premedicine (Preprofessional Program)',
			'Preoptometry'=>'Preoptometry (Preprofessional Program)',
			'Prepharmacy'=>'Prepharmacy (Preprofessional Program)',
			'Prephysical Therapy'=>'Prephysical Therapy (Preprofessional Program)',
			'Preseminary'=>'Preseminary (Preprofessional Program)',
			'Preveterinary Medicine'=>'Preveterinary Medicine (Preprofessional Program)',
			'Sports Management'=>'Sports Management (Preprofessional Program)',
			),
		),
	'participate_varsity_sport' => array(
		'type' => 'checkboxgroup',
		'display_name' => 'Do you plan to participate in a varsity sport?',
//		'display_style' => 'normal',
		'colspan' => '3',
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
	'participate_music?' => array(
		'type' => 'radio_inline_no_sort',
		'display_style' => '4',
		'display_name' => 'Do you plan to participate in music?',
		'options' => array(
			'Band'=>'Band',
			'Choir'=>'Choir',
			'Composition'=>'Composition',
			'Early Music Ensemble'=>'Early Music Ensemble',
			'Jazz Band'=>'Jazz Band',
			'Orchestra'=>'Orchestra',
			)
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
		'comments' => '<small>  30 min</small>',
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
	'overnight_date_time' => array(
		'type' => 'textdatetime',
//		'year_max' => '2011',
//		'second' => 0,
		'display_name' => 'Please indicate date. If requesting the night
						 prior to your visit day, please indicate arrival 
						 time as well',
		), 	
	);
	
	// if defined none of the default actions will be run (such as email_form_data) and you need to define the custom method and a
	// should_custom_method in the view (if they are not in the model).
	var $process_actions = array('my_custom_process');
	
	function custom_init()
	{
	
	}

	function on_every_time()
	{
		$state_field = $this->get_element_name_from_label('State/Province');
		$this->change_element_type($state_field, 'state_province');
		
		$gender_field_name = $this->get_element_name_from_label('Gender');
		$this->change_element_type($gender_field_name, 'radio_inline');
		
	}
	//////////////////////////////////////
	/*
	function on_every_time()
	{
		$username = reason_check_authentication();
		
		if ($username)
		{
			echo '<p>Your username is ' . $username . '</p>';
			$user_id = get_user_id($username);
			$user_entity = new entity($user_id);
			pray ($user_entity);
			$your_name = $user_entity->get_value('user_given_name');
			
			echo '<p>Welcome to the form ' . $your_name . '</p>';
		}
	
		$food_stuff_field_name = $this->get_element_name_from_label('Food Stuff');
		$this->set_comments($food_stuff_field_name, '<p>The list of foods has been carefully selected.</p>');
		
		$this->change_element_type('extra_field', 'textarea');
		$this->add_required($this->get_element_name_from_label('Last Name'));
	}	
	//////////////////////////////////////
	*/
	
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
}
?>
