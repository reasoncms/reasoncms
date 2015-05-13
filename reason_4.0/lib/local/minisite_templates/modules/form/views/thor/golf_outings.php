<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');

$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'GolfOutingForm';

/**
 * Form view for the various golf outing alumni events.
 * 
 * This form view allows the form builder (Alumni) to have three, radio-button
 * payment options.
 *   - "Golf Registration"
 *   - "Dinner Registration"
 *   - "Brunch Registration"
 * 
 * Since the total is changed and displayed via javascript, a check is done during the
 * run_error_checks phase to make sure the math adds up and that the total wasn't manipulated
 * manually by the end-user.
 * 
 * 
 * @package reason_package_local
 * @subpackage thor_view
 * @author Steve Smith
 */
class GolfOutingForm extends CreditCardThorForm {
    function custom_init() 
    { 
        $model =& $this->get_model(); 
        $head_items = $model->get_head_items(); 
        $head_items->add_javascript('/reason/local/js/form/golf_outings.js');
        $head_items->add_javascript(JQUERY_UI_URL);
        $head_items->add_stylesheet(JQUERY_UI_CSS_URL);
    } 

    // style up the form and add comments et al
    function on_every_time() 
    {
        parent :: on_every_time(); 
        $this->add_element('same_billing', 'checkboxfirst', array('display_name' => 'Billing address is same as above'));
        $this->move_element('same_billing', 'after', 'credit_card_name');
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
        $total          = 0;
        $golf_cost      = 0;
        $dinner_cost    = 0;
        $lunch_cost     = 0;
        $brunch_cost    = 0;

        if ($this->get_element_name_from_label('Golf Registration'))
            $golf_cost = $this->_cleanup_cost($this->get_value_from_label('Golf Registration'));
        if ($this->get_element_name_from_label('Dinner Registration'))
            $dinner_cost = $this->_cleanup_cost($this->get_value_from_label('Dinner Registration'));
        if ($this->get_element_name_from_label('Lunch Registration'))
            $dinner_cost = $this->_cleanup_cost($this->get_value_from_label('Lunch Registration'));
        if ($this->get_element_name_from_label('Brunch Registration'))
            $brunch_cost = $this->_cleanup_cost($this->get_value_from_label('Brunch Registration'));

        $total = ($golf_cost + $dinner_cost + $lunch_cost + $brunch_cost);
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
                $this->set_error($this->get_element_name_from_label('Address'), 'Since you checked "Billing address same as above", the Address field is required');
            } else {
                $this->set_value('billing_street_address', $this->get_value_from_label('Address'));
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
        // Check for javascript manipulation of the payment amount
        // strip the dollar sign from the payment amount
        $pa = $this->get_value_from_label('Payment Amount');
        $pay_amount = substr($pa, 1);

        if ($pay_amount != floatval($this->get_amount()))
        {
            $pa_element = $this->get_element_name_from_label('Payment Amount');
            $this->set_error($pa_element, '<strong>Incorrect Payment Amount</strong>. The amount set in the payment amount field does not equal the cost for all chosen options. Please check your math or <a href="http://enable-javascript.com/" target="_blank">enable javascript</a> to have the form automatically fill in this field.<br>');
        }
        parent :: run_error_checks();
    }
}