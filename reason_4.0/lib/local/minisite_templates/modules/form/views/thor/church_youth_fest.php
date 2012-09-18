<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');

$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'ChurchYouthFestPaymentForm';

/**
 * Use only for running test payments on the live site. This is only for the form creator to see the process
 * all the way through. Switch to credit_card_payment for live transactions
 *
 * @package reason_package_local
 * @subpackage thor_view
 * * @author Steve Smith
 */
class ChurchYouthFestPaymentForm extends CreditCardThorForm {
    

    // style up the form and add comments et al
    function on_every_time() {

      parent :: on_every_time(); 
      
      $this->change_element_type($this->get_element_name_from_label('State'), 'state');
      $this->set_element_properties($this->get_element_name_from_label('Zipcode'), array('size'=>5));
      $this->set_element_properties($this->get_element_name_from_label('# of Youth @ $25.00'), array('size'=>3));
      $this->set_element_properties($this->get_element_name_from_label('# of Sponsors @ $20.00'), array('size'=>3));
      $this->set_element_properties($this->get_element_name_from_label('# for Ropes Course @ $5.00'), array('size'=>3));
      $this->set_value($this->get_element_name_from_label('Revenue Budget Number'), '10-000-38101-45900');
      $this->set_value($this->get_element_name_from_label('Expense Budget Number'), '10-140-38101-51111');
    }

    function get_amount(){
        $youth = $this->get_value_from_label('# of Youth @ $25.00');
        $sponsors = $this->get_value_from_label('# of Sponsors @ $20.00');
        $ropes = $this->get_value_from_label('# for Ropes Course @ $5.00');

        $youth_total = $youth * 25;
        $sponsor_total = $sponsors * 20;
        $ropes_total = $ropes * 5;
        $total = $youth_total + $sponsor_total + $ropes_total;
        return $total;
    }

    function pre_error_check_actions(){
      $this->get_amount();
    }

    function run_error_checks(){
      if (intval($this->get_value('payment_amount')) != intval($this->get_amount())){
        $this->set_error('payment_amount', '<strong>Incorrect Payment Amount</strong>. The amount set in the payment amount field does not equal the cost for all chosen options. Please check your math or <a href="http://enable-javascript.com/" target="_blank">enable javascript</a> to have the form automatically fill in this field.');
      }
      parent :: run_error_checks();
    }
}
?>