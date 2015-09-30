<?php
/**
 * @package reason
 * @subpackage scripts
 */

class EditForm extends FormStep
{	
	var $display_name = 'Page Type Wizard &#8211; Edit a page type';
	var $elements = array();
	var $loci = array
		(
			'pre_bluebar',
			'main',
			'main_head',
			'main_post',
			'edit_link',
			'pre_banner',
			'banner_xtra',
			'post_banner',
			'pre_sidebar',
			'sidebar',
			'navigation',
			'footer',
			'sub_nav',
			'sub_nav_2',
			'sub_nav_3',
			'post_foot',
		);


	function init($args=array())
	{
		parent::init($args);
		if ($this->controller->get_form_data('page_type_name') != false)
		{
			$rpt =& get_reason_page_types();
			$pt = new ReasonPageType;
			if ($this->controller->get_form_data('page_type_name') != 'new')
			{
				$pt = $rpt->get_page_type($this->controller->get_form_data('page_type_name'));
			} else {
				$pt = $rpt->get_page_type('default');
			}

			if ($pt->get_region_names() != null) {
				$this->loci = $pt->get_region_names();
			}
			$this->add_element('page_type_name_new', 'text');
			$this->set_display_name('page_type_name_new', 'Page type name');
			foreach ($this->loci as $region_name) {
				$regionInfo = $pt->get_region($region_name);
				$this->add_element($region_name.'_header', 'comment', array('text' => '<h2 class="region">' . $region_name . '</h2>'));
				$this->add_element($region_name.'_module', 'text');
				$this->set_display_name($region_name.'_module','Module');
				$this->add_element($region_name.'_params', 'text', array('maxlength'=>40000));
				$this->set_display_name($region_name.'_params','Parameters');
			}
		}
	}

	// runs after the init and prefill of existing values from session.
	function on_every_time()
	{
		$rpt =& get_reason_page_types();
		if ($this->controller->get_form_data('page_type_name') != 'new')
		{
			$pt = $rpt->get_page_type($this->controller->get_form_data('page_type_name'));
		} else {
			$pt = $rpt->get_page_type('default');
		}
		$this->set_value('page_type_name_new', $this->controller->get_form_data('page_type_name'));

		foreach ($this->loci as $region_name)
		{
			$regionInfo = $pt->get_region($region_name);
			if ($this->controller->get_form_data($region_name.'_module') == '')
			{
				$this->set_value($region_name.'_module', $regionInfo['module_name']);
			}
			if ($this->controller->get_form_data($region_name.'_params') == '')
			{
				$this->set_value($region_name.'_params', (isset($regionInfo['module_params']) ? json_encode($regionInfo['module_params']):""));
			}

		}
	}

	function pre_show_form()
	{
		if (isset($this->display_name))
			printf('<h3>%s</h3>'."\n", $this->display_name);
		$str = <<<EOD
<p>Attributes of the requested page type are listed below. Module assignments should be entered relative to minisite_templates/modules. You may also specify parameters using either JSON or the editor (javascript only).</p> 
<style type="text/css">
.words {
	width: 0;
}
</style>
EOD;
		echo $str;
	}
	
	function run_error_checks() {
		$rpt =& get_reason_page_types();
		// Validate JSON.
		if ($this->controller->get_form_data('page_type_name') != 'new')
		{
			$this->pt = $rpt->get_page_type($this->controller->get_form_data('page_type_name'));
		} else {
			$this->pt = $rpt->get_page_type('default');
		}
		$loci = $this->pt->get_region_names();
		foreach ($loci as $region)
		{
			if ($this->get_value($region . "_params") != NULL || $this->get_value($region . "_params") != "") {
				if ($this->get_value($region . "_module") == NULL || $this->get_value($region . "_module") == "") {
					$this->set_error($region . '_params', "$region: you cannot specify module parameters without assigning a module.");
				}
				if (!is_array(json_decode($this->get_value($region . "_params"), true))) 
				{
					$this->set_error($region . '_params', "The JSON entered was invalid.");		
				}
			}
		}
	}
	
	function process()
	{
	}
}
?>