<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Lucas Welper
//    2010-11-03
//
//    Work on the first page of the dorian orchestra nomination form
//    which collects Director Info
//
////////////////////////////////////////////////////////////////////////////////

class DirectorInfoForm extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Dorian Orchestra Festival Nomination Director Information';
	var $elements = array(
                'director_information_header2' => array(
                        'type' => 'comment',
                        'text' => '<h4>Nomination Form</h4>',
                ),
		'director_information_header' => array(
			'type' => 'comment',
			'text' => '<h3>Director Information</h3>',
		),
		'director_first_name' => array(
			'type' => 'text',
                ),
                'director_last_name' => array(
                        'type' => 'text',
                ),
                'director_email' => array(
                        'type' => 'text',
                        'display_name' => 'Your E-mail',
                ),
                'school_name' => array(
                        'type' => 'text',
                ),
                'school_phone' => array(
                        'type' => 'text',
                ),
                'school_street_address' => array(
                        'type' => 'text',
                        'comments' => '<br>no PO boxes, please'
                ),
                'school_city' => array(
                        'type' => 'text',
                ),
                'school_state' => 'state',
                'school_zip' => array(
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
}
?>