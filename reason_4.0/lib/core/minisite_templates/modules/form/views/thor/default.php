<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/default.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultThorForm';

/**
 * DefaultThorForm is an extension of the DefaultForm used for Thor
 *
 * 1. Uses the custom HTML from the thor form instead of the controller default.
 *
 * @author Nathan White
 */

class DefaultThorForm extends DefaultForm
{
	function get_thank_you_html()
	{
		$model =& $this->get_model();
		$form =& $model->get_form_entity();
		return $form->get_value('thank_you_message');
	}
}
?>
