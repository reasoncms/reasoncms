ckage reason
 * @subpackage minisite_modules
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/default.php');

/**
 * Register form with Reason
 */
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultThorForm';

/**
 * DefaultThorForm is an extension of the DefaultForm used for Thor
 *
 * 1. Uses the custom HTML from the thor form instead of the controller default.
 * 2. Adds show_submitted_data_dynamic_fields and a method get_show_submitted_data_dynamic_fields.
 *    If set to true, elements added by the custom form (and not in the thor data) are included in e-mails and the confirmation data
 *
 * @author Nathan White
 */

class DefaultThorForm extends DefaultForm
{
	var $show_submitted_data_dynamic_fields = false;
	
	function get_thank_you_html()
	{
		$model =& $this->get_model();
		$form =& $model->get_form_entity();
		return $form->get_value('thank_you_message');
	}
	
	function get_show_submitted_data_dynamic_fields()
	{
		return (isset($this->show_submitted_data_dynamic_fields)) ? $this->show_submitted_data_dynamic_fields : false;
	}
}
?>
