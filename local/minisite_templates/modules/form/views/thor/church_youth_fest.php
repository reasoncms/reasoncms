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

      // parent :: on_every_time(); 
      $this->change_element_type($this->get_element_name_from_label('State'), 'state');
      //$this->change_element_type($this->get_element_name_from_label('Payment Amount Placeholder'), 'solidtext');
      $this->set_element_properties($this->get_element_name_from_label('Zipcode'), array('size'=>5));
      $this->set_element_properties($this->get_element_name_from_label('# of Youth @ $25.00'), array('size'=>3));
      $this->set_element_properties($this->get_element_name_from_label('# of Sponsors @ $20.00'), array('size'=>3));
      $this->set_element_properties($this->get_element_name_from_label('# for Ropes Course @ $5.00'), array('size'=>3));
    }

    function get_amount(){
        $youth = $this->get_value_from_label('# of Youth @ $25.00');
        $sponsors = $this->get_value_from_label('# of Sponsors @ $20.00');
        $ropes = $this->get_value_from_label('# for Ropes Course @ $5.00');

        $youth_total = $youth * 25;
        $sponsor_total = $sponsors * 20;
        $ropes_total = $ropes * 5;
        $total = $youth_total + $sponsor_total + $ropes_total;

        // $this->set_value($this->payment_element, $total);
    }

    function pre_error_check_actions(){
        $this->get_amount();
    }


}

?>