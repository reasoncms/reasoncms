<?php

////////////////////////////////////////////////////////////////////////////////
//
//    Matt Ryan
//    2005-02-17
//
//    Work on the second page of the giving form
//
//    Modified for Luther - Steve Smith
//    search for SLS to find modifications
//    2010-09-13
//
//
////////////////////////////////////////////////////////////////////////////////

include_once(WEB_PATH.'reason/local/stock/giftclass.php');
include_once(TYR_INC . 'tyr.php');
reason_include_once('classes/repeat_transaction_helper.php');

class GiftPageThreeForm extends FormStep {

    var $_log_errors        = true;
    var $no_session         = array('credit_card_number');
    var $error;
    var $expense_budget_number  = '10-202-60500-51111';
    var $revenue_budget_number  = 'Online Giving';
    var $transaction_comment    = 'Online gift';
    var $is_in_testing_mode; // This gets set using the value of the THIS_IS_A_DEVELOPMENT_REASON_INSTANCE constant or if the 'tm' (testing mode) request variable evaluates to an integer
    // the usual disco member data
    var $thank_you_image_id = '';
    var $thanks_blurb       = '';
    var $one_hundred_blurb  = '';
    var $elements = array(
        'review_note' => array(
            'type' => 'comment',
            'text' => 'Gift overview',
        ),
        'e-receipt_note' => array(
            'type' => 'comment',
            'text' => ''
        ),
        'mail_receipt' => array(
            'type' => 'checkboxfirst',
            'display_name' => 'Please check if you\'d also like to receive a paper receipt via U.S. mail.'
        ),
        'installment_notification_note_1' => array(
            'type' => 'comment',
            'text' => '<h3>Receipts</h3><p>Luther will send you printed receipt(s) via U.S. Mail for your tax records each year in January.</p>',
        ),
        'installment_notification_note_1a' => array(
            'type' => 'comment',
            'text' => '<h3>Receipts</h3><p>Luther will send you printed receipt via U.S. Mail for your tax records within one week of this gift.</p>',
        ),
        'installment_notification_note_2' => array(
            'type' => 'comment',
            'text' => '<h3>Notification</h3><p>We will notify you via email after you successfully submit this form. Check the box below if you also want email confirmations for each future installment.</p>',
        ),
        'installment_notification' => array(
            'type' => 'checkboxfirst',
            'display_name' => 'Email me each time my card is charged.',
        ),
        'payment_note' => array(
            'type' => 'comment',
            'text' => '<h3>Payment Method</h3>',
        ),
        'credit_card_name' => array(
            'type' => 'text',
            'display_name' => 'Name as it appears on card',
            'size' => 35,
        ),
        'credit_card_number' => array(
            'type' => 'text',
            'size' => 35,
        ),
        'credit_card_type' => array(
            'type' => 'radio_no_sort',
            'options' => array('Visa' => 'Visa', 'MasterCard' => 'MasterCard', 'American Express' => 'American Express', 'Discover' => 'Discover', 'none'=>'none'),
            'display_name' => 'Credit Card Type'
        ),
        'credit_card_type_icon' => array(
            'type' => 'comment',
            'text' => "<i class='fa fa-cc-visa formCCType' id='visaIcon'></i><i class='fa fa-cc-mastercard formCCType' id='mastercardIcon'></i><i class='fa fa-cc-amex formCCType' id='amexIcon'></i><i class='fa fa-cc-discover formCCType' id='discoverIcon'></i>",
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
        'credit_card_security_code' => array(
            'type' => 'text',
            'size' => 4,
            'display_name' => 'Security Code',
        ),
    //     'billing_address' => array(
    //         'type' => 'radio_no_sort',
    //         'options' => array('entered' => 'Use address provided on previous page', 'new' => 'Use a different address'),
    //         'display_name' => 'Billing Address',
    //         'default' => 'entered',
    //     ),
    //     'billing_street_address' => array(
    //         'type' => 'textarea',
    //         'rows' => 3,
    //         'cols' => 35,
    //         'display_name' => 'Street Address',
    //     ),
    //     'billing_city' => array(
    //         'type' => 'text',
    //         'size' => 35,
    //         'display_name' => 'City',
    //     ),
    //     'billing_state_province' => array(
    //         'type' => 'state_province',
    //         'display_name' => 'State/Province',
    //         'include_military_codes' => true,
    //     ),
    //     'billing_zip' => array(
    //         'type' => 'text',
    //         'display_name' => 'Zip/Postal Code',
    //         'size' => 35,
    //     ),
    //     'billing_country' => array(
    //         'type' => 'country',
    //         'display_name' => 'Country',
    //     ),
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
    //     'billing_address',
    );
    var $actions = array(
        'previous_step' => 'Make Changes To Your Gift',
        'next_step' => 'Submit Your Gift For Processing',
    );
    var $helper;
    var $date_format = 'j F Y';
    var $display_name = 'Gift Review / Card Info';
    var $error_header_text = 'Please check your form.';
    var $installment_type_to_word = array('Monthly' => 'month', 'Quarterly' => 'quarter', 'Yearly' => 'year');
    var $database_transformations = array(
        'credit_card_number' => 'obscure_credit_card_number',
        'installment_start_date' => 'trim_hours_from_datetime',
    );

    // style up the form and add comments et al
    function on_every_time() {
        $this->set_comments('credit_card_security_code', form_comment('
            <p><a data-reveal-id="cvv2Iframe">What\'s this?</a></p>
            <div class="reveal-modal medium" id="cvv2Iframe" data-reveal="">
                <iframe height="300px" width="100%" src="https://www.cvvnumber.com/cvv.html"></iframe>
                <a class="close-reveal-modal">×</a>
            </div>'));

        $year = date('Y');
        $this->change_element_type('credit_card_expiration_year', 'numrange', array('start' => $year, 'end' => $year + 15, 'display_name' => 'Expiration Year'));
        $this->add_element_group('inline', 'expiration_group', array('credit_card_expiration_month', 'credit_card_expiration_year'), array('use_element_labels' => false, 'display_name' => 'Expiration mm/yyyy'));
        $this->move_element('expiration_group', 'before', 'credit_card_security_code');

        $this->box_class = 'StackedBox';

        if (!$this->controller->get('gift_amount')) {
            echo '<div id="giftFormSetupError">You can\'t complete this step without having set up a gift; please go back to <a href="?_step=GiftPageOneForm">Gift Info</a> and provide a gift amount.</div>';
            $this->show_form = false;
            return;
        }
        $this->change_element_type('e-receipt_note', 'comment', array('text' => '<h3>E-receipt</h3><p>As part of our efforts to make big and small
            changes to reduce our impact on the environment, Luther College will send an e-receipt only
            to '. $this->controller->get('email') . '. Thank you for doing your part to help us reduce our paper usage.</p>'));
        if ($this->controller->get('installment_type') == 'Onetime') {
            $this->remove_element('installment_notification_note_1');
            $this->remove_element('installment_notification_note_2');
            $this->remove_element('installment_notification');
            if (intval($this->controller->get('gift_amount')) <= 100){
                $this->remove_element('installment_notification_note_1a');
            } else {
                $this->remove_element('e-receipt_note');
                $this->remove_element('mail_receipt');
            }
        }
        if ($this->controller->get('installment_type') != 'Onetime') {
            $this->remove_element('installment_notification_note_1a');
            $this->remove_element('e-receipt_note');
            $this->remove_element('mail_receipt');
        }
        if (THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || !empty($this->_request['tm'])) {
            $this->is_in_testing_mode = true;
        } else {
            $this->is_in_testing_mode = false;
        }
    }

    function post_error_check_actions() {
        if ($this->show_form) {
            $this->instantiate_helper();
            $text = $this->get_brief_review_text();
            if (!$this->_has_errors() && $this->controller->get('installment_type') != 'Onetime') {
                $text .= build_gift_review_detail_output($this->helper, $this->date_format);
            }
            $text .= '<a title="Modify Gift" href="?_step=GiftPageOneForm">Modify Gift</a>' . "\n";
            $this->change_element_type('review_note', 'comment', array('text' => $text));
        }
    }

    function pre_show_form() {
        echo '<div id="giftForm" class="pageThree">' . "\n";
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

    function get_brief_review_text() {
        $txt = '<div id="reviewGiftOverview">' . "\n";
        if ($this->controller->get('installment_type') == 'Onetime') {
            $txt .= '<p class="summary">You have indicated that you would like to make a one time gift of $' . number_format($this->controller->get('gift_amount'), 2, '.', ',') . '</p>' . "\n";
        } else {
            $txt .= '<p class="summary">You have indicated that you would like to make a recurring gift of $' . number_format($this->controller->get('gift_amount'), 2, '.', ',') . ' per ' . $this->installment_type_to_word[$this->controller->get('installment_type')] . ', starting on ' . prettify_mysql_datetime($this->controller->get('installment_start_date'), $this->date_format);
            if ($this->controller->get('installment_end_date') != 'indefinite') {
                $txt .= ' and ending on ' . date($this->date_format, $this->helper->get_last_repeat_timestamp());
            } else {
                $txt .= ' with no designated end date';
            }
            $txt .= '.</p>' . "\n";
        }
        $txt .= '</div>' . "\n";
        return $txt;
    }

    function get_confirmation_text() {

        $txt = '<div id="reviewGiftOverview">' . "\n";

        //$txt .= '<p class="printConfirm">Print this confirmation for your records.</p>' . "\n";
        if (reason_unique_name_exists('giving_thank_you_email_image')) {
            $this->thank_you_image_id = id_of('giving_thank_you_email_image');
            $txt .= "<img src='http://".$_SERVER['SERVER_NAME'].WEB_PHOTOSTOCK.$this->thank_you_image_id.".jpg' alt='Students Holding Thank You Sign'>";
        }
        if (reason_unique_name_exists('giving_form_thank_you_blurb')) {
            $this->thanks_blurb = strip_tags(get_text_blurb_content('giving_form_thank_you_blurb'));
            $txt .= '<p style="color: #444444; font-family: inherit; font-size: 1.05rem; font-weight: 300; text-align: left; line-height: 1.7; text-rendering: optimizelegibility; margin: 0 0 10px; padding: 0;" align="left">';
            $txt .= $this->thanks_blurb . "</p>\n";
	   }
        if ((intval($this->controller->get('gift_amount')) <= 100) && !$this->get_value('mail_receipt')) {
            if (reason_unique_name_exists('giving_form_100_dollars')){
                $this->one_hundred_blurb = get_text_blurb_content('giving_form_100_dollars');
                $txt .= $this->one_hundred_blurb . "\n";
			}
        }
        $txt .= '<p>Luther College is, for tax deduction purposes, a 501(c)(3) organization.</p>' . "\n";
        $txt .= '<ul>' . "\n";
        $txt .= '<li><strong>Date:</strong> ' . date($this->date_format) . '</li>' . "\n";
        $txt .= '</ul>' . "\n";
        $txt .= '<h4>Your Information</h4>' . "\n";
        $txt .= '<ul>' . "\n";
        $txt .= '<li><strong>Name:</strong> ' . $this->controller->get('first_name') . ' ' . $this->controller->get('last_name') . '</li>' . "\n";
        if (($this->controller->get('spouse_first_name') != 'First') || ($this->controller->get('spouse_last_name') != 'Last')) {
            $txt .= '<li><strong>Spouse Name:</strong> ';
        }
        if ($this->controller->get('spouse_first_name') != 'First') {
            $txt .= $this->controller->get('spouse_first_name') . ' ';
        }
        if ($this->controller->get('spouse_last_name') != 'Last') {
            $txt .= $this->controller->get('spouse_last_name') . '</li>' . "\n";
        }
        $txt .= '<li>'."\n".'<strong>Address:</strong>' . "\n" . $this->controller->get('address_1') . "\n" . $this->controller->get('address_2') . "\n" . $this->controller->get('city') . ' ' . $this->controller->get('state_province') . ' ' . $this->controller->get('zip') . "\n" . $this->controller->get('country') . "\n" .'</li>' . "\n";
        $txt .= '<li><strong>' . $this->controller->get('phone_type') . ' Phone:</strong> ' . $this->controller->get('phone') . '</li>' . "\n";
        $txt .= '<li><strong>E-mail:</strong> ' . $this->controller->get('email') . '</li>' . "\n";
        $txt .= '<li><strong>Luther Affiliation:</strong> ';
            $txt .= implode(', ', $this->controller->get('luther_affiliation')) . '</li>' . "\n";
        if ($this->controller->get('class_year')) {
            $txt .= '<li><strong>Class:</strong> ' . $this->controller->get('class_year') . '</li>' . "\n";
        }
        if ($this->controller->get('estate_plans')){
            foreach ($this->controller->get('estate_plans') as $plan) {
                if ($plan == 'have_estate_plans') {
                    $txt .= '<li><strong>Estate Plans:</strong> I\'ve included Luther in my estate plans</li>' . "\n";
                }
                if ($plan == 'send_estate_info') {
                    $txt .= '<li><strong>Estate Information:</strong> I would like information about including Luther in my estate planning</li>' . "\n";
                }
            }
        }
        $txt .= '</ul>' . "\n";
        $txt .= '<h4>Gift Details</h4>' . "\n";
        $txt .= '<ul>' . "\n";
        if ($this->controller->get('installment_type') == 'Onetime') {
            $txt .= '<li><strong>One time gift:</strong> $' . number_format($this->controller->get('gift_amount'), 2, '.', ',') . '</li>' . "\n";
            if ($this->controller->get('mail_receipt') == true)
                $txt .= '<li><strong>Mail Receipt:</strong> I would like a paper receipt via U.S. mail</li>' . "\n";
        } else {
            $txt .= '<li><strong>Installment gifts:</strong> $' . number_format($this->controller->get('gift_amount'), 2, '.', ',') . ' per ' . $this->installment_type_to_word[$this->controller->get('installment_type')] . ', starting on ' . prettify_mysql_datetime($this->controller->get('installment_start_date'), $this->date_format);
            if (!$this->helper->repeats_indefinitely()) {
                $txt .= ' and ending on ' . date($this->date_format, $this->helper->get_last_repeat_timestamp());
            }
            $txt .= '.</li>' . "\n";
        }

        // Build the Designation text
        // if multiple designation areas
        $designation    = $this->controller->get('gift_designation');
        $naa_opts       = $this->controller->get_form('GiftPageOneForm')->naa_opts;
        if ( $this->controller->get('split_gift') == true && $this->controller->get('split_designations') ){
            $txt .= "<li><strong>Designation Details:</strong></li>\n";
            $txt .= "<ul>\n";
            $splits = json_decode($this->controller->get('split_designations'));
            // pray($this->controller);
            if ( isset($splits->{'annual_fund'}) ){
                $txt .= "<li><strong>Annual Fund:</strong> $".$splits->{'annual_fund'}[0]."</li>\n";
            }
            if ( isset($splits->{'norse_athletic_association'}) ){
                $txt .= "<li><strong>Norse Athletic Association:</strong> $".$splits->{'norse_athletic_association'}[0]." – ".$naa_opts[$splits->{'naa_details'}]."</li>\n";
            }
            if ( isset($splits->{'scholarship_fund'}) ){
                $txt .= "<li><strong>Scholarship Fund:</strong> $".$splits->{'scholarship_fund'}[0]."</li>\n";
            }
            if ( isset($splits->{'__other__'}) ){
                $txt .= "<li><strong>Other:</strong> $".$splits->{'__other__'}[0];
                if ( $splits->{'other_details'} ){
                    $txt .= " – ".$splits->{'other_details'}."</li>\n";
                } else {
                    $txt .= "</li>\n";
                }
            }
            $txt .= "</ul>\n";
        } else {
            // or the value of a signal designation is an naa_opts key, then the gift is for the NAA,
            // otherwise the values are 'annual_fund' or 'scholarship_fund', any other text comes from the
            // other_details text and can be anything
            if ( array_key_exists($designation, $naa_opts) ) {
                $desig_txt = "Norse Athletic Association – {$naa_opts[$designation]}";
            }
            elseif ( $designation == "annual_fund" )
                $desig_txt = "Annual Fund";
            elseif ( $designation == "scholarship_fund" )
                $desig_txt = "Scholarship Fund";
            else
                $desig_txt = "Other – {$designation}";

            $txt .= "<li><strong>Designation:</strong> {$desig_txt}</li>\n";
        }
        if ($this->controller->get('comments_special_instructions'))
            $txt .= "<li><strong>Comments / Special Instructions:</strong> {$this->controller->get('comments_special_instructions')}</li>\n";
        if ($this->controller->get('match_gift') && $this->controller->get('employer_name')) {
            $txt .= '<li><strong>Employer Matching:</strong> My employer, ' . strip_tags($this->controller->get('employer_name')) . ', will match my gift.</li>' . "\n";
        }
        if ($this->controller->get('gift_prompt')) {
            $prompt = strip_tags($this->controller->get('gift_prompt'));
            switch ($prompt) {
                case 'mailing':
                    $prompt_txt = 'Received a mailing';
                    break;
                case 'email':
                    $prompt_txt = 'Received an email';
                    break;
                case 'phonathon':
                    $prompt_txt = 'Received a Phonathon call';
                    break;
                case 'staff_visit':
                    $prompt_txt = 'Development staff visit';
                    if ($this->controller->get('gift_prompt_details'))
                        $prompt_txt .= ' - ' . strip_tags($this->controller->get('gift_prompt_details'));
                    break;
                case 'other':
                    $prompt_txt = 'Other';
                    if ($this->controller->get('gift_prompt_details'))
                        $prompt_txt .= ' – ' . strip_tags($this->controller->get('gift_prompt_details'));
                    break;
            }
            $txt .= '<li><strong>Gift Prompt:</strong> '.$prompt_txt.'</li>' . "\n";
        }

        if ($this->controller->get('dedication') && $this->controller->get('dedication_details')) {
            $txt .= '<li><strong>Dedication:</strong> This gift is in ';
            if ($this->controller->get('dedication') == 'Memory') {
                $txt .= 'memory';
            }
            if ($this->controller->get('dedication') == 'Honor') {
                $txt .= 'honor';
            }
            if ($this->controller->get('dedication_details'));
                $txt .= ' of ' . $this->controller->get('dedication_details');
            $txt .= '</li>' . "\n";
        }

        if ($this->get_value('result_refnum')) {
            $txt .= '<li><strong>Transaction Reference Number:</strong> ' . $this->get_value('result_refnum') . '</li>' . "\n";
        }
        if ($this->get_value('result_authcode')) {
            $txt .= '<li><strong>Transaction Authorization Code:</strong> ' . $this->get_value('result_authcode') . '</li>' . "\n";
        }
        if ($this->controller->get('installment_type') != 'Onetime') {
            if ($this->get_value('installment_notification') == false) {
                $insert_txt = 'not ';
            } else {
                $insert_txt = '';
            }
            $txt .= '<li><strong>Transaction Notifications:</strong> You will ' . $insert_txt . 'receive email notifications when installments occur on this gift</li>' . "\n";
        }
        $txt .= '</ul>' . "\n";

        $txt .= '</div>' . "\n";
        return $txt;
    }

    function instantiate_helper() {
        $pass_vals = array('gift_amount', 'installment_type', 'installment_start_date', 'installment_end_date');
        $params_array = array();
        foreach ($pass_vals as $val) {
            if ($this->controller->get($val)) {
                $params_array[$val] = $this->controller->get($val);
            }
        }
        $this->helper = build_gift_transaction_helper($params_array);
    }

    function run_error_checks() {
        // if ($this->get_value('billing_address') == 'new'
        //         && (!$this->get_value('billing_street_address')
        //         || !$this->get_value('billing_city')
        //         || !$this->get_value('billing_state_province')
        //         || !$this->get_value('billing_zip')
        //         || !$this->get_value('billing_country') )) {
        //     $this->set_error('billing_address', 'Please enter your full billing address if the address you entered on the previous page was not the billing address for your credit card.');
        // }
        // if ($this->get_value('billing_address') == 'entered') {
        //     $address = $this->controller->get('address_1') .' '. $this->controller->get('address_1');
        //     $this->set_value('billing_street_address', $address);
        //     $this->set_value('billing_city', $this->controller->get('city'));
        //     $this->set_value('billing_state_province', $this->controller->get('state_province'));
        //     $this->set_value('billing_zip', $this->controller->get('zip'));
        // }

        if ($this->controller->get('installment_type') != 'Onetime') {
            $expire_timestamp = mktime(0, 0, 0, $this->get_value('credit_card_expiration_month') + 1, 1, $this->get_value('credit_card_expiration_year'));
            if ($expire_timestamp < strtotime($this->controller->get('installment_start_date'))) {
                $this->set_error('credit_card_expiration_month', 'The expiration date you entered is before the first installment of your gift. Please use a credit card that will be valid as of the first installment.');
            }
        }

        // Process credit card
        if (!$this->_has_errors()) {
            $pf = new gift;
            if ($this->controller->get('installment_type') == 'Onetime') {
                $immediate_amount = $this->controller->get('gift_amount');
            } else {
                $immediate_amount = 0;
            }
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
                    $immediate_amount,
                    $this->get_value('credit_card_number'),
                    $expiration_mmyy,
                    $this->get_value('credit_card_security_code'),
                    $this->revenue_budget_number,
                    $this->get_value('credit_card_name'),
                    $this->expense_budget_number,
                    $this->transaction_comment
            );

            $this->instantiate_helper();
            $this->helper->build_transactions_array();

            if ($this->controller->get('installment_type') != 'Onetime') {
                // A value of zero for $installment_quantity indicates no end date.
                if ($this->controller->get('installment_end_date') == 'indefinite') {
                    $installment_quantity = 0;
                } else {
                    $installment_quantity = $this->helper->get_repeat_quantity();
                }

                // PayPeriod is one of WEEK, BIWK, SMMO, FRWK, MONT, QTER, SMYR, QTER.
                $repeat_types = array('Monthly' => 'MONT', 'Quarterly' => 'QTER', 'Yearly' => 'YEAR');
                $pf_repeat_type = $repeat_types[$this->controller->get('installment_type')];
                if ($this->get_value('installment_notification') == true) {
                    $email = $this->controller->get('email');
                } else {
                    $email = '';
                }
                $pf->set_recur(
                        $this->controller->get('gift_amount'),
                        date('mdY', strtotime($this->controller->get('installment_start_date'))),
                        $installment_quantity,
                        $pf_repeat_type,
                        $email
                );
            }
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

                $confirm_text .= $this->get_confirmation_text();
                //$confirm_text .= build_gift_review_detail_output($this->helper, $this->date_format);

                $this->set_value('confirmation_text', $confirm_text);
                $pf->set_confirmation_text($confirm_text);

                // prep emails
                $replacements = array(
                    // '<th class="col1">Date</th>' => '',
                    // '<th class="col1">Year</th>' => '',
                    // '<th>Amount</th>' => '',
                    // '</td><td>' => ': ',
                    '–' => '-',
                    "Your Information" => "Donor Info",
                    '<p>Luther College is, for tax deduction purposes, a 501(c)(3) organization.</p>' => '',
                    '<p>Thank You!</p>' => '',
                    '<br />' => "\n",
                );
                $reg_replacements = array(
                    '/<img .*>/'        => '',
                    '/<p style.*<\/p>/' => '',
                    '/<p>This e-receipt.*College.<\/p>/' => '',
                );
                $mail_text = preg_replace(array_keys($reg_replacements), $reg_replacements, $confirm_text);
                $mail_text = str_replace(array_keys($replacements), $replacements, $confirm_text);
                $email_to_development = new Email('waskni01@luther.edu', 'noreply@luther.edu', 'noreply@luther.edu', 'New Online Gift ' . date('mdY H:i:s'), strip_tags($mail_text), $mail_text);
                $email_to_development->send();
                $email_to_giver = new Email($this->controller->get('email'), 'giving@luther.edu', 'giving@luther.edu', 'Luther College Online Gift Confirmation' . date('m.d.y: H:i:s'), strip_tags($mail_text), $confirm_text);
                $email_to_giver->send();
                if ($this->controller->get('estate_plans')) {
                   $email_to_estate_plans = new Email('kelly.wedmann@luther.edu', 'noreply@luther.edu', 'noreply@luther.edu', 'New Online Gift ' . date('mdY H:i:s'), strip_tags($mail_text), $mail_text);
                   $email_to_estate_plans->send();
                }
            }
        }
    }

    function where_to() {
        $refnum = $this->get_value('result_refnum');
        $text = $this->get_value('confirmation_text');
        reason_include_once('minisite_templates/modules/gift_form/gift_confirmation.php');
        $gc = new GiftConfirmation;
        $hash = $gc->make_hash($text);
        connectDB(REASON_DB);
        $url = get_current_url();
        $parts = parse_url($url);
        $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?r=' . $refnum . '&h=' . $hash;
        return $url;
    }

}

function build_gift_transaction_helper($params_array) {
    $helper = new repeatTransactionHelper();

    if ($params_array['installment_type'] == 'Onetime') {
        $helper->set_single_time_amount($params_array['gift_amount']);
        $helper->set_single_time_date(date('Y-m-d'));
    } else {
        $helper->set_repeat_amount($params_array['gift_amount']);
        $helper->set_repeat_type($params_array['installment_type']);
        $helper->set_repeat_start_date($params_array['installment_start_date']);
        $helper->set_end_date($params_array['installment_end_date']);
    }
    return $helper;
}

function build_gift_review_detail_output($helper, $date_format = 'j F Y') {
    // A yearly totals disclose link is inserted here by JS
    $txt = '<div id="reviewGiftDetails">' . "\n";
    $txt .= '<h3 id="yearlyTotalsHeading">Yearly Totals for This Gift</h3>' . "\n\n";
    $txt .= '<h4>Per calendar year:</h4>';
    $txt .= '<table cellpadding="0" cellspacing="0" border="0" summary="Total amounts given, by calendar year">' . "\n";
    $txt .= '<tr><th class="col1">Year</th><th>Amount </th></tr>' . "\n";
    $cy_gifts = $helper->get_calendar_year_totals();
    if ($helper->repeats_indefinitely()) {
        $break = false;
        $previous_amount_text = '';
        $i = 0;
        foreach ($cy_gifts as $year => $amount) {
            $amount_text = number_format($amount * .01, 2, '.', ',');
            if (empty($previous_amount_text) || $previous_amount_text != $amount_text) {
                $year_text = $year;
                $amount_post = '';
            } else {
                $year_text = 'Subsequently';
                $amount_post = ' per calendar year';
                $break = true;
            }
            $txt .= '<tr><td class="col1">' . $year_text . '</td><td>$' . $amount_text . $amount_post . '</td></tr>' . "\n";
            if ($break || $i > 500) { // second part is to avoid any possibility of an infinite loop
                break;
            }
            $previous_amount_text = $amount_text;
            $i++;
        }
    } else { // definite gift
        foreach ($cy_gifts as $year => $amount) {
            $txt .= '<tr><td class="col1">' . $year . '</td><td>$' . number_format($amount * .01, 2, '.', ',') . '</td></tr>' . "\n";
        }
    }
    $txt .= '</table>' . "\n";
    $txt .= '<h4>Per Luther fiscal year:</h4>';
    $txt .= '<table cellpadding="0" cellspacing="0" border="0" summary="Total amounts given, by Luther fiscal year">' . "\n";
    $txt .= '<tr><th class="col1">Year</th><th>Amount</th></tr>' . "\n";
    $fy_gifts = $helper->get_fiscal_year_totals();

    if ($helper->repeats_indefinitely()) {
        $break = false;
        $previous_amount_text = '';
        $i = 0;
        foreach ($fy_gifts as $start_year => $amount) {
            $amount_text = number_format($amount * .01, 2, '.', ',');
            if (empty($previous_amount_text) || $previous_amount_text != $amount_text) {
                $year_text = 'June ';
                $year_text .= $start_year;
                $year_text .= ' &#8211; May ';
                $year_text .= $start_year + 1;
                $amount_post = '';
            } else {
                $year_text = 'Subsequently';
                $amount_post = ' per Luther fiscal year';
                $break = true;
            }
            $txt .= '<tr><td class="col1">' . $year_text . '</td><td>$' . $amount_text . $amount_post . '</td></tr>' . "\n";
            if ($break || $i > 500) { // second part is to avoid any possibility of an infinite loop
                break;
            }
            $previous_amount_text = $amount_text;
            $i++;
        }
    } else {
        foreach ($fy_gifts as $start_year => $amount) {
            $txt .= '<tr><td class="col1">';
            $txt .= 'June ';
            $txt .= $start_year;
            $txt .= ' — May ';
            $txt .= $start_year + 1;
            $txt .= '</td><td>$' . number_format($amount * .01, 2, '.', ',') . '</td></tr>' . "\n";
        }
    }
    $txt .= '</table>' . "\n";
    $txt .= '<p class="givingTotalsDisclaimer">Amounts listed above reflect <em>this gift only</em>, not your overall giving history. Please contact us for giving history information.</p>';
    $txt .= '</div>' . "\n";
    return $txt;
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

