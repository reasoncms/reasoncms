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

class DorianSHCampTwoForm extends FormStep
{
	var $_log_errors = true;
	var $error;

        var $band_instruments_array = array(
                'Piccolo'               => 'Piccolo',
                'Flute'                 => 'Flute',
                'Oboe'                  => 'Oboe',
                'Bassoon'               => 'Bassoon',
                'Clarinet'              => 'Clarinet',
                'Bass Clarinet'         => 'Bass Clarinet',
                'Alto Sax'              => 'Alto Sax',
                'Tenor Sax'             => 'Tenor Sax',
                'Baritone Sax'          => 'Baritone Sax',
                'Horn'                  => 'Horn',
                'Trumpet'               => 'Trumpet',
                'Trombone'              => 'Trombone',
                'Euphonium/Baritone BC' => 'Euphonium/Baritone BC',
                'Euphonium/Baritone TC' => 'Euphonium/Baritone TC',
                'Tuba'                  => 'Tuba',
                'Percussion'            => 'Percussion',
        );
        var $orchestra_instruments_array = array(
                'Violin'    => 'Violin',
                'Viola'     => 'Viola',
                'Cello'     => 'Cello',
                'Bass'      => 'Bass',
                'Harp'      => 'Harp',
                'Flute'     => 'Flute',
                'Oboe'      => 'Oboe',
                'Bassoon'   => 'Bassoon',
                'Clarinet'  => 'Clarinet',
                'Horn'      => 'Horn',
                'Trumpet'   => 'Trumpet',
                'Trombone'  => 'Trombone',
                'Tuba'      => 'Tuba',
                'Percussion'=> 'Percussion',
        );
        var $woodwind_choir_instruments_array = array(
                'Flute'         => 'Flute',
                'Oboe'          => 'Oboe',
                'Bassoon'       => 'Bassoon',
                'Clarinet'      => 'Clarinet',
                'Bass Clarinet' => 'Bass Clarinet',
                'Alto Sax'      => 'Alto Sax',
                'Tenor Sax'     => 'Tenor Sax',
                'Baritone Sax'  => 'Baritone Sax',
        );
        var $brass_choir_instruments_array = array(
                'Trumpet'               => 'Trumpet',
                'Horn'                  => 'Horn',
                'Trombone'              => 'Trombone',
                'Euphonium/Baritone BC' => 'Euphonium/Baritone BC',
                'Euphonium TC'          => 'Euphonium TC',
                'Tuba'                  => 'Tuba',
        );
        var $jazz_band_instruments_array = array(
                'Alto Sax'      => 'Alto Sax',
                'Tenor Sax'     => 'Tenor Sax',
                'Baritone Sax'  => 'Baritone Sax',
                'Trumpet'       => 'Trumpet',
                'Trombone'      => 'Trombone',
                'Guitar'        => 'Guitar',
                'Bass'          => 'Bass',
                'Percussion'    => 'Percussion',
                'Piano'         => 'Piano',
        );
        var $lesson_instruments_array = array(
                'Voice'                 => 'Voice',
                'Dance'                 => 'Dance',
                'Flute'                 => 'Flute',
                'Oboe'                  => 'Oboe',
                'Bassoon'               => 'Bassoon',
                'Clarinet'              => 'Clarinet',
                'A. Sax'                => 'A. Sax',
                'T. Sax'                => 'T. Sax',
                'B. Sax'                => 'B. Sax',
                'Horn'                  => 'Horn',
                'Trumpet'               => 'Trumpet',
                'Trombone'              => 'Trombone',
                'Euphonium/Baritone'    => 'Euphonium/Baritone',
                'Tuba'                  => 'Tuba',
                'Percussion'            => 'Percussion',
                'Violin'                => 'Violin',
                'Viola'                 => 'Viola',
                'Cello'                 => 'Cello',
                'String Bass'           => 'String Bass',
                'Harp'                  => 'Harp',
                'Piano'                 => 'Piano',
                'Organ'                 => 'Organ',
                'Harpsichord'           => 'Harpsichord',
                'Classical guitar'      => 'Classical guitar',
                'Bass Guitar'           => 'Bass Guitar (if a teacher is available)',
        );

