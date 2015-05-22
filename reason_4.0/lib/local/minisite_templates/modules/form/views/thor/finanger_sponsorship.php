<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'reason/local/stock/pfproclass.php');

$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'FinangerSponsorshipForm';

/**
 * Form view for the various Finanger golf alumni event.
 *
 *
 * @package reason_package_local
 * @subpackage thor_view
 * @author Steve Smith
 */
class FinangerSponsorshipForm extends CreditCardThorForm {
    function custom_init()
    {
    	parent::custom_init();
        $model =& $this->get_model();
        $head_items = $model->get_head_items();
        $head_items->add_javascript('/reason/local/js/form/finanger_sponsorship.js');
        $head_items->add_javascript(JQUERY_UI_URL);
        $head_items->add_stylesheet(JQUERY_UI_CSS_URL);
    }

    function on_every_time()
    {
        $this->change_element_type($this->get_element_name_from_label('State'), 'state_province');
        $this->change_element_type($this->get_element_name_from_label('Additional Donation'), 'money');
        $this->change_element_type($this->get_element_name_from_label('Payment Amount'), 'money');

        $this->change_element_type($this->get_element_name_from_label('Sign 1'), 'textarea',
            array('display_name' => 'Text for Tee/Green Sponsorship 1', 'comments' => 'Please indicate the exact wording you would like to appear on sign 1'));
        $this->change_element_type($this->get_element_name_from_label('Sign 2'), 'textarea',
            array('display_name' => 'Text for Tee/Green Sponsorship 2', 'comments' => 'Please indicate the exact wording you would like to appear on sign 2'));
        $this->change_element_type($this->get_element_name_from_label('Sign 3'), 'textarea',
            array('display_name' => 'Text for Tee/Green Sponsorship 3', 'comments' => 'Please indicate the exact wording you would like to appear on sign 3'));
        $this->change_element_type($this->get_element_name_from_label('Sign 4'), 'textarea',
            array('display_name' => 'Text for Tee/Green Sponsorship 4', 'comments' => 'Please indicate the exact wording you would like to appear on sign 4'));
        $this->change_element_type($this->get_element_name_from_label('Sign 5'), 'textarea',
            array('display_name' => 'Text for Tee/Green Sponsorship 5', 'comments' => 'Please indicate the exact wording you would like to appear on sign 5'));
        parent::on_every_time();
    }

    /**
     * Figure the total cost based on the options
     *
     * @return string The total for all charges
     */
    function get_amount()
    {
        $sponsorships_amount    = 100;
        $other_amount           = 0;
        $total                  = 0;

        $sponsorships = $this->get_value_from_label('Please indicate number of sponsorships you wish to purchase');
        switch ( $sponsorships ) {
                case 'One - $100':
                    $total = $sponsorships_amount * 1;
                    break;
                case 'Two - $200':
                    $total = $sponsorships_amount * 2;
                    break;
                case 'Three - $300':
                    $total = $sponsorships_amount * 3;
                    break;
                case 'Four - $400':
                    $total = $sponsorships_amount * 4;
                    break;
                case 'Five - $500':
                    $total = $sponsorships_amount * 5;
                    break;
            }
        if ($this->get_value_from_label('Additional Donation')){
            $other_amount = $this->get_value_from_label('Additional Donation');
        }
        $total = $total + $other_amount;
        return $total;
    }

