<?php

////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the transcript request form
//
////////////////////////////////////////////////////////////////////////////////

class TranscriptPageOneForm extends FormStep {

    var $is_student;
    var $_log_errors = true;
    var $error;
    var $elements = array(
        'amount' => 'cloaked',
        'your_information_header' => array(
            'type' => 'comment',
            'text' => '<h3>Your Information</h3>',
        ),
        'name' => array(
            'type' => 'text',
            'display_name' => 'Name',
            'size' => 35,
        ),
         'date_of_birth' => array(
            'type' => 'textdate',
            'use_picker' => false,
            'comments' => 'mm/dd/yyyy'
        ),
        'daytime_phone' => array(
            'type' => 'text',
            'size' => 20,
            'comments' => '<br>include area code'
        ),
        'email' => array(
            'type' => 'text',
            'size' => 35,
        ),
        'LATF' => array(
            'type' => 'radio_no_sort',
            'display_name' => 'What is your status?',
            'options' => array(
                'Paid' => 'Enrolled after August 1, 2013 or paid the Lifetime Academic Transcript Fee.', 
                'Not paid' => 'I have not paid the Lifetime Academic Transcript Fee.'),
        ),
        'unofficial_header' => array(
            'type' => 'comment',
            'text' => '<h3>Unofficial transcripts</h3>',
        ),
        'unofficial' => array(
            'type' => 'radio_no_sort',
            'display_name' => 'Would you like an unofficial transcript?',
            'options' => array(
                'email' => 'Electronic (Email - to the address above)',
                'postal' => 'Physical copy (Postal mail)',
                'no' => 'No, thanks'
                )
        ),
        'unofficial_address' => array(
            'type' => 'textarea',
            'display_name' => 'Address',
        ),
        'official_header' => array(
            'type' => 'comment',
            'text' => '<h3>Official transcripts</h3>',
        ),
        'official_comment' => array(
            'type' => 'comment',
            'text' => 'One address per request'
        ),
        'number_of_official' => array(
            'type' => 'text',
            'display_name' => 'Number of <em>official</em> transcripts',
            'size' => 3,
        ),
        // 'delivery_header' => array(
        //     'type' => 'comment',
        //     'text' => '<h3>Delivery Information</h3>',
        // ),
        // 'delivery_location_header' => array(
        //     'type' => 'comment',
        //     'text' => '<h4>Delivery Location</h4>',
        // ),
        'delivery_type' => array(
            'type' => 'radio_no_sort',
            'display_name' => 'How should your official transcripts be delivered?',
            'options' => array(
                'email' => 'Electronic (Email)',
                'postal' => 'Physical copy (Postal mail)'
                )
        ),
        'deliver_to' => array(
            'type' => 'radio_no_sort',
            'display_name' => 'Where should these transcripts be delivered?',
            'options' => array('Your address' => 'Your address', 'institution' => 'An Institution/Company')
        ),
        // 'delivery_email' => array(
        //     'type' => 'text',
        //     'display_name' => 'Email address',
        // ),
        'institution_name' => array(
            'type' => 'text',
            'display_name' => 'Institution/Company&nbsp;Name',
        ),
        'institution_attn' => array(
            'type' => 'text',
            'display_name' => 'Attention'
        ),
        'official_email' => array(
            'type' => 'text',
            'display_name' => 'Email address',
        ),
        'address' => array(
            'type' => 'textarea',
        ),
        'city' => array(
            'type' => 'text',
            'size' => 35,
        ),
        'state_province' => array(
            'type' => 'state_province',
            'display_name' => 'State/Province',
        ),
        'zip' => array(
            'type' => 'text',
            'display_name' => 'Zip/Postal Code',
            'size' => 10,
        ),
        'country' => array(
            'type' => 'country',
            'display_name' => 'Country',
        ),
        'delivery_time_header' => array(
            'type' => 'comment',
            'text' => '<h4>Delivery Time</h4>',
        ),
        'delivery_time' => array(
            'type' => 'radio',
            'display_name' => 'When to prepare transcripts',
            'options' => array(
                'overnight' => 'Overnight via UPS. - $30<br>
                    (must be placed before 2 p.m. CST)',
                'now' => 'Send out as soon as possible<br>
						(allow 48 hours processing time)',
                'after current semester' => 'Wait until current semester grades are posted',
                'after degree' => 'Wait until degree is posted',
            ),
        ),
        'submitter_ip' => 'hidden',
    );
    var $required = array('date_of_birth', 'daytime_phone', 'email', 'deliver_to', 'delivery_time', 'LATF', 'delivery_type');
    var $display_name = 'Transcript Request Info';
    var $error_header_text = 'Please check your form.';

