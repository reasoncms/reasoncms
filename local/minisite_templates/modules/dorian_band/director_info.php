<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the dorian band nomination form
//    which collects Director Info
//
////////////////////////////////////////////////////////////////////////////////

class DirectorInfoForm extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Dorian Nomination Director Information';
	var $elements = array(
		'director_information_header' => array(
			'type' => 'comment',
			'text' => '<h3>Director Information</h3>',
		),
		'director_first_name' => array(
			'type' => 'text',
                ),
                'director_last_name' => array(
                        'type' => 'text',
			'size' => 15,
                ),
                'director_email' => array(
                        'type' => 'text',
                        'size' => 15,
                        'display_name' => 'Your email',
                ),
                'school_name' => array(
                        'type' => 'text',
                        'size' => 15,
                ),
                'school_phone' => array(
                        'type' => 'text',
                        'size' => 15,
                ),
                'school_street_address' => array(
                        'type' => 'text',
                        'size' => 15,
                        'comment' => 'no PO boxes, please'
                ),
                'school_city' => array(
                        'type' => 'text',
                        'size' => 15,
                ),
                'school_state' => 'state',
                'school_zip' => array(
                        'type' => 'text',
                        'size' => 15,
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