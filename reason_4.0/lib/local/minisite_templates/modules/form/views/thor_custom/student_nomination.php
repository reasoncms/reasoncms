<?
include_once('reason_header.php');
//include_once('/usr/local/webapps/reason/reason_package_local/disco/plasmature/types/datetime.php');
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');
include_once(DISCO_INC.'disco.php');
include_once(DISCO_INC.'plasmature/plasmature.php');


//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'StudentNominationForm';

/**
 * 
 * @author Steve Smith
 */


class StudentNominationForm extends LutherDefaultThorForm
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
		$sep1 = 244; // changing to sep1 for 2013 https://helpdesk.luther.edu/adminui/ticket.php?ID=25619
		$nov1 = 305; // changing to november 1st for 2014		

		if (date('L') > 0){ // if leap year increment August 1
			$aug1 = 213;
			$sep1 = 245;
			$nov1 = 306;
		}
		if ($today['yday'] < $nov1) {
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
