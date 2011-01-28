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
                'period_one_header' => array(
                        'type' => 'comment',
                        'text' => '<h4>Period 1 - 8:00-9:15</h4>',
                ),
                'period_one' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'options' => array(
                            '1'=>'Orchestra (Strings - periods 4 & 5 also)',
                            '2'=>'Concert Band (with period 5)',
                            '3'=>'Dance 1',
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
                            '1'=>'Choir (with period 6)',
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
                            '1'=>'Composition',
                            '2'=>'Elements of Music',
                            '3'=>'Girls Vocal Ensemble',
                            '4'=>'Dance 2',
                            '5'=>'Jazz Band Blue (or Jazz Class)',
                            '6'=>'Art 1 (Mask Making)',
                            '7'=>'Art 2 (Process of Design)',
                            '8'=>'Art 3 (Mixed Media)',
                            '9'=>'Percussuion Ensemble A',
                            '10'=>'Keyboard Workshop A',
                            '11'=>'Vocal Performance A',
                            '12'=>'Guitar Workshop',
                            '13'=>'Woodwind Choir',
                            '14'=>'Harp Workshop',
                            '15'=>'Multimedia Computing (Computer Graphics)',
                    ),
                    'display_name' => '(first&nbsp;choice)',
                ),
                'period_three_second' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'options' => array(
                            '1'=>'Composition',
                            '2'=>'Elements of Music',
                            '3'=>'Girls Vocal Ensemble',
                            '4'=>'Dance 2',
                            '5'=>'Jazz Band Blue (or Jazz Class)',
                            '6'=>'Art 1 (Mask Making)',
                            '7'=>'Art 2 (Process of Design)',
                            '8'=>'Art 3 (Mixed Media)',
                            '9'=>'Percussuion Ensemble A',
                            '10'=>'Keyboard Workshop A',
                            '11'=>'Vocal Performance A',
                            '12'=>'Guitar Workshop',
                            '13'=>'Woodwind Choir',
                            '14'=>'Harp Workshop',
                            '15'=>'Multimedia Computing (Computer Graphics)',
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
                            '1'=>'Orchestra (1:00-2:30; Strings-periods 1 & 5 also)',
                            '2'=>'Boys Vocal Ensemble',
                            '3'=>'Flute Choir',
                            '4'=>'Jazz Band White (or Jazz Class)',
                            '5'=>'Art 4 (Mask Making)',
                            '6'=>'Art 5 (Process of Design)',
                            '7'=>'Art 6 (Mixed Media)',
                            '8'=>'Percussuion Ensemble B',
                            '9'=>'Keyboard Workshop B',
                            '10'=>'Vocal Performance B',
                            '11'=>'Brass Choir',
                            '12'=>'Enjoyment of Music',
                            '13'=>'Electronic Music',
                    ),
                ),
                'period_four_second' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'display_name' => '(second&nbsp;choice)',
                    'options' => array(
                            '1'=>'Orchestra (1:00-2:30; Strings-periods 1 & 5 also)',
                            '2'=>'Boys Vocal Ensemble',
                            '3'=>'Flute Choir',
                            '4'=>'Jazz Band White (or Jazz Class)',
                            '5'=>'Art 4 (Mask Making)',
                            '6'=>'Art 5 (Process of Design)',
                            '7'=>'Art 6 (Mixed Media)',
                            '8'=>'Percussuion Ensemble B',
                            '9'=>'Keyboard Workshop B',
                            '10'=>'Vocal Performance B',
                            '11'=>'Brass Choir',
                            '12'=>'Enjoyment of Music',
                            '13'=>'Electronic Music',
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
                            '1'=>'Orchestra String Sectionals (with periods 1 & 4)',
                            '2'=>'Concert Band (with period 1)',
                            '3'=>'Dance 3',
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
                            '1'=>'Choir (with period 2)',
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
		echo '<div id="transcriptRequestForm" class="pageOne">'."\n";
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
