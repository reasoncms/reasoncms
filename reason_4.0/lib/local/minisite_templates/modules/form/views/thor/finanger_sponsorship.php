<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');

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
        $model =& $this->get_model();
        $head_items = $model->get_head_items();
        $head_items->add_javascript('/reason/local/js/form/finanger_sponsorship.js');
        $head_items->add_javascript(JQUERY_UI_URL);
        $head_items->add_stylesheet(JQUERY_UI_CSS_URL);
    }

    function on_every_time()
    {
        $this->add_element('same_billing', 'checkboxfirst');
        $this->move_element('same_billing', 'after', 'credit_card_name');
        $this->set_display_name('same_billing', 'Billing address is same as above');
        parent::on_every_time();
    }

    /**
     * Cleans any extra characters form the hidden field values and returns an int
     *
     * @param string The cost string
     * @return int The cost cleaned (all chars removed and an int returned)
     */
    private function _cleanup_cost($label)
    {
        if (preg_match('/([\d\.,]+)/',$label, $match))
            $this->set_value('payment_amount', '$'.$match[1]);
            return($match[1]);
    }

    /**
     * Figure the total cost based on the options
     *
     * @return string The total for all charges
     */
    function get_amount()
    {
        $golf           = 105;
        $brunch_dinner  = 45;
        $dinner_only    = 30;
        $total          = 0;


        for ($i=1; $i < 9; $i++) {
            $package = $this->get_value_from_label('Package ' . $i);
            switch ( $package ) {
                case 'golf':
                    $total = $total + $golf;
                    break;
                case 'brunch and dinner':
                    $total = $total + $brunch_dinner;
                    break;
                case 'dinner':
                    $total = $total + $dinner_only;
                    break;

                default:
                    $total = $total;
                    break;
            }
        }
        return $total;
    }

    function pre_error_check_actions()
    {
        // make the address fields required if "Same address as above is selected"
        // set the billing info based on that info
        if ($this->get_value('same_billing') == true)
        {
            // remove from required so we don't show redundant error messages
            $this->remove_required('billing_street_address');
            $this->remove_required('billing_city');
            $this->remove_required('billing_state_province');
            $this->remove_required('billing_zip');

            // Add error messages if "Same address as above" is checked
            // if the information is not provided check for address fields
            if ($this->get_value_from_label('Address') == "")
            {
                $this->set_error($this->get_element_name_from_label('Address'), 'Since you checked "Billing address same as above", the Street Address field is required');
            } else {
                $this->set_value('billing_street_address', $this->get_value_from_label('Street Address'));
            }
            if ($this->get_value_from_label('City') == "")
            {
                $this->set_error($this->get_element_name_from_label('City'), 'Since you checked "Billing address same as above", the City field is required');
            } else {
                $this->set_value('billing_city', $this->get_value_from_label('City'));
            }
            if ($this->get_value_from_label('State/Province') == "")
            {
                $this->set_error($this->get_element_name_from_label('State/Province'), 'Since you checked "Billing address same as above", the State/Province field is required');
            } else {
                $this->set_value('billing_state_province', $this->get_value_from_label('State/Province'));
            }
            if ($this->get_value_from_label('Zip/Postal Code') == "")
            {
                $this->set_error($this->get_element_name_from_label('Zip/Postal Code'), 'Since you checked "Billing address same as above", the Zip/Postal Code field is required');
            } else {
                $this->set_value('billing_zip', $this->get_value_from_label('Zip/Postal Code'));
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
            $this->set_error($donation_element, "<br>Please enter a number for a donation amount");
        }

        // Check for javascript manipulation of the payment amount
        // strip the dollar sign from the payment amount
        $pa = $this->get_value_from_label('Payment Amount');
        $pay_amount = substr($pa, 1);

        if ($pay_amount != floatval($this->get_amount()))
        {
            $pa_element = $this->get_element_name_from_label('Payment Amount');
            $this->set_error($pa_element, '<br><strong>Incorrect Payment Amount</strong>. The amount set in the payment amount field does not equal the cost for all chosen options. Please check your math or <a href="http://enable-javascript.com/" target="_blank">enable javascript</a> to have the form automatically fill in this field.<br>');
        }
        parent :: run_error_checks();
    }
}
