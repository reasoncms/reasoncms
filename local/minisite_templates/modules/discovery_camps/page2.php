<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2011-02-28
//
//    Work on the second page of the discovery camp form
//
////////////////////////////////////////////////////////////////////////////////

include_once(WEB_PATH.'stock/discovery_camps.php');
include_once(TYR_INC . 'tyr.php');

class DiscoveryCampsTwo extends FormStep
{
	var $_log_errors = true;
	var $no_session = array( 'credit_card_number' );
	var $error;
	var $expense_budget_number = '13-102-01808-51111';
        var $revenue_budget_number = '13-000-01808-40220';
        
	var $transaction_comment = 'Discovery Camps';
	var $is_in_testing_mode; // This gets set using the value of the THIS_IS_A_DEVELOPMENT_REASON_INSTANCE constant or if the 'tm' (testing mode) request variable evaluates to an integer

	// the usual disco member data
	var $elements = array(
		'review_note' => array(
			'type' => 'comment',
			'text' => '<h3>Payment Information</h3>',
                 ),
                'deposit_note' => array(
                    'type' => 'comment',
                    'text' => 'Please choose your payment amount. If you choose to only pay the deposit, the balance is due on registration day.
                        No refund of deposit after June 4. More information will follow.'
                ),
                'payment_amount' => 'hidden',
		'payment_note' => array(
			'type' => 'comment',
			'text' => '<h3>Payment Method</h3>',
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
			'start' => 2007,
			'end' => 2022,
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

	var $date_format = 'j F Y';
	var $display_name = 'Payment';
	var $error_header_text = 'Please check your form.';
	var $database_transformations = array('credit_card_number'=>'obscure_credit_card_number');

	// style up the form and add comments et al
	function on_every_time()
	{
            // calculate the total_cost of the camps
            $camp_1 = 85;
            $camp_2 = 95;
            $camp_3 = 170;
            $camp_4 = 145;
            $camp_5 = 170;
            $camp_6 = 150;
            $camp_7 = 145;
            $camp_8 = 170;
            $camp_9 = 150;
            $late_fee = 15; // after April 15, charge a late fee

            $april15 = 104; // April 15 == day 104 (105 on a leap year) on a 0 - 364 scale
            if (date('L')) {// if this year is a leap year
                $june1 = 105;
            }

            $date = getdate();
            if ($date['yday'] > $april15){
                $camp_1 = $camp_1 + $late_fee;
                $camp_2 = $camp_2 + $late_fee;
                $camp_3 = $camp_3 + $late_fee;
                $camp_4 = $camp_4 + $late_fee;
                $camp_5 = $camp_5 + $late_fee;
                $camp_6 = $camp_6 + $late_fee;
                $camp_7 = $camp_7 + $late_fee;
                $camp_8 = $camp_8 + $late_fee;
                $camp_9 = $camp_9 + $late_fee;
            }

            $total_cost = 0;
            if ($this->controller->get('camp_1'))
                    $total_cost = $total_cost + $camp_1;
            if ($this->controller->get('camp_2'))
                    $total_cost = $total_cost + $camp_2;
            if ($this->controller->get('camp_3'))
                    $total_cost = $total_cost + $camp_3;
            if ($this->controller->get('camp_4'))
                    $total_cost = $total_cost + $camp_4;
            if ($this->controller->get('camp_5'))
                    $total_cost = $total_cost + $camp_5;
            if ($this->controller->get('camp_6'))
                    $total_cost = $total_cost + $camp_6;
            if ($this->controller->get('camp_7'))
                    $total_cost = $total_cost + $camp_7;
            if ($this->controller->get('camp_8'))
                    $total_cost = $total_cost + $camp_8;
            if ($this->controller->get('camp_9'))
                    $total_cost = $total_cost + $camp_9;

            
            $this->change_element_type('payment_amount', 'solidtext');
            $this->set_value('payment_amount', '$'.$total_cost);

            if(THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty( $this->_request[ 'tm' ] ) )
            {
                    $this->is_in_testing_mode = true;
            }
            else
            {
                    $this->is_in_testing_mode = false;
            }

            $this->change_element_type('credit_card_expiration_year','numrange',array('start'=>date('Y'),'end'=>(date('Y')+15),'display_name' => 'Expiration Year'));
	}

	function pre_show_form()
	{
		echo '<div id="campForm" class="pageThree">'."\n";
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
	function get_confirmation_text()
	{

		$txt = '<div id="campOverview">'."\n";

		$txt .= '<p class="printConfirm">Print this confirmation for your records.</p>'."\n";
		$txt .= '<h3>Thank you for registering for Discovery Camps</h3>';
		if (reason_unique_name_exists('discovery_camps_thank_you_blurb'))
			$txt .= '<p>' . get_text_blurb_content('discovery_camps_thank_you_blurb'). '</p>';
		$txt .= '<p>If you experience technical problems using the registration form, please contact Emily Neal, Coordinator of School Outreach,
                     Sustainability, Wellness, and the Environment.</p>'."\n";
		$txt .= '<ul>'."\n";
		$txt .= '<li><strong>Date:</strong> '.date($this->date_format).'</li>'."\n";
		$txt .= '<h4>Camper Information</h4>';
		$txt .= '<li><strong>Name:</strong> '.$this->controller->get('first_name').' '.$this->controller->get('last_name').'</li>'."\n";
                $txt .= '<li><strong>Gender:</strong> '.$this->controller->get('gender').'</li>'."\n";
                $txt .= '<li><strong>Grade:</strong> '.$this->controller->get('grade').'</li>'."\n";
                $txt .= '<li><strong>Current Age:</strong> '.$this->controller->get('age').'</li>'."\n";
		$txt .= '<li><strong>Address:</strong>'."\n".$this->controller->get('address')."\n".$this->controller->get('city').' '.$this->controller->get('state_province').' '.$this->controller->get('zip').'</li>'."\n";
		$txt .= '<li><strong>T-shirt Size:</strong> '.$this->controller->get('t-shirt_size').'</li>'."\n";
                $txt .= '<h4>Parent/Guardian Information</h4>';
                $txt .= '<li><strong>Name:</strong> '.$this->controller->get('parent_guardian_name').'</li>'."\n";
                $txt .= '<li><strong>Home Phone:</strong> '.$this->controller->get('home_phone').'</li>'."\n";
                $txt .= '<li><strong>Work Phone:</strong> '.$this->controller->get('work_phone').'</li>'."\n";
		$txt .= '<li><strong>E-mail:</strong> '.$this->controller->get('e-mail').'</li>'."\n";
                $txt .= '<h4>Camps</h4>';
                if ($this->controller->get('camp_1')) {
                    $txt .= '<li>June 6-7 Grade 1</li>'."\n";
                }
		if ($this->controller->get('camp_2')) {
			$txt .= '<li>June 8-10 Grade 2</li>'."\n";
		}
		if ($this->controller->get('camp_3')) {
			$txt .= '<li>June 6-10 Grades 7-9</li>'."\n";
		}
                if ($this->controller->get('camp_4')) {
			$txt .= '<li>June 13-17 Grades 3-6</li>'."\n";
		}
                if ($this->controller->get('camp_5')) {
			$txt .= '<li>June 20-24 Grades 5-8</li>'."\n";
		}
                if ($this->controller->get('camp_6')) {
                    $txt .= '<li>June 27-July 1 Grades 9-12</li>'."\n";
                }
                if ($this->controller->get('camp_7')) {
                    $txt .= '<li>July 11-15 Grades 3-6</li>'."\n";
                }
                if ($this->controller->get('camp_8')) {
                    $txt .= '<li>July 18-22 Grades 3-6</li>'."\n";
                }
                if ($this->controller->get('camp_9')) {
                    $txt .= '<li>July 18-22 Grades 6-9</li>'."\n";
                }

		$txt .= '</ul>'."\n";
		$txt .= '</div>'."\n";
		return $txt;
	}
	function run_error_checks()
	{
                    if($this->get_value('billing_address') == 'new'
                        && (!$this->get_value('billing_street_address')
                        || !$this->get_value('billing_city')
                        || !$this->get_value('billing_state_province')
                        || !$this->get_value('billing_zip')
                        || !$this->get_value('billing_country') ) )
		{
			$this->set_error('billing_address', 'Please enter your full billing address if the address
                            you entered on the previous page was not the billing address for your credit card.');
		}


		// Process credit card
		if( !$this->_has_errors() )
		{
			$pf = new discovery_campsPF;

			$expiration_mm = str_pad($this->get_value('credit_card_expiration_month'), 2, '0', STR_PAD_LEFT);
			$expiration_yy = substr($this->get_value('credit_card_expiration_year'), 2, 2);
			$expiration_mmyy = $expiration_mm.$expiration_yy;

			foreach ($this->controller->get_element_names() as $element_name)
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
				$this->get_value('payment_amount'),
				$this->get_value('credit_card_number'),
				$expiration_mmyy,
				$this->revenue_budget_number,
				$this->get_value('credit_card_name'),
				$this->transaction_comment,
				$this->expense_budget_number
			);

			//$this->helper->build_transactions_array();


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
				//$confirm_text .= build_gift_review_detail_output( $this->helper, $this->date_format );

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
										'–'=>'-',
										//'<h3>'=>'--------------------'."\n\n",
										//'</h3>'=>'',
										'<br />'=>"\n",
									);

                                if (reason_unique_name_exists('discovery_camps_thank_you_blurb'))
                                    $confirm_text = get_text_blurb_content('discovery_camps_thank_you_blurb') . $confirm_text;
				else
                                    $confirm_text = '<p><strong>Thank you for your payment to Luther College!</strong></p>' . $confirm_text;

				$mail_text = str_replace(array_keys($replacements),$replacements,$confirm_text);
				$email_to_emily = new Email('nealem01@luther.edu', 'noreply@luther.edu','noreply@luther.edu', 'New Discovery Camper '.date('mdY H:i:s'),strip_tags($mail_text), $mail_text);
				$email_to_emily->send();
				$email_to_camper = new Email($this->controller->get('e-mail'),'nealem01@luther.edu','nealem01@luther.edu','Luther College Discovery Camps Confirmation',strip_tags($mail_text),$mail_text);
				$email_to_camper->send();
                    }
		}
	}
	function where_to()
	{
		$refnum = $this->get_value( 'result_refnum' );
		$text = $this->get_value( 'confirmation_text' );
		reason_include_once( 'minisite_templates/modules/discovery_camps/confirmation.php' );
		$camp_confirmation = new DiscoveryCampsConfirmation;
		$hash = $camp_confirmation->make_hash( $text );
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