<?
include_once('reason_header.php');
include_once('/usr/local/webapps/reason/reason_package_local/disco/plasmature/types/datetime.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
include_once(DISCO_INC.'disco.php');
include_once(DISCO_INC.'plasmature/plasmature.php');


//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'IndividualVisitEvalForm';

/**
 * 
 * @author Steve Smith
 */


class IndividualVisitEvalForm extends DefaultThorForm
{
	var $required = array(
	);

	function on_every_time()
	{	
		$date = $this->get_element_name_from_label('Date of Visit');
		$this->change_element_type($date, 'textdate', array('display_style' => 'normal','display_name' => '<strong>Date of Visit</strong>'));
		
		$time = $this->get_element_name_from_label('Time of Visit');
		$this->change_element_type($time, 'texttimepublic', array('display_style' => 'normal','display_name' => '<strong>Time of Visit</strong>'));
		
		$first_impression = $this->get_element_name_from_label('First Impression of Campus');
		$this->change_element_type($first_impression, 'radio_inline_no_sort', array('options' => array(
					'Excellent' => 'Excellent',
					'Good' => 'Good',
					'Fair' => 'Fair',
					'Poor' => 'Poor',
					'NA' => 'NA'),
					'display_style' => 'normal','display_name' => '<strong>First Impression of Campus</strong>'));
		
		$welcome = $this->get_element_name_from_label('Welcome at Visit Center');
		$this->change_element_type($welcome, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Welcome at Visit Center</strong>'));
		
		$counselor_meeting = $this->get_element_name_from_label('Meeting with Admission Counselor');
		$this->change_element_type($counselor_meeting, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Meeting with Admission Counselor</strong>'));
		
		$campus_tour = $this->get_element_name_from_label('Campus Tour');
		$this->change_element_type($campus_tour, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Campus Tour</strong>'));
		
		$first_faculty_meet = $this->get_element_name_from_label('Meeting with First Faculty Member');
		$this->change_element_type($first_faculty_meet, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Meeting with First Faculty Member</strong>'));
		
		$second_faculty_meet = $this->get_element_name_from_label('Meeting with Second Faculty Member');
		$this->change_element_type($second_faculty_meet, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Meeting with Second Faculty Member</strong>'));
		
		$coach_meet = $this->get_element_name_from_label('Meeting with Coach');
		$this->change_element_type($coach_meet, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Meeting with Coach</strong>'));
		
		$music_meet = $this->get_element_name_from_label('Meeting with Music Department Faculty');
		$this->change_element_type($music_meet, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Meeting with Music Department Faculty</strong>'));
		
		$audition = $this->get_element_name_from_label('Did you audition?');
		$this->change_element_type($audition, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Did you audition?</strong>'));
		
		$classroom_visit = $this->get_element_name_from_label('Classroom Visit');
		$this->change_element_type($classroom_visit, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Classroom Visit</strong>'));
		$lunch = $this->get_element_name_from_label('Lunch/Dinner');
		$this->change_element_type($lunch, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>First Impression of Campus</strong>'));

		$overnight = $this->get_element_name_from_label('Overnight Experience');
		$this->change_element_type($overnight, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Overnight Experience</strong>'));

		$other = $this->get_element_name_from_label('Other (please list what you are rating in the comment section below)');
		$this->change_element_type($other, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Other (please list what you are rating in the comment section below)</strong>'));

		$overall = $this->get_element_name_from_label('Overall Experience');
		$this->change_element_type($overall, 'radio_inline_no_sort', array(
				'options' => array(
					'Excellent' => 'Excellent',
					'Good' => 'Good',
					'Fair' => 'Fair',
					'Poor' => 'Poor',
					'NA' => 'NA'),
				'display_style' => 'normal',
				'display_name' => '<strong>Overall Experience</strong>'
				));
		
		$state_province = $this->get_element_name_from_label('State/Province');
		$this->change_element_type($state_province, 'state_province');
		
		$country = $this->get_element_name_from_label('Country');
		$this->change_element_type($country, 'country');
}
}
?>
