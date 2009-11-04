<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');

/**
 * Register form with Reason
 */
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'LutherDefaultForm';

/**
 * DefaultThorForm is an extension of the DefaultForm used for Thor
 *
 * 1. Uses the custom HTML from the thor form instead of the controller default.
 * 2. Adds show_submitted_data_dynamic_fields and a method get_show_submitted_data_dynamic_fields.
 *    If set to true, elements added by the custom form (and not in the thor data) are included in e-mails and the confirmation data
 *
 * @author Nathan White
 */

class LutherDefaultForm extends DefaultThorForm
{
	function on_every_time()
	{
		$gender = $this->get_element_name_from_label('Gender');
		$this->change_element_type($gender, 'radio_inline');
		
		$sex = $this->get_element_name_from_label('Sex');
		$this->change_element_type($sex, 'radio_inline');
		
		$state = $this->get_element_name_from_label('State');
		$this->change_element_type($state, 'state');
		
		$state_province = $this->get_element_name_from_label('State/Province');
		$this->change_element_type($state_province, 'state_province');
		
	}	
}
?>
