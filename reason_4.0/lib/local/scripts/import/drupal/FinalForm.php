<?php
class FinalForm extends FormStep
{	
	var $display_name = 'Drupal Import - Finished';
	
	function process()
	{
		$data = $this->controller->get_all_form_data();
	}
}
?>