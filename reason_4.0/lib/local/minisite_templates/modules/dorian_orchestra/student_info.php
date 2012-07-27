<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Lucas Welper
//    2010-11-03
//
//    Work on the first page of the dorian orchestra nomination form
//    which collects Student Info
//
////////////////////////////////////////////////////////////////////////////////

class StudentInfoForm extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Dorian Orchestra Festival Nomination Student Information';
	var $elements = array( 
		'student_information_header' => array(
			'type' => 'comment',
			'text' => '<h3>Student Information</h3>',
		),
		'student_first_name' => array(
			'type' => 'text',
                ),
                'student_last_name' => array(
                        'type' => 'text',
                ),
                'student_gender' => array(
                    'type' => 'radio_inline_no_sort',
                    'options' => array('Female' => 'Female', 'Male' => 'Male'),
                ),
                'student_email' => array(
                        'type' => 'text',
                       'display_name' => 'Student E-mail',
                ),
                'student_phone' => array(
                        'type' => 'text',
                ),
                'student_street_address' => array(
                        'type' => 'text',
                ),
                'student_city' => array(
                        'type' => 'text',
                ),
                'student_state' => 'state',
                'student_zip' => array(
                        'type' => 'text',
                ),
                'student_instrument' => array(
                        'type' => 'text',
                ),
                'student_grad_year' => array(
                    'type' => 'text',
                ),
                'years_orchestra_experience' => array(
                    'type' => 'text',
                ),
                'years_private_lessons' => array(
                    'type' => 'text',
                ),
                'private_instructor' => array(
                    'type' => 'text',
                ),
                'previously_attended' => array(
                    'type' => 'text',
                ),
                'student_section' => array(
                    'type' => 'text',
                ),
                'number_in_section' => array(
                    'type' => 'text',
                ),
                'chair_number' => array(
                    'type' => 'text',
                ),
                'faculty_audition_options' => array(
                    'type' => 'checkboxgroup_no_sort',
                    'display_name' => 'Faculty Audition Options (check all you would like):',
                    'options' => array(
                            'les' => 'Lesson',
                            'lsa' => 'Luther Scholarship Audition',
                            'mcp' => 'Masterclass performance',
                            'csp' => 'Concert solo performance')
                ),
                'faculty_audition_options_text' => array(
                    'type' => 'comment',
                    'text' => 'If you\'ve checked any of the options above, please list your chosen piece and its composer:',
                ),
                'faculty_audition_composer' => array(
                    'display_name' => 'Composer',
                    'type' => 'text',
                ),
                'faculty_audition_title_movement' => array(
                    'display_name' => 'Title & Movement',
                    'type' => 'text',
                ),
                'faculty_audition_accompanist' => array(
                    'display_name' => 'Do you wish Luther College to provide an accompanist for you ($15.00 fee)?',
                    'type' => 'radio_inline_no_sort',
                    'options' => array('Yes' => 'Yes', 'No' => 'No'),
                ),
                'recent_solo_label' => array(
                    'type' => 'comment',
                    'text' => 'Recently completed solo repertoire:',
                ),
                'recent_solo_label_1' => array(
                    'type' => 'comment',
                    'text' => 'Composer',
                ),
                'recent_solo_label_2' => array(
                    'type' => 'comment',
                    'text' => 'Title',
                ),
                'recent_solo_composer_1' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'recent_solo_title_1' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'recent_solo_composer_2' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'recent_solo_title_2' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'recent_solo_composer_3' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'recent_solo_title_3' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'recent_solo_composer_4' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'recent_solo_title_4' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'contests_festivals_label' => array(
                    'type' => 'comment',
                    'text' => 'Contests, festivals and other orchestras attended:',
                ),
                'contests_festivals_label_1' => array(
                    'type' => 'comment',
                    'text' => 'Composer',
                ),
                'contests_festivals_label_2' => array(
                    'type' => 'comment',
                    'text' => 'Title',
                ),
                'contests_festivals_what_1' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'contests_festivals_when_1' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'contests_festivals_what_2' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'contests_festivals_when_2' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'contests_festivals_what_3' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'contests_festivals_when_3' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'contests_festivals_what_4' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'contests_festivals_when_4' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'current_solo_repertoire' => array(
                    'type' => 'comment',
                    'text' => 'Current solo repertoire:',
                ),
                'current_solo_composer_1' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'current_solo_title_1' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'current_solo_composer_2' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'current_solo_title_2' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'current_solo_composer_3' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'current_solo_title_3' => array(
                    'display_name' => ' ',
                    'type' => 'text',
                ),
                'student_proficiency' => array(
                    'type' => 'radio_inline_no_sort',
                    'options' => array('Beginning' => 'beg', 'Intermediate' => 'int', 'Advanced' => 'adv', 'Comfortable at 1st Chair' => '1st'),
                    'display_name' => 'In my judgement, the level of this student\'s proficiency on his/her instrument:',
                ),
                'rank' => array(
                        'type' => 'select_no_sort',
                        'add_null_value_to_top' => true,
                        'comments' => '<br>Compared with ALL your applicants ho play THIS instrument, this student ranks:',
                        'options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4,
                            5 => 5, 6 => 6, 7 => 7,
                        ),
                ),
                'needs_housing' => array(
                    'type' => 'radio_inline_no_sort',
                    'options' => array('Yes' => 'Y', 'No' => 'N'),
                    'display_name' => 'Will this student need on-campus housing during the Festival?',
                ),
                'comments' => array(
                    'type' => 'textarea',
                )
            );

        var $required = array();

        function on_every_time()
        {
        //    $this->set_value('student_school_name', $this->controller->get('school_name'));
        }

        function pre_show_form()
	{
            //pray($_SESSION);
		echo '<div id="dorianOrchestraForm" class="studentForm">'."\n";
	}

	function post_show_form()
	{
		echo '</div>'."\n";
	}

    function run_error_checks() {


        if($this->has_errors() <> true){

            if (isset($_SESSION['student_count'])) {
                $_SESSION['student_count'] += 1;
            } else {
                $_SESSION['student_count'] = 1;
            }
            $session_string = 'student' . $_SESSION['student_count'];

            //save form fields to array in _SESSION
            foreach ($this->elements as $key => $value) {
                $_SESSION[$session_string][$key] = $this->get_value($key);
            }

            //clear form fields
            foreach ($this->elements as $key => $value) {
                $this->set_value($key, '');
            }

        }
    }
}
?>