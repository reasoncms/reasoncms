<?
reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'stock/pfproclass.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'SwimmingCampThorForm';

class SwimmingCampThorForm extends CreditCardThorForm
{				
	function on_every_time()
	{
		parent::on_every_time();
		

		
		$this->change_element_type($this->get_element_name_from_label('Gender'), 'radio_inline_no_sort');
		$this->change_element_type($this->get_element_name_from_label('Birth date'), 'textDate');
		$this->change_element_type($this->get_element_name_from_label('State'), 'state');
		

		
		/*
$p_element = $this->get_element_name_from_label('payment_amount');
		
		$this->change_element_type($p_element,'radio_no_sort', array(
			'display_name' => 'Camper Type',
			'options' => array(
				'$'.number_format(395,2,'.','')=>'Full Resident - $395.00 (All meals, housing, materials)',
				'$'.number_format(250,2,'.','')=>'Commuter - $250.00 (materials only - no meals)',
				''.number_format(50,2,'.','')=>'Deposit - $50.00 (non-refundable)')
*/
			
/*
				'Full' => 'Full Resident - $385.00 (All meals, housing, materials)',
				'Full-deposit' =>'or Full Resident deposit - $50.00 (non-refundable)',
				'Commuter' =>'Commuter - $240.00 (materials only - no meals)',
				'Commuter-deposit' =>'or Commuter deposit - $50.00 (non-refundable)')
*/
	//		)
	 //	);	

	$this->is_in_testing_mode = true;
	}

        function email_form_data_to_submitter()
	{
		$model =& $this->get_model();

		// Figure out who would get an email confirmation (either through a
		// Your Email field or by knowing the netid of the submitter
		if (!$recipient = $this->get_value_from_label('Parent\'s e-mail'))
		{
			if ($submitter = $model->get_email_of_submitter())
				$recipient = $submitter.'@luther.edu';
		}

		// If we're supposed to send a confirmation and we have an address...
		if ($recipient)
		{
			// Use the (first) form recipient as the return address if available
			if ($senders = $model->get_email_of_recipient())
			{
				list($sender) = explode(',',$senders, 1);
				if (strpos($sender, '@') === FALSE)
					$sender .= '@luther.edu';
			} else {
				$sender = 'noreply@luther.edu';
			}

			$thank_you = $model->get_thank_you_message();

			$email_values = $model->get_values_for_email_submitter_view();

			if (!($subject = $this->get_value_from_label('Confirmation Subject')))
				$subject = 'Thank you for your payment';

			$values = "\n";
			if ($model->should_email_data())
			{
				foreach ($email_values as $key => $val)
				{
					$values .= sprintf("\n%s:\n   %s\n", $val['label'], $val['value']);
				}
			}

			$html_body = $thank_you . nl2br($values);
			$txt_body = html_entity_decode(strip_tags($html_body));

			$mailer = new Email($recipient, $sender, $sender, $subject, $txt_body, $html_body);
			$mailer->send();
		}
	}	
}
?>