<?
reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'stock/pfproclass.php'); //<<<< Change this
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'CreditCardTestThorForm';

/**
 * Use only for running test payments on the live site. This is only for the form creator to see the process
 * all the way through. Switch to credit_card_payment for live transactions
 *
 * @package reason_package_local
 * @subpackage thor_view
 * * @author Steve Smith
 */

class CreditCardTestThorForm extends CreditCardThorForm
{
   // style up the form and add comments et al
    function on_every_time() {
        $this->set_value('submitter_ip', $_SERVER['REMOTE_ADDR']);

        $username = reason_check_authentication(); // this will force login
        $group = id_of('test_group');
        $has_access = (reason_user_is_in_group($username, $group));
        echo $username;
        if ($has_access) {
            die($username);
//            //$qlist = array('cn', 'sn');
//            //$dir = new directory_service();
//
//            //$lookup_login = 'uid=' . $username . ',dc=luther,dc=edu'; /// username is get login norsekey
//            //$dir->serv_inst['ldap_luther']->set_conn_param('cn=webauth,dc=luther,dc=edu', $lookup_login);
//
//            //$dir->search_by_attribute('uid', $username, $qlist);
//
//            //if (($dir->get_first_value('edupersonprimaryaffiliation') == 'Student') || ($dir->get_first_value('edupersonprimaryaffiliation') == 'Student - Not Enrolled this Term')) {
//           
//            $name = $dir->get_first_value('cn');
//            //$last_name  = $dir->get_first_value('sn');
//            $email = $dir->get_first_value('mail');
//
//            $this->show_form = true;
//
//            $this->change_element_type('name', 'solidtext');
//            $this->set_value('name', $name);
//            $this->change_element_type('e-mail', 'text');
//            $this->set_value('e-mail', $email);
        } else {
            if (reason_check_authentication ()) {
                echo '<div class = "loginlogout">';
                echo '<p>You are logged in as ' . reason_check_authentication() . '. Unfortunately, you do not have access to fill out this form.
                            Please contact the Office of the Registrar if you think this is an error.</p>';
                echo '</div>';
            }
            $this->show_form = false;
        }
    }

    function no_show_form() {
        $url = get_current_url();
        $parts = parse_url($url);
        $url = $parts['scheme'] . '://' . $parts['host'] . '/login/?dest_page=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];

        $txt = '<h3>Access to this form is restricted</h3>';
        //$txt .= '<p>You are not currently logged in. Luther College students and alumni have access to this form. The contents will be displayed after you login.' . "\n";
        $txt .= '<p>To request a transcript electronically (which requires your user name and password, ie: norsekey),
            please <a href="' . $url . '">log in</a>.</p>';
        $txt .= '<p>The request form will be displayed after you login. This method <strong>requires former students 
            to pay</strong> for the transcripts via <strong>credit card</strong>.</p>';
        $txt .= '<p>If you have forgotten your norsekey (username or password), please try our automated <a href="https://norsekey.luther.edu/prod1/forgot.php">
                Forgot My Norsekey</a> system to reset your password.</p>';
        if (reason_unique_name_exists('transcript_request_form')) {
            $asset_url = '/registrar/assets/Transcript_Request_Form.pdf';
        }
        $txt .= '<p>If you prefer, you can mail in your request and payment (cash or check) by downloading and filling out
            a <a href="' . $asset_url . '">Tanscript Request Form (pdf)</a>.</p>';
        $txt .= '<div class = "loginlogout">';

        
        $txt .= '<a href="' . $url . '">Log In</a>';
        $txt .= '</div>';
        return $txt;
    }

    function pre_show_form() {
        /// show a logout link if logged in
        if (reason_check_authentication ()) {
            echo '<div class = "loginlogout">';
            echo '<p>You are logged in as ' . $this->get_value('name') . '</p>';
            $url = get_current_url();
            $parts = parse_url($url);
            $url = $parts['scheme'] . '://' . $parts['host'] . '/login/?logout=true&dest_page=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
            $txt = '<a href="' . $url . '">Log Out</a>';
            $txt .= '</div>';
            echo $txt;
        }
        echo '<div id="transcriptRequestForm" class="pageOne">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }
}
?>