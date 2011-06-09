<?

include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
//include_once('disco/boxes/boxes.php');
$GLOBALS['_form_view_class_names'][basename(__FILE__, '.php')] = 'TeacherCandidateForm';

/**
 * IndividualVisitForm adds visit request info to Thor form
 * that gets personal info
 *
 * @author Steve Smith
 */
class TeacherCandidateForm extends DefaultThorForm {

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
        'COMM' => 'Communication',
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
        'MIS' => ' Management Info Systems',
        'MATH' => '	Mathematics',
        'MSTAT' => ' Mathematics/Statistics',
        'MEDT' => ' Medical Technology',
        'MLAN' => ' Modern Languages',
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
    var $statesAP = array(
        'AL' => 'Ala.',
        'AK' => 'Alaska',
        'AZ' => 'Ariz.',
        'AR' => 'Ark.',
        'CA' => 'Calif.',
        'CO' => 'Colo.',
        'CT' => 'Conn.',
        'DE' => 'Del.',
        'DC' => 'D.C.',
        'FL' => 'Fla.',
        'GA' => 'Ga.',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Ill.',
        'IN' => 'Ind.',
        'IA' => 'Iowa',
        'KS' => 'Kan.',
        'KY' => 'Ky.',
        'LA' => 'La.',
        'ME' => 'Maine',
        'MD' => 'Md.',
        'MA' => 'Mass.',
        'MI' => 'Mich.',
        'MN' => 'Minn.',
        'MS' => 'Miss.',
        'MO' => 'Mo.',
        'MT' => 'Mont.',
        'NE' => 'Neb.',
        'NV' => 'Nev.',
        'NH' => 'N.H.',
        'NJ' => 'N.J.',
        'NM' => ' N.M.',
        'NY' => 'N.Y.',
        'NC' => 'N.C.',
        'ND' => ' N.D.',
        'OH' => 'Ohio',
        'OK' => ' Okla.',
        'OR' => 'Ore.',
        'PA' => 'Pa.',
        'RI' => 'R.I.',
        'SC' => 'S.C.',
        'SD' => 'S.D.',
        'TN' => 'Tenn.',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vt.',
        'VA' => 'Va.',
        'WA' => 'Wash.',
        'WV' => 'W.Va.',
        'WI' => 'Wis.',
        'WY' => 'Wyo.',
    );

