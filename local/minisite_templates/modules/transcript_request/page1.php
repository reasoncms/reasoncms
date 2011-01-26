<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the transcript request form
//
////////////////////////////////////////////////////////////////////////////////

class TranscriptPageOneForm extends FormStep
{
	var $_log_errors = true;
	var $error;
	
	var $elements = array(
		'amount' => 'cloaked',
		'your_information_header' => array(
			'type' => 'comment',
			'text' => '<h3>Your Information</h3>',
		),
		'first_name' => array(
			'type' => 'text',
			'size' => 35,
		),
		'middle_initial' =>  array(
			'type' => 'text',
			'size'=> 2,
                ),
		'last_name' => array(
			'type' => 'text',
			'size'=> 35,
		),
		'previous_name' => array(
			'type' => 'text',
			'size' => 35,
		),
		'daytime_phone' => array(
			'type' => 'text',
			'size' => 20,
		),
		'e-mail' => array(
			'type' => 'text',
			'size' => 35,
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
		'country' => array(
			'type' => 'country',
			'display_name' => 'Country',
		),
		'official_header' => array(
			'type' => 'comment',
			'text' => '<h3>Official transcripts</h3>',
		),
		'official' => array(
			'type' => 'text',
			'display_name' => 'Number of <em>official</em> transcripts',
			'size' => 3,
			'comments' => ' $5 each, for former students',
		),
		'unofficial_header' => array(
			'type' => 'comment',
			'text' => '<h3>Unofficial transcripts</h3>',
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
            } else {
                    echo "False";
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