	var $elements = array(
                'participation_header' => array(
			'type' => 'comment',
			'text' => '<h3>Participation</h3>',
		),
                'participation_header_2' => array(
                        'type' => 'comment',
                        'text' => 'Check which you will be participating in:'
                ),
                'choir_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Choir',
		),
		'band_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Band',
		),
		'band_instrument' => array(
                        'display_name' => '&nbsp;',
                        'comments'=>'<div class="smallText comment">Instrument</div>',
                        'type' => 'select_no_sort',
                        'add_null_value_to_top' => true,
                        'options' => array(),
                ),
                'orchestra_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Orchestra',
		),
		'orchestra_instrument' => array(
                        'display_name' => '&nbsp;',
                        'comments'=>'<div class="smallText comment">Instrument</div>',
                        'type' => 'select_no_sort',
                        'add_null_value_to_top' => true,
                        'options' => array(),
                ),
                'jazz_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Jazz Band',
		),
		'jazz_instrument' => array(
                        'display_name' => '&nbsp;',
                        'comments'=>'<div class="smallText comment">Instrument</div>',
                        'type' => 'select_no_sort',
                        'add_null_value_to_top' => true,
                        'options' => array(),
                ),
                'wind_choir_participant' => array(
			'type' => 'checkboxfirst',
			'display_name' => 'Woodwind Choir',
		),
		'wind_choir_instrument' => array(
                        'display_name' => '&nbsp;',
                        'comments'=>'<div class="smallText comment">Instrument</div>',
                        'type' => 'select_no_sort',
                        'add_null_value_to_top' => true,
                        'options' => array(),
                ),
