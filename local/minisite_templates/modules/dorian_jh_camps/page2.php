<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    Lucas Welper
//    2011-01-26
//
//    Work on the second page of the dorian camp form
//
////////////////////////////////////////////////////////////////////////////////

class DorianJHCampsTwoForm extends FormStep
{
	var $_log_errors = true;
	var $error;

	var $elements = array(
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
                'wind_choir_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Will you play in woodwind choir?',
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
                     'text' => '<h3>Sets of Private Lessons</h3>',
                ),
                 'private_lessons' => array(
                     'type' => 'radio_inline_no_sort',
                     'display_name' => '&nbsp;',
                     'options' => array(0 => 'None', 1 => 1 ,2 => 2),
                     'comments' => 'One set equals two half-hour lessons'
                ),
                'lesson_instrument_1' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments' => 'Instrument'
                ),
                'lesson_instrument_2' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments' => 'Instrument 2'
                ),
                'period_header' => array(
                    'type' => 'comment',
                    'text' => '<br /><br /><h4>Registration guidelines for the scheduling choices below</h4>',
                ),
                'period_header_1' => array(
                    'type' => 'comment',
                    'text' => '1) You may choose to leave one period free each day.  You may not leave more than two free.',
                ),
                'period_header_2' => array(
                    'type' => 'comment',
                    'text' => '2) Ensembles and workshops meet more than one period per day (as noted in each selection);  register for each session of these multipart activities.',
                ),
                'period_one_header' => array(
                        'type' => 'comment',
                        'text' => '<h4>Period 1 - 8:00-9:15</h4>',
                ),
                'period_one' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'options' => array(
                            'orchestra'=>'Orchestra (Strings - periods 4 & 5 also)',
                            'concert_band'=>'Concert Band (with period 5)',
                            'dance_1'=>'Dance 1',
                     ),
                    'display_name' => '&nbsp;',
                ),
                'period_two_header' => array(
                        'type' => 'comment',
                        'text' => '<h4>Period 2 - 9:30-10:45</h4>',
                ),
                'period_two' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'display_name' => '&nbsp;',
                    'options' => array(
                            'choir'=>'Choir (with period 6)',
                    ),
                ),
                'period_three_header' => array(
                        'type' => 'comment',
                        'text' => '<h4>Period 3 - 11:00-noon</h4>',
                ),
                'period_three_first' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'options' => array(
                            'composition'=>'Composition',
                            'elements_of_music'=>'Elements of Music',
                            'girls_vocal_ensemble'=>'Girls Vocal Ensemble',
                            'dance_2'=>'Dance 2',
                            'jazz_band_blue'=>'Jazz Band Blue (or Jazz Class)',
                            'art_1'=>'Art 1 (Mask Making)',
                            'art_2'=>'Art 2 (Process of Design)',
                            'art_3'=>'Art 3 (Mixed Media)',
                            'percussion_ensemble_A'=>'Percussuion Ensemble A',
                            'keyboard_workshop_A'=>'Keyboard Workshop A',
                            'vocal_performance_A'=>'Vocal Performance A',
                            'guitar_workshop'=>'Guitar Workshop',
                            'woodwind_choir'=>'Woodwind Choir',
                            'harp_workshop'=>'Harp Workshop',
                            'multimedia_computing'=>'Multimedia Computing (Computer Graphics)',
                    ),
                    'display_name' => '(first&nbsp;choice)',
                ),
                'period_three_second' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'options' => array(
                            'composition'=>'Composition',
                            'elements_of_music'=>'Elements of Music',
                            'girls_vocal_ensemble'=>'Girls Vocal Ensemble',
                            'dance_2'=>'Dance 2',
                            'jazz_band_blue'=>'Jazz Band Blue (or Jazz Class)',
                            'art_1'=>'Art 1 (Mask Making)',
                            'art_2'=>'Art 2 (Process of Design)',
                            'art_3'=>'Art 3 (Mixed Media)',
                            'percussion_ensemble_A'=>'Percussuion Ensemble A',
                            'keyboard_workshop_A'=>'Keyboard Workshop A',
                            'vocal_performance_A'=>'Vocal Performance A',
                            'guitar_workshop'=>'Guitar Workshop',
                            'woodwind_choir'=>'Woodwind Choir',
                            'harp_workshop'=>'Harp Workshop',
                            'multimedia_computing'=>'Multimedia Computing (Computer Graphics)',
                    ),
                    'display_name' => '(second&nbsp;choice)',
                ),
                'period_four_header' => array(
                        'type' => 'comment',
                        'text' => '<h4>Period 4 - 1:00-2:00</h4>',
                ),
                'period_four_first' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'display_name' => '(first&nbsp;choice)',
                    'options' => array(
                            'orchestra'=>'Orchestra (1:00-2:30; Strings-periods 1 & 5 also)',
                            'boys_vocal_ensemble'=>'Boys Vocal Ensemble',
                            'flute_choir'=>'Flute Choir',
                            'jazz_band_white'=>'Jazz Band White (or Jazz Class)',
                            'art_4'=>'Art 4 (Mask Making)',
                            'art_5'=>'Art 5 (Process of Design)',
                            'art_6'=>'Art 6 (Mixed Media)',
                            'percussion_ensemble_B'=>'Percussuion Ensemble B',
                            'keyboard_workshop_B'=>'Keyboard Workshop B',
                            'vocal_performance_B'=>'Vocal Performance B',
                            'brass_choir'=>'Brass Choir',
                            'enjoyment_of_music'=>'Enjoyment of Music',
                            'electronic_music'=>'Electronic Music',
                    ),
                ),
                'period_four_second' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'display_name' => '(second&nbsp;choice)',
                    'options' => array(
                         // 'orchestra'=>'Orchestra (1:00-2:30; Strings-periods 1 & 5 also)',
                            'boys_vocal_ensemble'=>'Boys Vocal Ensemble',
                            'flute_choir'=>'Flute Choir',
                            'jazz_band_white'=>'Jazz Band White (or Jazz Class)',
                            'art_4'=>'Art 4 (Mask Making)',
                            'art_5'=>'Art 5 (Process of Design)',
                            'art_6'=>'Art 6 (Mixed Media)',
                            'percussion_ensemble_B'=>'Percussuion Ensemble B',
                            'keyboard_workshop_B'=>'Keyboard Workshop B',
                            'vocal_performance_B'=>'Vocal Performance B',
                            'brass_choir'=>'Brass Choir',
                            'enjoyment_of_music'=>'Enjoyment of Music',
                            'electronic_music'=>'Electronic Music',
                    ),
                ),
                'period_five_header' => array(
                        'type' => 'comment',
                        'text' => '<h4>Period 5 - 2:45-4:00</h4>',
                ),
                'period_five' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'display_name' => '&nbsp;',
                    'options' => array(
                            'orchestra'=>'Orchestra String Sectionals (with periods 1 & 4)',
                            'concert_band'=>'Concert Band (with period 1)',
                            'dance_3'=>'Dance 3',
                    ),
                ),
                'period_six_header' => array(
                        'type' => 'comment',
                        'text' => '<h4>Period 6 - 4:15-5:30</h4>',
                ),
                'period_six' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'display_name' => '&nbsp;',
                    'options' => array(
                            'choir'=>'Choir (with period 2)',
                    ),
                ),
	);

	var $required = array();

	var $display_name = 'Participation';
	var $error_header_text = 'Please check your form.';



	// style up the form and add comments et al
	function on_every_time()
	{

	}

	function pre_show_form()
	{
		echo '<div id="campForm" class="pageTwo">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}

        function  run_error_checks()
        {
                //check orchestra requirements
                if (($this->get_value('period_one') == 1) || ($this->get_value('period_four_first') == 1) || ($this->get_value('period_five') == 1)){
                    if (($this->get_value('period_one') <> 1) || ($this->get_value('period_four_first') <> 1) || ($this->get_value('period_five') <> 1)){
                        $this->set_error('period_one', 'Orchestra requires periods 1, 4 and 5.');
                    }
                }
                //check concert band requirements
                if(($this->get_value('period_one') == 2) || ($this->get_value('period_five') == 2)){
                    if(($this->get_value('period_one') <> 2) || ($this->get_value('period_five') <> 2)){
                        $this->set_error('period_one', 'Conert Band requires periods 2 and 5.');
                    }
                }
                //check choir requirements
                if(($this->get_value('period_two') == 1) || ($this->get_value('period_six') == 1)){
                    if(($this->get_value('period_two') <> 1) || ($this->get_value('period_six') <> 1)){
                        $this->set_error('period_two', 'Choir requires periods 2 and 6.');
                    }
                }
        }
}
?>
