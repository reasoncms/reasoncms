<?

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');
// include_once '/usr/local/webapps/reason/reason_package_local/carl_util/dir_service/services/ds_mysql_royal_visit.php';
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'ChristmasAtLutherForm';

/**
 * Use only for running test payments on the live site. This is only for the form creator to see the process
 * all the way through. Switch to credit_card_payment for live transactions
 *
 * @package reason_package_local
 * @subpackage thor_view
 * * @author Steve Smith
 */
class ChristmasAtLutherForm extends CreditCardThorForm {
    var $thursday_tickets;
    var $friday630_tickets;
    var $friday915_tickets;
    var $saturday_tickets;
    var $sunday_tickets;

    var $thursday_students;
    var $friday630_students;
    var $friday915_students;
    var $saturday_students;
    var $sunday_students;

    var $thursday_names;
    var $friday630_names;
    var $friday915_names;
    var $saturday_names;
    var $sunday_names;

    var $thursday_count = 0;
    var $friday630_count = 0;
    var $friday915_count = 0;
    var $saturday_count = 0;
    var $sunday_count = 0;

    // style up the form and add comments et al
    function on_every_time() {
        parent::on_every_time();

        if (reason_check_authentication() == 'smitst01'){
            $this->is_in_testing_mode = true;
        }

        $this->thursday_tickets = $this->get_element_name_from_label('Thursday');
        $this->friday630_tickets = $this->get_element_name_from_label('Friday630');
        $this->friday915_tickets = $this->get_element_name_from_label('Friday915');
        $this->saturday_tickets = $this->get_element_name_from_label('Saturday');
        $this->sunday_tickets = $this->get_element_name_from_label('Sunday');

        $this->thursday_students = $this->get_element_name_from_label('ThursdayStudents');
        $this->friday630_students = $this->get_element_name_from_label('Friday630Students');
        $this->friday915_students = $this->get_element_name_from_label('Friday915Students');
        $this->saturday_students = $this->get_element_name_from_label('SaturdayStudents');
        $this->sunday_students = $this->get_element_name_from_label('SundayStudents');

        $this->thursday_names = $this->get_element_name_from_label('ThursdayNames');
        $this->friday630_names = $this->get_element_name_from_label('Friday630Names');
        $this->friday915_names = $this->get_element_name_from_label('Friday915Names');
        $this->saturday_names = $this->get_element_name_from_label('SaturdayNames');
        $this->sunday_names = $this->get_element_name_from_label('SundayNames');

        //Style up form
        $this->change_element_type($this->thursday_tickets, 'radio_inline', array(
            'display_name' => 'Number of tickets for Thursday\'s performance', 'options' => array(1 => 1, 2 => 2, 3 => 3 , 4 => 4)));
        $this->change_element_type($this->friday630_tickets, 'radio_inline', array(
            'display_name' => 'Number of tickets for Friday\'s, 6:30 performance', 'options' => array('1','2','3','4')));
        $this->change_element_type($this->friday915_tickets, 'radio_inline', array(
            'display_name' => 'Number of tickets for Friday\'s, performance', 'options' => array('1','2','3','4')));
        $this->change_element_type($this->saturday_tickets, 'radio_inline', array(
            'display_name' => 'Number of tickets for Saturday\'s, performance', 'options' => array('1','2','3','4')));
        $this->change_element_type($this->sunday_tickets, 'radio_inline', array(
            'display_name' => 'Number of tickets for Sunday\'s, performance', 'options' => array('1','2','3','4')));

        $this->change_element_type($this->thursday_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));
        $this->change_element_type($this->friday630_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));
        $this->change_element_type($this->friday915_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));
        $this->change_element_type($this->saturday_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));
        $this->change_element_type($this->sunday_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));

        $this->change_element_type($this->thursday_names, 'text', array('display_name' => 'Name(s) of non-participating Luther students'));
        $this->change_element_type($this->friday630_names, 'text', array('display_name' => 'Name(s) of non-participating Luther students'));
        $this->change_element_type($this->friday915_names, 'text', array('display_name' => 'Name(s) of non-participating Luther students'));
        $this->change_element_type($this->saturday_names, 'text', array('display_name' => 'Name(s) of non-participating Luther students'));
        $this->change_element_type($this->sunday_names, 'text', array('display_name' => 'Name(s) of non-participating Luther students'));
    }

    function run_error_checks(){
        // die(pray($this->get_model()));
        // die(pray($this));

        $thur_ticks = intval($this->get_value($this->thursday_tickets));
        $fri630_ticks = intval($this->get_value($this->friday630_tickets));
        $fri915_ticks = intval($this->get_value($this->friday915_tickets));
        $sat_ticks = intval($this->get_value($this->saturday_tickets));
        $sun_ticks = intval($this->get_value($this->sunday_tickets));
        
        if (($thur_ticks + $fri630_ticks + $fri915_ticks + $sat_ticks + $sun_ticks) > 4 ){
            $this->set_error($this->thursday_tickets, 'You can only order a total of 4 tickets.');
        }

        $thur_students = intval($this->get_value($this->thursday_students));
        $fri630_students = intval($this->get_value($this->friday630_students));
        $fri915_students = intval($this->get_value($this->friday915_students));
        $sat_students = intval($this->get_value($this->saturday_students));
        $sun_students = intval($this->get_value($this->sunday_students));
        
        // if (!is_int($thursday_students)) {
        //     $this->set_error($this->thursday_students, 'NOOOOO');
        // }

        if ($thur_students && !$this->get_value($this->thursday_names)){
            $this->set_error($this->thursday_names, 'Please provide the name(s) of the students');
        }
        if ($fri630_students && !$this->get_value($this->friday630_names)){
            $this->set_error($this->friday630_names, 'Please provide the name(s) of the students');
        }
        if ($fri915_students && !$this->get_value($this->friday915_names)){
            $this->set_error($this->friday915_names, 'Please provide the name(s) of the students');
        }
        if ($sat_students && !$this->get_value($this->saturday_names)){
            $this->set_error($this->saturday_names, 'Please provide the name(s) of the students');
        }
        if ($sun_students && !$this->get_value($this->sunday_names)){
            $this->set_error($this->sunday_names, 'Please provide the name(s) of the students');
        }

        parent::run_error_checks();
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

    function get_available_ticket_count($performance){
        //Connect to Available tickets database
        connectDB('christmas_available_ticket_connection');
        
        //


        //reconnect to ReasonDB
        connectDB(REASON_DB);

        return $available;

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