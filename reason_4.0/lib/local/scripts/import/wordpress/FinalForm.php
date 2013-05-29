<?php
class FinalForm extends FormStep
{	
	var $display_name = 'Wordpress Import - Finished';
	
	function process()
	{
		$data = $this->controller->get_all_form_data();
	}
}
?>