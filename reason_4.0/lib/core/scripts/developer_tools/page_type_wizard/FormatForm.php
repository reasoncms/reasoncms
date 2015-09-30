<?php
/**
 * @package reason
 * @subpackage scripts
 */
class FormatForm extends FormStep
{	
	var $display_name = "Page Type Wizard &#8211; Choose an export format";

	function init($args=array())
	{
		parent::init($args);
		$pt = new ReasonPageType;
		$formats = $pt->get_export_formats();
		foreach ($formats as $format => $formatInfo) 
		{
		// !TODO: Should I add a check here to make it only show types that make sense?
			if ($formatInfo['printable'] == true)
				$formatNames[$format] = $formatInfo['name'];
		};
		$this->add_element('Format', 'radio', array("options" => $formatNames));
	}

	function on_every_time()
	{	
	}

	function pre_show_form()
	{
		$requested_page_type = $this->controller->get_form_data('page_type_name_new');

		if (isset($this->display_name))
			printf('<h3>%s</h3>'."\n", $this->display_name . " for " . $requested_page_type);

		$str = "<p>Please choose an export format.</p>";


		echo $str;
	}

	function post_show_form()
	{
		echo "<br /> <br /> <div id='display'>";
			$checkpt = $this->get_page_type();
			echo "Page Type Name: " .  $checkpt->get_name();
			$checkpt->get_as_html();
		echo "</div>";
	}
	
	function process()
	{
		//$this->controller->update_session_form_vars();
		$str = <<<EOD
<h3>Page Type Wizard &#8211; Definition</h3>

<p>Copy and paste the text below, exactly as-is, into your page types definition file.</p> 
EOD;
		echo $str;
		$pageType = $this->get_page_type();
		echo "<pre>";
		
		$selected_export_format = $this->controller->get('Format');
		$pageType->set_name($this->controller->get_form_data('page_type_name_new'));
		$pageType->set_export_format($selected_export_format);
		echo $pageType->export();
		echo "</pre>";
	}

	function get_page_type()
	{
//		if (!isset($this->pt))
			$this->_build_page_type();
		return $this->pt;
	}

	function _build_page_type()
	{
		$rpt =& get_reason_page_types();
		if ($this->controller->get_form_data('page_type_name') != 'new')
		{
			$this->pt = $rpt->get_page_type($this->controller->get_form_data('page_type_name'));
		} else {
			$this->pt = $rpt->get_page_type('default');
		}
		$loci = $this->pt->get_region_names();
		foreach ($loci as $region)
		{
			$params_array = json_decode($this->controller->get_form_data($region . "_params"), true);
			$this->pt->set_region(
				$region, // The name of the region to be set.
				$this->controller->get_form_data($region . "_module"), // The name of the module to set.
				$rpt->resolve_filename($this->controller->get_form_data($region . "_module")), // The filename. if this doesn't exist, blank.
				$params_array // the params.
			);
		}
	}	
}
?>
