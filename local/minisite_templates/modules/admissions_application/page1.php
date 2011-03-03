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
 *  First page of the application
 *
 *  Enrollment Term
 *  Student Type
 * 
 */
class ApplicationPageOne extends FormStep
{
	var $_log_errors = true;
	var $error;
	
	var $elements = array(
            'header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Enrollment Information</h3>',
            ),
            'student_type' => array(
                    'type' => 'radio_no_sort',
                    'display_name' => 'What type of student will you be enrolling as?',
                    'options' => array('FR'=>'First Year', 'TR'=>'Transfer'),
            ),
            'enrollment_term' => array(
                    'display_name' => 'When do you wish to enroll at Luther?',
                    'type' => 'text',
            ),
            'submitter_ip'=>'hidden',
	);

        var $required = array('student_type', 'enrollment_term');
	var $display_name = 'Enrollment Info';
	var $error_header_text = 'Please check your form.';

	// style up the form and add comments et al
	function on_every_time()
	{
            $this->set_value('submitter_ip', $_SERVER[ 'REMOTE_ADDR' ]);

            $date = getdate();
            
            if ($date['mon'] <= 5){
                $year = $date['year'];
            } else {
                $year = $date['year'] +1;
            }

            $this->change_element_type('enrollment_term', 'radio_no_sort', array(
                'options' => array($year.'FA' => 'Fall ' . $year, $year.'SP' => 'Spring '.$year),
                'comments' => '<em>Show some year logic here</em>'));
	}

	function pre_show_form()
	{
            echo '<div id="admissionsApp" class="pageOne">'."\n";
	}
	function post_show_form()
	{
            echo '</div>'."\n";
	}
}
?>
