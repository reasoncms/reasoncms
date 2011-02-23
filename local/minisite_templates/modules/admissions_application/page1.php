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
                    'display_name' => '&nbsp;',
                    'options' => array('FR'=>'First Year', 'TR'=>'Transfer'),
                    'default' => 'FR',
            ),
            'enrollment_term' => array(
                    'display_name' => 'When do you plan to enroll?',
                    'type' => 'text',
            ),
            'submitter_ip'=>'hidden',
	);

        var $required = array('student_type', 'enrollment_type');
	var $display_name = 'Enrollment Info';
	var $error_header_text = 'Please check your form.';

	// style up the form and add comments et al
	function on_every_time()
	{
            $this->set_value('submitter_ip', $_SERVER[ 'REMOTE_ADDR' ]);

            $date = getdate();
            pray($date);
            $this->change_element_type('enrollment_term', 'radio_no_sort', array(
                'options' => array('Fall' => 'Fall', 'Spring' => 'Spring')));
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
