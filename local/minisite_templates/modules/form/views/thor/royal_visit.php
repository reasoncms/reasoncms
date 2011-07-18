<?php
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'RoyalVisitThorForm';

class RoyalVisitThorForm extends LutherDefaultThorForm {

    function on_every_time() {
        parent::on_every_time();
        
        echo '<script type="text/javascript" src="/reason/js/royalvisit.js"></script>';

        
        
    }
    
    function pre_error_check_actions() {
        parent::pre_error_check_actions();
        
        $guest_first_name = $this->get_element_name_from_label('Guest First Name');
        $guest_last_name = $this->get_element_name_from_label('Guest Last Name');
        $guest_address = $this->get_element_name_from_label('Guest Address');
        $guest_city = $this->get_element_name_from_label('Guest City');
        $guest_state = $this->get_element_name_from_label('Guest State');
        $guest_zip_code = $this->get_element_name_from_label('Guest Zip code');
        $guest_phone = $this->get_element_name_from_label('Guest Phone');
        $guest_email = $this->get_element_name_from_label('Guest Email');
        
        if ($this->get_value_from_label('Do you want an extra guest ticket?') == 'Yes' ) {

            $this->add_required($guest_first_name);
            $this->add_required($guest_last_name);
            $this->add_required($guest_address);
            $this->add_required($guest_city);
            $this->add_required($guest_state);
            $this->add_required($guest_zip_code);
            $this->add_required($guest_phone);
            $this->add_required($guest_email);
        }
    }
    
}
?>