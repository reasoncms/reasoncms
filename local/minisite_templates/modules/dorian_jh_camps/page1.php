<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    Lucas Welper
//    2011-01-26
//
//    Work on the first page of the dorian camp form
//
////////////////////////////////////////////////////////////////////////////////

class DorianJHCampsOneForm extends FormStep
{
	var $_log_errors = true;
	var $error;
        var $display_name = 'Camper Information';

	var $elements = array(
		'amount' => 'cloaked',
		'camper_information_header' => array(
			'type' => 'comment',
			'text' => '<h3>Camper Information</h3>',
		),
		'first_name' => array(
			'type' => 'text',
			'size' => 35,
		),
		'last_name' => array(
			'type' => 'text',
			'size'=> 35,
		),
                'gender' => array(
                    'type' => 'radio_inline',
                    'options' => array('F'=>'Female', 'M'=>'Male'),
                ),
		'address' => array(
			'type' => 'text',
			'size' => 35,
		),
		'city' => array(
			'type' => 'text',
			'size' => 35,
		),
		'state_province' => array(
			'type' => 'state_province',
			'display_name' => 'State/Province',
		),
		'zip' => array(
			'type' => 'text',
			'display_name' => 'Zip/Postal Code',
			'size' => 35,
		),
		'home_phone' => array(
			'type' => 'text',
			'size' => 20,
		),
                'e-mail' => array(
			'type' => 'text',
			'size' => 35,
		),
                'school' => 'text',
                'grade' => array(
                    'type' => 'text',
                    'size' => 2,
                    'display_name' => 'Grade completed by June 2011',
                ),
                'roomate_requested' => array(
                    'type' => 'text',
                    'comments' => 'Males, one name, Females, one or two names',
                   // 'display_name' => 'Roomate&nbsp;Requested',
                ),
                'participation_header' => array(
			'type' => 'comment',
			'text' => '<h3>Participation</h3>',
		),
		'band_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Will you play in band?',
		),
		'band_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
                'orchestra_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Will you play in orchestra?',
		),
		'orchestra_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
                'jazz_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Will you play in jazz band?',
		),
		'jazz_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
                'orchestra_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Will you play in band?',
		),
		'orchestra_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
                'wind_choir_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Will you play in wind choir?',
		),
		'wind_choir_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
                 'brass_choir_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Will you play in brass choir?',
		),
		'brass_choir_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
                 'private_lessons_header' => array(
                     'type' => 'comment',
                     'text' => '<h3>Private Lessons</h3>',
                 ),
                 'private_lesson_sets' => array(
                     'type' => 'radio_inline_no_sort',
                     'options' => array(1,2),
                 ),
	);

	//var $required = array('first_name', 'last_name', 'daytime_phone', 'e-mail', 'address', 'city', 'state_province', 'zip', 'delivery');

	var $error_header_text = 'Please check your form.';



	// style up the form and add comments et al
	function pre_show_form()
	{
		echo '<div id="dorianJHCampForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
}
?>
