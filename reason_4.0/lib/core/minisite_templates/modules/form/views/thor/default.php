<?php
/**
 * @package reason
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

	function get_closed_html()
	{
		$html = '<div class="closedMessage"><h3>This form is closed.</h3>'."\n";
		$model =& $this->get_model();
		if ($model->submission_limit_is_exceeded())
		{
			$html .= $this->get_submission_limit_exceeded_html();	
		}
		else
		{
			$html .= $this->get_date_limited_html();
		}
		$html .= '</div>';
		return $html;
	}

	function get_submission_limit_exceeded_html()
	{
		$model =& $this->get_model();
		$form =& $model->get_form_entity();
		$limit = $form->get_value('submission_limit');
		$html = '<p>The limit of '.$limit. ' submission';
		$html .= ($limit > 1) ? 's' : '';
		$html .= ' has been reached.</p>';
		return $html;
	}

	function get_date_limited_html()
	{
		$model =& $this->get_model();
		$form =& $model->get_form_entity();
		if ($model->before_open_date())
		{
			return '</p>Submissions to this form will be allowed beginning '. date('l, F jS, Y \a\t g:i:s A', strtotime($form->get_value('open_date'))).'.</p>';
		}
		else if ($model->after_close_date())
		{
			return '</p>Submissions to this form were closed on '. date('l, F jS, Y \a\t g:i:s A', strtotime($form->get_value('close_date'))).'.</p>';
			
		}
	}
}
?>