    // style up the form and add comments et al
    function on_every_time() {
        $this->set_value('submitter_ip', $_SERVER['REMOTE_ADDR']);

        $username = reason_check_authentication(); // this will force login
        $group = id_of('transcripts_group');
        $has_access = (reason_user_is_in_group($username, $group));
        $has_access = true;
        $username = 'smitst01';

        if ($has_access) {
            $qlist = array('alumcn', 'sn');
            $qlist = array('cn', 'alumcn', 'displayname', 'edupersonaffiliation', 'alumaffiliation');
            $dir = new directory_service();

            $lookup_login = 'uid=' . $username . ',dc=luther,dc=edu'; /// username is get login norse key
            $dir->serv_inst['ldap_luther']->set_conn_param('cn=webauth,dc=luther,dc=edu', $lookup_login);

            $dir->search_by_attribute('uid', $username, $qlist);
           
            $name = $dir->get_first_value('cn');
            $display_name = $dir->get_first_value('displayname');
            $alum_name = $dir->get_first_value('alumcn');
            $first_name = $dir->get_first_value('givenname');
            $last_name  = $dir->get_first_value('sn');
            $email = $dir->get_first_value('mail');

            $this->show_form = true;

            $this->change_element_type('name', 'solidtext');

            /*
             * if alumcn is set (e.g. the user is an alumni
             * use it as the name
             * else use displayname
             */
            if ($alum_name) {
                $this->set_value('name', $alum_name);
            } else {
                $this->set_value('name', $display_name);
            }
//            if ($dir->get_first_value('edupersonaffiliation') == 'Alumni' || $dir->get_first_value('alumaffiliation') == 'Alumni' ){
//                $this->set_value('name', $alum_name);
//            } else {
//                $this->set_value('name', $display_name);
//            }
            $this->change_element_type('email', 'text');
            $this->set_value('email', $email);
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
        $txt .= '<h3>Electronic Request</h3>';
        //$txt .= '<p>You are not currently logged in. Luther College students and alumni have access to this form. The contents will be displayed after you login.' . "\n";
        $txt .= '<p>To request a transcript, official or unofficial, electronically (requires user name and
                password, ie: norse key), please <a href="' . $url . '">log in</a>.</p>';
        $txt .= '<p>The request form will be displayed after you login. This method <u>requires graduates/
former students to pay</u> for the transcript via credit card.</p>';
        $txt .= '<p>If you have forgotten your norse key (user name or password), please try our automated
                <a href="https://norsekey.luther.edu/prod1/forgot.php">
                Forgot My Norse Key</a> system to reset your password.</p>';
        if (reason_unique_name_exists('transcript_request_form')) {
            $asset_url = '/registrar/assets/Transcript_Request_Form.pdf';
        }
        
         $txt .= '<div class = "loginlogout">';

        $txt .= '<a href="' . $url . '">Log In</a>';
        $txt .= '</div>';
        
        $txt .= '<h3>Mail or Fax Transcript Request form (pdf)</h3>';
        
        $txt .= '<p>If you prefer, you can mail in your request and payment (cash, check or money order) by downloading and filling out
            a <a href="' . $asset_url . '">Transcript Request Form (pdf)</a>.</p>';
        
        $txt .= '<p><b>Questions:</b> Contact the Registrar’s Office at 563-387-1234 or <a href="mailto:registrar@luther.edu">registrar@luther.edu</a> if
            you have questions regarding your transcript request.</p>';
        
        
       
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

    /*
     * Payment is required for alumns
     * No payment is required for current students
     * No payment is required for an unofficial transcript
     */

    function needs_payment() {
       $pay_amount = 0;
       $official_number = $this->get_value('number_of_official');
       $deliver_time = $this->get_value('delivery_time');

       if ($this->get_value('LATF') == 'Not paid') {
            $pay_amount = $pay_amount + ($official_number * 5);
        }
        $this->set_value('amount', $pay_amount);

        if ($this->get_value('delivery_time') == 'overnight') {
            $pay_amount = $pay_amount + 30;
            $this->set_value('amount', $pay_amount);
        }

        if ($pay_amount == 0) {
            return 'TranscriptRequestConfirmation';
        } else {
            return 'TranscriptPageTwoForm';
        }
    }

    function  pre_error_check_actions() {
        parent::pre_error_check_actions();
        if ($this->get_value('unofficial') /*&& !$this->get_value('unofficial_address)')*/ && !$this->get_value('number_of_official')) {
            $this->remove_required('deliver_to');
            $this->remove_required('delivery_time');
            $this->remove_required('delivery_type');
        }
    }
    function run_error_checks() {
        parent::run_error_checks();
		
		if (!$this->get_value('unofficial') && !$this->get_value('number_of_official')){
			$this->set_error('unofficial', 'Please indicate whether you\'d like to recieve an official or unofficial transcript.');
		}

        if (($this->get_value('unofficial') == 'postal') && !$this->get_value('unofficial_address')) {
            $this->set_error('unofficial_address', 'Since you\'d like an unofficial transcript, please include an address.');
        }
        if ($this->get_value('number_of_official') && (!preg_match('/^\d+$/', $this->get_value('number_of_official')))) {
            $this->set_error('number_of_official', "Please enter a whole number.");
        }
        if ($this->get_value('delivery_type') == 'paper' && (!$this->get_value('number_of_official') ) ) {
       // if (!$this->get_value('number_of_official'))  {
           $this->set_error('number_of_official', 'Since you chose to send paper transcripts, please tell us how many to send.');
        }
		if ($this->get_value('deliver_to') && !$this->get_value('number_of_official')){
			$this->set_error('number_of_official', 'Please indicate the number of official transcripts we should send?');
		}
        if ($this->get_value('number_of_official') && $this->get_value('delivery_type') == 'postal' && $this->get_value('deliver_to') == 'Your address'
            && (!$this->get_value('address') || !$this->get_value('city') || !$this->get_value('state_province')
                || !$this->get_value('zip') || !$this->get_value('country') ) ) {
                    $this->set_error('address', 'Please enter the full delivery address.');
        }
        if ($this->get_value('number_of_official') && $this->get_value('delivery_type') == 'email' && $this->get_value('deliver_to') == 'Your address'
           && !$this->get_value('official_email') ) {
                   $this->set_error('official_email', 'Please enter an email address.');
        } 
        if ($this->get_value('number_of_official') && $this->get_value('delivery_type') == 'postal' && $this->get_value('deliver_to') == 'institution'
           && (!$this->get_value('institution_name')
               || !$this->get_value('address')
               || !$this->get_value('city')
               || !$this->get_value('state_province')
               || !$this->get_value('zip')
               || !$this->get_value('country') ) ) {
                   $this->set_error('institution_name', 'Please enter the full delivery address.');
               echo $this->get_value('zip');
        }
        if ($this->get_value('number_of_official') && $this->get_value('delivery_type') == 'email' && $this->get_value('deliver_to') == 'institution'
           && (!$this->get_value('institution_name')|| !$this->get_value('official_email') ) ) {
               $this->set_error('institution_name', 'Please enter institution details.');
       }
    }
}
