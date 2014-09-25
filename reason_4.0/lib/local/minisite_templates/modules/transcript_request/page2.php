<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-11-24
//
//    Work on the second page of the transcript request form
//
////////////////////////////////////////////////////////////////////////////////

include_once(WEB_PATH.'stock/transcriptPFclass.php');

class TranscriptPageTwoForm extends FormStep
{
	var $_log_errors = true;
	var $no_session = array( 'credit_card_number' );
	var $error;
	var $expense_budget_number = '10-140-41000-51114';
	var $revenue_budget_number = '10-000-41000-40282';
	var $transaction_comment = 'Transcript Req';
	var $is_in_testing_mode; // This gets set using the value of the THIS_IS_A_DEVELOPMENT_REASON_INSTANCE constant or if the 'tm' (testing mode) request variable evaluates to an integer
	
	// the usual disco member data
	var $elements = array(
		'review_note' => array(
			'type' => 'comment',
			'text' => 'Transcript overview',
		),
		'payment_note' => array(
			'type' => 'comment',
			'text' => '<h3>Payment Method</h3>',
		),
		'payment_amount' => array(
			'type' => 'solidtext',
		),
		'credit_card_type' => array(
			'type' => 'radio_no_sort',
			'options' => array('Visa'=>'Visa','MasterCard'=>'MasterCard','American Express'=>'American Express','Discover'=>'Discover'),
		),
		'credit_card_number' => array(
			'type' => 'text',
			'size'=>35,
		),
		'credit_card_expiration_month' => array(
			'type' => 'month',
			'display_name' => 'Expiration Month',
		),
		'credit_card_expiration_year' => array(
			'type' => 'numrange',
			'start' => 2010,
			'end' => 2016,
			'display_name' => 'Expiration Year',
		),
		'credit_card_name' => array(
			'type' => 'text',
			'display_name' => 'Name as it appears on card',
			'size'=>35,
		),
		'billing_address' => array(
			'type' => 'radio_no_sort',
			'options' => array('entered'=>'Use address provided on previous page','new'=>'Use a different address'),
			'display_name' => 'Billing Address',
			'default' => 'entered',
		),
		'billing_street_address' => array(
			'type' => 'textarea',
			'rows' => 3,
			'cols' => 35,
			'display_name' => 'Street Address',
		),
		'billing_city' => array(
			'type' => 'text',
			'size'=>35,
			'display_name' => 'City',
		),
		'billing_state_province' => array(
			'type' => 'state_province',
			'display_name' => 'State/Province',
			'include_military_codes' => true,
		),
		'billing_zip' => array(
			'type' => 'text',
			'display_name' => 'Zip/Postal Code',
			'size'=>35,
		),
		'billing_country' => array(
			'type' => 'country',
			'display_name' => 'Country',
		),
		'confirmation_text' => array(
			'type' => 'hidden',
		),
		'result_refnum' => array(
			'type' => 'hidden',
		),
		'result_authcode' => array(
			'type' => 'hidden',
		),
	);
	var $required = array(
		'credit_card_type',
		'credit_card_number',
		'credit_card_expiration_month',
		'credit_card_expiration_year',
		'credit_card_name',
		'billing_address',
	);
	var $actions = array(
		'previous_step'=>'Change Request',
		'next_step'=>'Pay',
	);
	var $date_format = 'j F Y';
	var $display_name = 'Transcript Review / Card Info';
	var $error_header_text = 'Please check your form.';
	var $database_transformations = array('credit_card_number'=>'obscure_credit_card_number',);
	// style up the form and add comments et al
	function on_every_time()
	{             
        $this->box_class = 'StackedBox';
		if( !$this->controller->get('amount'))
		{
			echo '<div id="transcriptSetupError">Sorry. There was a problem setting up payment for your form. Please return to <a href="?_step=TranscriptPageOneForm">Transcript Request Form</a> and try again.</div>';
			$this->show_form = false;
			return;
		}

		$this->set_value('payment_amount', '$'.number_format($this->controller->get('amount'),2,'.',','));

		if(THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty( $this->_request[ 'tm' ] ) )
		{
			$this->is_in_testing_mode = true;
		}
		else
		{
			$this->is_in_testing_mode = false;
		}

		if (reason_check_authentication() == 'smitst01')
		{
			echo "You are Steve";
			$this->is_in_testing_mode = true;
		}
		
		$this->change_element_type('credit_card_expiration_year','numrange',array('start'=>date('Y'),'end'=>(date('Y')+15),'display_name' => 'Expiration Year'));
	}
	
