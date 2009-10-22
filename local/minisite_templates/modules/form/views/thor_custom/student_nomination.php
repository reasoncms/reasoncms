<?
include_once('reason_header.php');
//include_once('/usr/local/webapps/reason/reason_package_local/disco/plasmature/types/datetime.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
include_once(DISCO_INC.'disco.php');
include_once(DISCO_INC.'plasmature/plasmature.php');


//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'IndividualVisitForm';

/**
 * 
 * @author Steve Smith
 */


class IndividualVisitForm extends DefaultThorForm
{
	function on_every_time()
	{	
		$state_field = $this->get_element_name_from_label('State');
		$this->change_element_type($state_field, 'state');
		
		$state_province_field = $this->get_element_name_from_label('State/Province');
		$this->change_element_type($state_province_field, 'state_province');
		
		$grad_year = $this->get_element_name_from_label('High School Graduation Year');
		$this->change_element_type($grad_year, 'year');
		$grad_year_properties = array('num_years_before_today' => 4, 'num_years_after_today' => 4,);
		$this->set_element_properties($grad_year, $grad_year_properties);
	}
}
?>