// removed due to lack of participants, per jim buzza
//                'brass_choir_participant' => array(
//			'type' => 'checkboxfirst',
//			'display_name' => 'Will you play in brass choir?',
//		),
//		'brass_choir_instrument' => array(
//                    'type' => 'text',
//                    'display_name' => '&nbsp;',
//                    'comments'=>'<div class="smallText comment">Instrument</div>',
//                ),
                'workshops_header' => array(
                    'type' => 'comment',
                    'text' => '<h3>Workshops</h3>',
                ),
                'workshops' => array(
                    'type' => 'radio_no_sort',
                    'display_name' => '&nbsp;',
                    'options' => array(
                        'music_theatre' => 'Music Theatre Workshop**',
                        'acting_company' => 'Acting Company Workshop',
                        'keyboard_workshop' => 'Keyboard Workshop**',
                        'harp_workshop' => 'Harp Workshop',
                        'none' => 'none',
                    ),
                    'comments' => '**Music Theater and Keyboard workshops require audition recordings<br>
                        postmarked by 5/15/13 or attach an mp3 to an e-mail to
                        <a href="mailto:dorian@luther.edu?Subject=Dorian%20Audition">dorian@luther.edu</a>',
                ),
                'private_lessons_header' => array(
                     'type' => 'comment',
                     'text' => '<h3>Sets of Private Lessons</h3>- Each pair of lessons costs $37<br />- One set equals two half-hour lessons',
                ),
                 'private_lessons' => array(
                     'type' => 'radio_inline_no_sort',
                     'display_name' => '&nbsp;',
                     'options' => array(0 => 'None', 1 => 1 ,2 => 2),
//                     'comments' => 'One set equals two half-hour lessons'
                ),
                'lesson_instrument_1' => array(
                    'type' => 'select_no_sort',
                    'display_name' => '&nbsp;',
                    'comments' => '<br />Instrument',
                    'add_null_value_to_top' => true,
                    'options' => array(),
                ),
                'lesson_instrument_2' => array(
                    'type' => 'select_no_sort',
                    'display_name' => '&nbsp;',
                    'comments' => '<br />Instrument&nbsp;2',
                    'add_null_value_to_top' => true,
                    'options' => array(),
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
							'music_theatre' => 'Music Theatre Workshop (requires periods 1, 2, 4, 5, & 6)',
							'acting_company' => 'Acting Company Workshop (requires periods 1, 3, 4, & 5)',
							'keyboard_workshop' => 'Keyboard Workshop (with periods 4 & 5)',
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
                            'music_theatre' => 'Music Theatre Workshop (requires periods 1, 2, 4, 5, & 6)',
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
							'acting_company' => 'Acting Company Workshop (requires periods 1, 3, 4, & 5)',
                            'chamber_choir' => 'Chamber Choir',
                            'woodwind_choir'=>'Woodwind Choir',
                            'jazz_band'=>'Jazz Band',
                            'percussion_ensemble' => 'Percussion Ensemble',
                            'music_theory' => 'Music Theory',
                            'conducting' => 'Conducting',
                            'dance_2'=>'Dance 2 (10:50 - 12:10)',
                            'guitar_workshop' => 'Guitar Workshop (with period 4)',
                            'composition'=>'Composition',
                            'art_1'=>'Art 1 (Mask Making)',
                            'art_2'=>'Art 2 (Clay Sculpture)',
                            'art_3'=>'Art 3 (Mixed Media)',
                            'art_4'=>'Art 4 (Pop!)',
                            'movie_making'=>'Making a Movie',
                            'harp_workshop' => 'Harp Workshop (with period 5)',
                            'multimedia_computing'=>'Multimedia Computing (Computer Graphics)',
                            'poetry_writing'=>'Words That Sing: Poetry Writing Workshop',
                    ),
                    'display_name' => '(first&nbsp;choice)',
                ),
                'period_three_second' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'options' => array(
							'acting_company' => 'Acting Company Workshop (requires periods 1, 3, 4, & 5)',
                            'chamber_choir' => 'Chamber Choir',
                            'woodwind_choir'=>'Woodwind Choir',
                            'jazz_band'=>'Jazz Band',
                            'percussion_ensemble' => 'Percussion Ensemble',
                            'music_theory' => 'Music Theory',
                            'conducting' => 'Conducting',
                            'dance_2'=>'Dance 2 (10:50 - 12:10)',
                            'guitar_workshop' => 'Guitar Workshop (with period 4)',
                            'composition'=>'Composition',
                            'art_1'=>'Art 1 (Mask Making)',
                            'art_2'=>'Art 2 (Clay Sculpture)',
                            'art_3'=>'Art 3 (Mixed Media)',
                            'art_4'=>'Art 4 (Pop!)',
                            'movie_making'=>'Making a Movie',
                            'harp_workshop' => 'Harp Workshop (with period 5)',
                            'multimedia_computing'=>'Multimedia Computing (Computer Graphics)',
                            'poetry_writing'=>'Words That Sing: Poetry Writing Workshop',
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
                            'music_theatre' => 'Music Theatre Workshop (requires periods 1, 2, 4, 5, & 6)',
							'acting_company' => 'Acting Company Workshop (requires periods 1, 3, 4, & 5)',
                            'keyboard_workshop' => 'Keyboard Workshop (with periods 1 & 5)',
                            'intermediate_jazz_improv' => 'Intermediate Jazz Improvisation',
                            'orchestra'=>'Orchestra (Winds & Percussion with period 1; Strings with periods 1 & 5)',
                            'vocal_performance'=>'Vocal Performance',
                            'guitar_workshop' => 'Guitar Workshop (1:00-2:30; with period 3)',
                            'electronic_music'=>'Electronic Music',
                            'art_5'=>'Art 5 (Mask Making)',
                            'art_6'=>'Art 6 (Clay Sculpture)',
                            'art_7'=>'Art 7 (Mixed Media)',
                            'art_8'=>'Art 8 (Scenic Painting Techniques)',
                            'improv' => 'Improvisation',
                            'musical_styles' => 'Musical Styles',
                            'flute_choir'=>'Flute Choir',
                    ),
                ),
                'period_four_second' => array(
                    'type' => 'select_no_sort',
                    'add_null_value_to_top' => true,
                    'display_name' => '(second&nbsp;choice)',
                    'options' => array(
                            'music_theatre' => 'Music Theatre Workshop (requires periods 1, 2, 4, 5, & 6)',
							'acting_company' => 'Acting Company Workshop (requires periods 1, 3, 4, & 5)',
                            'keyboard_workshop' => 'Keyboard Workshop (with periods 1 & 5)',
                            'intermediate_jazz_improv' => 'Intermediate Jazz Improvisation',
                            'orchestra'=>'Orchestra (Winds & Percussion with period 1; Strings with periods 1 & 5)',
                            'vocal_performance'=>'Vocal Performance',
                            'guitar_workshop' => 'Guitar Workshop (1:00-2:30; with period 3)',
                            'electronic_music'=>'Electronic Music',
                            'art_5'=>'Art 5 (Mask Making)',
                            'art_6'=>'Art 6 (Clay Sculpture)',
                            'art_7'=>'Art 7 (Mixed Media)',
                            'art_8'=>'Art 8 (Scenic Painting Techniques)',
                            'improv' => 'Improvisation',
                            'musical_styles' => 'Musical Styles',
                            'flute_choir'=>'Flute Choir',
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
                            'music_theatre' => 'Music Theatre Workshop (requires periods 1, 2, 4, 5, & 6)',
							'acting_company' => 'Acting Company Workshop (requires periods 1, 3, 4, & 5)',
                            'keyboard_workshop' => 'Keyboard Workshop (with periods 1 & 4)',
                            'orchestra'=>'Orchestra String Sectionals (with periods 1 & 4)',
                            'concert_band'=>'Concert Band (with period 1)',
                            'dance_3'=>'Dance 3',
                            'harp_workshop' => 'Harp Workshop (with period 3)',
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
                            'music_theatre' => 'Music Theatre Workshop (requires periods 1, 2, 4, 5, & 6)',
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
                $this->change_element_type('band_instrument', 'select_no_sort', array('options' => $this->band_instruments_array));
                $this->change_element_type('orchestra_instrument', 'select_no_sort', array('options' => $this->orchestra_instruments_array));
                $this->change_element_type('jazz_instrument', 'select_no_sort', array('options' => $this->jazz_band_instruments_array));
                $this->change_element_type('wind_choir_instrument', 'select_no_sort', array('options' => $this->woodwind_choir_instruments_array));
                $this->change_element_type('brass_choir_instrument', 'select_no_sort', array('options' => $this->brass_choir_instruments_array));
                $this->change_element_type('lesson_instrument_1', 'select_no_sort', array('options' => $this->lesson_instruments_array));
                $this->change_element_type('lesson_instrument_2', 'select_no_sort', array('options' => $this->lesson_instruments_array));
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
				 //check acting company requirements
                if (($this->get_value('period_one') == 'acting_company') 
						|| ($this->get_value('period_three_first') == 'acting_company') 
						|| ($this->get_value('period_four_first') == 'acting_company') 
						|| ($this->get_value('period_five') == 'acting_company')){
                    if (($this->get_value('period_one') != 'acting_company') 
							|| ($this->get_value('period_three_first') != 'acting_company') 
							|| ($this->get_value('period_four_first') != 'acting_company') 
							|| ($this->get_value('period_five') != 'acting_company')){
                        $this->set_error('period_one', 'Acting Company requires periods 1, 3, 4 and 5.');
                    }
                }
                //check orchestra requirements
                if (($this->get_value('period_one') == 'orchestra') || ($this->get_value('period_four_first') == 'orchestra') || ($this->get_value('period_five') == 'orchestra')){
                    if (($this->get_value('period_one') != 'orchestra') || ($this->get_value('period_four_first') != 'orchestra') || ($this->get_value('period_five') != 'orchestra')){
                        $this->set_error('period_one', 'Orchestra requires periods 1, 4 and 5.');
                    }
                }
                //check concert band requirements
                if(($this->get_value('period_one') == 'concert_band') || ($this->get_value('period_five') == 'concert_band')){
                    if(($this->get_value('period_one') != 'concert_band') || ($this->get_value('period_five') != 'concert_band')){
                        $this->set_error('period_one', 'Conert Band requires periods 1 and 5.');
                    }
                }
                //check choir requirements
                if(($this->get_value('period_two') == 'choir') || ($this->get_value('period_six') == 'choir')){
                    if(($this->get_value('period_two') != 'choir') || ($this->get_value('period_six') != 'choir' )){
                        $this->set_error('period_two', 'Choir requires periods 2 and 6.');
                    }
                }
                //check music theatre requirements
                if (($this->get_value('period_one') == 'music_theatre')
                        || ($this->get_value('period_two') == 'music_theatre')
                        || ($this->get_value('period_four_first') == 'music_theatre')
                        || ($this->get_value('period_five') == 'music_theatre')
                        || ($this->get_value('period_six') == 'music_theatre') ){
                    if (($this->get_value('period_one') != 'music_theatre')
                            || ($this->get_value('period_two') != 'music_theatre')
                            || ($this->get_value('period_four_first') != 'music_theatre')
                            || ($this->get_value('period_five') != 'music_theatre')
                            || ($this->get_value('period_six') != 'music_theatre')){
                        $this->set_error('period_one', 'Music Theatre requires periods 1, 2, 4, 5, & 6.');
                    }
                } 
                //check keyboard workshop requirements
                if (($this->get_value('period_one') == 'keyboard_workshop') || ($this->get_value('period_four_first') == 'keyboard_workshop') || ($this->get_value('period_five') == 'keyboard_workshop')){
                    if (($this->get_value('period_one') != 'keyboard_workshop') || ($this->get_value('period_four_first') != 'keyboard_workshop') || ($this->get_value('period_five') != 'keyboard_workshop')){
                        $this->set_error('period_one', 'Keyboard Workshop requires periods 1, 4 and 5.');
                    }
                }
                //check mixed media requirements
                if(($this->get_value('period_one') == 'mixed_media') || ($this->get_value('period_five') == 'mixed_media')){
                    if(($this->get_value('period_one') != 'mixed_media') || ($this->get_value('period_five') != 'mixed_media')){
                        $this->set_error('period_one', 'Mixed Media Art/Dance Workshop requires periods 1 and 5.');
                    }
                }
                //check guitar workshop requirements
                if(($this->get_value('period_three_first') == 'guitar_workshop') || ($this->get_value('period_three_second') == 'guitar_workshop')
                        ||($this->get_value('period_four_first') == 'guitar_workshop') || ($this->get_value('period_four_second') == 'guitar_workshop')){
                    if(($this->get_value('period_three_first') != 'guitar_workshop') || ($this->get_value('period_four_first') != 'guitar_workshop')){
                        $this->set_error('period_three_first', 'Guitar Workshop requires periods 3 and 4.');
                    }
                }
                //check harp workshop requirements
                if(($this->get_value('period_three_first') == 'harp_workshop') || ($this->get_value('period_three_second') == 'harp_workshop')
                        ||($this->get_value('period_five') == 'harp_workshop')){
                    if(($this->get_value('period_three_first') != 'harp_workshop') || ($this->get_value('period_four_first') != 'harp_workshop')){
                        $this->set_error('period_three_first', 'Harp Workshop requires periods 3 and 5.');
                    }
                }
        }
}
?>