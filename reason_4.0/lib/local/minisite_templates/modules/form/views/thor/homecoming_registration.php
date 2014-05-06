<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_with_no_payment_option.php');
include_once(WEB_PATH . 'stock/pfproclass.php');

$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'HomecomingRegistrationForm';

/**
 * Use only for running test payments on the live site. This is only for the form creator to see the process
 * all the way through. Switch to credit_card_payment for live transactions
 *
 * @package reason_package_local
 * @subpackage thor_view
 * * @author Steve Smith
 */
class HomecomingRegistrationForm extends CreditCardNoPaymentThorForm {

    function custom_init() 
    { 
        $model =& $this->get_model(); 
        $head_items = $model->get_head_items(); 
        $head_items->add_javascript('/reason/local/js/form/homecoming_registration.js');
          // $head_items->add_head_item('meta',array('http-equiv'=>'refresh','content'=>'1;url=error.html', true, array('before' => '<noscript>', 'after' => '</noscript>')));
    } 

    // style up the form and add comments et al
    function on_every_time() 
    {

        parent :: on_every_time(); 
        $this->set_value($this->get_element_name_from_label('Revenue Budget Number'), '!!!!GetNumbers!!!!');
        $this->set_value($this->get_element_name_from_label('Expense Budget Number'), '!!!!GetNumbers!!!!');

        $this->add_element('your_information_header', 'comment', array('text'=>'<h4>Your Information</h4>'));
        $this->move_element('your_information_header', 'before', $this->get_element_name_from_label('Current First Name'));
        $this->add_element('guest_information_header', 'comment', array('text'=>'<h4>Spouse/Guest Information</h4>'));
        $this->move_element('guest_information_header', 'before', $this->get_element_name_from_label('Spouse/Guest Name'));
        $this->add_element('alumni_dinner_header', 'comment', array('text'=>'<h4>Alumni Dinner Reservations</h4>'));
        $this->move_element('alumni_dinner_header', 'before', $this->get_element_name_from_label('Friday\'s Alumni Dinner'));
        $this->add_element('class_reunion_reservations_header', 'comment', array('text'=>'<h4>Class Reunion Reservations</h4>'));
        $this->move_element('class_reunion_reservations_header', 'after', $this->get_element_name_from_label('Friday\'s Alumni Dinner'));
        $this->add_element('50_year_reunion_header', 'comment', array('text'=>'<h4>50 Year Reunion</h4>'));
        $this->move_element('50_year_reunion_header', 'before', $this->get_element_name_from_label('50th Reunion Luncheon'));

        $class_year = $this->get_element_name_from_label('Reunion Class Year');
          $this->change_element_type($class_year, 'year', array('num_years_before_today'=>'75','num_years_after_today'=>'-1'));
        // $this->change_element_type($class_year, 'chosen_select', array('options' => array($this->_set_years())));
        $attended_luther = $this->get_element_name_from_label('Attended Luther');
        $this->change_element_type($attended_luther, 'radio_inline_no_sort');
        $guest_class_year = $this->get_element_name_from_label('Guest Class Year');
          $this->change_element_type($guest_class_year, 'year', array('num_years_before_today'=>'75','num_years_after_today'=>'-1'));
        // $this->change_element_type($guest_class_year, 'chosen_select', array('options' => array($this->_set_years())));
        $dining_restrictions = $this->get_element_name_from_label('Dining Restrictions?');
        $this->change_element_type($dining_restrictions, 'textarea', array('display_name'=>
          'Do you or any of your guests have any dining restrictions?'));
        $parade = $this->get_element_name_from_label('Ride in Parade?');
        $this->change_element_type($parade, 'radio_inline_no_sort');
        $this->add_element('hr', 'hr');
        $this->move_element('hr', 'after', $this->get_element_name_from_label('50th Reunion Booklet'));
        $this->add_element('same_billing', 'checkboxfirst', array('display_name' => 'Billing address is same as above'));
        $this->move_element('same_billing', 'after', 'credit_card_name');
        echo $this->get_value('same_billing');

    }