    function pre_error_check_actions()
    {

        $sponsorship_value  = $this->get_value_from_label('Please indicate number of sponsorships you wish to purchase');
        if ( $sponsorship_value ) {
            switch ( $sponsorship_value ) {
                case 'One - $100':
                    if ( !$this->get_value_from_label('Sign 1') ) {
                        $this->set_error($this->get_element_name_from_label('Sign 1'), '<br>Please provide text for Sign 1');
                    }
                    break;
                case 'Two - $200':
                    if ( !$this->get_value_from_label('Sign 2') ) {
                        $this->set_error($this->get_element_name_from_label('Sign 1'), '<br>Please provide text for Sign 1');
                        $this->set_error($this->get_element_name_from_label('Sign 2'), '<br>Please provide text for Sign 2');
                    }
                    break;
                case 'Three - $300':
                    if ( !$this->get_value_from_label('Sign 3') ) {
                        $this->set_error($this->get_element_name_from_label('Sign 1'), '<br>Please provide text for Sign 1');
                        $this->set_error($this->get_element_name_from_label('Sign 2'), '<br>Please provide text for Sign 2');
                        $this->set_error($this->get_element_name_from_label('Sign 3'), '<br>Please provide text for Sign 3');
                    }
                    break;
                case 'Four - $400':
                    if ( !$this->get_value_from_label('Sign 4') ) {
                        $this->set_error($this->get_element_name_from_label('Sign 1'), '<br>Please provide text for Sign 1');
                        $this->set_error($this->get_element_name_from_label('Sign 2'), '<br>Please provide text for Sign 2');
                        $this->set_error($this->get_element_name_from_label('Sign 3'), '<br>Please provide text for Sign 3');
                        $this->set_error($this->get_element_name_from_label('Sign 4'), '<br>Please provide text for Sign 4');
                    }
                    break;
                case 'Five - $500':
                    if ( !$this->get_value_from_label('Sign 5') ) {
                        $this->set_error($this->get_element_name_from_label('Sign 1'), '<br>Please provide text for Sign 1');
                        $this->set_error($this->get_element_name_from_label('Sign 2'), '<br>Please provide text for Sign 2');
                        $this->set_error($this->get_element_name_from_label('Sign 3'), '<br>Please provide text for Sign 3');
                        $this->set_error($this->get_element_name_from_label('Sign 4'), '<br>Please provide text for Sign 4');
                        $this->set_error($this->get_element_name_from_label('Sign 5'), '<br>Please provide text for Sign 5');
                    }
                    break;
                default:
                    break;
            }
        }
        parent::pre_error_check_actions();
    }

    function run_error_checks()
    {
        // set error if a first name is set, but no package and dinner
        $sponsorship_value  = $this->get_value_from_label('Please indicate number of sponsorships you wish to purchase');
        $sponsorship_element  = $this->get_element_name_from_label('Please indicate number of sponsorships you wish to purchase');
        $donation_value     = $this->get_value_from_label('Additional Donation');
        $donation_element     = $this->get_element_name_from_label('Additional Donation');
        if ( !$sponsorship_value && !$donation_value ) {
            $this->set_error($sponsorship_element, "<br>Either a Tee/Green sponsorship or a donation is required.");
        }

        if ( $donation_value && !is_numeric($donation_value) ) {
            $this->set_error($donation_element, "<br>Please enter a valid dollar amount for a donation amount");
        }

        // Check for javascript manipulation of the payment amount
        // strip the dollar sign from the payment amount
        $pa = $this->get_value_from_label('Payment Amount');
        if ($pa != floatval($this->get_amount()))
        {
            $pa_element = $this->get_element_name_from_label('Payment Amount');
            $this->set_error($pa_element, '<br><strong>Incorrect Payment Amount</strong>. The amount set in the payment amount field does not equal the cost for all chosen options. Please check your math or <a href="http://enable-javascript.com/" target="_blank">enable javascript</a> to have the form automatically fill in this field.<br>');
        }
        parent :: run_error_checks();
    }

    function email_form_data()
    {
         //parent :: email_form_data();
         $model =& $this->get_model();
         $sender = 'www@luther.edu';
         $recipient = $model->get_email_of_recipient();
         $recipients = explode(',',$recipient);

         if (in_array('@', $recipients)===FALSE){
                foreach($recipients as $recipient){
                     $recipient .= '@luther.edu';
                }
         }

         $subject = 'Response to Form: ' . $model->get_form_name();
         $heading = "<h2><strong>".$model->get_form_name()."</strong></h2>";
         $email_data = $model->get_values_for_email();
         $values = "\n";
         if ($model->should_email_data()){
                foreach($email_data as $key => $val){
                      if (!empty($this->get_value_from_label($val['label']))){
                             $values .= sprintf("\n<strong>%s:</strong>\t   %s\n", $val['label'], $val['value']);
                 //end form one, begin credit card form
                             if ($val['label']=='Payment Amount'){
                                   $val['label']='Payment Method';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('credit_card_type'));
                                   $val['label']='Name as it appears on card';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('credit_card_name'));
                                   $val['label']='Billing Street Address';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_street_address'));
                                   $val['label']='Billing City';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_city'));
                                   $val['label']='Billing State/Province';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_state_province'));
                                   $val['label']='Billing Zip/Postal Code';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_city'));
                                   $val['label']='Billing Country';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_country'));

                             }

                      }

                }
          }

          $submission_time = date("Y-m-d H:i:s");

          $values .= sprintf("\n<strong>%s:</strong>\t    %s\n",'Form Submission Time', $submission_time);
          $vl = nl2br($values);
          $html_body =$heading . $vl;
          $txt_body = html_entity_decode(strip_tags($html_body));
          $mailer = new Email($recipient, $sender, $sender, $subject, $txt_body, $html_body);
          $mailer->send();

    }  //close email function
}
