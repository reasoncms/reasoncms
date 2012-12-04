<?php
/**
 * This file contains the EditNewsletter disco form step for use in the 
 * newsletter wizard admin module. 
 * 
 * @see NewsletterModule
 * @author Andrew Bacon
 * @author Nate White
 * @package reason
 * @subpackage admin
 */

/**
 * Disco multi-step form step for NewsletterModule that lets
 * the user make any changes to the output of the newsletter.
 * 
 * Now that the newsletter has the formatted data in it, we can
 * let the user play around with it a little. If they want to
 * fix some formatting mishap or change wording, this is their
 * chance.
 * 
 * This step:
 * <ul><li>Reexports the data in the format they just 
 * selected</li>
 * <li>Instantiates loki with said export as the value</li>
 * </ul>
 * And then sends the edited draft on to the next step.
 * 
 * @see NewsletterModule
 */

class EditNewsletter extends FormStep
{
	// the usual disco member data
	var $elements = array();
	var $required = array();
	var $error_header_text = 'Please check your form.';
	
	function init($args=array())
	{
		parent::init($args);
		if ($this->controller->get_current_step() != 'EditNewsletter')
			return;
		$site_id = (integer) $_REQUEST['site_id'];
        $editor_name = html_editor_name($site_id);
        $params = html_editor_params($site_id,$this->controller->user_id);
        $this->add_element('newsletter_loki',$editor_name,$params);
        $this->set_display_name('newsletter_loki',' ');
	}
	
	function on_every_time()
	{
		$session_loki_val = $this->controller->get_form_data('newsletter_loki');
		if (empty($session_loki_val))
		{
			$dump = $this->controller->get_all_form_data();
			$exporter = new NewsletterExporter(assemble_data($dump));
			$exported_newsletter = $exporter->export($this->controller->get_form_data('templateChooser'));
			$this->set_value('newsletter_loki', $exported_newsletter);
		} else 
		{
			$this->set_value('newsletter_loki', $session_loki_val);
		}
	}
	
	function pre_show_form()
	{
		echo "<h1>Step Four &#8212; Edit the Content of the Newsletter</h1>";
		echo "<p>You can make changes to the body of the newsletter here.</p>";
	}
	
	function process()
	{
	}
}

?>