<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');

$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'FinangerGolfForm';

/**
 * Form view for the various Finanger golf alumni event.
 *
 *
 * @package reason_package_local
 * @subpackage thor_view
 * @author Steve Smith
 */
class FinangerGolfForm extends CreditCardThorForm {
    var $package_options = array(
        'golf'              => 'Golf (includes cart, brunch, and dinner) – $105/person',
        'brunch and dinner' => 'Brunch and Dinner – $45/person',
        'dinner'            => 'Dinner only – $30/person'
    );
    var $dinner_options = array(
        'chicken'   => 'Chicken Italiano',
        'salmon'    => 'Almond Dijon Grilled Salmon'
    );
    var $division_options = array(
        'men'   => 'Men',
        'women' => 'Women',
        'co-ed' => 'Co-Ed'
    );

    function custom_init()
    {
        $model =& $this->get_model();
        $head_items = $model->get_head_items();
        $head_items->add_javascript('/reason/local/js/form/finanger_golf_form.js');
        $head_items->add_javascript(JQUERY_UI_URL);
        $head_items->add_stylesheet(JQUERY_UI_CSS_URL);
    }

    // style up the form and add comments et al
    function on_every_time()
    {
        $this->add_element('guest_1_header', 'comment', array('text' => '<h3>Golfer/Guest 1 Information</h3>'));
        $this->move_element('guest_1_header', 'before', $this->get_element_name_from_label('First Name 1'));

        $this->change_element_type($this->get_element_name_from_label('Class 1'), 'year',
            array('display_name' => 'Class year (if applicable)', 'num_years_before_today' => '60', 'num_years_after_today' => '-1'));
        $this->change_element_type($this->get_element_name_from_label('Package 1'), 'radio_no_sort',
            array('display_name' => 'Please select one', 'options' => $this->package_options));
        $this->change_element_type($this->get_element_name_from_label('Dinner 1'), 'radio_no_sort',
            array('display_name' => 'Dinner Selection', 'options' => $this->dinner_options));
        $this->change_element_type($this->get_element_name_from_label('Diet 1'), 'text',
            array('display_name' => 'Dietary Restrictions'));
        $this->add_element('guest_1_wrapper', 'comment', array('text' => '<div id="guest_1_wrapper">'));
        $this->move_element('guest_1_wrapper', 'before', 'guest_1_header');
        $this->add_element('guest_1_close', 'comment', array('text' => '</div>'));
        $this->move_element('guest_1_close', 'after', $this->get_element_name_from_label('Diet 1'));

        $this->add_element('guest_2_header', 'comment', array('text' => '<h3>Golfer/Guest 2 Information</h3>'));
        $this->move_element('guest_2_header', 'before', $this->get_element_name_from_label('First Name 2'));
        $this->change_element_type($this->get_element_name_from_label('Class 2'), 'year',
            array('display_name' => 'Class year (if applicable)', 'num_years_before_today' => '60', 'num_years_after_today' => '-1'));
        $this->change_element_type($this->get_element_name_from_label('Package 2'), 'radio_no_sort',
            array('display_name' => 'Please select one', 'options' => $this->package_options));
        $this->change_element_type($this->get_element_name_from_label('Dinner 2'), 'radio_no_sort',
            array('display_name' => 'Dinner Selection', 'options' => $this->dinner_options));
        $this->change_element_type($this->get_element_name_from_label('Diet 2'), 'text',
            array('display_name' => 'Dietary Restrictions'));
        $this->add_element('guest_2_wrapper', 'comment', array('text' => '<div id="guest_2_wrapper">'));
        $this->move_element('guest_2_wrapper', 'before', 'guest_2_header');
        $this->add_element('guest_2_close', 'comment', array('text' => '</div>'));
        $this->move_element('guest_2_close', 'after', $this->get_element_name_from_label('Diet 2'));

        // $this->add_element('guest_3_wrapper', 'comment', array('text' => '<div id="guest_2_wrapper">'));
        $this->add_element('guest_3_header', 'comment', array('text' => '<h3>Golfer/Guest 3 Information</h3>'));
        $this->move_element('guest_3_header', 'before', $this->get_element_name_from_label('First Name 3'));
        $this->change_element_type($this->get_element_name_from_label('Class 3'), 'year',
            array('display_name' => 'Class year (if applicable)', 'num_years_before_today' => '60', 'num_years_after_today' => '-1'));
        $this->change_element_type($this->get_element_name_from_label('Package 3'), 'radio_no_sort',
            array('display_name' => 'Please select one', 'options' => $this->package_options));
        $this->change_element_type($this->get_element_name_from_label('Dinner 3'), 'radio_no_sort',
            array('display_name' => 'Dinner Selection', 'options' => $this->dinner_options));
        $this->change_element_type($this->get_element_name_from_label('Diet 3'), 'text',
            array('display_name' => 'Dietary Restrictions'));
        $this->add_element('guest_3_wrapper', 'comment', array('text' => '<div id="guest_3_wrapper">'));
        $this->move_element('guest_3_wrapper', 'before', 'guest_3_header');
        $this->add_element('guest_3_close', 'comment', array('text' => '</div>'));
        $this->move_element('guest_3_close', 'after', $this->get_element_name_from_label('Diet 3'));

        $this->add_element('guest_4_header', 'comment', array('text' => '<h3>Golfer/Guest 4 Information</h3>'));
        $this->move_element('guest_4_header', 'before', $this->get_element_name_from_label('First Name 4'));
        $this->change_element_type($this->get_element_name_from_label('Class 4'), 'year',
            array('display_name' => 'Class year (if applicable)', 'num_years_before_today' => '60', 'num_years_after_today' => '-1'));
        $this->change_element_type($this->get_element_name_from_label('Package 4'), 'radio_no_sort',
            array('display_name' => 'Please select one', 'options' => $this->package_options));
        $this->change_element_type($this->get_element_name_from_label('Dinner 4'), 'radio_no_sort',
            array('display_name' => 'Dinner Selection', 'options' => $this->dinner_options));
        $this->change_element_type($this->get_element_name_from_label('Diet 4'), 'text',
            array('display_name' => 'Dietary Restrictions'));
        $this->add_element('guest_4_wrapper', 'comment', array('text' => '<div id="guest_4_wrapper">'));
        $this->move_element('guest_4_wrapper', 'before', 'guest_4_header');
        $this->add_element('guest_4_close', 'comment', array('text' => '</div>'));
        $this->move_element('guest_4_close', 'after', $this->get_element_name_from_label('Diet 4'));

        $this->add_element('guest_5_header', 'comment', array('text' => '<h3>Golfer/Guest 5 Information</h3>'));
        $this->move_element('guest_5_header', 'before', $this->get_element_name_from_label('First Name 5'));
        $this->change_element_type($this->get_element_name_from_label('Class 5'), 'year',
            array('display_name' => 'Class year (if applicable)', 'num_years_before_today' => '60', 'num_years_after_today' => '-1'));
        $this->change_element_type($this->get_element_name_from_label('Package 5'), 'radio_no_sort',
            array('display_name' => 'Please select one', 'options' => $this->package_options));
        $this->change_element_type($this->get_element_name_from_label('Dinner 5'), 'radio_no_sort',
            array('display_name' => 'Dinner Selection', 'options' => $this->dinner_options));
        $this->change_element_type($this->get_element_name_from_label('Diet 5'), 'text',
            array('display_name' => 'Dietary Restrictions'));
        $this->add_element('guest_5_wrapper', 'comment', array('text' => '<div id="guest_5_wrapper">'));
        $this->move_element('guest_5_wrapper', 'before', 'guest_5_header');
        $this->add_element('guest_5_close', 'comment', array('text' => '</div>'));
        $this->move_element('guest_5_close', 'after', $this->get_element_name_from_label('Diet 5'));

        $this->add_element('guest_6_header', 'comment', array('text' => '<h3>Golfer/Guest 6 Information</h3>'));
        $this->move_element('guest_6_header', 'before', $this->get_element_name_from_label('First Name 6'));
        $this->change_element_type($this->get_element_name_from_label('Class 6'), 'year',
            array('display_name' => 'Class year (if applicable)', 'num_years_before_today' => '60', 'num_years_after_today' => '-1'));
        $this->change_element_type($this->get_element_name_from_label('Package 6'), 'radio_no_sort',
            array('display_name' => 'Please select one', 'options' => $this->package_options));
        $this->change_element_type($this->get_element_name_from_label('Dinner 6'), 'radio_no_sort',
            array('display_name' => 'Dinner Selection', 'options' => $this->dinner_options));
        $this->change_element_type($this->get_element_name_from_label('Diet 6'), 'text',
            array('display_name' => 'Dietary Restrictions'));
        $this->add_element('guest_6_wrapper', 'comment', array('text' => '<div id="guest_6_wrapper">'));
        $this->move_element('guest_6_wrapper', 'before', 'guest_6_header');
        $this->add_element('guest_6_close', 'comment', array('text' => '</div>'));
        $this->move_element('guest_6_close', 'after', $this->get_element_name_from_label('Diet 6'));

        $this->add_element('guest_7_header', 'comment', array('text' => '<h3>Golfer/Guest 7 Information</h3>'));
        $this->move_element('guest_7_header', 'before', $this->get_element_name_from_label('First Name 7'));
        $this->change_element_type($this->get_element_name_from_label('Class 7'), 'year',
            array('display_name' => 'Class year (if applicable)', 'num_years_before_today' => '60', 'num_years_after_today' => '-1'));
        $this->change_element_type($this->get_element_name_from_label('Package 7'), 'radio_no_sort',
            array('display_name' => 'Please select one', 'options' => $this->package_options));
        $this->change_element_type($this->get_element_name_from_label('Dinner 7'), 'radio_no_sort',
            array('display_name' => 'Dinner Selection', 'options' => $this->dinner_options));
        $this->change_element_type($this->get_element_name_from_label('Diet 7'), 'text',
            array('display_name' => 'Dietary Restrictions'));
        $this->add_element('guest_7_wrapper', 'comment', array('text' => '<div id="guest_7_wrapper">'));
        $this->move_element('guest_7_wrapper', 'before', 'guest_7_header');
        $this->add_element('guest_7_close', 'comment', array('text' => '</div>'));
        $this->move_element('guest_7_close', 'after', $this->get_element_name_from_label('Diet 7'));

        $this->add_element('guest_8_header', 'comment', array('text' => '<h3>Golfer/Guest 8 Information</h3>'));
        $this->move_element('guest_8_header', 'before', $this->get_element_name_from_label('First Name 8'));
        $this->change_element_type($this->get_element_name_from_label('Class 8'), 'year',
            array('display_name' => 'Class year (if applicable)', 'num_years_before_today' => '60', 'num_years_after_today' => '-1'));
        $this->change_element_type($this->get_element_name_from_label('Package 8'), 'radio_no_sort',
            array('display_name' => 'Please select one', 'options' => $this->package_options));
        $this->change_element_type($this->get_element_name_from_label('Dinner 8'), 'radio_no_sort',
            array('display_name' => 'Dinner Selection', 'options' => $this->dinner_options));
        $this->change_element_type($this->get_element_name_from_label('Diet 8'), 'text',
            array('display_name' => 'Dietary Restrictions'));
        $this->add_element('guest_8_wrapper', 'comment', array('text' => '<div id="guest_8_wrapper">'));
        $this->move_element('guest_8_wrapper', 'before', 'guest_8_header');
        $this->add_element('guest_8_close', 'comment', array('text' => '</div>'));
        $this->move_element('guest_8_close', 'after', $this->get_element_name_from_label('Diet 8'));

        $this->change_element_type($this->get_element_name_from_label('Other'), 'textarea',
            array('display_name' => 'Other Comments/Notes'));

        $this->change_element_type($this->get_element_name_from_label('Division 1'), 'radio_inline_no_sort',
            array('display_name' => 'Division for Pairing/Foursome 1', 'options' => $this->division_options));
        $this->change_element_type($this->get_element_name_from_label('Grouping 1'), 'textarea',
            array('display_name' => 'Pairing/Foursome 1'));
        $this->change_element_type($this->get_element_name_from_label('Division 2'), 'radio_inline_no_sort',
            array('display_name' => 'Division for Pairing/Foursome 2', 'options' => $this->division_options));
        $this->change_element_type($this->get_element_name_from_label('Grouping 2'), 'textarea',
            array('display_name' => 'Pairing/Foursome 2'));
        $this->add_element('groupings_header', 'comment', array(
            'text' => '<h3>Pairings/Foursomes</h3>
                <p>Please list playing partners or foursomes (if different than above). Please include first names, last names, and class years (if applicable).</p>'));
        $this->move_element('groupings_header', 'before', $this->get_element_name_from_label('Division 1'));

        parent::on_every_time();

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

    function run_error_checks()
    {
        for ($i=1; $i < 9; $i++) {
            // set error if a first name is set, but no package and dinner
            if ($this->get_value_from_label("First Name {$i}") && !$this->get_value_from_label("Package {$i}")) {
                $this->set_error($this->get_element_name_from_label("Package {$i}"), "<br>Please choose a package for golfer/guest {$i}");
                $this->set_error($this->get_element_name_from_label("Dinner {$i}"), "<br>Please choose an entree for golfer/guest {$i}");
            }
            // set error if package is set, but no dinner
            if ($this->get_value_from_label("Package {$i}") && !$this->get_value_from_label("Dinner {$i}")) {
                $this->set_error($this->get_element_name_from_label("Dinner {$i}"), "<br>Please choose an entree for golfer/guest {$i}");
            }
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

    function email_form_data()
    {
         //parent :: email_form_data();
         $model =& $this->get_model();
         $sender = 'www@luther.edu';
         $recipient = $model->get_email_of_recipient();
         $recipients = explode(',',$recipient);

         if (in_array('@', $recipients)===FALSE){
                foreach($recipients as $recipient){
                     $recipient .= '@luther.edu';
                }
         }

         $subject = 'Response to Form: ' . $model->get_form_name();
         $heading = "<h2><strong>".$model->get_form_name()."</strong></h2>";
         $email_data = $model->get_values_for_email();
         $values = "\n";
         if ($model->should_email_data()){
                foreach($email_data as $key => $val){
                      if (!empty($this->get_value_from_label($val['label']))){
                             $values .= sprintf("\n<strong>%s:</strong>\t   %s\n", $val['label'], $val['value']);
			     //end form one, begin credit card form
                             if ($val['label']=='Payment Amount'){
                                   $val['label']='Payment Method';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('credit_card_type'));
                                   $val['label']='Name as it appears on card';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('credit_card_name'));
                                   $val['label']='Billing Street Address';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_street_address'));
                                   $val['label']='Billing City';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_city'));
                                   $val['label']='Billing State/Province';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_state_province'));
                                   $val['label']='Billing Zip/Postal Code';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_city'));
                                   $val['label']='Billing Country';
                                   $values .= sprintf("\n<strong>%s:</strong>\t    %s\n", $val['label'],$this->get_value('billing_country'));

                             }

                      }

                }
          }
                                                     
          $submission_time = date("Y-m-d H:i:s");

          $values .= sprintf("\n<strong>%s:</strong>\t    %s\n",'Form Submission Time', $submission_time);
          $vl = nl2br($values);
          $html_body =$heading . $vl;
          $txt_body = html_entity_decode(strip_tags($html_body));
          $mailer = new Email($recipient, $sender, $sender, $subject, $txt_body, $html_body);
          $mailer->send();

    }  //close email function

}

