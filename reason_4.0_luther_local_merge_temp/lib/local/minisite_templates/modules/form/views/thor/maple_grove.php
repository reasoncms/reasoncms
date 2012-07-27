<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');

$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'MapleGrovePaymentForm';

/**
 * Use only for running test payments on the live site. This is only for the form creator to see the process
 * all the way through. Switch to credit_card_payment for live transactions
 *
 * @package reason_package_local
 * @subpackage thor_view
 * * @author Steve Smith
 */
class MapleGrovePaymentForm extends CreditCardThorForm {
    

    // style up the form and add comments et al
    function on_every_time() {

      parent :: on_every_time(); 
      // $this->change_element_type($this->get_element_name_from_label('State'), 'state');
      $this->set_element_properties($this->get_element_name_from_label('Class Year'), array('size'=>4));
      $this->change_element_type($this->get_element_name_from_label('Attended Luther?'), 'radio_no_sort');
      $this->set_element_properties($this->get_element_name_from_label('Class Year or Years Attended'), array('size'=>7));
      $this->set_element_properties($this->get_element_name_from_label('Zip'), array('size'=>5));
      $this->set_element_properties($this->get_element_name_from_label('Number of Adults - $10.00 each'), array('size'=>3));
      $this->set_element_properties($this->get_element_name_from_label('Number of Children 6-17 - $5.00'), array('size'=>3));
      $this->set_element_properties($this->get_element_name_from_label('Number of Children Under 5 - free'), array('size'=>3));
    }

    function get_amount(){
        $adults = $this->get_value_from_label('Number of Adults - $10.00 each');
        $children = $this->get_value_from_label('Number of Children 6-17 - $5.00 each');

        $adult_total = $adults * 10;
        $children_total = $children * 5;
        $total = $adult_total + $children_total;
        return $total;

        // $this->set_value($this->payment_element, $total);
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