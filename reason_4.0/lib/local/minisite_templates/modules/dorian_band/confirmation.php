<?php
////////////////////////////////////////////////////////////////////////////////
//
//    fix me
//
////////////////////////////////////////////////////////////////////////////////

class ConfirmationForm extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Dorian Band Festival Nomination Student Information';
	var $elements = array(
		'blah' => array(
			'type' => 'comment',
			'text' => '<h3>Blah Comment</h3>',
		),
            );

        var $required = array('blah');

        function on_every_time()
        {
        }

        function pre_show_form()
	{
		echo '<div id="dorianBandForm" class="studentForm">'."\n";
	}

	function post_show_form()
	{
		echo '</div>'."\n";
	}

        function post_error_check_actions()
        {
        }
}
?>