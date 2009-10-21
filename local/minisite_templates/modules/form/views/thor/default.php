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
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'LutherDefaultThorForm';

/**
 * DefaultThorForm is an extension of the DefaultForm used for Thor
 *
 * 1. Uses the custom HTML from the thor form instead of the controller default.
 * 2. Adds show_submitted_data_dynamic_fields and a method get_show_submitted_data_dynamic_fields.
 *    If set to true, elements added by the custom form (and not in the thor data) are included in e-mails and the confirmation data
 *
 * @author Nathan White
 */

class LutherDefaultThorForm extends DefaultThorForm
{
	function on_every_time()
	{
		$state_field = $this->get_element_name_from_label('State');
		$this->change_element_type($state_field, 'state');
		
		$state_province_field = $this->get_element_name_from_label('State/Province');
		$this->change_element_type($state_province_field, 'state_province');

	}
}
?>
