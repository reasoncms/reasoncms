<?php

/**
 * Note: this appears to be obsolete code. It should probably not be committed.
 */

class Finished extends FormStep
{
	// the usual disco member data
	var $elements = array();
	var $required = array();
	var $error_header_text = 'Please check your form.';
	var $show_form = false;
	
	
	function init($args = array())
	{
		parent::init();
	
	}

	function no_show_form()
	{
		$tos = $this->controller->get_form_data('recipients');
		$split_list = split_email_list($tos);
		echo "<h1>Newsletter sent!</h1>";
		echo "<h2>Process complete.<h2>";
		if(!empty($split_list))
		{
		echo "<p>You have sent your email! A list of recipients is provided below:</p>";

		echo "<ul>";
		foreach ($split_list as $email)
			echo "<li>" . htmlentities($email) . "</li>";
		echo "</ul>";
		}
		echo '<p><a href="">Send another newsletter</a></p>'."\n";
		$this->controller->destroy_form_data();
	}

	function run() 
	{
	}
	
	function process()
	{
	}
	
	
	function run_error_checks()
	{
	}

};

?>