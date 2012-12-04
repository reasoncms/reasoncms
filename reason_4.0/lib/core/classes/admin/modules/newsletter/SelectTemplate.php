<?php
/**
 * This file contains the SelectTemplate disco form step for use in the 
 * newsletter wizard admin module. 
 * 
 * @see NewsletterModule
 * @author Andrew Bacon
 * @author Nate White
 * @package reason
 * @subpackage admin
 */

/**
 * Disco multi-step form step for NewsletterModule that 
 * asks the user to pick a 'template' (export format) for
 * their newsletter. 
 * 
 * Because there is some metadata for events and posts, we can
 * provide a more meaningful newsletter by including different
 * information. These different levels of display are
 * represented by templates. 
 * 
 * This step:
 * <ul><li>Shows the user how each template will display
 * their data</li>
 * <li>Asks which template to use</li>
 * </ul>
 * And then sends their choice on to the next step.
 * 
 * @see NewsletterModule
 */
class SelectTemplate extends FormStep
{
	// the usual disco member data
	var $elements = array(
	);
	var $required = array();
	var $error_header_text = 'Please check your form.';
	
	function init($args=array())
	{
		if ($this->controller->get_current_step() != 'SelectTemplate')
			return;
		parent::init($args);

		$exporter = new NewsletterExporter();
		$formats = $exporter->get_export_formats();
		foreach ($formats as $format=>$info)
		{
			$templates[$format] = $info['name'];
		}
		$this->add_element('templateChooser', 'radio_inline', array('options' => $templates, 'display_name' => 'Template'));
		$this->add_required('templateChooser');
	}
	
	function on_every_time()
	{
		// reset newsletter_loki.
		$this->controller->set_form_data('newsletter_loki', '');
	}

	function pre_show_form()
	{
		echo "<h1>Step Three &#8212; Select a Template</h1>";
		echo "<p>Select a template from the choices below.</p>";
		echo '<div id="previewDiv">';

		$dump = $this->controller->get_all_form_data();
		$exporter = new NewsletterExporter(assemble_data($dump));
		$formats = $exporter->get_export_formats();
		foreach ($formats as $format => $info)
		{
			echo '<h3 class="templateName">' . $info['name'] . "</h3>";
			echo '<div id="' . $format . '" name="' . $info['name'] . '" class="newsletterTemplate">';
			echo $exporter->export($format);
			echo "</div>";
			echo '<br class="templateName" />';
		}
		echo "</div>";
	}
	function post_show_form() 
	{
	
	}
	
	function process()
	{
	}
}
?>