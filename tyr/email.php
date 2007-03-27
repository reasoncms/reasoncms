<?php
include_once('paths.php');
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

// 
// This class represents an email. Example usage:
// 				$email = new Email( $to, $from, $replyto, $subject, $txtbody, $htmlbody );
// 				$email->send();
//
class Email
{
	var $_tos;
	var $_froms;
	var $_replytos;
	var $_subject;
	var $_txtbody;
	var $_htmlbody;


	function Email($tos, $froms = '', $replytos = '', $subject = '', $txtbody = '', $htmlbody = '') {
		$this->add_tos($tos);
		$this->add_froms($froms);
		$this->add_replytos($replytos);
		$this->set_subject($subject);
		$this->set_txtbody($txtbody);
		$this->set_htmlbody($htmlbody);
	}

	// For details about $tos see $this->_prettify_addresses()
	function add_tos($tos) {
		$this->_tos .= $this->_prettify_addresses($tos);
	}

	// For details about $froms see $this->_prettify_addresses()
	function add_froms($froms) {
		$this->_froms .= $this->_prettify_addresses($froms);
	}

	// For details about $replytos see $this->_prettify_addresses()
	function add_replytos($replytos) {
		$this->_replytos .= $this->_prettify_addresses($replytos);
	}

	function set_subject($subject) {
		$this->_subject = $subject;
	}

	function set_txtbody($txtbody) {
		$this->_txtbody = $txtbody;
	}

	function set_htmlbody($htmlbody) {
		$this->_htmlbody = $htmlbody;
	}

	function send() {
		$multipart_boundary = uniqid('the_multipart_boundary_is_');
	
		$email =
			 "From: " . $this->_froms . "\n" .
			 "Bcc: " . $this->_tos . "\n" .
			 "Reply-To: " . $this->_replytos . "\n" .
			 "MIME-version: 1.0\n";

		if ( !empty($this->_htmlbody) ) {
			$email .= 'Content-Type: multipart/alternative; boundary="' . $multipart_boundary . '"' . "\n"; // define boundary
			$email .= "\nThis is a MIME message. If you are reading this text, you may want to consider changing to a mail reader or gateway that understands how properly to handle MIME multipart messages.\n\n"; // preamble
			$email .= "--" . $multipart_boundary . "\n"; // first boundary
		}
	
		$email .= "Content-Type: text/plain; charset=UTF-8\n";
		$email .= "Content-Disposition: inline";
	
		$email .= "\n\n" . $this->_txtbody;
	
		if ( !empty($this->_htmlbody) ) {
			$email .= "\n\n--" . $multipart_boundary . "\n";
			$email .= "Content-Type: text/html; charset=UTF-8\n";
			$email .= "Content-Disposition: inline\n\n";
			$email .= $this->_htmlbody;
			$email .= "\n\n--" . $multipart_boundary . "--\n"; //last boundary
		}
	
		mail( '', $this->_subject, '', $email );
	}


	// Paramater $addresses can be any of the following:
	//   a valid email address,
	//   an username in the directory,
	//   a comma-delimited combination of addresses and/or usernames, or
	//   an array of address and/or usernames.
	// 
	// This function works as follows: For each address, first treats
	// the address as a netid and tries to find a corresponding
	// address in the directory (if this fails, it assumes that the address
	// was intended as an address rather than a username); second, checks
	// whether the address is valid
	// 
	// If the address is invalid, it is sent to the webmaster and an error is triggered
	function _prettify_addresses($addresses) {

		$pretty_addresses = '';
		if ( !is_array($addresses) )
			$addresses = explode(',', $addresses);
		foreach ( $addresses as $address ) {
			$address = trim($address);
			if ( !empty($address) ) {
				$dir = new directory_service();
				$result = $dir->search_by_attribute('ds_username', $address, array('ds_email'));
				$dir_value = $dir->get_first_value('ds_email');
				$address = (!empty($dir_value)) ? $dir_value : $address;
				$num_results = preg_match( '/^([-.]|\w)+@([-.]|\w)+\.([-.]|\w)+$/i', $address );
				if ($num_results <= 0)
				{
					trigger_error('The address ' . $address . ' is invalid - sending e-mail to ' . WEBMASTER_EMAIL_ADDRESS . ' instead.');
					$pretty_addresses .= WEBMASTER_EMAIL_ADDRESS . ', ';
				}
				else
				{
					$pretty_addresses .= $address . ', ';
				}
			}
		}

		return $pretty_addresses;
	}
}

?>
