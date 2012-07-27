<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'ApplicantSurveyForm';

/**
 * IndividualVisitForm adds visit request info to Thor form
 * that gets personal info
 *
 * @author Steve Smith
 */

class ApplicantSurveyForm extends DefaultThorForm
{
	
	function on_every_time()
	{	
		$gender = $this->get_element_name_from_label('Gender');
		$this->change_element_type($gender, 'radio_inline_no_sort');
		
		$state_field = $this->get_element_name_from_label('State/Province');
		$this->change_element_type($state_field, 'state_province');
		
		$chronicle = $this->get_element_name_from_label('The Chronicle of Higher Education');
		$this->change_element_type($chronicle, 'checkboxfirst');
		
		$decorah_newspaper = $this->get_element_name_from_label('Decorah Newspapers');		
		$this->change_element_type($decorah_newspaper, 'checkboxfirst');
		
		$gazette = $this->get_element_name_from_label('Cedar Rapids Gazette');
		$this->change_element_type($gazette, 'checkboxfirst');
		
		$gazette = $this->get_element_name_from_label('Cedar Rapids Gazette');
		$this->change_element_type($gazette, 'checkboxfirst');
		
		$luther = $this->get_element_name_from_label('Luther College website');
		$this->change_element_type($luther, 'checkboxfirst');
		
		$mfad = $this->get_element_name_from_label('Minority Faculty Applicant Database (MFAD)');
		$this->change_element_type($mfad, 'checkboxfirst');
		
		$elca = $this->get_element_name_from_label('ELCA website');
		$this->change_element_type($elca, 'checkboxfirst');
		
		$cfd = $this->get_element_name_from_label('Consortium for Faculty Diversity (CFD)');
		$this->change_element_type($cfd, 'checkboxfirst');
		
		$journal = $this->get_element_name_from_label('Professional journal/website');
		$this->change_element_type($journal, 'checkboxfirst');

		$grad = $this->get_element_name_from_label('Graduate program advisor/office');
		$this->change_element_type($grad, 'checkboxfirst');
	}	
}
?>
