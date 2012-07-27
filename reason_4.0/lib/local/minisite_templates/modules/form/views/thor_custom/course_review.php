<?
include_once('reason_header.php');
include_once('/usr/local/webapps/reason/reason_package_local/disco/plasmature/types/datetime.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
include_once(DISCO_INC.'disco.php');
include_once(DISCO_INC.'plasmature/plasmature.php');


//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'CourseReviewForm';

/**
 * 
 * @author Steve Smith
 */


class CourseReviewForm extends DefaultThorForm
{
	function on_every_time()
	{	
		$gender = $this->get_element_name_from_label('Gender');
		$this->change_element_type($gender, 'radio_inline_no_sort');
		
		$birthdate = $this->get_element_name_from_label('Birthdate');
		$this->change_element_type($birthdate, 'textdate');
		
		$welcome = $this->get_element_name_from_label('Welcome at Visit Center');
		$this->change_element_type($welcome, 'radio_inline_no_sort', array('display_style' => 'normal','display_name' => '<strong>Welcome at Visit Center</strong>'));
		
		$state = $this->get_element_name_from_label('State');
		$this->change_element_type($state, 'state');
		
		$date = getdate();
		$year = $this->get_element_name_from_label('Beginning year');
		$this->change_element_type($year, 'radio_inline', array('options' => array($date['year'], ($date['year']+1),($date['year']+2),($date['year']+3))));
		
		$applicant_type = $this->get_element_name_from_label('Applicant type');
		$this->change_element_type($applicant_type, 'radio_inline_no_sort');
		
		$beginning_term = $this->get_element_name_from_label('Beginning term');
		$this->change_element_type($beginning_term, 'select_no_sort', array('add_null_value_to_top' => true,));
	}
}
?>
