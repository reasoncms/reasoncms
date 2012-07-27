<?php
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'RoyalVisitThorForm';

class RoyalVisitThorForm extends LutherDefaultThorForm {

    function on_every_time() {
        parent::on_every_time();
        
        echo '<script type="text/javascript" src="/reason/js/royalvisit.js"></script>';
        
        $special_seating = $this->get_element_name_from_label('Accessibility Issues?');
        $extra_guest_ticket = $this->get_element_name_from_label('Do you want an extra guest ticket for $20?');
        
        $this->change_element_type($special_seating, 'radio_inline_no_sort');
        
        $url = get_current_url();
        if ($url == 'https://reasondev.luther.edu/150/events/royalvisit/stafflottery/'){
            $this->change_element_type($extra_guest_ticket, 'radio_inline_no_sort',array('comments' => 'Additional ticket is $20, unless that person is a faculty/staff person'));
        }else{
           $this->change_element_type($extra_guest_ticket, 'radio_inline_no_sort'); 
        }
    }
    
    function pre_error_check_actions() {
        parent::pre_error_check_actions();
        
        $guest_first_name = $this->get_element_name_from_label('Guest First Name');
        $guest_last_name = $this->get_element_name_from_label('Guest Last Name');
        $guest_email = $this->get_element_name_from_label('Guest Email');
        
        $seating_describe = $this->get_element_name_from_label('Please describe');
        
        if ($this->get_value_from_label('Accessibility Issues?') == 'Yes' ) {

            $this->add_required($seating_describe);
        }
        
        if ($this->get_value_from_label('Do you want an extra guest ticket for $20?') == 'Yes' ) {

            $this->add_required($guest_first_name);
            $this->add_required($guest_last_name);
            $this->add_required($guest_email);
        }
    }
    
}
?>