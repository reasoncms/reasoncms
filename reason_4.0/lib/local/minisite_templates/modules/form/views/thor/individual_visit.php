<?

include_once('reason_header.php');
include_once('/usr/local/webapps/reason/reason_package_local/disco/plasmature/types/datetime.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
//include_once('disco/boxes/boxes.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'IndividualVisitForm';

/**
 * IndividualVisitForm adds visit request info to Thor form
 * that gets personal info
 *
 * @author Steve Smith
 */
class IndividualVisitForm extends DefaultThorForm {

    // do NOT use date ranges (i.e. 20110801-20110805) the error checking is not setup to handle this.
    var $disabled_dates = array(
        '20111010', '20111020', '20111021', '20111024', '20111025', '20111026',
        '20111111', '20111124', '20111125',
        '20111216', '20111219', '20111220', '20111221', '20111222', 'xxxx1223', 'xxxx1224', 'xxxx1225', '20111226', '20111227', '20111228', '20111229', '20111230', 'xxxx1231',
        'xxxx0101', '20120127', '20120130', '20120131',
        '20120218', '20120225',
        '20120319', '20120320', '20120321', '20120322', '20120323', '20120330',
        '20120406', '20120409', '20120423',
		'20120521', '20120522', '20120523', '20120524',
		'20120618',
        '20120704', '20120724',
        '20120806', '20120807', '20120808', '20120809', '20120810', '20120823'
     );

    var $elements = array(
        'high_school' => array(
            'type' => 'text',
            'display_style' => 'normal',
        ),
        'graduation_year' => array(
            'type' => 'year',
            'num_years_after_today' => 4,
            'num_years_before_today' => 4,
        ),
        'transfer' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'Are you a transfer student?',
            'display_style' => 'normal',
            'options' => array('Yes' => 'Yes', 'No' => 'No',),
        ),
        'transfer_college' => array(
            'type' => 'textarea',
            'display_name' => 'If yes, what is the name and address of the school you previously attended?'
        ),
        'visit_activities' => array(
            'type' => 'comment',
            'text' => '<h3>Please check any of the following activities
					that you would like to do as part of your campus visit.
					We will try to accommodate as many of your requests as 
					possible.</h3>',
        ),
        'meet_counselor' => array(
            'type' => 'checkboxfirst',
            'display_name' => 'Meet with an Admissions Counselor',
            'display_style' => 'normal',
            'comments' => '<small>  (30 min)</small>',
        ),
        'tour' => array(
            'type' => 'checkboxfirst',
            'display_name' => 'Take a campus tour',
            'display_style' => 'normal',
            'comments' => '<small>  (60 min)</small>',
        ),
        /*         * * Remove for Summer ** */
        // 'meet_faculty' => array(
        //     'type' => 'checkboxfirst',
        //     'display_name' => 'Meet with a faculty member',
        //     'display_style' => 'normal',
        //     'comments' => '<small>  (30 min)</small>',
        // ),
        /*         * * Remove for Summer ** */
        // 'meet_faculty_details' => array(
        //     'type' => 'select_no_sort',
        //     'add_null_value_to_top' => true,
        //     'display_name' => 'Select Department',
        //     'options' => array(
        //         'Accounting' => 'Accounting',
        //         'Africana Studies' => 'Africana Studies',
        //         'Art' => 'Art',
        //         'Athletic Training' => 'Athletic Training',
        //         'Biblical Languages' => 'Biblical Languages',
        //         'Biology' => 'Biology',
        //         'Business' => 'Business',
        //         'Chemistry' => 'Chemistry',
        //         'Classical Studies' => 'Classical Studies',
        //         'Classics' => 'Classics',
        //         'Communication Studies' => 'Communication Studies',
        //         'Computer Science' => 'Computer Science',
        //         'Economics' => 'Economics',
        //         'Education' => 'Education',
        //         'English' => 'English',
        //         'Environmental Studies' => 'Environmental Studies',
        //         'French' => 'French',
        //         'German' => 'German',
        //         'Health' => 'Health',
        //         'History' => 'History',
        //         'International Studies' => 'International Studies',
        //         'Management' => 'Management',
        //         'Management Information Systems' => 'Management Information Systems',
        //         'Mathematics' => 'Mathematics',
        //         'Mathematics/Statistics' => 'Mathematics/Statistics',
        //         'Museum Studies' => 'Museum Studies',
        //         'Music' => 'Music',
        //         'Nursing' => 'Nursing',
        //         'Philosophy' => 'Philosophy',
        //         'Physical Education' => 'Physical Education',
        //         'Physics' => 'Physics',
        //         'Political Science' => 'Political Science',
        //         'Psychology' => 'Psychology',
        //         'Religion' => 'Religion',
        //         'Russian Studies' => 'Russian Studies',
        //         'Scandinavian Studies' => 'Scandinavian Studies',
        //         'Social Welfare' => 'Social Welfare',
        //         'Social Work' => 'Social Work',
        //         'Sociology' => 'Sociology',
        //         'Spanish' => 'Spanish',
        //         'Speech and Theatre' => 'Speech and Theatre',
        //         'Theatre/Dance' => 'Theatre/Dance',
        //         'Women\'s and Gender Studies' => 'Women\'s and Gender Studies',
        //         '--' => '--',
        //         'PreProfessional Programs' => 'PreProfessional Programs',
        //         'Arts Management' => '--Arts Management',
        //         'International Management Studies' => '--International Management Studies',
        //         'Predentistry' => '--Predentistry',
        //         'Preengineering' => '--Preengineering',
        //         'Prelaw' => '--Prelaw',
        //         'Premedicine' => '--Premedicine',
        //         'Preoptometry' => '--Preoptometry',
        //         'Prepharmacy' => '--Prepharmacy',
        //         'Prephysical Therapy' => '--Prephysical Therapy',
        //         'Preveterinary Medicine' => '--Preveterinary Medicine',
        //         'Sports Management' => '--Sports Management'
        //     ),
        // ),
        /*         * * Remove for Summer ** */
        // 'meet_second_faculty' => array(
        //     'type' => 'checkboxfirst',
        //     'display_name' => 'Meet with a second faculty member',
        //     'display_style' => 'normal',
        //     'comments' => '<small>  (30 min)</small>',
        // ),
        /*         * * Remove for Summer ** */
        // 'meet_second_faculty_details' => array(
        //     'type' => 'select_no_sort',
        //     'add_null_value_to_top' => true,
        //     'display_name' => 'Select Department',
        //     'options' => array(
        //         'Accounting' => 'Accounting',
        //         'Africana Studies' => 'Africana Studies',
        //         'Art' => 'Art',
        //         'Athletic Training' => 'Athletic Training',
        //         'Biblical Languages' => 'Biblical Languages',
        //         'Biology' => 'Biology',
        //         'Business' => 'Business',
        //         'Chemistry' => 'Chemistry',
        //         'Classical Studies' => 'Classical Studies',
        //         'Classics' => 'Classics',
        //         'Communication Studies' => 'Communication Studies',
        //         'Computer Science' => 'Computer Science',
        //         'Economics' => 'Economics',
        //         'Education' => 'Education',
        //         'English' => 'English',
        //         'Environmental Studies' => 'Environmental Studies',
        //         'French' => 'French',
        //         'German' => 'German',
        //         'Health' => 'Health',
        //         'History' => 'History',
        //         'International Studies' => 'International Studies',
        //         'Management' => 'Management',
        //         'Management Information Systems' => 'Management Information Systems',
        //         'Mathematics' => 'Mathematics',
        //         'Mathematics/Statistics' => 'Mathematics/Statistics',
        //         'Museum Studies' => 'Museum Studies',
        //         'Music' => 'Music',
        //         'Nursing' => 'Nursing',
        //         'Philosophy' => 'Philosophy',
        //         'Physical Education' => 'Physical Education',
        //         'Physics' => 'Physics',
        //         'Political Science' => 'Political Science',
        //         'Psychology' => 'Psychology',
        //         'Religion' => 'Religion',
        //         'Russian Studies' => 'Russian Studies',
        //         'Scandinavian Studies' => 'Scandinavian Studies',
        //         'Social Welfare' => 'Social Welfare',
        //         'Social Work' => 'Social Work',
        //         'Sociology' => 'Sociology',
        //         'Spanish' => 'Spanish',
        //         'Speech and Theatre' => 'Speech and Theatre',
        //         'Theatre/Dance' => 'Theatre/Dance',
        //         'Women\'s and Gender Studies' => 'Women\'s and Gender Studies',
        //         '--' => '--',
        //         'PreProfessional Programs' => 'PreProfessional Programs',
        //         'Arts Management' => '--Arts Management',
        //         'International Management Studies' => '--International Management Studies',
        //         'Predentistry' => '--Predentistry',
        //         'Preengineering' => '--Preengineering',
        //         'Prelaw' => '--Prelaw',
        //         'Premedicine' => '--Premedicine',
        //         'Preoptometry' => '--Preoptometry',
        //         'Prepharmacy' => '--Prepharmacy',
        //         'Prephysical Therapy' => '--Prephysical Therapy',
        //         'Preveterinary Medicine' => '--Preveterinary Medicine',
        //         'Sports Management' => '--Sports Management'
        //     ),
        // ),
        /*         * * Remove for Summer ** */
        // 'observe_class' => array(
        //     'type' => 'checkboxfirst',
        //     'display_name' => 'Sit in on a class',
        //     'display_style' => 'normal',
        //     'comments' => '<small> MWF (60 min) T Th (90 min)</small>',
        // ),
//        /*** Remove for Summer ***/
        // 'observe_class_details' => array(
        //     'type' => 'select_no_sort',
        //     'add_null_value_to_top' => true,
        //     'display_name' => 'Select Department',
        //     'options' => array(
        //         'Accounting' => 'Accounting',
        //         'Africana Studies' => 'Africana Studies',
        //         'Art' => 'Art',
        //         'Athletic Training' => 'Athletic Training',
        //         'Biblical Languages' => 'Biblical Languages',
        //         'Biology' => 'Biology',
        //         'Business' => 'Business',
        //         'Chemistry' => 'Chemistry',
        //         'Classical Studies' => 'Classical Studies',
        //         'Classics' => 'Classics',
        //         'Communication Studies' => 'Communication Studies',
        //         'Computer Science' => 'Computer Science',
        //         'Economics' => 'Economics',
        //         'Education' => 'Education',
        //         'English' => 'English',
        //         'Environmental Studies' => 'Environmental Studies',
        //         'French' => 'French',
        //         'German' => 'German',
        //         'Health' => 'Health',
        //         'History' => 'History',
        //         'International Studies' => 'International Studies',
        //         'Management' => 'Management',
        //         'Management Information Systems' => 'Management Information Systems',
        //         'Mathematics' => 'Mathematics',
        //         'Mathematics/Statistics' => 'Mathematics/Statistics',
        //         'Museum Studies' => 'Museum Studies',
        //         'Music' => 'Music',
        //         'Nursing' => 'Nursing',
        //         'Philosophy' => 'Philosophy',
        //         'Physical Education' => 'Physical Education',
        //         'Physics' => 'Physics',
        //         'Political Science' => 'Political Science',
        //         'Psychology' => 'Psychology',
        //         'Religion' => 'Religion',
        //         'Russian Studies' => 'Russian Studies',
        //         'Scandinavian Studies' => 'Scandinavian Studies',
        //         'Social Welfare' => 'Social Welfare',
        //         'Social Work' => 'Social Work',
        //         'Sociology' => 'Sociology',
        //         'Spanish' => 'Spanish',
        //         'Speech and Theatre' => 'Speech and Theatre',
        //         'Theatre/Dance' => 'Theatre/Dance',
        //         'Women\'s and Gender Studies' => 'Women\'s and Gender Studies',
        //         '--' => '--',
        //         'PreProfessional Programs' => 'PreProfessional Programs',
        //         'Arts Management' => '--Arts Management',
        //         'International Management Studies' => '--International Management Studies',
        //         'Predentistry' => '--Predentistry',
        //         'Preengineering' => '--Preengineering',
        //         'Prelaw' => '--Prelaw',
        //         'Premedicine' => '--Premedicine',
        //         'Preoptometry' => '--Preoptometry',
        //         'Prepharmacy' => '--Prepharmacy',
        //         'Prephysical Therapy' => '--Prephysical Therapy',
        //         'Preveterinary Medicine' => '--Preveterinary Medicine',
        //         'Sports Management' => '--Sports Management'
        //     ),
        // ),
        /*         * * Remove for Summer ** */
        // 'chapel' => array(
        //     'type' => 'checkboxfirst',
        //     'display_style' => 'normal',
        //     'comments' => '<small>  (30 min) daily at 10:30</small>',
        // ),
        'lunch' => array(
            'type' => 'checkboxfirst',
            'display_name' => 'Lunch',
            'display_style' => 'normal',
            'comments' => '<small>  (30-60 min)</small>',
        ),
        /*** Remove for Summer ***/
        // 'meet_coach' => array(
        //     'type' => 'checkboxfirst',
        //     'display_name' => 'Conversation with a coach',
        //     'display_style' => 'normal',
        //     'comments' => '<small>  (30 min)</small>',
        // ),
        /*** Remove for Summer ***/
        // 'meet_coach_details' => array(
        //     'type' => 'select',
        //     'display_name' => 'Select Sport',
        //     'add_null_value_to_top' => true,
        //     'options' => array(
        //         'Baseball' => 'Baseball',
        //         'Basketball' => 'Basketball',
        //         'Cross Country' => 'Cross Country',
        //         'Football' => 'Football',
        //         'Golf' => 'Golf',
        //         'Soccer' => 'Soccer',
        //         'Softball' => 'Softball',
        //         'Swimming & Diving' => 'Swimming & Diving',
        //         'Tennis' => 'Tennis',
        //         'Track & Field' => 'Track & Field',
        //         'Volleyball' => 'Volleyball',
        //         'Wrestling' => 'Wrestling',
        //     ),
        // ),
        /*         * * Remove for Summer ** */
        // 'choir' => array(
        //     'type' => 'checkboxfirst',
        //     'display_name' => 'Observe a choir rehearsal, if available',
        //     'display_style' => 'normal',
        //     'comments' => '<small>  MWF 1:30 (60 min)</small>',
        // ),
        /*         * * Remove for Summer ** */
        // 'band' => array(
        //     'type' => 'checkboxfirst',
        //     'display_name' => 'Observe a band rehearsal, if available',
        //     'display_style' => 'normal',
        //     'comments' => '<small>  MWF 12:15 (60 min)</small>',
        // ),
        /*         * * Remove for Summer ** */
        // 'orchestra' => array(
        //     'type' => 'checkboxfirst',
        //     'display_name' => 'Observe an orchestra rehearsal, if available',
        //     'display_style' => 'normal',
        //     'comments' => '<small>  MTWTHF 4:00 (60 min)</small>',
        // ),
        /*** Remove for Summer ***/
       // 'music_audition' => array(
       //     'type' => 'checkboxfirst',
       //     'display_name' => 'Perform a music audition for scholarship',
       //     'display_style' => 'normal',
       //     'comments' => '<small>  Seniors Only (30 min)</small>',
       // ),
        /*** Remove for Summer ***/
       // 'music_audition_details' => array(
       //     'type' => 'select_no_sort_js',
       //     'display_name' => 'Select Instrument/Voice',
       //     'add_null_value_to_top' => true,
       //     'options' => array(
       //         'Flute' => 'Flute',
       //         'Oboe' => 'Oboe',
       //         'Clarinet' => 'Clarinet',
       //         'Saxophone' => 'Saxophone',
       //         'Bassoon' => 'Bassoon',
       //         'Horn' => 'Horn',
       //         'Trumpet' => 'Trumpet',
       //         'Trombone' => 'Trombone',
       //         'Euphonium' => 'Euphonium',
       //         'Tuba' => 'Tuba',
       //         'Percussion' => 'Percussion',
       //         'Piano' => 'Piano',
       //         'Harp' => 'Harp',
       //         'Voice' => 'Voice',
       //         'Violin' => 'Violin',
       //         'Viola' => 'Viola',
       //         'Cello' => 'Cello',
       //         'Double Bass' => 'Double Bass',
       //     ),
       // ),
        /*         * * Remove for Summer ** */
        // 'meet_music_faculty' => array(
        //     'type' => 'checkboxfirst',
        //     'display_style' => 'normal',
        //     'display_name' => 'Conversation with music faculty',
        //     'comments' => '<small>  (30 min)</small>',
        // ),
        /*         * * Remove for Summer ** */
        // 'meet_music_faculty_details' => array(
        //     'type' => 'select',
        //     'display_name' => 'Select Discipline',
        //     'display_style' => 'right',
        //     'add_null_value_to_top' => true,
        //     'options' => array(
        //         'Band' => 'Band',
        //         'Choir' => 'Choir',
        //         'Composition' => 'Composition',
        //         'Early Music' => 'Early Music',
        //         'Jazz' => 'Jazz',
        //         'Music Education' => 'Music Education',
        //         'Orchestra' => 'Orchestra',
        //         'Theory' => 'Theory',
        //         'Flute' => 'Flute',
        //         'Oboe' => 'Oboe',
        //         'Clarinet' => 'Clarinet',
        //         'Saxophone' => 'Saxophone',
        //         'Bassoon' => 'Bassoon',
        //         'Horn' => 'Horn',
        //         'Trumpet' => 'Trumpet',
        //         'Trombone' => 'Trombone',
        //         'Euphonium' => 'Euphonium',
        //         'Tuba' => 'Tuba',
        //         'Percussion' => 'Percussion',
        //         'Piano' => 'Piano',
        //         'Harp' => 'Harp',
        //         'Voice' => 'Voice',
        //         'Violin' => 'Violin',
        //         'Viola' => 'Viola',
        //         'Cello' => 'Cello',
        //         'Double Bass' => 'Double Bass',
        //     ),
        // ),
        'additional_request' => array(
            'type' => 'textarea',
            'rows' => 2,
            'cols' => 35,
            'display_name' => 'Additional Request',
        ),
            /*             * * Remove for Summer ** */
        // 'housing_note' => array(
        //     'type' => 'comment',
        //     'text' => '<h3>Overnight Housing</h3> (Seniors Only - Please provide two weeks notice)',
        // ),
            /*             * * Remove for Summer ** */
      //   'overnight_housing' => array(
      //       'type' => 'checkboxfirst',
      //       'display_name' => 'I would like to request overnight housing
						// with a current Luther student',
      //   ),
            /*             * * Remove for Summer ** */
        // 'overnight_note' => array(
        //     'type' => 'comment',
        //     'text' => 'Please indicate whether you\'d like to stay with a student on the <strong>day of your visit</strong> or on <strong>the night prior.</strong>',
        // ),
            /*             * * Remove for Summer ** */
        // 'overnight_day' => array(
        //     'type' => 'radio_no_sort',
        //     'display_style' => 'normal',
        //     'options' => array(
        //         'Day of visit' => 'Day of visit',
        //         'Night prior to visit' => 'Night prior to visit',),
        // ),
            /*             * * Remove for Summer ** */
        // 'overnight_prior_arrival_time' => array(
        //     'type' => 'select_no_sort',
        //     'display_name' => 'If arriving the night prior, please indicate arrival time',
        //     'display_style' => 'normal',
        //     'add_null_value_to_top' => true,
        //     'options' => array(
        //         '5:00' => '5:00 p.m.',
        //         '5:30' => '5:30 p.m.',
        //         '6:00' => '6:00 p.m.',
        //         '6:30' => '6:30 p.m.',
        //         '7:00' => '7:00 p.m.',
        //         '7:30' => '7:30 p.m.',
        //         '8:00' => '8:00 p.m.',
        //         '8:30' => '8:30 p.m.',
        //         '9:00' => '9:00 p.m.',
        //     ),
        // ),
            /*             * * Remove for Summer ** */
        // 'emergency_contact' => array(
        //     'type' => 'text',
        //     'size' => 45,
        //     'comments' => '<br>Whom should we contact in case of emergency?'
        // ),
            /*             * * Remove for Summer ** */
        // 'emergency_phone_number' => array(
        //     'type' => 'text',
        //     'size' => 20
        // ),
    );
    var $required = array(
        'high_school',
        'graduation_year',
    );
    // if defined none of the default actions will be run (such as email_form_data) and you need to define the custom method and a
    // should_custom_method in the view (if they are not in the model).
    var $process_actions = array('email_form_data_to_submitter',);


    function on_every_time() {
        $disabled_dates_string = '';
        foreach ($this->disabled_dates as $date) {
            $disabled_dates_string .= 'disable-';
            $disabled_dates_string .= $date;
            $disabled_dates_string .= ' ';
        }

        $visitdate_properties = array(
            'datepicker_class_arg' => 'split-date fill-grid-no-select disable-days-67
                            statusformat-l-cc-sp-d-sp-F-sp-Y opacity-99 range-low-today
                            range-high-20120912 ' . $disabled_dates_string . '',);

        $visitdate_field = $this->get_element_name_from_label('Visit Date');
        $this->change_element_type($visitdate_field, 'textdatepublic');
        $this->set_element_properties($visitdate_field, $visitdate_properties);

        $visittime_field = $this->get_element_name_from_label('Visit Time');
        $this->change_element_type(
            $visittime_field, 'select_no_sort', array(
                'display_style' => 'normal',
                'add_null_value_to_top' => true,
                'options' => array(
                    '8:30' => '8:30 a.m.',
                    '9:00' => '9:00 a.m.',
                    '9:30' => '9:30 a.m.',
                    '10:00' => '10:00 a.m.',
                    '10:30' => '10:30 a.m.',
                    '11:00' => '11:00 a.m.',
                    '11:30' => '11:30 a.m.',
                    '12:00' => '12:00 p.m.',
                    '12:30' => '12:30 p.m.',
                    '1:00' => '1:00 p.m.',
                    '1:30' => '1:30 p.m.',
                    '2:00' => '2:00 p.m.',
                    '2:30' => '2:30 p.m.',
                    '3:00' => '3:00 p.m.',
                    '3:30' => '3:30 p.m.',
                ),
            )
        );

        $gender = $this->get_element_name_from_label('Gender');
        $this->change_element_type($gender, 'radio_inline_no_sort');

        $state_field = $this->get_element_name_from_label('State/Province');
        $this->change_element_type($state_field, 'state_province');
    }

    function email_form_data_to_submitter() {
        $model = & $this->get_model();

        // Figure out who would get an email confirmation (either through a
        // Your Email field or by knowing the netid of the submitter
        if (!$recipient = $this->get_value_from_label('Email')) {
            if ($submitter = $model->get_email_of_submitter())
                $recipient = $submitter . '@luther.edu';
        }

        // If we're supposed to send a confirmation and we have an address...
        if ($recipient) {
            // Use the (first) form recipient as the return address if available
            if ($senders = $model->get_email_of_recipient()) {
                list($sender) = explode(',', $senders, 1);
                if (strpos($sender, '@') === FALSE)
                    $sender .= '@luther.edu';
            } else {
                $sender = 'auto-form-process@luther.edu';
            }

            $thank_you = $model->get_thank_you_message();

            $email_values = $model->get_values_for_email_submitter_view();

            if (!($subject = $this->get_value_from_label('Confirmation Subject')))
                $subject = 'Thank you for requesting a visit';

            $values = "\n";
            if ($model->should_email_data()) {
                foreach ($email_values as $key => $val) {
                    $values .= sprintf("\n%s:   %s\n", $val['label'], $val['value']);
                }

                $high_school = $this->get_value('high_school');
                if ($high_school)
                    $values .= "\n High School: " . $high_school;

                $graduation_year = $this->get_value('graduation_year');
                if ($graduation_year)
                    $values .= "\t Graduation Year: " . $graduation_year . "\n";

                $transfer = $this->get_value('transfer');
                $transfer_college = $this->get_value('transfer_college');
                if ($transfer_college || $transfer)
                    $values .= "\n Transfer College: " . $transfer_college . "\n";

                $meet_counselor = $this->get_value('meet_counselor');
                if ($meet_counselor)
                    $values .= "\n Meet with admissions counselor: Yes \n";

                $tour = $this->get_value('tour');
                if ($tour)
                    $values .= "\n Take campus tour: Yes \n";

                $meet_faculty = $this->get_value('meet_faculty');
                $meet_faculty_details = $this->get_value('meet_faculty_details');
                if ($meet_faculty || $meet_faculty_details)
                    $values .= "\n Meet with faculty from: " . $meet_faculty_details . "\n";

                $meet_second_faculty = $this->get_value('meet_second_faculty');
                $meet_second_faculty_details = $this->get_value('meet_second_faculty_details');
                if ($meet_second_faculty || $meet_second_faculty_details)
                    $values .= "\n Meet with second faculty from: " . $meet_second_faculty_details . "\n";

                $observe_class = $this->get_value('observe_class');
                $observe_class_details = $this->get_value('observe_class_details');
                if ($observe_class || $observe_class_details)
                    $values .= "\n Observe class: " . $observe_class_details . "\n";

                $chapel = $this->get_value('chapel');
                if ($chapel)
                    $values .= "\n Attend chapel: Yes \n";

                $lunch = $this->get_value('lunch');
                if ($lunch)
                    $values .= "\n Eat lunch: Yes \n";

                $meet_coach = $this->get_value('meet_coach');
                $meet_coach_details = $this->get_value('meet_coach_details');
                if ($meet_coach || $meet_coach_details)
                    $values .= "\n Meet with coach from: " . $meet_coach_details . "\n";

                $choir = $this->get_value('choir');
                if ($choir)
                    $values .= "\n Observe a choir rehearsal: Yes \n";

                $band = $this->get_value('band');
                if ($band)
                    $values .= "\n Observe a band rehearsal: Yes \n";

                $orchestra = $this->get_value('orchestra');
                if ($orchestra)
                    $values .= "\n Observe an orchestra rehearsal: Yes \n";

                $music_audition = $this->get_value('music_audition');
                $music_audition_details = $this->get_value('music_audition_details');
                if ($music_audition || $music_audition_details)
                    $values .= "\n Perform a music audition for: " . $music_audition_details . "\n";

                $meet_music_faculty = $this->get_value('meet_music_faculty');
                $meet_music_faculty_details = $this->get_value('meet_music_faculty_details');
                if ($meet_music_faculty || $meet_music_faculty_details)
                    $values .= "\n Meet with music faculty from: " . $meet_music_faculty_details . "\n";

                $additional_request = $this->get_value('additional_request');
                if ($additional_request)
                    $values .= "\n Additional request: " . $additional_request . "\n";

                $overnight_housing = $this->get_value('overnight_housing');
                $overnight_day = $this->get_value('overnight_day');
                if ($overnight_housing)
                    $values .= "\n Overnight housing arrival information: " . $this->get_value('overnight_day');

                if ($overnight_day == "Night prior to visit")
                    $values .= "  Arrival time: " . $this->get_value('overnight_prior_arrival_time');
            }

            $html_body = $thank_you . nl2br($values);
            $txt_body = html_entity_decode(strip_tags($html_body));

            // Send thank you message and details of request to the requestor
            $recipient_mailer = new Email($recipient, $sender, $sender, $subject, $txt_body, $html_body);
            $recipient_mailer->send();

            // Send details of the request to the administrator listed as recipient on the form builder
            $html_body2 = nl2br($values);
            $txt_body2 = html_entity_decode(strip_tags($html_body2));
            $subject2 = "IndividualVisitRequest" . date('ymd');
            $admin_mailer = new Email($sender, $sender, $sender, $subject2, $txt_body2, $html_body2);
            $admin_mailer->send();
        }
    }

    function is_date_disabled() {

        $enteredDate = $this->get_value_from_label('Visit Date');
        $day = date('D', strtotime($enteredDate));
        $splitDate = explode('-', $enteredDate);

        if (in_array('xxxx' . $splitDate[1] . $splitDate[2], $this->disabled_dates)) {
            return true;
        } elseif (in_array($splitDate[0] . $splitDate[1] . $splitDate[2], $this->disabled_dates)) {
            return true;
        } elseif (($day == 'Sun') || ($day == 'Sat')) {
            return true;
        } else {
            return false;
        }
    }

    function pre_error_check_actions() {
        parent::pre_error_check_actions();

        if ($this->get_value('overnight_housing')) {
            $this->add_required('emergency_contact');
            $this->add_required('emergency_phone_number');
        }
    }

    function run_error_checks() {
        $enteredDate = $this->get_value_from_label('Visit Date');
        $date = strtotime($this->get_value_from_label('Visit Date'));
        $day = date('D', $date);

        if ($this->is_date_disabled()) {
            $this->set_error($this->get_element_name_from_label(
                            'Visit Date'), 'This date is not available, check the calendar to the right for available dates');
        }
    }

    function should_my_custom_process() {
        return true;
    }

}

?>
