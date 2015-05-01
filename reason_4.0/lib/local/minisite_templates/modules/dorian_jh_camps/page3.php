<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Work on the second page of the giving form
//
//   Steve Smith & Lucas Welper
//    2011-01-26
//
//
////////////////////////////////////////////////////////////////////////////////

include_once(WEB_PATH.'reason/local/stock/dorian_jh_camps.php');
include_once(TYR_INC . 'tyr.php');
//reason_include_once( 'classes/repeat_transaction_helper.php' );
reason_include_once('minisite_templates/modules/form/credit_card_shim.php');

class DorianJHCampsThreeForm extends FormStep
{
    var $_log_errors = true;
    var $no_session = array( 'credit_card_number' );
    var $error;
    var $expense_budget_number = '10-000-08520-12121';
    var $revenue_budget_number = '10-000-08520-22000';
    var $transaction_comment = 'Dorian Camp';
    var $is_in_testing_mode; // This gets set using the value of the THIS_IS_A_DEVELOPMENT_REASON_INSTANCE constant or if the 'tm' (testing mode) request variable evaluates to an integer

    var $date_format = 'j F Y';
    var $display_name = 'Payment';
    var $error_header_text = 'Please check your form.';
    var $database_transformations = array('credit_card_number'=>'obscure_credit_card_number');

