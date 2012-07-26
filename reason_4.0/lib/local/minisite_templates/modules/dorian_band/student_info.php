<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the dorian band nomination form
//    which collects Student Info
//
////////////////////////////////////////////////////////////////////////////////

class StudentInfoForm extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Dorian Nomination Director Information';
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
                'student_phone_number' => array(
                        'type' => 'text',
                ),
                'student_gender' => array(
                        'type' => 'text',
                ),
                'student_email_address' => array(
                        'type' => 'text',
                ),
                'student_instrument' => array(
                        'type' => 'text',
                ),
                'student_experience' => array(
                        'type' => 'text',
                ),
                'student_year_in_school' => array(
                        'type' => 'text',
                ),
                'student_graduation_year' => array(
                        'type' => 'text',
                ),
                'student_position' => array(
                        'type' => 'text',
                ),
                'student_number_in_section' => array(
                        'type' => 'text',
                ),
                'student_desired_participation' => array(
                        'type' => 'text',
                ),
            );

        var $required = array('director_first_name','director_last_name', 'director_email',
                'school_name', 'school_phone', 'school_street_address', 'school_city',
                'school_state', 'school_zip');

        function pre_show_form()
	{
		echo '<div id="dorianBandForm" class="directorForm">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
        function post_error_check_actions()
        {
            $stud_count = $_SESSION['student_count'];
            if (isset($stud_count)){
                $stud_count = 1;
            }else{
                $stud_count += 1;
                $_SESSION['student_count'] = $stud_count;
            }
            $session_string = 'student'.$stud_count;

            echo($session_string);

            foreach($this->elements as $key)
            {
                $_SESSION[$session_string][$key] = $this->get_value($key);
            }

            
            pray($_SESSION[$session_string]);
        }
        
}
?>