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
include_once( DISCO_INC . 'plugins/honeypot/honeypot.php' );

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
	
	function custom_init()
	{
		$this->prefill_fields();
	}
	
	function run_load_phase()
	{
		$this->add_honeypot();
		parent::run_load_phase();
	}
	
	function add_honeypot()
	{
		$honeypot = new HoneypotDiscoPlugin($this);
	}
	
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

	function run_error_checks()
	{
		$model = $this->get_model();
		if ($model->form_has_event_ticket_elements()) {
			$ticket_request = $model->event_tickets_get_request();
			$request_status = $model->event_tickets_ticket_request_is_valid($ticket_request);
			if (!$request_status['status']) {
				$this->set_error($request_status['disco_element_id'], $request_status['message']);
			}
		}
	}
	 
	/**
	 * Get form fields where value prepopulation was enabled
	 * 
	 * @return array array of fields to enable prepopulation;
	 *     array keys are thor element names, values are the
	 *     urldecoded strings Disco should find 
	 *     in the request array
	 */
	function fields_to_prepopulate_from_url()
	{
		$model = $this->get_model();
		$thor_core =& $model->get_thor_core_object();
		$form =& $model->get_form_entity();

		$fields_str = $form->get_value('prefill_these_form_fields');
		$fields_array = explode(",", $fields_str);

		$fields = array();
		$url_key_prefix = "p_";
		foreach ($fields_array as $thorColId) {
			$fieldName = $thor_core->get_column_label($thorColId);
			if (!is_string($fieldName)) {
				// Don't prefill text comment form fields
				continue;
			}

			$request_key = $url_key_prefix . strtolower($fieldName);

			// We're not encoding the key here per se, rather, we need to tell
			// Disco what the url *decoded* request key is probably going to be. 
			// We're trying to guess the key in the $_REQUEST array. 
			// It's straightforward for almost all characters, 
			// but reserved url chars might fail.
			// The char replacements below give us pretty good coverage.
			$request_key = trim(str_replace(array(" ", ".", "+"), "_", $request_key));

			$fields[$thorColId] = $request_key;
		}

		return $fields;
	}

	/**
	 * Enable form prefilling for select form fields from URL params
	 */
	function prefill_fields()
	{
		foreach ($this->fields_to_prepopulate_from_url() as $element_name => $prepopulate_key) {
			$this->enable_prepopulation($element_name, $prepopulate_key);
		}
	}
}

?>
