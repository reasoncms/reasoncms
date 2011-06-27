<?php

include_once 'application_utils.php';

/**
 * Admissions Application Module
 *
 * @author Steve Smith
 * @author Lucas Welper
 * @since 2011-02-11
 * @package ControllerStep
 *
 */
/*
 *  First page of the application
 *
 *  Enrollment Term
 *  Student Type
 *
 */
class ApplicationConfirmation extends FormStep {

    var $openid_id;
    var $_log_errors = true;
    var $error;
    
    var $display_name = 'Confirmation';

    ///////////////
    // style up the form and add comments et al
    function on_every_time() {
        $this->show_form = false;
    }

    function no_show_form() {
        echo(check_login(get_current_url(), $this));
        echo 'This is where the confirmation message should go!';
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="confirmation">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

    function pre_fill_form() {
    }
}

?>