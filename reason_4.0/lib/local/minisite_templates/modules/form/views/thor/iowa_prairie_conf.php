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
    function on_every_time() {
      parent :: on_every_time(); 
      $this->add_element('registration_fees_header', 'comment', array('text'=>'<h4>Registration Fees</h4>See <a href="/iowaprairieconference/RegistrationPrelim">registration information</a> page for more details.'));
      $this->move_element('registration_fees_header', 'before', $this->get_element_name_from_label('Registration Type'));
      $this->add_element('lodging_header', 'comment', array('text'=>'<h4>Luther College Dorm Lodging Reservations</h4>If reserving a double room for two registrants, only one registrant should fill out the dorm lodging section.'));
      $this->move_element('lodging_header', 'after', $this->get_element_name_from_label('Registration Type'));
    }

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
    }

    function pre_error_check_actions(){
      $this->get_amount();
    }

    function run_error_checks(){
      $pa = $this->get_value_from_label('Payment Amount');
      $p = explode(' - ', $pa);
      $pay_amount = substr($p[0], 1);
      echo $pay_amount;
      if (intval($this->get_value($pay_amount)) != intval($this->get_amount())){
        $this->set_error($pa, '<strong>Incorrect Payment Amount</strong>. The amount set in the payment amount field does not equal the cost for all chosen options. Please check your math or <a href="http://enable-javascript.com/" target="_blank">enable javascript</a> to have the form automatically fill in this field.');
      }
      parent :: run_error_checks();
    }
}
?>