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
 *  Sixth page of the application
 *
 *  Misc Information
 *          College Plan
 *          Influences
 *          Other Colleges Applied to
 *          Personal Statement
 *          Criminal History
 *          More????
 */
class ApplicationPageSix extends FormStep {

    var $openid_id;
    var $_log_errors = true;
    var $error;
    var $majors_array = array(
        'ACCTG' => 'Accounting',
        'AFRS' => 'Africana Studies',
        'ANTH' => 'Anthropology/Archaeology',
        'ARCH' => 'Architecture',
        'ART' => 'Art',
        'ARTM' => 'Art Management',
        'ATHT' => 'Athletic Training',
        'BIBL' => 'Biblical Languages',
        'BIOC' => 'Biochemistry',
        'BIOC' => 'Biology',
        'BIOE' => 'Biology (Environmental)',
        'CHEM' => 'Chemistry',
        'CLAST' => 'Classical Studies',
        'COMM' => 'Communication Studies',
        'CS' => 'Computer Science',
        'ECON' => 'Economics',
        'EDUC' => 'Education',
        'EDEL' => 'Education-Elemenatary',
        'EDSE' => 'Education-Secondary',
        'EDSP' => 'Education-Special',
        'ENGL' => 'English',
        'ENVS' => 'Environmental Studies',
        'FINA' => 'Fine Arts',
        'FREN' => 'French',
        'GER' => 'German',
        'GRDE' => 'Graphic Design',
        'GRK' => 'Greek',
        'HLTH' => 'Health',
        'HIST' => 'History',
        'INTS' => 'International Management',
        'IS' => 'International Studies',
        'JOUR' => 'Journalism',
        'LAT' => 'Latin',
        'MGT' => 'Management',
        //'MIS' => 'Management Info Systems',
        'MATH' => 'Mathematics',
        'MSTAT' => 'Mathematics/Statistics',
        'MEDT' => 'Medical Technology',
        'MLAN' => 'Modern Languages',
        'MUST' => 'Museum Studies',
        'MUS' => 'Music',
        'MUSE' => 'Music Education',
        'MUSM' => 'Music Management',
        'MUSP' => 'Music Performance',
        'NSCI' => 'Natural Science',
        'NURS' => 'Nursing',
        'PHIL' => 'Philosophy',
        'PE' => 'Physical Education',
        'PTOT' => 'Physical/Occ Therapy',
        'PHYS' => 'Physics',
        'POLS' => 'Political Science',
        'PDEN' => 'Pre-dental',
        'PENG' => 'Pre-engineering',
        'PFOR' => 'Pre-forestry',
        'PLAW' => 'Pre-law',
        'PMED' => 'Pre-medicine',
        'POPT' => 'Pre-optometry',
        'PPHA' => 'Pre-pharmacy',
        'PPT' => 'Pre-physical therapy',
        'PSEM' => 'Pre-seminary',
        'PVET' => 'Pre-veterinary',
        'PSYB' => 'Psychobiology',
        'PSYC' => 'Psychology',
        'REL' => 'Religion',
        'RUST' => 'Russian Studies',
        'SCST' => 'Scandanavian Studies',
        'SSCI' => 'Social Science',
        'SW' => 'Social Work',
        'SOC' => 'Sociology',
        'SOPO' => 'Soc/Political Science',
        'SPAN' => 'Spanish',
        'SPMT' => 'Sports Management',
        'THD' => 'Theatre/Dance',
        'THDM' => 'Theatre/Dance Management',
        'UND' => 'Deciding',
        'WOMS' => 'Women\'s Studies',
    );
    var $elements = array(
        'college_plan_comment' => array(
            'type' => 'comment',
            'text' => '<h3>Academic Interests</h3>',
        ),
        'college_plan_1' => array(
            'type' => 'select',
            'display_name' => 'First Choice',
            'options' => array(),
        ),
        'college_plan_2' => array(
            'type' => 'select',
            'display_name' => 'Second Choice',
            'options' => array()
        ),
        'music_audition_comment' => array(
            'type' => 'comment',
            'text' => 'Do you intend to audition for a music scholarship?',
        ),
        'music_audition' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('Yes' => 'Yes', 'No' => 'No'),
        ),
        'instrument_comment' => array(
            'type' => 'comment',
            'text' => 'If yes, with which instrument will you audition?',
        ),
        'music_audition_instrument' => array(
            'type' => 'select_no_sort',
            'display_name' => '&nbsp;',
            'add_null_value_to_top' => true,
            'options' => array(
                'Flute' => 'Flute',
                'Oboe' => 'Oboe',
                'Clarinet' => 'Clarinet',
                'Bassoon' => 'Bassoon',
                'Saxophone' => 'Saxophone',
                'Horn' => 'Horn',
                'Trumpet' => 'Trumpet',
                'Trombone' => 'Trombone',
                'Tuba' => 'Tuba',
                'Piano' => 'Piano',
                'Organ' => 'Organ',
                'Harp' => 'Harp',
                'Harpsichord' => 'Harpsichord',
                'Guitar' => 'Guitar',
                'Percussion' => 'Percussion',
                'Violin' => 'Violin',
                'Viola' => 'Viola',
                'Cello' => '\'Cello',
                'Double Bass' => 'Double Bass',
                'Voice' => 'Voice',
            ),
        ),
        'financial_aid_comment' => array(
            'type' => 'comment',
            'text' => 'Do you plan to apply for financial aid?',
        ),
        'financial_aid' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('Yes' => 'Yes', 'No' => 'No'),
        ),
        'influences_comment' => array(
            'type' => 'comment',
            'text' => 'Please list the influences that led you to Luther College.'
        ),
        'influences' => array(
            'type' => 'textarea_no_label',
        ),
        'other_colleges_comment' => array(
            'type' => 'comment',
            'text' => 'Please list the colleges in which you have applied or intend to apply.'
        ),
        'other_colleges' => array(
            'type' => 'textarea_no_label',
        ),
        'personal_statment_header' => array(
            'type' => 'comment',
            'text' => '<h3>Personal Statement</h3>',
        ),
        'personal_statement_instructions' => array(
            'type' => 'comment',
            'text' => 'In at least 250 words, please describe an activity, interest, experience or achievement in your life
                  that has been particularly meaningful to you. Please note that you may mail a graded paper or a general 
                  writing sample to our Office of Admissions in lieu of submitting a personal statement.'
        ),
        'personal_statement' => array(
            'type' => 'textarea_no_label',
        ),
        'disciplinary_header' => array(
            'type' => 'comment',
            'text' => '<h3>Disciplinary History</h3>',
        ),
        'conviction_history_comment' => array(
            'type' => 'comment',
            'text' => 'Have you ever been convicted of a misdemeanor, felony, or other crime?',
        ),
        'conviction_history' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('Yes' => 'Yes', 'No' => 'No'),
        ),
        'conviction_details_comment' => array(
            'type' => 'comment',
            'text' => 'If yes, please describe.',
        ),
        'conviction_history_details' => array(
            'type' => 'textarea_no_label',
            'display_name' => '&nbsp;',
        ),
        'hs_discipline_comment' => array(
            'type' => 'comment',
            'text' => 'Have you ever been found responsible for a discplinary violation while attending high school that resulted in probation,
                  suspension, dismissal, or expulsion?',
        ),
        'hs_discipline' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => '&nbsp;',
            'options' => array('Yes' => 'Yes', 'No' => 'No'),
        ),
        'discipline_details_comment' => array(
            'type' => 'comment',
            'text' => 'If yes, please describe.',
        ),
        'hs_discipline_details' => array(
            'type' => 'textarea_no_label',
            'display_name' => '&nbsp;',
        ),
        'honesty_statement' => array(
            'type' => 'checkboxfirst',
            'display_name' => 'Checking this box indicates that all information in my application is complete, factually correct, and honestly
                  presented.'
        ),
        'logout6' => array(
            'type' => 'hidden',
        ),
    );
    var $required = array('college_plan_1', 'music_audition_instrument', 'financial_aid', 'conviction_history',
        'conviction_history_details', 'hs_discipline', 'hs_discipline_details', 'honesty_statement');
    var $display_name = 'Last Page';
    var $error_header_text = 'Please check your form.';

    function no_show_form() {
        echo(check_login());
    }

    function on_every_time() {
        $this->openid_id = check_open_id($this);
        if (is_submitted($this->openid_id)) {
            echo(already_submitted_message());
            $this->show_form = false;
        } else {
            $this->show_form = true;

            $this->change_element_type('college_plan_1', 'select', array('options' => $this->majors_array));
            $this->change_element_type('college_plan_2', 'select', array('options' => $this->majors_array));

            $this->pre_fill_form();
        }
    }

    function pre_fill_form() {
        // check if the open_id has is set
        if ($this->openid_id) {
            // get an existing users data from the db based on openid_id and the form
            get_applicant_data($this->openid_id, $this);
        } else {
            // no show form, invite to login
            $this->show_form = false;
        }
    }

    function pre_show_form() {
        echo '<div id="admissionsApp" class="pageSix">' . "\n";
    }

    function post_show_form() {
        echo '</div>' . "\n";
    }

    function process() {
        // write the the data
        set_applicant_data($this->openid_id, $this);
        check_logout($this);
    }

    function run_error_checks() {
        $this->_error_flag = false;
    }

}

?>