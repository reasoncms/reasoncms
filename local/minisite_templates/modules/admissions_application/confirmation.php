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
        /*
         * check if applicants with a set open_id are landing here prematurely
         * if so, display an error message with the required fields and break
         */
        $this->openid_id = check_open_id($this);
        $submitted = is_submitted($this->openid_id);
        if (!$submitted){
            $i = 0;
            $error_div = "";
            foreach ($this->controller->forms as $name => $form) {
                $error_header = "<div style='width:655px;border:1px solid red;border-radius:5px;background-color:#FFB2B2;padding:5px;margin:5px;'>
                <span style='font-weight:bold;'>Required fields: " . $form->display_name . "</span>&nbsp;&nbsp;";
                $error_footer = "</div>";
                $i++;
                switch ($i) {
                    case 1:
                        $validation = $p1_valid = validate_page1($form);
                        break;
                    case 2:
                        $validation = $p2_valid = validate_page2($form);
                        break;
                    case 3:
                        $validation = $p3_valid = validate_page3($form);
                        break;
                    case 4:
                        $validation = $p4_valid = validate_page4($form);
                        break;
                    case 5:
                        $validation = $p5_valid = validate_page5($form);
                        break;
                    case 6:
                        $validation = $p6_valid = validate_page6($form);
                        break;
                }
                if (!$validation['valid']) {
                    $error_div .= $error_header;
                    foreach ($validation as $val_key => $val_value) {
                        $error_div .= " <a href='/admissions/apply/?_step=" . $name . "#" . $val_key . "_error'>" . $val_value . "</a>&nbsp;&nbsp; ";
                    }
                }
                $error_div .= $error_footer;
            }

            if (!$p1_valid['valid'] || !$p2_valid['valid'] || !$p3_valid['valid'] || !$p4_valid['valid'] || !$p5_valid['valid'] || !$p6_valid['valid']) {
                echo '<p>Oops! Looks like you\'ve landed here accidentally.</p>';
                echo 'Please complete the following required fields to submit your application:' . $error_div;
                break;
            }
        }
//        connectDB(REASON_DB);
        /*
         * check if an open_id is set and a submit_date is set
         * if so, display the thank you blurb
         * if not, send them back to the first page
         */
        if (($this->openid_id) && ($submitted)) {
            echo $this->get_thank_you_blurb();
        } elseif (($this->openid_id) && (!$submitted)) {
            echo '<p>Oops! Looks like you\'ve landed here accidentally.</p>';
            echo 'To complete your application, please return to <a href="/admissions/apply/?_step=ApplicationPageSix">page 6</a>
                and click "Submit your application" at the bottom of the page.';
            break;
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