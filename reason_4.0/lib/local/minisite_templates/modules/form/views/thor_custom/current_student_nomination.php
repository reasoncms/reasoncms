<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/luther_default.php');
include_once(DISCO_INC.'disco.php');
include_once(DISCO_INC.'plasmature/plasmature.php');


//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'CurrentStudentNominationForm';

/**
 * 
 * @author Steve Smith
 */


class CurrentStudentNominationForm extends LutherDefaultThorForm
{
    function on_every_time()
    {   
        $state_field = $this->get_element_name_from_label('State');
        $this->change_element_type($state_field, 'state');
        
        $state_province_field = $this->get_element_name_from_label('State');
        $this->change_element_type('id_9765x1CI8m', 'state');
        
        $today = getdate();
        
        $grad_year = $this->get_element_name_from_label('High School Graduation Year');
        
        // If the date is before February 2, let submitters choose the current year.
        // If the date is after February 2, let submitters choose the current year+1.
        $feb1 = 32; // the numeric representation (yday) of February 1st
        
        if ($today['yday'] < $feb1) {
            $this->change_element_type($grad_year, 'radio_inline', array(
                'options' => array(
                    ($today['year']) => ($today['year']),
                    ($today['year']+1) => ($today['year']+1), 
                    ($today['year']+2) => ($today['year']+2), 
                    ($today['year']+3) => ($today['year']+3)
                    )));
        } else {
            $this->change_element_type($grad_year, 'radio_inline', array(
                'options' => array( 
                    ($today['year']+1) => ($today['year']+1), 
                    ($today['year']+2) => ($today['year']+2), 
                    ($today['year']+3) => ($today['year']+3),
                    ($today['year']+4) => ($today['year']+4)
                    )));
        }
    }
}
