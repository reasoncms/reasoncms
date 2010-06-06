<?php
////////////////////////////////////////////////////////////////////////////////
//
//    Steve Smith
//    2010-05-18
//
//    Work on the first page of the homecoming form
//
////////////////////////////////////////////////////////////////////////////////

class HomecomingRegistrationConfirmation extends FormStep
{
	function on_every_time()
	{
		$this->show_form = false;
		echo 'Thank you for registering for Homecoming.';
		echo $this->controller->get('amount');
	}
}
?>