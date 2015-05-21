<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'reason/local/stock/pfproclass.php');

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
      $this->set_value($this->get_element_name_from_label('Revenue Budget Number'), '13-000-00619-22000');
      $this->set_value($this->get_element_name_from_label('Expense Budget Number'), '13-000-00619-12121');

      $july4 = 184; // July 4 == day 184 (185 on a leap year) on a 0 - 364 scale
      if (date('L')){ // if this year is a leap year
        $july4 = 185;
      }
      $date = getdate();
      if ($date['yday'] > $july4){
        $this->change_element_type($this->get_element_name_from_label('Registration Type'), 'radio_no_sort',
          array('options'=>array(
                '$90 - Regular Full'=>'$90 - Regular Full',
                '$55 - Friday Only'=>'$55 - Friday Only',
                '$45 - Saturday Only'=>'$45 - Saturday Only',
                '$50 - Graduate Student'=>'$50 - Graduate Student',
                '$45 - Undergraduate Student'=>'$45 - Undergraduate Student',
                '$200 - Commercial Vendor'=>'$200 - Commercial Vendor',
                '$100 - Non-Profit Exhibitor'=>'$100 - Non-Profit Exhibitor',
                '$500 - Friends of IPC Sponsorship'=>'$500 - Friends of IPC Sponsorship'))
          );
      }

      $this->add_element('registration_fees_header', 'comment', array('text'=>'<h4>Registration Fees</h4>See <a href="/iowaprairieconference/RegistrationPrelim">registration information</a> page for more details.'));
      $this->move_element('registration_fees_header', 'before', $this->get_element_name_from_label('Registration Type'));
      $this->add_element('lodging_header', 'comment', array('text'=>'<h4>Luther College Dorm Lodging Reservations</h4>If reserving a double room for two registrants, only one registrant should fill out the dorm lodging section.'));
      $this->move_element('lodging_header', 'after', $this->get_element_name_from_label('Registration Type'));

      $friday_trip = $this->get_element_name_from_label('Choose destination of Friday afternoon field trip');
      $this->add_element('friday_trip_header', 'comment', array('text'=>'<h3>We are sorry, but the Prairie Song Farm field trip is FULL.</h3> All full, late registrations and Friday late registrations will be automatically registered for the Chipera Prairie Friday afternoon field trip. <br />Chipera Prairie is a 90-acre site with native tallgrass prairie and has never been plowed. This site is managed by the Winneshiek County Conservation Board.'));
      $this->move_element('friday_trip_header', 'before', $friday_trip);
      $this->change_element_type($friday_trip, 'radio', 
        array('options'=>array('Chipera Prairie (Winneshiek Country Conservation Board site in Jackson Junction)'=>'Chipera Prairie (Winneshiek Country Conservation Board site in Jackson Junction)')));
      $this->set_value($friday_trip, 'Chipera Prairie (Winneshiek Country Conservation Board site in Jackson Junction)');
    }

    function get_amount(){
        $reg = $this->get_value_from_label('Registration Type');
        $r = explode(' - ', $reg);
        $reg_amount = substr($r[0], 1);

        $single_nights = $this->get_value_from_label('Single Room ($42.50/night) - single bed, with linens');
        if (is_array($single_nights)){
          $single_count = count($single_nights);
          $s_nights = 42.50 * $single_count;
        } else {
          $s_nights = 0;
        }

        $double_nights = $this->get_value_from_label('Double Room ($79.00/night) - two single beds, with linens');
        if (is_array($double_nights)){
          $double_count = count($double_nights);
          $d_nights = 79 * $double_count;
        } else {
          $d_nights = 0;
        }

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
      
      $pa_element = $this->get_element_name_from_label('Payment Amount');
      if ($pay_amount != floatval($this->get_amount())){
        $this->set_error($pa_element, '<strong>Incorrect Payment Amount</strong>. The amount set in the payment amount field does not equal the cost for all chosen options. Please check your math or <a href="http://enable-javascript.com/" target="_blank">enable javascript</a> to have the form automatically fill in this field.');
      }
      parent :: run_error_checks();
    }
}