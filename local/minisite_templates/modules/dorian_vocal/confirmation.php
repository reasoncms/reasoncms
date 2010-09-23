<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith and Lucas Welper
//    2010-09-20
//
//    Work on the confirmation page of the dorian vocal nomination form
//
////////////////////////////////////////////////////////////////////////////////

class ConfirmationForm extends FormStep
{
	var $_log_errors = true;
	var $error;
    var $display_name = 'Dorian Vocal Festival Nomination Director Information';
	var $elements = array(
		'xdirectorinformation_header' => array(
			'type' => 'comment',
			'text' => '<h3>Director Information</h3>',
		),
		'xdirectorfirst_name' => array(
			'type' => 'text',
                ),
                'xdirectorlast_name' => array(
                        'type' => 'text',
                ),
                'xdirectoremail' => array(
                        'type' => 'text',
                        'display_name' => 'Your E-mail',
                ),
                'xschoolname' => array(
                        'type' => 'text',
                ),
                'xschoolphone' => array(
                        'type' => 'text',
                ),
                'xschoolstreet_address' => array(
                        'type' => 'text',
                        'comments' => '<br>no PO boxes, please'
                ),
                'xschoolcity' => array(
                        'type' => 'text',
                ),
                'xschoolstate' => 'state',
                'xschoolzip' => array(
                        'type' => 'text',
                ),
            );

        var $required = array('xdirectorfirst_name','xdirectorlast_name', 'xdirectoremail',
                'xschoolname', 'xschoolphone', 'xschoolstreet_address', 'xschoolcity',
                'xschoolstate', 'xschoolzip');

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