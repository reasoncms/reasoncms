<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');

$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'IowaPrairieConferenceForm';

/**
 * Use only for running test payments on the live site. This is only for the form creator to see the process
 * all the way through. Switch to credit_card_payment for live transactions
 *
 * @package reason_package_local
 * @subpackage thor_view
 * * @author Steve Smith
 */
class IowaPrairieConferenceForm extends CreditCardThorForm {

    // style up the form and add comments et al
    // function on_every_time() {
    //   parent :: on_every_time(); 
    //   // $this->change_element_type($this->get_element_name_from_label('State'), 'state');
    //   // $this->set_element_properties($this->get_element_name_from_label('Class Year'), array('size'=>4));
    //   // $this->change_element_type($this->get_element_name_from_label('Attended Luther?'), 'radio_no_sort');
    //   // $this->set_element_properties($this->get_element_name_from_label('Class Year or Years Attended'), array('size'=>7));
    //   // $this->set_element_properties($this->get_element_name_from_label('Zip'), array('size'=>5));
    //   // $this->set_element_properties($this->get_element_name_from_label('Number of Adults - $10.00 each'), array('size'=>3));
    //   // $this->set_element_properties($this->get_element_name_from_label('Number of Children 6-17 - $5.00'), array('size'=>3));
    //   // $this->set_element_properties($this->get_element_name_from_label('Number of Children Under 5 - free'), array('size'=>3));
    // }

    function get_amount(){
        $reg = $this->get_value_from_label('Registration Type');
        $r = explode(' - ', $reg);
        $reg_amount = substr($r[0], 1);

        $single_nights = $this->get_value_from_label('Single Room ($42.50/night) - single bed, with linens');
        $single_count = count($single_nights);
        $s_nights = 42.50 * $single_count;

        $double_nights = $this->get_value_from_label('Double Room ($79.00/night) - two single beds, with linens');
        $double_count = count($double_nights);
        $d_nights = 79 * $double_count;

        $total = $reg_amount + $s_nights + $d_nights;
        return $total;

        // $this->set_value($this->payment_element, $total);
    }

    function pre_error_check_actions(){
      $this->get_amount();
    }

    function run_error_checks(){
      $pa = $this->get_element_name_from_label('Payment Amount');
      if (intval($this->get_value($pa)) != intval($this->get_amount())){
        $this->set_error($pa, '<strong>Incorrect Payment Amount</strong>. The amount set in the payment amount field does not equal the cost for all chosen options. Please check your math or <a href="http://enable-javascript.com/" target="_blank">enable javascript</a> to have the form automatically fill in this field.');
      }
      parent :: run_error_checks();
    }
}
?>