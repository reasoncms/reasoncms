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
                'participation_header' => array(
			'type' => 'comment',
			'text' => '<h3>Participation</h3>',
		),
		'band_participant' => array(
			'type' => 'checkbox_first',
			'display_name' => 'Will you play in band?',
		),
		'band_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
		),
                'orchestra_participant' => array(
			'type' => 'checkbox_first',
			'display_name' => 'Will you play in orchestra?',
		),
		'orchestra_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
		),
                'jazz_participant' => array(
			'type' => 'checkbox_first',
			'display_name' => 'Will you play in jazz band?',
		),
		'jazz_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
		),
                'orchestra_participant' => array(
			'type' => 'checkbox_first',
			'display_name' => 'Will you play in band?',
		),
		'orchestra_instrument' => array(
                    'type' => 'text',
                    'display_name' => '&nbsp;',
                    'comments'=>'<div class="smallText comment">Instrument</div>',
                    ),
		),
		'unofficial' => array(
			'type' => 'radio_inline_no_sort',
			'display_name' => 'Would you like an <em>unofficial</em> transcript',
			'options' => array('yes' => 'Yes', 'no' => 'No'),
		),
		'delivery_header' => array(
			'type' => 'comment',
			'text' => '<h3>Delivery</h3>',
		),
		'delivery' => array(
			'type' => 'radio',
			'display_name' => 'When to prepare transcripts',
			'options' => array(
				'now'=>'Send out as soon as possible<br>
						(allow 48 hours processing time)',
				'after current semester' => 'Wait until current semester grades are posted',
				'after degree' => 'Wait until degree is posted',
			),
		),
	);

	var $required = array('first_name', 'last_name', 'daytime_phone', 'e-mail', 'address', 'city', 'state_province', 'zip', 'delivery');

	var $display_name = 'Transcript Request Info';
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
