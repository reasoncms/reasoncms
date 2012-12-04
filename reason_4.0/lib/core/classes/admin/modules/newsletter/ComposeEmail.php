<?php
/**
 * This file contains the ComposeEmail disco form step for use in the 
 * newsletter builder admin module. 
 * 
 * @see NewsletterModule
 * @author Andrew Bacon
 * @author Nate White
 * @package reason
 * @subpackage admin
 */

/**
 * Disco multi-step form step for NewsletterModule that does the
 * sending of the email (or lets them copy-paste it into their
 * own client.
 * 
 * This step:
 * <ul><li>Asks for Reply-to, subject, and recipients</li>
 * <li>If JS is enabled, offers to let the user send it</li>
 * <li>Does some basic email format and netid existence
 * checking</li>
 * <li>Sends the email!</li>
 * </ul>
 * 
 * The where_to() in this class is executed immediately after the 
 * process phase, so you'll want to disable it if you want to see 
 * any errors.
 * 
 * @see NewsletterModule
 */
class ComposeEmail extends FormStep
{
	// the usual disco member data
	/* var $elements = array(
		'sender' => array(
			'type' => 'solidtext',
			'display_name' => 'From'
		),
		'reply_to' => array(
			'type' => 'text',
			'display_name' => 'Reply-to'
		),
		'reply_to_help' => array(
			'type' => 'comment',
			'text' => 'You may enter a reply-to address here. This should contain the email address that should be contacted in response to the newsletter.'
		),
		'recipients' => array(
			'type' => 'textarea',
			'display_name' => 'To',
			'cols' => '60',
			'rows' => '4'
		),
		'subject_line' => array(
			'type' => 'text',
			'display_name' => 'subject'
		),
	);
	var $required = array('recipients', 'subject_line'); */
	var $error_header_text = 'Please check your form.';
	
	function init($args=array())
	{
		parent::init($args);
	}
	
	function on_every_time()
	{
		// You need to prefill the sender field. 
		/* $sender = $this->controller->user_id;
		$sender = new Entity($sender);
		$this->set_value('sender', $sender->get_value('name'));
		$this->set_value('subject_line', htmlspecialchars($this->controller->get_form_data('newsletter_title'))); */
		unset($this->actions['next']);
	}

	function pre_show_form()
	{
		/* echo "<h1>Step Five &#8212; Send the Newsletter</h1>";
		echo "<p>You may use Reason to send your newsletter. You may enter Carleton NetIDs or emails in the \"to\" field, separated by semicolons or commas. You may also review the body of the email below. To make any changes, press the button labeled \"Go Back\".</p><p>Alternatively, you can copy and paste the newsletter as you see it below into your preferred email client.</p>";
		echo '<div id="tabList" class="haveJS"><ul><li><a href="#use_reason_tab">Send using Reason</a></li><li><a href="#use_client_tab">Send with your own email client</a></li></ul></div>';
		echo '<div id="use_reason_tab">'; */
		echo '<div id="ComposeEmailStep">'."\n";
		echo "<p class='basicInstructions'>Copy and paste the newsletter as you see it below into your preferred email client.</p>";
		$final_text =  tidy(carl_get_safer_html($this->controller->get_form_data('newsletter_loki')));
		echo '<div id="html" class="previewDiv">' . $final_text . '</div>';
		echo '</div>';
	}
	
	function run_error_checks()
	{
		/*
		$reply_to = $this->get_value('reply_to');
		if (!empty($reply_to))
			$valid = is_valid_email($reply_to);
		else
			$valid = true;
		if (!$valid)
			$this->set_error('reply_to', 'The reply-to address you have provided is not a valid email address.');

		$tos = $this->get_value('recipients');
		$split_list = split_email_list($tos);
		if (empty($split_list))
		{
			$this->set_error('recipients', 'The recipient addresses you have provided are incorrectly formatted.');
		}
		$valids = validate_mixed_list($split_list);
		print_r($valids);
		print_r($split_list);
		
		// This would be simpler with array_diff_key, but that requires php5
		$invalids = array_diff(array_keys($split_list),array_keys($valids));
		foreach($invalids as $k=>$v)
			$invalids[$k] = $split_list[$k];
		if (!empty($invalids))
		{
			$list_of_invalids = '';
			foreach ($invalids as $invalid)
				$list_of_invalids .=  htmlentities($invalid) . '; ';
			$this->set_error('recipients', (count($invalids) == 1) ? 'The following email address or netid was invalid: ' . $list_of_invalids : 'The following email addresses were invalid: ' . $list_of_invalids);
		
		}
		*/
	}
	
	function process()
	{
		/*
		$final_text =  expand_all_links_in_html(tidy(carl_get_safer_html($this->controller->get_form_data('newsletter_loki'))));
		$tos = $this->get_value('recipients');
		$split_list = split_email_list($tos);
		//var_dump($split_list);
		//foreach ($split_list as $email)
		//	echo "Is '$email' valid? " . is_valid_mixed($email) . "<br />";
		$from = $this->get_value('sender');
		$subject = $this->get_value('subject_line');
		$reply_to = $this->get_value('reply_to');
		$txtbody = to_plaintext($final_text);
		$htmlbody = $final_text;
		
		$email = new Email($split_list, $from, $reply_to, $subject, $txtbody, $htmlbody);
		$face = $this->controller->authenticated_user_id;
		if ($email->send())
			log_email($tos, $from, $subject, $face);
		*/
	}
	/*
	function where_to()
	{
		return carl_make_redirect(array('newsletterIsFinished' => 'true', '_step' => ''));
	}
	*/
}
?>
