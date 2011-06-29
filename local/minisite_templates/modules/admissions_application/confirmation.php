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
        $this->get_thank_you_blurb();
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="confirmation">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

}

?>