    // style up the form and add comments et al
    function on_every_time()
    {
    	$this->add_element('review_note', 'comment', array('text' => '<h3>Payment Information</h3>'));
    	$this->add_element('deposit_note', 'comment', array('text' => 'Please choose your payment amount. If you choose to only pay the deposit, the balance is due on registration day. No refund of deposit after June 4. More information will follow.'));
    	 
    	$credit_card_shim = new creditCardShim();
    	$credit_card_shim->show_credit_card(&$this);
    	
        $this->box_class = 'StackedBox';
        // calculate the total_cost of the camp by adding lesson_cost (if present) to the camp_cost
        $camp_cost = 486;
        $per_lesson_cost = 39;
        $lesson_cost = 0;
        $lesson_msg = '';
        if ($this->controller->get('private_lessons'))
        {
            $lesson_cost = $per_lesson_cost * $this->controller->get('private_lessons');
            switch($this->controller->get('private_lessons')){
                case '1':
                    $lesson_msg = '<br />(camp, plus $' . $per_lesson_cost . ' for 1 lesson)';
                    break;
                case '2':
                    $lesson_msg = '<br />(camp, plus $' . $per_lesson_cost*2 . ' for 2 lessons)';
                    break;
                default:
                    $lesson_msg = '';
            }
        }
        $total_cost = $camp_cost + $lesson_cost;
        $this->change_element_type('payment_amount', 'radio_no_sort', array(
                'options' => array(
                    '$40' => '$40 - Deposit only',
                    '$' . $total_cost => '$' . $total_cost . ' - Total cost' . $lesson_msg
                )
            )
        );
        if(THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty( $this->_request[ 'tm' ] ) )
        {
            $this->is_in_testing_mode = true;
        }
        else
        {
            $this->is_in_testing_mode = false;
        }

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
        $txt .= '<h3>Thank you for registering for Dorian Camp</h3>';
        if (reason_unique_name_exists('dorian_jh_thank_you_blurb'))
            $txt .= '<p>' . get_text_blurb_content('dorian_jh_thank_you_blurb'). '</p>';
        $txt .= '<p>If you experience technical problems using the registration form, please contact the Luther College Music Office.</p>'."\n";
        $txt .= '<ul>'."\n";
        $txt .= '<li><strong>Date:</strong> '.date($this->date_format).'</li>'."\n";
        $txt .= '<h4>Your Information</h4>';
        $txt .= '<li><strong>Name:</strong> '.$this->controller->get('first_name').' '.$this->controller->get('last_name').'</li>'."\n";
        $txt .= '<li><strong>Gender:</strong> '.$this->controller->get('gender').'</li>'."\n";
        $txt .= '<li><strong>Address:</strong>'."\n".$this->controller->get('address')."\n".$this->controller->get('city').' '.$this->controller->get('state_province').' '.$this->controller->get('zip').'</li>'."\n";
        $txt .= '<li><strong>Home Phone:</strong> '.$this->controller->get('home_phone').'</li>'."\n";
        $txt .= '<li><strong>E-mail:</strong> '.$this->controller->get('e-mail').'</li>'."\n";
        $txt .= '<li><strong>School:</strong> '.$this->controller->get('school').'</li>'."\n";
        $txt .= '<li><strong>Grade:</strong> '.$this->controller->get('grade').'</li>'."\n";
        if ($this->controller->get('roomate_requested'))
        {
            $txt .= '<li><strong>Requested Roomate(s) :</strong> '.$this->controller->get('roomate_requested').'</li>'."\n";
        }
        $txt .= '<h4>Participation</h4>';
        if ($this->controller->get('band_participant'))
        {
            $txt .= '<li>You\'ll play ' .$this->controller->get('band_instrument'). ' in band.</li>'."\n";
        }
        if ($this->controller->get('orchestra_participant'))
        {
            $txt .= '<li>You\'ll play ' .$this->controller->get('orchestra_instrument'). ' in orchestra.</li>'."\n";
        }
        if ($this->controller->get('jazz_participant'))
        {
            $txt .= '<li>You\'ll play ' .$this->controller->get('jazz_instrument'). ' in jazz band.</li>'."\n";
        }
        if ($this->controller->get('wind_choir_participant'))
        {
            $txt .= '<li>You\'ll play ' .$this->controller->get('wind_choir_instrument'). ' in wind choir.</li>'."\n";
        }
        if ($this->controller->get('brass_choir_participant'))
        {
            $txt .= '<li>You\'ll play ' .$this->controller->get('brass_choir_instrument'). ' in brass choir.</li>'."\n";
        }
        if ($this->controller->get('private_lessons'))
        {
            $txt .= '<li>You\'d like ' .$this->controller->get('private_lessons'). ' set(s) of private lessons for ' . $this->controller->get('lesson_instrument_1');
            if ($this->controller->get('lesson_instrument_2'))
            {
                $txt .= ' and ' . $this->controller->get('lesson_instrument_2');
            }
            $txt .= '</li>'."\n";
        }

        $txt .= '<li><strong>Period 1:</strong>'.$this->controller->get('period_one').'</li>'."\n";
        $txt .= '<li><strong>Period 2:</strong>'.$this->controller->get('period_two').'</li>'."\n";
        $txt .= '<li><strong>Period 3:</strong></li>'."\n";
        $txt .= '<ul>';
        $txt .= '<li>'.$this->controller->get('period_three_first').' (first choice)'.'</li>'."\n";
        $txt .= '<li>'.$this->controller->get('period_three_second').' (second choice)'.'</li>'."\n";
        $txt .= '</ul>';
        $txt .= '<li><strong>Period 4:</strong></li>'."\n";
        $txt .= '<ul>';
        $txt .= '<li>'.$this->controller->get('period_four_first').' (first choice)'.'</li>'."\n";
        $txt .= '<li>'.$this->controller->get('period_four_second').' (second choice)'.'</li>'."\n";
        $txt .= '</ul>';
        $txt .= '<li><strong>Period 5:</strong>'.$this->controller->get('period_five').'</li>'."\n";
        $txt .= '<li><strong>Period 6:</strong>'.$this->controller->get('period_six').'</li>'."\n";
        $txt .= '<li><strong>Amt Paid:</strong> '.$this->controller->get('payment_amount').'</li>'."\n";
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
                if($this->get_value('billing_address') == 'entered') {
                    $this->set_value('billing_street_address', $this->controller->get('address'));
                    $this->set_value('billing_city', $this->controller->get('city'));
                    $this->set_value('billing_state_province', $this->controller->get('state_province'));
                    $this->set_value('billing_zip', $this->controller->get('zip'));
                }


        // Process credit card
        $pf = new dorian_jh;
        $credit_card_shim = new creditCardShim();
        $credit_card_shim->process_credit_card(&$this, &$pf);
        return;
        
        if( !$this->_has_errors() )
        {
            $pf = new dorian_jh;

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
            	$this->get_value('credit_card_security_code'),
                $this->revenue_budget_number,
                $this->get_value('credit_card_name'),
                $this->expense_budget_number,
                $this->transaction_comment,
                $this->get_value('billing_street_address'),
                $this->get_value('billing_city'),
                $this->get_value('billing_state_province'),
                $this->get_value('billing_zip'),
                $this->controller->get('e-mail'),
                $this->controller->get('home_phone')
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
                /*
if (reason_unique_name_exists('giving_form_thank_you_blurb'))
                    $confirm_text = get_text_blurb_content('giving_form_thank_you_blurb') . $confirm_text;
                else
                    $confirm_text = '<p><strong>Thank you for your gift to Luther College!</strong></p>' . $confirm_text;
*/
                $mail_text = str_replace(array_keys($replacements),$replacements,$confirm_text);
                $email_to_music = new Email(array('dorian@luther.edu', 'buzzja01@luther.edu'), 'noreply@luther.edu','noreply@luther.edu', 'New Dorian Camper '.date('mdY H:i:s'),strip_tags($mail_text), $mail_text);
                $email_to_music->send();
                $email_to_giver = new Email($this->controller->get('e-mail'),'dorian@luther.edu','dorian@luther.edu','Luther College Dorian Camp Confirmation',strip_tags($mail_text),$mail_text);
                $email_to_giver->send();

//              $add_headers = 'Content-Type: text/plain; charset="utf-8"'."\r\n".'From: "Luther College Giving" <giving@luther.edu>' . "\r\n" .
//'Reply-To: "Luther College Giving" <giving@luther.edu>';
                /*
$add_headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-Type: text/html; charset="utf-8"'."\r\n".'From: "Luther College Giving" <giving@luther.edu>' . "\r\n" .
'Reply-To: "Luther College Giving" <giving@luther.edu>';
                mail($this->controller->get('email'),'Luther College Gift Confirmation', $mail_text, $add_headers);
                mail('waskni01@luther.edu', 'New Online Gift', strip_tags($mail_text), $add_headers);
*/
                //}
            }
        }
    }
    function where_to()
    {
        $refnum = $this->get_value( 'result_refnum' );
        $text = $this->get_value( 'confirmation_text' );
        reason_include_once( 'minisite_templates/modules/dorian_jh_camps/dorian_jh_camp_confirmation.php' );
        $camp_confirmation = new DorianJHCampConfirmation;
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
