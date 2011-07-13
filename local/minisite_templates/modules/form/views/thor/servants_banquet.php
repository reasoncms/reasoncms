<?php
reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'ServantsBanquetForm';

class ServantsBanquetForm extends CreditCardThorForm {

    function on_every_time() {
        parent::on_every_time();

        echo '<script type="text/javascript" src="/reason/js/servants_banquet.js"></script>';
        //$this->is_in_testing_mode = true;
    }


function pre_error_check_actions() {
        parent::pre_error_check_actions();

        $guest_first_name = $this->get_element_name_from_label('Guest First Name');
        $guest_last_name = $this->get_element_name_from_label('Guest Last Name');
        $guest_choice = $this->get_element_name_from_label('Guest Entree Choice');

        


        if ($this->get_value_from_label('Payment Amount') == '$60 with guest' ) {

            $this->add_required($guest_first_name);
            $this->add_required($guest_last_name);
            $this->add_required($guest_choice);
        }
}
}

?>