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
 *  Show the confirmation message
 *
 */
class ApplicationConfirmation extends FormStep {

    var $openid_id;
    var $_log_errors = true;
    var $error;

    function get_thank_you_blurb() {
        if (reason_unique_name_exists('admissions_application_thank_you_blurb')) {
            $blurb = get_text_blurb_content('admissions_application_thank_you_blurb');
        } else {
            $blurb = '<p><strong>Thank you!</strong></p>
                <p>We\'re having trouble generating the confirmation message, but your application for admission to
                Luther College <strong>has been received</strong>.</p>' . "\n";
        }
        return $blurb;
    }

    // style up the form and add comments et al
    function on_every_time() {
        $this->show_form = false;

        //check if an open_id is set
        // if not, send them back to the first page
        if (check_open_id($this)) {
            echo $this->get_thank_you_blurb();
        } else {
            echo 'How\'d you get here?';
            header("Location:/admissions/apply/");
        }
        $this->controller->destroy_form_data();
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="confirmation">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

}

?>