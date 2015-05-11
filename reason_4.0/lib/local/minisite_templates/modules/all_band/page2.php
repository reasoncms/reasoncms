<?php

////////////////////////////////////////////////////////////////////////////////
//
//    Work on the second page of the All Band form
//
//   Steve Smith
//    2011-01-26
//
//
////////////////////////////////////////////////////////////////////////////////

include_once(WEB_PATH.'reason/local/stock/allBandPFclass.php');
include_once(TYR_INC . 'tyr.php');
reason_include_once('minisite_templates/modules/form/credit_card_shim.php');

//reason_include_once( 'classes/repeat_transaction_helper.php' );

class AllBandTwo extends FormStep {

    var $_log_errors = true;
    var $no_session = array('credit_card_number');
    var $error;
    var $expense_budget_number = '10-000-60200-12100';
    var $revenue_budget_number = '10-000-60200-20000';
    var $transaction_comment = 'All Band Alumni Reunion';
    var $is_in_testing_mode; // This gets set using the value of the THIS_IS_A_DEVELOPMENT_REASON_INSTANCE constant or if the 'tm' (testing mode) request variable evaluates to an integer

    var $date_format = 'j F Y';
    var $display_name = 'Payment';
    var $error_header_text = 'Please check your form.';
    var $database_transformations = array('credit_card_number' => 'obscure_credit_card_number');

    function get_total_cost() {
        // calculate the total_cost of conference by adding registration_type, room_type, and additional_meal_tickets
        switch ($this->controller->get('registration_type')) {
            case 'Participant':
                $reg_cost = 75;
                break;
            case 'Participant&Guest':
                $reg_cost = 110;
                break;
        }

        // calculate room costs if set
        switch ($this->controller->get('room_type')) {
            case 'single':
                $room_cost = 72;
                break;
            case 'double':
                $room_cost = 124;
                break;
            default:
                $room_cost = 0;
        }

        // multiply room_cost times # of nights staying
//                if ($this->controller->get('arrival_date')) {
//                    $arrival_date = $this->controller->get('arrival_date');
//                    //$datetime1 = new DateTime($arrival_date);
//                    $arrival_date = str_replace('-', '', $arrival_date);
//                    $arrival_date = (int)$arrival_date;
//
//
//                }
//                if ($this->controller->get('departure_date')) {
//                    $departure_date = $this->controller->get('departure_date');
//                    //$datetime2 = new DateTime($departure_date);
//                    $departure_date = str_replace('-', '', $departure_date);
//                    $departure_date = (int)$departure_date;
//                }
//                //$interval = $datetime1->diff($datetime2);
//                //$nights = $interval->format('%a') . '<br>';
//                $nights = $departure_date - $arrival_date;
//
//
//                $room_cost = $room_cost * $nights;
//
//                // calculate cost of attend_banquet
//                if ($this->controller->get('attend_banquet') == 'Yes'){
//                    $banq_cost = 35;
//                } else {
//                    $banq_cost = 0;
//                }
//
//                // calculate additional meal ticket costs
//                $additional_meals = $this->controller->get('additional_meal_tickets');
//                $meal_cost = 0;
//                if ($additional_meals) {
//                    foreach ($additional_meals as $key => $value) {
//                        if ($value == 'Banquet')
//                            $meal_cost = $meal_cost + 35;
//                        if ($value == 'Barbecue')
//                            $meal_cost = $meal_cost + 25;
//                        if ($value == 'Reception')
//                            $meal_cost = $meal_cost + 15;
//                    }
//                }
//
//                // calculate shuttle costs
//                if ($this ->controller->get('shuttle_tickets')) {
//                    $shuttle_tix = (int)$this ->controller->get('shuttle_tickets');
//                    $shuttle_cost = $shuttle_tix * 50;
//                }
        return $reg_cost + $room_cost /* + $banq_cost +$meal_cost + $shuttle_cost */;
    }