	function post_error_check_actions()
	{
		if ($this->show_form)
		{
			$text = $this->get_brief_review_text();
			$text .= '<p class="changeRequestButton"><a href="?_step=TranscriptPageOneForm">Change Transcript Request Information</a></p>'."\n";
			$this->change_element_type( 'review_note', 'comment', array('text'=>$text) );
		}
	}
	
	function pre_show_form()
	{
		echo '<div id="transcriptForm" class="pageTwo">'."\n";
		if( $this->is_in_testing_mode )
		{
			echo '<div class="announcement">';
			echo 'Testing mode on. '."\n";
			echo 'Credit cards will not be charged in this mode.'."\n";
			echo '</div>'."\n";
		}
	}
	function post_show_form()
	{
		echo '</div>'."\n";
	}
	function get_brief_review_text()
	{
		$txt = '<div id="reviewOverview">'."\n";
		$txt .= '</div>'."\n";
		return $txt;
	}
	
	function get_confirmation_text()
	{
		$amount = $this->controller->get('amount');

        $name = $this->controller->get('name');
        $date_of_birth = $this->controller->get('date_of_birth');
        $latf = $this->controller->get('LATF');
        $daytime_phone = $this->controller->get('daytime_phone');
        $email = $this->controller->get('email');
        $address = $this->controller->get('address');
        $city = $this->controller->get('city');
        $state_province = $this->controller->get('state_province');
        $zip = $this->controller->get('zip');
        $country = $this->controller->get('country');
        //$unofficial = $this->controller->get('unofficial');
        //$unofficial_address = $this->controller->get('unofficial_address');
        $delivery_type = $this->controller->get('delivery_type');
        $number_of_official = $this->controller->get('number_of_official');
        $deliver_to = $this->controller->get('deliver_to');
        $institution_name = $this->controller->get('institution_name');
        $institution_attn = $this->controller->get('institution_attn');
        $official_email = $this->controller->get('official_email');
        $delivery_time = $this->controller->get('delivery_time');
        $submitter_ip = $this->controller->get('submitter_ip');

        $txt = '<div id="reviewTranscriptRequest">' . "\n";
        $txt .= '<ul>' . "\n";
        $txt .= '<li><strong>Date:</strong> ' . date($this->date_format) . '</li>' . "\n";
        $txt .= '<li><strong>Name:</strong> ' . $name . '</li>' . "\n";
        $txt .= '<li><strong>Date of Birth:</strong> ' . $date_of_birth . '</li>' . "\n";
        $txt .= '<li><strong>Lifetime Academic Transcript Fee:</strong> ' . $latf . '</li>'."\n";
        $txt .= '<li><strong>Daytime Phone:</strong> ' . $daytime_phone . '</li>' . "\n";
        $txt .= '<li><strong>Email:</strong> ' . $email . '</li>' . "\n";
        /*if ($unofficial != 'no' && $unofficial != '') {
            $txt .= '<li><strong>Unofficial transcripts requested:</strong> Yes - '. $unofficial .' </li>' . "\n";
            if ($unofficial == 'postal') {
                $txt .= '<li><strong>Mail to:</strong> ' . $unofficial_address . '</li>' . "\n";
                }
            }*/
        if ($delivery_type == 'postal') {
            if ($number_of_official) {
                $txt .= '<li><strong>Official paper transcripts requested:</strong> ' . $number_of_official . '</li>' . "\n";
                $txt .= '<li><strong>Delivery Information:</strong> ' . $deliver_to . '</li>' . "\n";
                $txt .= '<ul>' . "\n";
                if ($deliver_to == 'institution') {
                    $txt .= '<li><strong>Institution/Company:</strong><br />' .
                            $institution_name . '<br />' .
                            'Attn: ' . $institution_attn . '<br />';
                }
                $txt .= '<li><strong>Address:</strong><br />' . $address . '<br />' . $city . ' ' . $state_province . ' ' . $zip . ' ' . $country . '</li>' . "\n";
                $txt .= '<li><strong>Delivery Timeline: </strong>' . $delivery_time . '</li>' . "\n";
                $txt .= '</ul>' . "\n";
            }
        }
        if ($delivery_type == 'email') {
           $txt .= '<li><strong>Electronic transcripts requested:</strong> ' . $number_of_official . '</li>' . "\n";
           $txt .= '<li><strong>Delivery Information</strong></li>' . "\n";
           $txt .= '<ul>' . "\n";
           if ($deliver_to == 'institution') {
               $txt .= '<li><strong>Institution/Company:</strong><br />' .
                       $institution_name . '<br />' .
                       'Attn: ' . $institution_attn . '<br />' .
                       $official_email . '</li>' . "\n";
           } else { //deliver to requestor
               $txt .= '<li><strong>Your Email Address: </strong>' . $official_email . '</li>' . "\n";
           }
           $txt .= '<li><strong>Delivery Timeline: </strong>' . $delivery_time . '</li>' . "\n";
           $txt .= '</ul>' . "\n";
        }
        $txt .= '</ul>' . "\n";
        $txt .= '</div>' . "\n";

		return $txt;
	}
	
	
	function run_error_checks()
	{
		if ($this->get_value('billing_address') == 'new'
                        && (!$this->get_value('billing_street_address')
                        || !$this->get_value('billing_city')
                        || !$this->get_value('billing_state_province')
                        || !$this->get_value('billing_zip')
                        || !$this->get_value('billing_country') ) )
		{
			$this->set_error('billing_address','Please enter your full billing address if the address you entered on the previous page was not the billing address for your credit card.');
		}

		
		// Process credit card
		if( !$this->_has_errors() )
		{
			$pf = new transcriptPF;
			$expiration_mm = str_pad($this->get_value('credit_card_expiration_month'), 2, '0', STR_PAD_LEFT);
			$expiration_yy = substr($this->get_value('credit_card_expiration_year'), 2, 2);
			$expiration_mmyy = $expiration_mm.$expiration_yy;
			
			foreach($this->controller->get_element_names() as $element_name)
			{
				if($this->controller->get($element_name))
				{
					if(empty($this->database_transformations[$element_name]))
					{
						$pass_info[$element_name] = $this->controller->get($element_name);
					}
					else
					{
						$pass_info[$element_name] = $this->database_transformations[$element_name]($this->controller->get($element_name));
					}
				}
			}
			foreach($this->elements as $element_name => $vals)
			{
				if($this->get_value($element_name))
				{
					if(empty($this->database_transformations[$element_name]))
					{
						$pass_info[$element_name] = $this->get_value($element_name);
					}
					else
					{
						$pass_info[$element_name] = $this->database_transformations[$element_name]($this->get_value($element_name));
					}
				}
			}
			
			$pf->set_params( $pass_info );
			
			$pf->set_info(
				$this->controller->get('amount'),
				$this->get_value('credit_card_number'),
				$expiration_mmyy,
				$this->revenue_budget_number,
				$this->get_value('credit_card_name'),
				$this->expense_budget_number,
				$this->transaction_comment
			);
			
			/* THIS IS WHERE THE TRANSACTION TAKES PLACE */
			// Test mode: $result = $pf->transact('test');
			// Live mode: $result = $pf->transact();
			if($this->is_in_testing_mode)
			{
				$result = $pf->transact('test');
			}
			else
			{
				$result = $pf->transact();
			}
			 if (!$pf->approved)
			 {
				$message = $pf->message;
				$this->set_error('credit_card_number',$message);
			}
			else
			{
				// It's important that these things happen before we build the confirmation text, since they are needed by that code.
				if(!empty($result['REFNUM']))
				{
					$this->set_value( 'result_refnum', $result['REFNUM'] );
				}
				else
				{
					trigger_error( 'No Reference Number (REFNUM) in transaction result.' );
				}
				$this->set_value( 'result_authcode', $result['AUTHCODE'] );
				
				$confirm_text = $this->get_confirmation_text();
                                $confirm_text .= '<div id="reviewTranscriptRequestRefnum">'."\n";
                                $confirm_text .= '<ul>'."\n";
                                $confirm_text .= '<li><strong>REFNUM: </strong>'.$result['REFNUM'].'</li>'."\n";
                                $confirm_text .= '<li><strong>Amount Paid: </strong>$'.$this->controller->get('amount').'</li>'."\n";
                                $confirm_text .= '</ul></div>';
				
				$this->set_value( 'confirmation_text', $confirm_text );
				$pf->set_confirmation_text( $confirm_text );
				
				// This is where we send the confirmation email.
				// for now we are filtering out obviously bad/non-carleton email addresses
				// NOTE: REMOVE THIS FILTER BEFORE WE GO LIVE
				//if(strstr( $this->controller->get('email'), 'carleton.edu' ) )
				//{
				$replacements = array(
										'<th class="col1">Date</th>'=>'',
										'<th class="col1">Year</th>'=>'',
										'<th>Amount</th>'=>'',
										'</td><td>'=>': ',
										'�'=>'-',
										'<h3>'=>'--------------------'."\n\n",
										'</h3>'=>'',
										'<br />'=>"\n",
									);
				if (reason_unique_name_exists('transcript_thank_you_blurb'))
					$confirm_text_with_blurb = get_text_blurb_content('transcript_thank_you_blurb') . $confirm_text;
				else
					$confirm_text_with_blurb = '<p><strong>Your transcript request has been made.</strong></p>' . $confirm_text;
				
				//}
				$mail_text = str_replace(array_keys($replacements),$replacements,$confirm_text);
				$mail = new Email($this->controller->get('email'),'registrar@luther.edu','registrar@luther.edu','Luther College Transcript Request',strip_tags($confirm_text_with_blurb),$confirm_text_with_blurb);
				$mail->send();
				
				$mail2 = new Email('registrar@luther.edu', 'noreply@luther.edu','noreply@luther.edu', 'New Transcript Request '.date('mdY H:i:s'),strip_tags($mail_text), $mail_text);
				$mail2->send();
			}
		}
	}
	function where_to()
	{
		$refnum = $this->get_value( 'result_refnum' );
		$text = $this->get_value( 'confirmation_text' );
		reason_include_once( 'minisite_templates/modules/transcript_request/transcript_confirmation.php' );
		$gc = new TranscriptConfirmation;
		$hash = $gc->make_hash( $text );
		connectDB( REASON_DB );
		$url = get_current_url();
		$parts = parse_url( $url );
		$url = $parts['scheme'].'://'.$parts['host'].$parts['path'].'?r='.$refnum.'&h='.$hash;
		return $url;
	}
}

function obscure_credit_card_number ( $cc_num )
{
	$char_count = strlen ( $cc_num );
	$obscure_end = $char_count-4;
	$obscured_num = '';
	for($i=0; $i<$obscure_end; $i++)
	{
		$obscured_num .= 'x';
	}
	$obscured_num .= substr( $cc_num, $char_count-4 );
	return $obscured_num;
}
function trim_hours_from_datetime( $datetime )
{
	return substr( $datetime, 0, 10 );
}

?>
