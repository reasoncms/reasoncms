<?php
/**
 * @package reason
 * @subpackage scripts
 */
class SelectForm extends FormStep
{	
	// the usual disco member data
	var $elements = array(
		'page_type_name'=>array(
			'display_name'=>'Page Type',
			'type'=>'select_no_sort',
		),
	);
	var $required = array('page_type_name');
	var $error_header_text = 'Please check your form.';
	var $display_name = 'Page Type Wizard &#8211; Page type select';


	function init($args=array())
	{
		parent::init($args);
		$pts = new ReasonPageTypes;
		$pageTypeList = $pts->get_page_type_names();
		// change element types here
		foreach ($pageTypeList as $index => $pageType)
		{
			$options[$pageType] = $pageType;
		}
		natsort($options);
		$options = array("new" => "New Page Type") + $options;
		$this->change_element_type('page_type_name', 'select_no_sort', array('options' => $options));	
	}

	function on_every_time()
	{
			$this->controller->destroy_form_data();
	}

	function pre_show_form()
	{
		if (isset($this->display_name))
			printf('<h3>%s</h3>'."\n", $this->display_name);
		
		$str = '<p>Please select the page type you would like to view or edit from the list below.</p>';
		echo $str;

	}
	
	function process()
	{
//		die($this->get_value("page_type_name") . "       " . $this->controller->get_form_data("page_type_name"));
	if ($this->get_value("page_type_name") != $this->controller->get_form_data("page_type_name"))
		{
//			$this->controller->set_form_data("reset", "true");

		}
	}
}
?>