<?
reason_include_once('minisite_templates/modules/form/views/thor/credit_card_payment.php');
include_once(WEB_PATH.'stock/pfproclass.php');
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
    }

    function email_form_data_to_submitter()
    {
        $model =& $this->get_model();

        //Check for a Parent/Guardian email element, if one is there set as recipient
        $elements = $this->get_element_names();
        foreach ($elements as $element => $value) {
            $dn = $this->get_display_name($value);
            $re = "/^Parent'?s?\\s?\\/?\\s?(Guardian)?'?s? [Ee]-?mail$/"; 
            $match = preg_match($re, $dn);
            if ($match) {
                $recipient = $this->get_value_from_label($dn);
            }
        }

		if ( !$recipient )
		{
			if ($submitter = $model->get_email_of_submitter())
				$recipient = $submitter.'@luther.edu';
		}

		// If we're supposed to send a confirmation and we have an address...
		if ( $recipient )
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