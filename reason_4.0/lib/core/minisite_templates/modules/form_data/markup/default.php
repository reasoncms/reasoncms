<?php

$GLOBALS['form_data_markups']['minisite_templates/modules/form_data/markup/default.php'] = 'DefaultFormDataMarkup';

class DefaultFormDataMarkup
{
	protected $form;
	protected $form_data;
	protected $label_map;
	function set_form($form)
	{
		$this->form = $form;
	}
	function add_head_items($head_items)
	{
		//
	}
	function set_form_data($form_data)
	{
		$this->form_data = $form_data;
	}
	function set_label_map($label_map)
	{
		$this->label_map = $label_map;
	}
	function get_label($key)
	{
		if(isset($this->label_map[$key]))
		{
			return $this->label_map[$key];
		}
		return NULL;
	}
	function get_key($label)
	{
		return array_search($label, $this->label_map);
	}
	function get_label_value($label, $row)
	{
		if($key = $this->get_key($label))
		{
			return $row[$key];
		}
		else
		{
			trigger_error('Label "'.$label.'" not found.');
		}
	}
	function get_markup()
	{
		return spray($this->form_data);
	}
}