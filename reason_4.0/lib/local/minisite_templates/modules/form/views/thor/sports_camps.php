<?
reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'reason/local/stock/pfproclass.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'SportsCampsThorForm';

class SportsCampsThorForm extends CreditCardThorForm
{				
	function on_every_time()
	{
		parent::on_every_time();

                //pretty up somethings
		$this->change_element_type($this->get_element_name_from_label('Gender'), 'radio_inline_no_sort');
		$this->change_element_type($this->get_element_name_from_label('Birth date'), 'textDate');
		$this->change_element_type($this->get_element_name_from_label('State'), 'state');

                $form_name =& $this->get_model()->get_form_name();
                $expense_element = $this->get_element_name_from_label('Expense Budget Number');
                $revenue_element = $this->get_element_name_from_label('Revenue Budget Number');
                
                // changing expense numbers for ofs reporting
                // 5/17/2011
                if (stristr($form_name, 'running') !== false ){
                    $this->set_value($expense_element, '13-000-08704-12121');
                    $this->set_value($revenue_element, '13-000-08704-22000');
                }
                if (stristr($form_name, 'football') !== false ){
                    $this->set_value($expense_element, '13-000-16400-12121');
					$this->set_value($revenue_element, '13-000-16400-22000');
                }
                if (stristr($form_name, 'soccer') !== false ){
                    $this->set_value($expense_element, '13-000-08705-12121');
					$this->set_value($revenue_element, '13-000-08705-22000');
                }
                if (stristr($form_name, 'tennis') !== false ){
                    $this->set_value($expense_element, '13-000-08709-12121');
					$this->set_value($revenue_element, '13-000-08709-22000');
                }
                if (stristr($form_name, 'swim') !== false ){
                    $this->set_value($expense_element, '13-000-08712-12121');
					$this->set_value($revenue_element, '13-000-08712-22000');
                }
                if (stristr($form_name, 'frisbee') !== false ){
                    $this->set_value($expense_element, '13-000-08716-12121');
					$this->set_value($revenue_element, '13-000-08716-22000');
                }
                if (stristr($form_name, 'volleyball') !== false ){
                    $this->set_value($expense_element, '13-000-08710-12121');
					$this->set_value($revenue_element, '13-000-08710-22000');
                }
                if (stristr($form_name, 'basketball') !== false){
                    $this->set_value($expense_element, '13-000-08702-12121');
					$this->set_value($revenue_element, '13-000-08702-22000');
                }
                if (stristr($form_name, 'wrestling') !== false){
                    // there are two different nunbers for wrestling camps
                    // one for the team camp all others are the individual camps
                    if ($form_name == 'Wrestling Team Camps Registration Form' ){
                        $this->set_value($expense_element, '13-000-08708-12121');
						$this->set_value($revenue_element, '13-000-08708-22000');
                    } else {
                        $this->set_value($expense_element, '13-000-08707-12121');
                        $this->set_value($revenue_element, '13-000-08707-22000');
                    }
                }
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