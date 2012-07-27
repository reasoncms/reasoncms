<?php

////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-06-15 (very early in the morning)
//
//    Work on the second page of the homecoming registration form
//
////////////////////////////////////////////////////////////////////////////////

include_once(WEB_PATH . 'stock/homecomingPFclass.php');
// include_once('/usr/local/webapps/reason/reason_package_local/local/minisite_templates/modules/paypal/homecomingPFclass.php');

class HomecomingRegistrationTwoForm extends FormStep {

    var $_log_errors = true;
    var $no_session = array('credit_card_number');
    var $error;
    var $expense_budget_number = '10-202-60201-51331';
    var $revenue_budget_number = '10-000-60201-44906';
    var $transaction_comment = 'Homecoming Reg';
    var $is_in_testing_mode; // This gets set using the value of the THIS_IS_A_DEVELOPMENT_REASON_INSTANCE constant or if the 'tm' (testing mode) request variable evaluates to an integer
    // the usual disco member data
    var $elements = array(
        'review_note' => array(
            'type' => 'comment',
            'text' => 'Homecoming overview',
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
            'options' => array('Visa' => 'Visa', 'MasterCard' => 'MasterCard', 'American Express' => 'American Express', 'Discover' => 'Discover'),
        ),
        'credit_card_number' => array(
            'type' => 'text',
            'size' => 35,
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
            'size' => 35,
        ),
        'billing_address' => array(
            'type' => 'radio_no_sort',
            'options' => array('entered' => 'Use address provided on previous page', 'new' => 'Use a different address'),
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
            'size' => 35,
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
            'size' => 35,
        ),
        'billing_country' => array(
            'type' => 'country',
//          'size'=>35,
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
        'previous_step' => 'Make Changes To Your Weekend',
        'next_step' => 'Submit Your Gift For Processing',
    );
    var $date_format = 'j F Y';
    var $display_name = 'Homecoming Review / Card Info';
    var $error_header_text = 'Please check your form.';
    var $database_transformations = array(
        'credit_card_number' => 'obscure_credit_card_number',
    );

    // style up the form and add comments et al
    function on_every_time() {
                
        if (!$this->controller->get('amount')) {
            echo '<div id="homecomingSetupError">Sorry. There was a problem setting up payment for your form.
                            Please return to <a href="?_step=HomecomingRegistrationOneForm">Homecoming Registration</a> and try again.</div>';
            $this->show_form = false;
            return;
        }

        $this->set_value('payment_amount', '$' . number_format($this->controller->get('amount'), 2, '.', ','));

        if (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty($this->_request['tm'])) {
            $this->is_in_testing_mode = true;
        } else {
            $this->is_in_testing_mode = false;
        }

        if (reason_check_authentication() == 'smitst01'){
            echo 'You are Steve. Test away.';
            $this->is_in_testing_mode = true;
        }
        
        $this->change_element_type('credit_card_expiration_year', 'numrange', array('start' => date('Y'), 'end' => (date('Y') + 15), 'display_name' => 'Expiration Year'));
    }
    
    function post_error_check_actions() {
        if ($this->show_form) {
            $text = '<p class="changeRegistrationButton"><a href="?_step=HomecomingRegistrationOneForm">Change Registration Information</a></p>' . "\n";
            $this->change_element_type('review_note', 'comment', array('text' => $text));
        }
    }

    function pre_show_form() {
        echo '<div id="homecomingForm" class="pageTwo">' . "\n";
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
        $txt = '<div id="reviewHomecomingRegistration">' . "\n";
        $txt .= '<p class="printConfirm">Print this confirmation for your records.</p>' . "\n";
        $txt .= '<ul>' . "\n";
        $txt .= '<li><strong>Date:</strong> ' . date($this->date_format) . '</li>' . "\n";
        $txt .= '<li><strong>Name:</strong> ' . $this->controller->get('current_first_name') . ' ' . $this->controller->get('current_last_name') . '</li>' . "\n";
        $class_year = $this->controller->get('class_year');
        $txt .= '<li><strong>Class Year:</strong> ' . $class_year . '</li>' . "\n";
        $txt .= '<li><strong>Graduation Name:</strong> ' . $this->controller->get('graduation_name') . '</li>' . "\n";
        if ($this->controller->get('preferred_first_name')) {
            $txt .= '<li><strong>Preferred First Name:</strong> ' . $this->controller->get('preferred_first_name') . '</li>' . "\n";
        }
        $txt .= '<li><strong>Address:</strong>' . "\n" . $this->controller->get('address') . "\n" . $this->controller->get('city') . ' ' . $this->controller->get('state_province') . ' ' . $this->controller->get('zip') . /* $this->controller->get('country'). */'</li>' . "\n";
        if ($this->controller->get('home_phone')) {
            $txt .= '<li><strong>Home Phone:</strong> ' . $this->controller->get('home_phone') . '</li>' . "\n";
        }
        if ($this->controller->get('cell_phone')) {
            $txt .= '<li><strong>Cell Phone:</strong> ' . $this->controller->get('cell_phone') . '</li>' . "\n";
        }
        $txt .= '<li><strong>Email:</strong> ' . $this->controller->get('e-mail') . '</li>' . "\n";
        if ($this->controller->get('guest_name')) {
            $txt .= '<li><strong>Spouse/Guest Name:</strong> ' . $this->controller->get('guest_name') . '</li>' . "\n";
        }
        if ($this->controller->get('attended_luther')) {
            $txt .= '<li><strong>Guest Class Year:</strong> ' . $this->controller->get('attended_luther') . '</li>' . "\n";
        }
        if ($this->controller->get('attend_program')) {
            $txt .= '<li><strong>Tickets for Alumni Dinner:</strong> ' . ($this->controller->get('attend_program')) . '</li>' . "\n";
        }
        //new stuff////
        if ($this->controller->get('dinner_dietary_restrictions')) {
            $txt .= '<li><strong>Alumni Dinner dietary restrictions?</strong> ' . $this->controller->get('dinner_dietary_restrictions') . '</li>' . "\n";
        }
        // if ($this->controller->get('dinner_guests_names')) {
        //     $txt .= '<li><strong>Alumni Dinner Guest Names and Class Year (if applicable)</strong> ' . $this->controller->get('dinner_guests_names') . '</li>' . "\n";
        // }
        // if ($this->controller->get('vegetarian_guests')) {
        //     $txt .= '<li><strong>Do any of your guests require vegetarian meal?</strong> ' . $this->controller->get('vegetarian_guests') . '</li>' . "\n";
        // }
        // if ($this->controller->get('vegetarian_guests_names')) {
        //     $txt .= '<li><strong>Please list the vegetarian guests</strong> ' . $this->controller->get('vegetarian_guests_names') . '</li>' . "\n";
        // }
        // if ($this->controller->get('seating_preference')) {
        //     $txt .= '<li><strong>Please tell us with whom you wish to be seated</strong> ' . $this->controller->get('seating_preference') . '</li>' . "\n";
        // }///////////
        if ($this->controller->get('attend_50th_reception')) {
            $txt .= '<li><strong>Reservation for Friday\'s reception:</strong> ' . $this->controller->get('attend_50th_reception') . '</li>' . "\n";
        }
        if ($this->controller->get('attend_luncheon')) {
            $txt .= '<li><strong>Attend ' . $class_year . ' Reunion Luncheon :</strong> Class of ' . $class_year . '</li>' . "\n";
        }
        if ($this->controller->get('attend_dinner_50_to_25')) {
            $txt .= '<li><strong>Attend ' . $class_year . ' Reunion Dinner:</strong> Class of ' . $class_year . '</li>' . "\n";
        }
        if ($this->controller->get('attend_dinner_20_to_10')) {
            $txt .= '<li><strong>Attend ' . $class_year . ' Reunion Reception:</strong> Class of ' . $class_year . '</li>' . "\n";
        }
        if ($this->controller->get('attend_dinner_5')) {
            $txt .= '<li><strong>Attend ' . $class_year . ' Reunion Reception:</strong> Class of ' . $class_year . '</li>' . "\n";
        }
        if ($this->controller->get('ride_in_parade')) {
            $txt .= '<li><strong>Ride in the Parade:</strong> ' . $this->controller->get('ride_in_parade') . '</li>' . "\n";
        }
        
        
        $txt .= '</ul>' . "\n";
        $txt .= '</div>' . "\n";
        $this->set_value('confirmation_text', $txt);
        return $txt;
    }

    function run_error_checks() {
        if ($this->get_value('billing_address') == 'new' && (!$this->get_value('billing_street_address') || !$this->get_value('billing_city') || !$this->get_value('billing_state_province') || !$this->get_value('billing_zip') || !$this->get_value('billing_country') )) {
            $this->set_error('billing_address', 'Please enter your full billing address if the address you entered on the previous page was not the billing address for your credit card.');
        }


        // Process credit card
        if (!$this->_has_errors()) {
            $pf = new homecomingPF;
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
                    '?' => '-',
                    '<h3>' => '--------------------' . "\n\n",
                    '</h3>' => '',
                    '<br />' => "\n",
                );
                if (reason_unique_name_exists('homecoming_thank_you_blurb'))
                    $confirm_text_with_blurb = get_text_blurb_content('homecoming_thank_you_blurb') . $confirm_text;
                else
                    $confirm_text_with_blurb = '<p><strong>Thank you for registering for Homecoming!</strong></p>' . $confirm_text;

                //}
                $mail_text = str_replace(array_keys($replacements), $replacements, $confirm_text);
                $mail = new Email($this->controller->get('e-mail'), 'alumni@luther.edu', 'alumni@luther.edu', 'Luther College Homecoming Registration Confirmation', strip_tags($confirm_text_with_blurb), $confirm_text_with_blurb);
                $mail->send();

                $mail2 = new Email('alumni@luther.edu', 'noreply@luther.edu', 'noreply@luther.edu', 'New Homecoming Registration ' . date('mdY H:i:s'), strip_tags($mail_text), $mail_text);
                $mail2->send();
            }
        }
    }

    function where_to() {
        $refnum = $this->get_value('result_refnum');
        $text = $this->get_value('confirmation_text');
        reason_include_once('minisite_templates/modules/homecoming_registration/homecoming_confirmation.php');
        $gc = new HomecomingConfirmation;
        $hash = $gc->make_hash($text);
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