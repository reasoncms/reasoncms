<?

reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH . 'stock/pfproclass.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'ChristmasAtLutherForm';

/**
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

    // style up the form and add comments et al
    function on_every_time() {

        parent::on_every_time();

        if (reason_check_authentication() == 'smitst01'){
            $this->is_in_testing_mode = true;
        }
        if (reason_check_authentication() == 'wilbbe01'){
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
        $this->change_element_type($this->thursday_tickets, 'select', array(
            'display_name' => 'Number of tickets for Thursday\'s performance', 'options' => array(1 => 1, 2 => 2, 3 => 3 , 4 => 4)));
        $this->change_element_type($this->friday630_tickets, 'select', array(
            'display_name' => 'Number of tickets for Friday\'s 6:30 performance', 'options' => array(1 => 1, 2 => 2, 3 => 3 , 4 => 4)));
        $this->change_element_type($this->friday915_tickets, 'select', array(
            'display_name' => 'Number of tickets for Friday\'s 9:15 performance', 'options' => array(1 => 1, 2 => 2, 3 => 3 , 4 => 4)));
        $this->change_element_type($this->saturday_tickets, 'select', array(
            'display_name' => 'Number of tickets for Saturday\'s performance', 'options' => array(1 => 1, 2 => 2, 3 => 3 , 4 => 4)));
        $this->change_element_type($this->sunday_tickets, 'select', array(
            'display_name' => 'Number of tickets for Sunday\'s performance', 'options' => array(1 => 1, 2 => 2, 3 => 3 , 4 => 4)));

        $this->change_element_type($this->thursday_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));
        $this->change_element_type($this->friday630_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));
        $this->change_element_type($this->friday915_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));
        $this->change_element_type($this->saturday_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));
        $this->change_element_type($this->sunday_students, 'text', array('display_name' => 'Number for non-participating Luther students', 'size' => '3', 'comments' => '<small>No Charge</small>'));

        $this->change_element_type($this->thursday_names, 'text', array('display_name' => 'Name(s) of non-participating Luther student(s)'));
        $this->change_element_type($this->friday630_names, 'text', array('display_name' => 'Name(s) of non-participating Luther student(s)'));
        $this->change_element_type($this->friday915_names, 'text', array('display_name' => 'Name(s) of non-participating Luther student(s)'));
        $this->change_element_type($this->saturday_names, 'text', array('display_name' => 'Name(s) of non-participating Luther student(s)'));
        $this->change_element_type($this->sunday_names, 'text', array('display_name' => 'Name(s) of non-participating Luther student(s)'));

        $alum = $this->get_element_name_from_label('Luther College alumnus');
        $class = $this->get_element_name_from_label('Class Year');
        $parent = $this->get_element_name_from_label('Current Parent of a Luther College student');
        $this->change_element_type($alum, 'checkbox');

        $this->change_element_type($class, 'text', array('size' => 5));

        $this->change_element_type($parent, 'checkbox');

        $this->change_element_type($this->get_element_name_from_label('Accessibilty Issues'), 'textarea_no_label');

        //adding divs to show how many tickets are left
        $this->add_element('thursday_count', 'comment', array(
            'text' => '<div class="countClass"><p id="thursday_count"> tickets remaining</p></div>'));
        $this->add_element_group('inline', 'thursday_group', array($this->thursday_tickets, 'thursday_count'), array('use_element_labels' => false, 'use_group_display_name' => true, 'display_name' => 'Number of tickets for Thursday\'s performance'));
        $this->move_element('thursday_group', 'before', $this->thursday_students);
        
        $this->add_element('friday630_count', 'comment', array(
            'text' => '<div id="friday630_count" class="countClass"></div>'));
        $this->add_element_group('inline', 'friday630_group', array($this->friday630_tickets, 'friday630_count'), array('use_element_labels' => false, 'use_group_display_name' => true, 'display_name' => 'Number of tickets for Friday\'s 6:30 performance'));
        $this->move_element('friday630_group', 'before', $this->friday630_students);

        $this->add_element('friday915_count', 'comment', array(
            'text' => '<div id="friday915_count" class="countClass"></div>'));
        $this->add_element_group('inline', 'friday915_group', array($this->friday915_tickets, 'friday915_count'), array('use_element_labels' => false, 'use_group_display_name' => true, 'display_name' => 'Number of tickets for Friday\'s 9:15 performance'));
        $this->move_element('friday915_group', 'before', $this->friday915_students);

        $this->add_element('saturday_count', 'comment', array(
            'text' => '<div id="saturday_count" class="countClass"></div>'));
        $this->add_element_group('inline', 'saturday_group', array($this->saturday_tickets, 'saturday_count'), array('use_element_labels' => false, 'use_group_display_name' => true, 'display_name' => 'Number of tickets for Saturday\'s performance'));
        $this->move_element('saturday_group', 'before', $this->saturday_students);

        $this->add_element('sunday_count', 'comment', array(
            'text' => '<div id="sunday_count" class="countClass"></div>'));
        $this->add_element_group('inline', 'sunday_group', array($this->sunday_tickets, 'sunday_count'), array('use_element_labels' => false, 'use_group_display_name' => true, 'display_name' => 'Number of tickets for Sunday\'s performance'));
        $this->move_element('sunday_group', 'before', $this->sunday_students);

        //adding a reservation box
        $this->add_element('code', 'text', array(
            'display_name' => 'Reservation Code',
            'size' => 7));
        $this->move_element('code', 'before', $this->get_element_name_from_label('First Name'));
        $this->add_required('code');
    }

    function run_error_checks(){
        
        $account = $this->get_value('code');
        $last = $this->get_value_from_label('Last Name');  
        
        try{
            $pdo = new PDO("mysql:dbname=tix_for_reason;host=database-1.luther.edu","reason_user","8shtKGFGw4.v7cMm");
            $statement = $pdo->prepare("SELECT first FROM customer where account=" . $account . " and last ='" . $last . "'");
            $statement->execute();
            $results=$statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
        connectDB(REASON_DB);

        if (!$results){
            $this->set_error('code', 'You are not eligible to order tickets');
        }

        $thur_tix = intval($this->get_value($this->thursday_tickets));
        $fri630_tix = intval($this->get_value($this->friday630_tickets));
        $fri915_tix = intval($this->get_value($this->friday915_tickets));
        $sat_tix = intval($this->get_value($this->saturday_tickets));
        $sun_tix = intval($this->get_value($this->sunday_tickets));
        
        if (($thur_tix + $fri630_tix + $fri915_tix + $sat_tix + $sun_tix) > 4 ){
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
            $this->set_error($this->thursday_names, 'Please provide the name(s) of the student(s)');
        }
        if ($fri630_students && !$this->get_value($this->friday630_names)){
            $this->set_error($this->friday630_names, 'Please provide the name(s) of the student(s)');
        }
        if ($fri915_students && !$this->get_value($this->friday915_names)){
            $this->set_error($this->friday915_names, 'Please provide the name(s) of the student(s)');
        }
        if ($sat_students && !$this->get_value($this->saturday_names)){
            $this->set_error($this->saturday_names, 'Please provide the name(s) of the student(s)');
        }
        if ($sun_students && !$this->get_value($this->sunday_names)){
            $this->set_error($this->sunday_names, 'Please provide the name(s) of the student(s)');
        }

        if (intval($this->get_value('payment_amount')) != intval($this->get_amount())){
        $this->set_error('payment_amount', '<strong>Incorrect Payment Amount</strong>. The amount set in the payment amount field does not equal the cost for all chosen options. Please check your math or <a href="http://enable-javascript.com/" target="_blank">enable javascript</a> to have the form automatically fill in this field.');
      }

        parent::run_error_checks();
    }

    function get_amount(){
        $total = intval($this->get_value($this->thursday_tickets))
            + intval($this->get_value($this->friday630_tickets))
            + intval($this->get_value($this->friday915_tickets))
            + intval($this->get_value($this->saturday_tickets))
            + intval($this->get_value($this->sunday_tickets));
        $total = $total * 25;
        

        return $total;
    }
}

?>