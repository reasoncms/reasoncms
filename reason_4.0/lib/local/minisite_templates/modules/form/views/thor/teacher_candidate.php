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
	var $experience_array = array(
	    'Student Teaching' => 'Student Teaching',
	    'Other Practica' => 'Other Practica (Ed115, Teaching Methods, JR BLK, etc.)',
	    'Work Experience' => 'Work Experience Related to Teaching',
	);

	function on_every_time() {
		parent::on_every_time();

		echo '<script type="text/javascript" src="'.REASON_HTTP_BASE_PATH.'js/teacher_candidate.js"></script>';
		echo '<link rel="stylesheet" type="text/css" href="'.REASON_HTTP_BASE_PATH.'jquery-ui-1.8.12.custom/css/redmond/jquery-ui-1.8.12.custom.css"></link>';
		
    // add and move <hr> elements
		$this->add_element('hr1', 'hr');
		$this->add_element('hr2', 'hr');
		$this->add_element('hr3', 'hr');
		$this->move_element('hr1', 'before', $this->get_element_name_from_label('License (K-6)'));
		$this->move_element('hr2', 'before', $this->get_element_name_from_label('License (5-12)'));
		$this->move_element('hr3', 'before', $this->get_element_name_from_label('License (K-12)'));
		
	//Experience types
		$this->change_element_type($this->get_element_name_from_label('Experience Type #1'), 'select_no_sort', 
			array('options' => $this->experience_array, 'add_null_value_to_top' => true));
		$this->change_element_type($this->get_element_name_from_label('Experience Type #2'), 'select_no_sort', 
			array('options' => $this->experience_array, 'add_null_value_to_top' => true));
		$this->change_element_type($this->get_element_name_from_label('Experience Type #3'), 'select_no_sort', 
			array('options' => $this->experience_array, 'add_null_value_to_top' => true));
		$this->change_element_type($this->get_element_name_from_label('Experience Type #4'), 'select_no_sort', 
			array('options' => $this->experience_array, 'add_null_value_to_top' => true));
		$this->change_element_type($this->get_element_name_from_label('Experience Type #5'), 'select_no_sort', 
			array('options' => $this->experience_array, 'add_null_value_to_top' => true));
		$this->change_element_type($this->get_element_name_from_label('Experience Type #6'), 'select_no_sort', 
			array('options' => $this->experience_array, 'add_null_value_to_top' => true));
		
//  majors/minors select box
		$this->change_element_type($this->get_element_name_from_label('Major'), 'select', array('options' => $this->majors_array)); //major
		$this->change_element_type($this->get_element_name_from_label('Second Major'), 'select', array('options' => $this->majors_array)); //second major
		$this->change_element_type($this->get_element_name_from_label('Minor'), 'select', array('options' => $this->majors_array)); //minor
		$this->change_element_type($this->get_element_name_from_label('Second Minor'), 'select', array('options' => $this->majors_array)); //second minor		
	}
	
}
?>

