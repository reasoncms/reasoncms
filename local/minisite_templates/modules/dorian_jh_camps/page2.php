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
                'period_one_header' => array(
                        'type' => 'comment',
                        'text' => '<h3>Period 1</h3>',
                ),
                'period_one' => array(
                    'type' => 'radio_inline',
                    'options' => array(
                            '1'=>'Orchestra (Strings - periods 4 & 5 also)',
                            '2'=>'Concert Band (with period 5)'),
                            '3'=>'Dance 1',
                ),
                'period_two' => array(
                    'type' => 'radio_inline',
                    'options' => array(
                            '1'=>'Choir (with period 6)',
                    ),
                ),
                'period_three' => array(
                    'type' => 'radio_inline',
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
                ),
                'period_four' => array(
                    'type' => 'radio_inline',
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
                'period_five' => array(
                    'type' => 'radio_inline',
                    'options' => array(
                            '1'=>'Orchestra String Sectionals (with periods 1 & 4)',
                            '2'=>'Concert Band (with period 1)',
                            '3'=>'Dance 3',
                    ),
                ),
                'period_six' => array(
                    'type' => 'radio_inline',
                    'options' => array(
                            '1'=>'Choir (with period 2)',
                    ),
                ),
	);

	var $required = array();

	var $display_name = 'Dorian Junior High Camp Registration';
	var $error_header_text = 'Please check your form.';



	// style up the form and add comments et al
	function on_every_time()
	{
             $username = reason_check_authentication(); // this will force login

            if ($username) {
                    echo "true";
                    $this->show_form = true;
            } else {
                    echo "False";
                    echo '<div id ="transcriptLogin">'."\n";
                    echo 'Access to this for is limited...lalala'."\n";
                    echo '</div>'."\n";

                    reason_require_authentication('form_login_msg');
            }
            echo $username;
            $group = id_of('transcripts_group');
            $has_access = (reason_user_is_in_group($username, $group));

	}

	function pre_show_form()
	{
		echo '<div id="transcriptRequestForm" class="pageOne">'."\n";
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}

	function needs_payment()
	{
	  	$amount = 0;
	  	$official_number = $this->get_value('official');

		if (isset($official_number))
	  	{
			$amount = $amount + ($official_number * 5);
		}
		$this->set_value('amount', $amount);

		if ($amount == 0)
		{
			return 'TranscriptConfirmation';
		}else{
			return 'TranscriptPageTwoForm';
		}
	}
        function  run_error_checks()
        {
            parent::run_error_checks();

            if (!preg_match('/^\d+$/', $this->get_value(official)))
            {
                $this-> set_error($this->official, "Please enter a whole number");
            }
        }
}
?>
