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

        parent::on_every_time();
    }

    /**
     * Figure the total cost based on the options
     * 
     *
     * @return string The total for all charges
     */
    function get_amount()
    {
        $other_amount           = 0;
        $total                  = 0;

        // All sponsorship levels should have a dollar sign followed by the value at the end (e.g. Tee - $150)
        if (preg_match('/\$(\d+)$/', $this->get_value_from_label('Please indicate the sponsorship level'), $matches))
		{
        	$total = floatval($matches[1]);	
		}

        if ($this->get_value_from_label('Additional Donation'))
        {
            $other_amount = $this->get_value_from_label('Additional Donation');
        }
        $total = $total + $other_amount;
        return $total;
    }


    function run_error_checks()
    {
        // set error if a first name is set, but no package and dinner
        $sponsorship_value  = $this->get_value_from_label('Please indicate the sponsorship level');
        $sponsorship_element  = $this->get_element_name_from_label('Please indicate the sponsorship level');
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
}
