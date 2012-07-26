<?

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');
include_once '/usr/local/webapps/reason/reason_package_local/carl_util/dir_service/services/ds_mysql_royal_visit.php';
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'RoyalVisitPaymentForm';

/**
 * Use only for running test payments on the live site. This is only for the form creator to see the process
 * all the way through. Switch to credit_card_payment for live transactions
 *
 * @package reason_package_local
 * @subpackage thor_view
 * * @author Steve Smith
 */
class RoyalVisitPaymentForm extends CreditCardThorForm {

    // style up the form and add comments et al
    function on_every_time() {
        parent::on_every_time();

        $username = reason_check_authentication(); // this will force login
        if ($username) {
            $this->show_form = true;
//            echo '<hr>';

            $payment_amount = $this->get_element_name_from_label('Payment Amount');
            $this->change_element_type($payment_amount, 'text');
//            $this->set_display_name($payment_amount, 'text');
            
            

            $dir = new directory_service();
            $dir->serv_inst['mysql_royal_visit'];

            $auth = $dir->authenticate($username, $_SESSION['password']);
            connectDB('royal_visit_connection');

            ///////   Faculty/Staff/Emerti Query  ///////
            $query = "SELECT * FROM `faculty_staff_lottery` WHERE `email`='" . $username . "'";
            $result = db_query($query);
            $num_rows = mysql_num_rows($result);
            $this->set_value($payment_amount, '$20');
            if ($num_rows > 0) {
                for ($num_rows; $num_rows > 0; $num_rows--) {
                    $row = mysql_fetch_assoc($result);
                    $values = array_values($row);

                    $name = $this->get_element_name_from_label('Your Name');
                    $this->change_element_type($name, 'solidtext');
                    $this->set_value($name, $row['Your First Name'] . ' ' . $row['Your Last Name']);

                    $guest_name = $this->get_element_name_from_label('Guest Name');
                    $this->change_element_type($guest_name, 'solidtext');
                    $this->set_value($guest_name, $row['Guest First Name'] . ' ' . $row['Guest Last Name']);

                    if (($row['Guest First Name'] != '') && ($row['Do you want an extra guest ticket for $20?'] == 'Yes')) {
                        echo '<hr>';
                        echo 'Tickets will be sent via intercampus mail for Faculty and Staff.<br>';
                        echo 'Emeriti will recieve tickets via USPS.' . "\n";
                        $this->set_value($this->payment_element, '$20');
                        $this->change_element_type($this->payment_element, 'hidden');

                        $this->change_element_type($this->get_element_name_from_label('Address'), 'hidden');
                    }
                }
            }

            ///////   General Public Query  ///////
            $query = "SELECT * FROM `general_public_lottery` WHERE `email`='" . $username . "'";
            $result = db_query($query);
            $num_rows = mysql_num_rows($result);
            if ($num_rows > 0) {
                echo '<hr>';
                echo 'Tickets will be sent via USPS.' . "\n";
                for ($num_rows; $num_rows > 0; $num_rows--) {
                    $row = mysql_fetch_assoc($result);
                    $values = array_values($row);

                    $name = $this->get_element_name_from_label('Your Name');
                    $this->change_element_type($name, 'solidtext');
                    $this->set_value($name, $row['Your First Name'] . ' ' . $row['Your Last Name']);

                    $guest_name = $this->get_element_name_from_label('Guest Name');
                    $this->change_element_type($guest_name, 'solidtext');
                    $this->set_value($guest_name, $row['Guest First Name'] . ' ' . $row['Guest Last Name']);

                    $address = $this->get_element_name_from_label('Address');
                    $this->change_element_type($address, 'solidtext');
                    $this->set_value($address, $row['Your Address'] . ', ' . $row['Your City'] . ', ' . $row['Your State'] . ' ' . $row['Your Zip code']);
                    //// Two Tickets ////
                    if (($row['Guest First Name'] != 'NULL') && ($row['Do you want an extra guest ticket for $20?'] == 'Yes')) {
                        $this->set_value($payment_amount, '$40');
                        parent::set_value('payment_amount', '$40');
//                        $this->remove_element($payment_amount);
//                        parent::remove_element('payment_amount');

//                        $this->set_value($this->payment_element, '$40');
//                        $this->change_element_type($this->payment_element, 'solidtext');
//                        $this->remove_element($this->payment_element);
                        $this->change_element_type($payment_amount, 'hidden');
                        
                    }
                    //// One Tickets ////
                    if (($row['Guest First Name'] = 'NULL') && ($row['Do you want an extra guest ticket for $20?'] == 'No')) {
                        $this->set_value($this->payment_element, '$20');
                        $this->change_element_type($this->payment_element, 'hidden');
                        $this->change_element_type($this->get_element_name_from_label('Guest Name'), 'hidden');
                    }
                }
            }
            connectDB(REASON_DB);

            $this->change_element_type($this->get_element_name_from_label('Comments'), 'hidden');



//                $this->change_element_type('e-mail', 'text');
//                $this->set_value('e-mail', $email);
        } else {
//                if (reason_check_authentication ()) {
//                    echo '<div class = "loginlogout">';
//                    echo '<p>You are logged in as ' . reason_check_authentication() . '. Unfortunately, you do not have access to fill out this form.
//                            Please contact the Office of the Registrar if you think this is an error.</p>';
//                    echo '</div>';
//                }
            $this->show_form = false;
        }
    }

    function no_show_form() {
        $url = get_current_url();
        $parts = parse_url($url);
        $url = $parts['scheme'] . '://' . $parts['host'] . '/login/?dest_page=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];

        $txt = '<h3>Access to this form is restricted</h3>';
        //$txt .= '<p>You are not currently logged in. Luther College students and alumni have access to this form. The contents will be displayed after you login.' . "\n";
//        $txt .= '<p>To request a transcript electronically (which requires your user name and password, ie: norsekey),
//            please <a href="' . $url . '">log in</a>.</p>';
//        $txt .= '<p>The request form will be displayed after you login. This method <strong>requires former students
//            to pay</strong> for the transcripts via <strong>credit card</strong>.</p>';
//        $txt .= '<p>If you have forgotten your norsekey (username or password), please try our automated <a href="https://norsekey.luther.edu/prod1/forgot.php">
//                Forgot My Norsekey</a> system to reset your password.</p>';
//        if (reason_unique_name_exists('transcript_request_form')) {
//            $asset_url = '/registrar/assets/Transcript_Request_Form.pdf';
//        }
//        $txt .= '<p>If you prefer, you can mail in your request and payment (cash or check) by downloading and filling out
//            a <a href="' . $asset_url . '">Tanscript Request Form (pdf)</a>.</p>';
//        $txt .= '<div class = "loginlogout">';
//        $txt .= '<a href="' . $url . '">Log In</a>';
//        $txt .= '</div>';
        return $txt;
    }

//    function pre_show_form() {
//        /// show a logout link if logged in
//        if (reason_check_authentication ()) {
//            echo '<div class = "loginlogout">';
//            echo '<p>You are logged in as ' . $this->get_value('name') . '</p>';
//            $url = get_current_url();
//            $parts = parse_url($url);
//            $url = $parts['scheme'] . '://' . $parts['host'] . '/login/?logout=true&dest_page=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
//            $txt = '<a href="' . $url . '">Log Out</a>';
//            $txt .= '</div>';
//            echo $txt;
//        }
//        echo '<div id="transcriptRequestForm" class="pageOne">' . "\n";
//    }
//    function post_show_form() {
//        echo '</div>' . "\n";
//    }
}

?>