    function get_amount()
    {
        $five_cost          = ltrim($this->get_value_from_label('5 year cost'), '$');
        $ten_cost           = ltrim($this->get_value_from_label('10 year cost'), '$');
        $fifteen_cost       = ltrim($this->get_value_from_label('15 year cost'), '$');
        $twenty_cost        = ltrim($this->get_value_from_label('20 year cost'), '$');
        $twentyfive_cost    = ltrim($this->get_value_from_label('25 year cost'), '$');
        $thirty_cost        = ltrim($this->get_value_from_label('30 year cost'), '$');
        $thirtyfive_cost    = ltrim($this->get_value_from_label('35 year cost'), '$');
        $forty_cost         = ltrim($this->get_value_from_label('40 year cost'), '$');
        $fortyfive_cost     = ltrim($this->get_value_from_label('45 year cost'), '$');
        $fifty_cost         = ltrim($this->get_value_from_label('50 year cost'), '$');
        $fiftyfive_cost     = ltrim($this->get_value_from_label('55 year cost'), '$');
        $booklet_cost       = ltrim($this->get_value_from_label('Booklet cost'), '$');
        $alumni_dinner_cost = ltrim($this->get_value_from_label('Alumni Dinner cost'), '$');
        $reunion_cost = 0;

        $total = 0;

        $class_year = intval($this->get_value_from_label('Reunion Class Year'));
        $cur_year = idate('Y');
        $is_reunion_year = ($cur_year - $class_year) % 5;
        if ($is_reunion_year == 0) {
            $reunion = $cur_year - $class_year;
            switch ($reunion) {
                case '5':
                    $reunion_cost = $five_cost;
                    break;
                case '10':
                    $reunion_cost = $ten_cost;
                    break;
                case '15':
                    $reunion_cost = $fifteen_cost;
                    break;
                case '20':
                    $reunion_cost = $twenty_cost;
                    break;
                case '25':
                    $reunion_cost = $twentyfive_cost;
                    break;
                case '30':
                    $reunion_cost = $thirty_cost;
                    break;
                case '35':
                    $reunion_cost = $thirtyfive_cost;
                    break;
                case '40':
                    $reunion_cost = $forty_cost;
                    break;
                case '45':
                    $reunion_cost = $fortyfive_cost;
                    break;
                case '50':
                    $reunion_cost = $fifty_cost;
                    break;
                case '55':
                    $reunion_cost = $fiftyfive_cost;
                    break;
                default:
                    $reunion_cost = 0;
                    break;
            }
        } 

        $total = 0;
        $total = (($alumni_dinner_cost * intval($this->get_value_from_label('Friday\'s Alumni Dinner')))
                + ($reunion_cost * intval($this->get_value_from_label('Reunion Dinner/Reception')))
                + ($booklet_cost * intval($this->get_value_from_label('50th Reunion Booklet'))));
        return $total;
    }

    function pre_error_check_actions()
    {
        if ($this->get_value('same_billing') == true)
        {
            $this->add_required($this->get_element_name_from_label('Address'));
            $this->add_required($this->get_element_name_from_label('City'));
            $this->add_required($this->get_element_name_from_label('State/Province'));
            $this->add_required($this->get_element_name_from_label('Zip/Postal Code'));
        }
        $this->get_amount();
        parent::pre_error_check_actions();
    }

    function run_error_checks()
    {
        $pa = $this->get_value_from_label('Payment Amount');

        $pa_element = $this->get_element_name_from_label('Payment Amount');
        if ($pay_amount != intval($this->get_amount())){
        $this->set_error($pa_element, '<strong>Incorrect Payment Amount</strong>. The amount set in the payment amount field does not equal the cost for all chosen options. Please check your math or <a href="http://enable-javascript.com/" target="_blank">enable javascript</a> to have the form automatically fill in this field.');
        }

        if ($this->get_value('same_billing') == true)
        {
            // check for address fields
            if ($this->get_value_from_label('Address') == "")
            {
                $this->set_error($this->get_element_name_from_label('Address'), 'Please provide an Address');
            }
            if ($this->get_value_from_label('City') == "")
            {
                $this->set_error($this->get_element_name_from_label('City'), 'Please provide a City');
            }
            if ($this->get_value_from_label('State/Province') == "")
            {
                $this->set_error($this->get_element_name_from_label('State/Province'), 'Please provide a State/Province');
            }
            if ($this->get_value_from_label('Zip/Postal Code') == "")
            {
                $this->set_error($this->get_element_name_from_label('Zip/Postal Code'), 'Please provide a Zip/Postal Code');
            }
            $this->set_billing_info();
        }
        parent :: run_error_checks();
    }

    function set_billing_info()
    {
        $this->set_value('billing_street_address', $this->get_value_from_label('Address'));
        $this->set_value('billing_city', $this->get_value_from_label('City'));
        $this->set_value('billing_state_province', $this->get_value_from_label('State/Province'));
        $this->set_value('billing_zip', $this->get_value_from_label('Zip/Postal Code'));
    }
}