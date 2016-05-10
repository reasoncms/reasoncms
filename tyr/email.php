<?php
/**
 * @package tyr
 */

/**
 * include the paths settings
 */
include_once('paths.php');
/**
 * include the directory service so that usernames as well as email addresses can be sent to this class
 */
include_once( CARL_UTIL_INC . 'basic/email_funcs.php' );

/**
 * This class represents an email. Example usage:
 * <code>
 * 		$email = new Email( $to, $from, $replyto, $subject, $txtbody, $htmlbody );
 *		$email->send();
 * </code>
 */
class Email
{
	/**
	 * @var PHPMailer
	 */
	var $PHPMailer;

	/**
	 * Construct the Email object
	 * @param mixed $tos array or comma-separated string of usernames and/or email addresses
	 * @param mixed $froms array or comma-separated string of usernames and/or email addresses
	 * @param mixed $replytos array or comma-separated string of usernames and/or email addresses
	 * @param string $subject the email's subject
	 * @param string $txtbody The text version of the email body
	 * @param string $htmlbody An HTML version of the email body (if available)
	 * @param string $address_types one of "mixed", "email", or "username" -- if tos, froms, and replytos are all either email addresses or usernames, indicating that here can save lookup time
	 * @param array $attachments array of filepaths to optional attachments
	 * @return void
	 */
	function Email($tos, $froms = '', $replytos = '', $subject = '', $txtbody = '', $htmlbody = '', $address_types = 'mixed', $attachments = array()) {
		if($address_types != 'mixed' && $address_types != 'email' && $address_types != 'username')
		{
			trigger_error('$address_types parameter ('.$address_types.') must be "mixed","email", or "username." Defaulting to "mixed".');
			$address_types = 'mixed';
		}
		
		$exceptions = true;
		$this->PHPMailer = new PHPMailer($exceptions);
		$this->PHPMailer->CharSet = 'utf-8';
		
		$this->add_tos($tos, $address_types);
		$this->add_froms($froms, $address_types);
		$this->add_replytos($replytos, $address_types);
		$this->set_subject($subject);
		$this->set_txtbody($txtbody);
		$this->set_htmlbody($htmlbody);
		$this->set_attachments($attachments);
	}

	// For details about $tos see prettify_email_addresses()
	function add_tos($tos, $address_types = 'mixed') {
		$address_array = prettify_email_addresses($tos, $address_types, 'array');

		foreach ($address_array as $address) {
			$this->PHPMailer->AddAddress($address);
		}
	}

	// For details about $froms see prettify_email_addresses()
	function add_froms($froms, $address_types = 'mixed') {
		$address_array = prettify_email_addresses($froms, $address_types, 'array');

		$from = array_pop($address_array);
		if (!$from) {
			$from = WEBMASTER_EMAIL_ADDRESS;
		}

		$this->PHPMailer->setFrom($from);
	}

	// For details about $froms see prettify_email_addresses()
	function add_ccs($ccs, $address_types = 'mixed') {
		$address_array = prettify_email_addresses($ccs, $address_types, 'array');

		foreach ($address_array as $address) {
			$this->PHPMailer->AddCC($address);
		}
	}

	// For details about $replytos see prettify_email_addresses()
	function add_replytos($replytos, $address_types = 'mixed') {
		$address_array = prettify_email_addresses($replytos, $address_types, 'array');

		foreach ($address_array as $address) {
			$this->PHPMailer->AddReplyTo($address);
		}
	}

	function set_subject($subject) {
		$this->PHPMailer->Subject = $subject;
	}

	function set_txtbody($txtbody) {
		$this->PHPMailer->Body = $txtbody;
	}

	function set_htmlbody($htmlbody) {
		if ($htmlbody) {
			$this->PHPMailer->IsHTML(true);

			// Copy txt body to altbody
			$this->PHPMailer->AltBody = $this->PHPMailer->Body;

			// Set html body as main body
			$this->PHPMailer->Body = $htmlbody;
		}
	}

	function set_attachments($attachments) {
		if (!is_array($attachments)) {
			return;
		}
		foreach ($attachments as $name => $file_path) {
			if (is_file($file_path)) {
				$this->PHPMailer->addAttachment($file_path, $name);
			}
		}
	}

	/**
	 * Send the Email
	 *
	 * @return boolean Accepted for delivery
	 */
	function send() {
		return $this->PHPMailer->send();
	}

}

?>
