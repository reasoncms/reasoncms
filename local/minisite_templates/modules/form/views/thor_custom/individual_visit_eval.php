<?
include_once('reason_header.php');
//include_once('/usr/local/webapps/reason/reason_package_local/disco/plasmature/types/datetime.php');
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
	function on_every_time()
	{	
		$first_impression = $this->get_element_name_from_label('First Impression of Campus');
		$this->change_element_type($first_impression, 'radio_inline_no_sort');
		
		$welcome = $this->get_element_name_from_label('Welcome at Visit Center');
		$this->change_element_type($welcome, 'radio_inline_no_sort');
		
		$counselor_meeting = $this->get_element_name_from_label('Meeting with Admission Counselor');
		$this->change_element_type($counselor_meeting, 'radio_inline_no_sort');
		
		$campus_tour = $this->get_element_name_from_label('Campus Tour');
		$this->change_element_type($campus_tour, 'radio_inline_no_sort');
		
		$first_faculty_meet = $this->get_element_name_from_label('Meeting with First Faculty Member');
		$this->change_element_type($first_faculty_meet, 'radio_inline_no_sort');
		
		$second_faculty_meet = $this->get_element_name_from_label('Meeting with Second Faculty Member');
		$this->change_element_type($second_faculty_meet, 'radio_inline_no_sort');
		
		$coach_meet = $this->get_element_name_from_label('Meeting with Coach');
		$this->change_element_type($coach_meet, 'radio_inline_no_sort');
		
		$music_meet = $this->get_element_name_from_label('Meeting with Member of Music Department');
		$this->change_element_type($music_meet, 'radio_inline_no_sort');
		
		$audition = $this->get_element_name_from_label('Did you audition?');
		$this->change_element_type($audition, 'radio_inline_no_sort');
		
		$classroom_visit = $this->get_element_name_from_label('Classroom Visit');
		$this->change_element_type($classroom_visit, 'radio_inline_no_sort');

		$lunch = $this->get_element_name_from_label('Lunch/Dinner');
		$this->change_element_type($lunch, 'radio_inline_no_sort');

		$overnight = $this->get_element_name_from_label('Overnight Experience');
		$this->change_element_type($overnight, 'radio_inline_no_sort');

		$other = $this->get_element_name_from_label('Other (please list what you are rating in the comment section below)');
		$this->change_element_type($other, 'radio_inline_no_sort');

		$overall = $this->get_element_name_from_label('Overall Experience');
		$this->change_element_type($overall, 'radio_inline_no_sort', array('options' => array('Excellent' => 'Excellent','Good' => 'Good',
				'Fair' => 'Fair','Poor' => 'Poor','NA' => 'NA')));
}
}
?>