    function on_every_time() {
        parent::on_every_time();

        echo '<script type="text/javascript" src="/reason/js/teacher_candidate.js"></script>';
        echo '<link rel="stylesheet" type="text/css" href="/reason/jquery-ui-1.8.12.custom/css/redmond/jquery-ui-1.8.12.custom.css"/>';

        //Personal Information header
        $this->add_element('personal_information_header', 'comment', array('text' => '<h3> <b> Personal Information </b></h3>'));
        $this->move_element('personal_information_header', 'before', $this->get_element_name_from_label('First Name'));

        //Current Address header
        $this->add_element('current_address_header', 'comment', array('text' => '<h3><b> <br/>Current Address (February 1 - June 1)</b> </h3>'));
        $this->move_element('current_address_header', 'after', $this->get_element_name_from_label('Second Minor'));


        //Permanent Address header
        $this->add_element('permanent_address_header', 'comment', array('text' => '<h3><b> <br/>Permanent Address </b> </h3>'));
        $this->move_element('permanent_address_header', 'before', $this->get_element_name_from_label('Street Address (P.O. Box or SPO)'));

        // Student teaching experience header
        $this->add_element('student_teaching_exp_header', 'comment', array('text' => '<h3><b><br/> Student Teaching Experience </b> </h3>'));
        $this->move_element('student_teaching_exp_header', 'after', 'id_Gc49132284');

        // Other Practica header
        $this->add_element('other_practica_header', 'comment', array('text' => '<h3><b><br/> Other Practica (Ed 115, Teaching Methods, JR BLK, etc.)  </b> </h3>'));
        $this->move_element('other_practica_header', 'after', 'id_514LI01395');

        // Other work experience related to teaching header
        $this->add_element('other_work_exp_header', 'comment', array('text' => '<h3><b> <br/>Other Work Experience Related To Teaching </b> </h3>'));
        $this->move_element('other_work_exp_header', 'after', 'id_9HuR58CbT4');

        // Licensures and Endorsements  header
        $this->add_element('licensures_endorsements_header', 'comment', array('text' => '<h3><b><br/> Licensures and Endorsements </b> </h3>'));
        $this->move_element('licensures_endorsements_header', 'after', 'id_RH86094q7x');


        // Elementary Educators   header
        $this->add_element('elementary_educators_header', 'comment', array('text' => '<h4><b> Elementary Educators </b> </h4>'));
        $this->move_element('elementary_educators_header', 'before', $this->get_element_name_from_label('License (K-6)'));



        // License (K-6) header
        $license = $this->get_element_name_from_label('License (K-6)');

        $this->add_element('license_header', 'comment', array('text' => '<h5> License (K-6) </h5>'));
        $this->move_element('license_header', 'before', $license);

        $this->set_display_name($license, '&nbsp;');


        // Academic endorsements header
        $acd_endors = $this->get_element_name_from_label('Academic Endorsements (K-8)');

        $this->add_element('academic_endorsements_header', 'comment', array('text' => '<h5> Academic Endorsements (K-8) </h5>'));
        $this->move_element('academic_endorsements_header', 'before', $acd_endors);

        $this->set_display_name($acd_endors, '&nbsp;');

        // Special endorsements header
        $special_endors = $this->get_element_name_from_label('Special Endorsements');

        $this->add_element('special_endorsements_header', 'comment', array('text' => '<h5> Special Endorsements </h5>'));
        $this->move_element('special_endorsements_header', 'before', $special_endors);

        $this->set_display_name($special_endors, '&nbsp;');


        // Elementary Educators   header

        $this->add_element('secondary_ed_header', 'comment', array('text' => '<h4> <b> Secondary Educators <b> </h4>'));
        $this->move_element('secondary_ed_header', 'after', 'id_7543102P80');

        // Coaching header
        $this->add_element('coaching_header', 'comment', array('text' => '<h5> Coaching </h5>'));
        $this->move_element('coaching_header', 'before', 'id_7543102P80');

        $this->set_display_name('id_7543102P80', '&nbsp;');

        // License header
        $licenseb = $this->get_element_name_from_label('License:');

        $this->add_element('licenseb_header', 'comment', array('text' => '<h5> License </h5>'));
        $this->move_element('licenseb_header', 'after', 'secondary_ed_header');

        $this->set_display_name('id_4522C49qsC', '&nbsp;');


        // Additional Endorsements header
        $add_info = $this->get_element_name_from_label('Additional Endorsements:that may be added to a 5-12 license:');

        $this->add_element('add_info_header', 'comment', array('text' => '<h5> Additional Endorsements <br /> that may be added <br /> to a 5-12 license: </h5>'));
        $this->move_element('add_info_header', 'after', 'id_4522C49qsC');

        $this->set_display_name($add_info, '&nbsp;');

        // Coaching #2 header
        $this->add_element('coachingb_header', 'comment', array('text' => '<h5> Coaching </h5>'));
        $this->move_element('coachingb_header', 'after', 'id_2X18nzbEh7');

        $this->set_display_name('id_d4355bf0fF', '&nbsp;');

        // Personal Statement header
        $this->add_element('ps_statement_header', 'comment', array('text' => '<h5> Personal Statement <br/>(60 words or less): </h5>'));
        $this->move_element('ps_statement_header', 'before', 'id_014c481772');

        $this->set_display_name('id_014c481772', '&nbsp;');

        // Quote About Teaching  header
        $this->add_element('teaching_quote_header', 'comment', array('text' => '<h5> Quote About Teaching <br/> (optional- 20 words or less): </h5>'));
        $this->move_element('teaching_quote_header', 'before', 'id_89z4P12Q95');

        $this->set_display_name('id_89z4P12Q95', '&nbsp;');

        // final info

        $this->add_element('info_text', 'comment', array('text' => '<p> When you have finished, please verify that all information entered is correct. Remember, <b>your account will be charged $25</b> to offset the cost of producing the brochure <b>unless you notify the Career Center in writing that you do not wish to particpate</b>. Click the <b>Submit Form </b> button below to send this form to the Career Center. Thank you!</p>'));
        $this->move_element('teaching_quote_header', 'after', 'id_89z4P12Q95');

        //  majors/minors select box
        $this->change_element_type('id_6h023d62d9', 'select', array('options' => $this->majors_array)); //major
        $this->change_element_type('id_346b89152y', 'select', array('options' => $this->majors_array)); //second major
        $this->change_element_type('id_9e48201lb2', 'select', array('options' => $this->majors_array)); //minor
        $this->change_element_type('id_U144y41Tc9', 'select', array('options' => $this->majors_array)); //second minor
        // state select
        $this->change_element_type('id_4u421Ah7XE', 'state'); //major
        $this->change_element_type('id_SD04x1ki40', 'state'); //major
    }

}
?>