    // style up the form and add comments et al
    function on_every_time() {
        $credit_card_shim = new creditCardShim();
        $credit_card_shim->show_credit_card($this);
        
        $this->change_element_type('payment_amount', 'solidtext');
        $this->set_value('payment_amount', '$' . $this->get_total_cost());

        if (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty($this->_request['tm'])) {
            $this->is_in_testing_mode = true;
        } else {
            $this->is_in_testing_mode = false;
        }

        if (reason_check_authentication() == 'smitst01') {
            echo 'you are steve';
            $this->is_in_testing_mode = true;
        }

    }

    function pre_show_form() {
        echo '<div id="allBandForm" class="pageTwo">' . "\n";
        if ($this->is_in_testing_mode) {
            echo '<div class="announcement">';
            echo 'Testing mode on. ' . "\n";
            echo 'Credit cards will not be charged in this mode.' . "\n";
            echo '</div>' . "\n";
        }
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

    function get_confirmation_text() {

        $txt = '<div id="reunionOverview">' . "\n";

        $txt .= '<p class="printConfirm">Print this confirmation for your records.</p>' . "\n";
        $txt .= '<h3>Thank you for registering for the All Band Alumni Reunion</h3>';
//		if (reason_unique_name_exists('dorian_sh_thank_you_blurb'))
//			$txt .= '<p>' . get_text_blurb_content('dorian_sh_thank_you_blurb'). '</p>';
        $txt .= '<p>If you experience technical problems using the registration form, please contact the<a href="mailto:alumni@luther.edu">Alumni Office</a><p>' . "\n";
        $txt .= '<ul>' . "\n";
        $txt .= '<li><strong>Date:</strong> ' . date($this->date_format) . '</li>' . "\n";
        $txt .= '<h4>Your Information</h4>';
        $txt .= '<li><strong>Name:</strong> ' . $this->controller->get('first_name') . ' ' . $this->controller->get('last_name') . '</li>' . "\n";
        if ($this->controller->get('graduation_name')) {
            $txt .= '<li><strong>Graduation Name:</strong> ' . $this->controller->get('graduation_name') . '</li>' . "\n";
        }
        if ($this->controller->get('class_year')) {
            $txt .= '<li><strong>Class:</strong> ' . $this->controller->get('class_year') . '</li>' . "\n";
        }
        $txt .= '<li><strong>Address:</strong>' . "\n" . $this->controller->get('address') . "\n";
        $txt .= $this->controller->get('city') . ' ';
        if ($this->controller->get('state_province')) {
            $txt .= $this->controller->get('state_province') . ' ';
        }
        if ($this->controller->get('zip_postal')) {
            $this->controller->get('zip_postal') . '</li>' . "\n";
        }
        $txt .= '<li><strong>Phone:</strong> ' . $this->controller->get('phone') . '</li>' . "\n";
        if ($this->controller->get('cell_phone')) {
            $txt .= '<li><strong>Cell Phone:</strong> ' . $this->controller->get('cell_phone') . '</li>' . "\n";
        }
        $txt .= '<li><strong>E-mail:</strong> ' . $this->controller->get('e-mail') . '</li>' . "\n";
        $txt .= '<li><strong>T-shirt Size:</strong> ' . $this->controller->get('t-shirt_size') . '</li>' . "\n";
        $txt .= '<li><strong>Instrument:</strong> ' . $this->controller->get('instrument') . '</li>' . "\n";
        if ($this->controller->get('guest_first_name')) {
            $txt .= '<li><strong>Guest Name:</strong> ' . $this->controller->get('guest_first_name') . ' ' . $this->controller->get('guest_last_name') . '</li>' . "\n";
            if ($this->controller->get('guest_graduation_name')) {
                $txt .= '<li><strong>Guest Graduation Name:</strong> ' . $this->controller->get('guest_graduation_name') . '</li>' . "\n";
            }
        }
        $txt .= '<li><strong>Registration Type:</strong> ' . $this->controller->get('registration_type') . '</li>' . "\n";
        if ($this->controller->get('room_type')) {
            $txt .= '<li><strong>Requested Room Type :</strong> ' . $this->controller->get('room_type') . '</li>' . "\n";
        }
        if ($this->controller->get('roommate_name')) {
            $txt .= '<li><strong>Requested Roommate:</strong> ' . $this->controller->get('roommate_name') . '</li>' . "\n";
        }
        ##############################################
        ##############################################
//                if ($this->controller->get('arrival_date')){
//                    $txt .= '<li><strong>Arrival Date:</strong> '.$this->controller->get('arrival_date').'</li>'."\n";
//                }
//                if ($this->controller->get('departure_date')){
//                    $txt .= '<li><strong>Departure Date:</strong> '.$this->controller->get('departure_date').'</li>'."\n";
//                }
//		if ($this->controller->get('additional_meal_tickets'))
//		{
//                    $tix_txt = '';
//                    $tix_array =$this->controller->get('additional_meal_tickets');
//                    foreach ($tix_array as $key => $value) {
//                       if ($value != 'Reception'){
//                           $tix_txt .= $value . ', ';
//                       } else {
//                           $tix_txt .= $value;
//                       }
//                    }
//                    $txt .= '<strong><li>Additional Meal Tickets:</strong> ' .$tix_txt. '</li>'."\n";
//		}
//                if ($this->controller->get('shuttle_tickets'))
//		{
//                        $txt .= '<strong><li>Shuttle Tickets:</strong> '.$this->controller->get('shuttle_tickets').'</li>'."\n";
//		}
//                if ($this->controller->get('dietary_needs'))
//		{
//			$txt .= '<strong><li>Dietary Restrictions/Needs:</strong> ' .$this->controller->get('dietary_needs'). '</li>'."\n";
//		}
        $txt .= '<li><strong>Total Amount Charged:</strong> $' . $this->get_total_cost() . '</li>' . "\n";

        $txt .= '</ul>' . "\n";
        $txt .= '</div>' . "\n";
        return $txt;
    }

    function run_error_checks() {
        if ($this->get_value('billing_address') == 'new'
                && (!$this->get_value('billing_street_address')
                || !$this->get_value('billing_city')
                || !$this->get_value('billing_state_province')
                || !$this->get_value('billing_zip')
                || !$this->get_value('billing_country') )) {
            $this->set_error('billing_address', 'Please enter your full billing address if the address
                            you entered on the previous page was not the billing address for your credit card.');
        }
        if ($this->get_value('billing_address') == 'entered') {
            $this->set_value('billing_street_address', $this->controller->get('address'));
            $this->set_value('billing_city', $this->controller->get('city'));
            $this->set_value('billing_state_province', $this->controller->get('state_province'));
            $this->set_value('billing_zip', $this->controller->get('zip_postal'));
        }


        // Process credit card
        if (!$this->_has_errors()) {
            $pf = new allBandPF;

            $expiration_mm = str_pad($this->get_value('credit_card_expiration_month'), 2, '0', STR_PAD_LEFT);
            $expiration_yy = substr($this->get_value('credit_card_expiration_year'), 2, 2);
            $expiration_mmyy = $expiration_mm . $expiration_yy;

            foreach ($this->controller->get_element_names() as $element_name) {
                if ($this->controller->get($element_name)) {
                    if (empty($this->database_transformations[$element_name])) {
                        $pass_info[$element_name] = $this->controller->get($element_name);
                    } else {
                        $pass_info[$element_name] = $this->database_transformations[$element_name]($this->controller->get($element_name));
                    }
                }
            }
            foreach ($this->elements as $element_name => $vals) {
                if ($this->get_value($element_name)) {
                    if (empty($this->database_transformations[$element_name])) {
                        $pass_info[$element_name] = $this->get_value($element_name);
                    } else {
                        $pass_info[$element_name] = $this->database_transformations[$element_name]($this->get_value($element_name));
                    }
                }
            }

            $pf->set_params($pass_info);

            $pf->set_info(
                    $this->get_value('payment_amount'),
                    $this->get_value('credit_card_number'),
                    $expiration_mmyy,
                    $this->revenue_budget_number,
                    $this->get_value('credit_card_name'),
                    $this->expense_budget_number,
                    $this->transaction_comment,
                    $this->get_value('billing_street_address'),
                    $this->get_value('billing_city'),
                    $this->get_value('billing_state_province'),
                    $this->get_value('billing_zip'),
                    $this->controller->get('e-mail')
            );

            //$this->helper->build_transactions_array();


            /* THIS IS WHERE THE TRANSACTION TAKES PLACE */
            // Test mode: $result = $pf->transact('test');
            // Live mode: $result = $pf->transact();
            if ($this->is_in_testing_mode) {
                $result = $pf->transact('test');
            } else {
                $result = $pf->transact();
            }
            if (!$pf->approved) {
                $message = $pf->message;
                $this->set_error('credit_card_number', $message);
            } else {
                // It's important that these things happen before we build the confirmation text, since they are needed by that code.
                if (!empty($result['REFNUM'])) {
                    $this->set_value('result_refnum', $result['REFNUM']);
                } else {
                    trigger_error('No Reference Number (REFNUM) in transaction result.');
                }
                $this->set_value('result_authcode', $result['AUTHCODE']);

                $confirm_text = $this->get_confirmation_text();
                //$confirm_text .= build_gift_review_detail_output( $this->helper, $this->date_format );

                $this->set_value('confirmation_text', $confirm_text);
                $pf->set_confirmation_text($confirm_text);

                // This is where we send the confirmation email.
                // for now we are filtering out obviously bad/non-carleton email addresses
                // NOTE: REMOVE THIS FILTER BEFORE WE GO LIVE
                //if(strstr( $this->controller->get('email'), 'carleton.edu' ) )
                //{
                $replacements = array(
                    '<th class="col1">Date</th>' => '',
                    '<th class="col1">Year</th>' => '',
                    '<th>Amount</th>' => '',
                    '</td><td>' => ': ',
                    '–' => '-',
                    //'<h3>'=>'--------------------'."\n\n",
                    //'</h3>'=>'',
                    '<br />' => "\n",
                );
                $mail_text = str_replace(array_keys($replacements), $replacements, $confirm_text);
                $email_to_alumni = new Email(array('ferrka01@luther.edu', 'rihajudy@luther.edu', 'rohershr@luther.edu'),
                                'noreply@luther.edu', 'noreply@luther.edu', 'New All Band Registration ' . date('mdY H:i:s'), strip_tags($mail_text), $mail_text);
                $email_to_alumni->send();
                $email_to_registrant = new Email($this->controller->get('e-mail'), 'alumni@luther.edu', 'alumni@.edu', 'All Band Alumni Reunion Confirmation', strip_tags($mail_text), $mail_text);
                $email_to_registrant->send();
            }
        }
    }

    function where_to() {
        $refnum = $this->get_value('result_refnum');
        $text = $this->get_value('confirmation_text');
        reason_include_once('minisite_templates/modules/all_band/all_band_confirmation.php');
        $confirmation = new AllBandConfirmation;
        $hash = $confirmation->make_hash($text);
        connectDB(REASON_DB);
        $url = get_current_url();
        $parts = parse_url($url);
        $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?r=' . $refnum . '&h=' . $hash;
        return $url;
    }

}

function obscure_credit_card_number($cc_num) {
    $char_count = strlen($cc_num);
    $obscure_end = $char_count - 4;
    $obscured_num = '';
    for ($i = 0; $i < $obscure_end; $i++) {
        $obscured_num .= 'x';
    }
    $obscured_num .= substr($cc_num, $char_count - 4);
    return $obscured_num;
}

function trim_hours_from_datetime($datetime) {
    return substr($datetime, 0, 10);
}

?>