<?php

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'StudentTreatsThorForm';

class StudentTreatsThorForm extends CreditCardThorForm {

    function on_every_time() {
        parent::on_every_time();

        //import js
        echo '<script type="text/javascript" src="'.REASON_HTTP_BASE_PATH.'js/student_treats.js"></script>';

        // radio header
        $this->add_element('radio_header', 'comment', array('text' => '<h4> </br> How many treat orders would you like to place?  All treat orders are $20 each.</h4>'));
        $this->move_element('radio_header', 'after', $this->get_element_name_from_label('Cell Phone'));

        $this->change_element_type($this->get_element_name_from_label('#1  Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#2  Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#3  Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#4 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#5 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#6 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#7 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#8 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#9 Date For Delivery'), 'textDate');
        $this->change_element_type($this->get_element_name_from_label('#10 Date For Delivery'), 'textDate');


        //$this->is_in_testing_mode = true;
    }

    function pre_error_check_actions() {
        parent::pre_error_check_actions();
        
        $date1 = $this->get_element_name_from_label('#1  Date For Delivery');
        $occassion1 = $this->get_element_name_from_label('#1 Occasion Type: birthday, holiday, etc.');
        $type1 = $this->get_element_name_from_label('#1 Treat Type');

        $date2 = $this->get_element_name_from_label('#2  Date For Delivery');
        $occassion2 = $this->get_element_name_from_label('#2 Occasion Type: birthday, holiday, etc.');
        $type2 = $this->get_element_name_from_label('#2 Treat Type');

        $date3 = $this->get_element_name_from_label('#3  Date For Delivery');
        $occassion3 = $this->get_element_name_from_label('#3 Occasion Type: birthday, holiday, etc.');
        $type3 = $this->get_element_name_from_label('#3 Treat Type');

        $date4 = $this->get_element_name_from_label('#4 Date For Delivery');
        $occassion4 = $this->get_element_name_from_label('#4 Occasion Type: birthday, holiday, etc.');
        $type4 = $this->get_element_name_from_label('#4 Treat Type');

        $date5 = $this->get_element_name_from_label('#5 Date For Delivery');
        $occassion5 = $this->get_element_name_from_label('#5 Occasion Type: birthday, holiday, etc.');
        $type5 = $this->get_element_name_from_label('#5 Treat Type');

        $date6 = $this->get_element_name_from_label('#6 Date For Delivery');
        $occassion6 = $this->get_element_name_from_label('#6 Occasion Type: birthday, holiday, etc.');
        $type6 = $this->get_element_name_from_label('#6 Treat Type');
        
        $date7 = $this->get_element_name_from_label('#7 Date For Delivery');
        $occassion7 = $this->get_element_name_from_label('#7 Occasion Type: birthday, holiday, etc.');
        $type7 = $this->get_element_name_from_label('#7 Treat Type');
        
        $date8 = $this->get_element_name_from_label('#8 Date For Delivery');
        $occassion8 = $this->get_element_name_from_label('#8 Occasion Type: birthday, holiday, etc.');
        $type8 = $this->get_element_name_from_label('#8 Treat Type');
        
        $date9 = $this->get_element_name_from_label('#9 Date For Delivery');
        $occassion9 = $this->get_element_name_from_label('#9 Occasion Type: birthday, holiday, etc.');
        $type9 = $this->get_element_name_from_label('#9 Treat Type');
        
        $date10 = $this->get_element_name_from_label('#10 Date For Delivery');
        $occassion10 = $this->get_element_name_from_label('#10 Occasion Type: birthday, holiday, etc.');
        $type10 = $this->get_element_name_from_label('#10 Treat Type');


        if ($this->get_value_from_label('Payment Amount') == '$20 - 1 treat') {
          
            $this->remove_required($date2);
            $this->remove_required($occassion2);
            $this->remove_required($type2);

            $this->remove_required($date3);
            $this->remove_required($occassion3);
            $this->remove_required($type3);

            $this->remove_required($date4);
            $this->remove_required($occassion4);
            $this->remove_required($type4);

            $this->remove_required($date5);
            $this->remove_required($occassion5);
            $this->remove_required($type5);

            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }

        if ($this->get_value_from_label('Payment Amount') == '$40 - 2 treats') {
            
            $this->remove_required($date3);
            $this->remove_required($occassion3);
            $this->remove_required($type3);

            $this->remove_required($date4);
            $this->remove_required($occassion4);
            $this->remove_required($type4);

            $this->remove_required($date5);
            $this->remove_required($occassion5);
            $this->remove_required($type5);

            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }

        if ($this->get_value_from_label('Payment Amount') == '$60 - 3 treats') {
            
            $this->remove_required($date4);
            $this->remove_required($occassion4);
            $this->remove_required($type4);

            $this->remove_required($date5);
            $this->remove_required($occassion5);
            $this->remove_required($type5);

            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }

        if ($this->get_value_from_label('Payment Amount') == '$80 - 4 treats') {
            
            $this->remove_required($date5);
            $this->remove_required($occassion5);
            $this->remove_required($type5);

            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }

        if ($this->get_value_from_label('Payment Amount') == '$100 - 5 treats') {
           
            $this->remove_required($date6);
            $this->remove_required($occassion6);
            $this->remove_required($type6);
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        if ($this->get_value_from_label('Payment Amount') == '$120 - 6 treats') {
            
            $this->remove_required($date7);
            $this->remove_required($occassion7);
            $this->remove_required($type7);
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        if ($this->get_value_from_label('Payment Amount') == '$140 - 7 treats') {
            
            $this->remove_required($date8);
            $this->remove_required($occassion8);
            $this->remove_required($type8);
            
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        if ($this->get_value_from_label('Payment Amount') == '$160 - 8 treats') {
          
            $this->remove_required($date9);
            $this->remove_required($occassion9);
            $this->remove_required($type9);
            
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        if ($this->get_value_from_label('Payment Amount') == '$180 - 9 treats') {
            
                     
            $this->remove_required($date10);
            $this->remove_required($occassion10);
            $this->remove_required($type10);
        }
        
        
    }

}

?>
