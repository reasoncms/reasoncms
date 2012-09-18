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
		
		$state_province_field = $this->get_element_name_from_label('State');
		$this->change_element_type('id_9765x1CI8m', 'state');
		
		$today = getdate();
		
		$grad_year = $this->get_element_name_from_label('High School Graduation Year');
		
		// If the date is before August 2, let submitters choose the current year+1.
		// If the date is before August 2, let submitters choose the current year+1.
		$aug1 = 212; // the numeric representation (yday) of August 1st
		

		if (date('L') > 0){ // if leap year increment August 1
			$aug1 = 213;
		}
		if ($today['yday'] < $aug1) {
			$this->change_element_type($grad_year, 'radio_inline', array(
				'options' => array(
					($today['year']+1) => ($today['year']+1), 
					($today['year']+2) => ($today['year']+2), 
					($today['year']+3) => ($today['year']+3)
					)));
		} else {
			$this->change_element_type($grad_year, 'radio_inline', array(
				'options' => array( 
					($today['year']+2) => ($today['year']+2), 
					($today['year']+3) => ($today['year']+3),
					($today['year']+4) => ($today['year']+4)
					)));
		}
	}
}
?>
