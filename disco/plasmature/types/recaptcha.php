<?php
/**
 * Recaptcha type library.
 *
 * @package disco
 * @subpackage plasmature
 */
require_once PLASMATURE_TYPES_INC."default.php";
include_once(SETTINGS_INC . 'recaptcha_settings.php' );

/**
 * Adds ReCaptcha to the form.
 *
 * @package disco
 * @subpackage plasmature
 * @author Amanda Frisbee
 * @author Nathan White
 */
class recaptchaType extends defaultType
{
	var $type = 'recaptcha';
	var $type_valid_args = array('public_key','private_key');
	var $lang = 'en';
	var $recaptcha;

	var $standard_error_message = "You must respond to the reCAPTCHA challenge question to complete the form";

	/**
	 * If recaptcha public and private keys are not available - lets trigger an error.
	 */
	function additional_init_actions($args = array())
	{
		$this->recaptcha = new \ReCaptcha\ReCaptcha($this->get_private_key());

		 if (!$this->keys_available())
		 {
		 	trigger_error('recaptcha plasmature type element is not setup correctly - make sure public and private keys are provided as an argument or defined as defaults in recaptcha_settings.php');
		 }
	}
	
	function get_display()
	{
		if ($this->keys_available())
		{
			$output = '<div class="g-recaptcha" data-sitekey="' . $this->get_public_key() . '"></div>';
			$output .= '<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=' . $this->lang . '"></script>';
			return $output;
		}
	}
	
	function is_hidden()
	{
		return (!$this->keys_available());
	}
	
	function grab_value()
	{
		if ($this->keys_available())
		{
			if (isset($_POST['g-recaptcha-response']))
			{
				$resp = $this->recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
				if ($resp->isSuccess())
				{
					return 1;
				}
				else
				{
					$error_codes = $resp->getErrorCodes();
					if (!empty($error_codes)) {
						/*
						 * Possible error codes:
						 * missing-input-secret		The secret parameter is missing.
						 * invalid-input-secret		The secret parameter is invalid or malformed.
						 * missing-input-response	The response parameter is missing.
						 * invalid-input-response	The response parameter is invalid or malformed.
						 * bad-request				The request is invalid or malformed.
						 *
						 * */
						$errorCodes = $resp->getErrorCodes();
						$triggered_error_message = '';
						if (
							in_array('missing-input-secret', $errorCodes) ||
							in_array('invalid-input-secret', $errorCodes)
						) {
							$triggered_error_message .= "The reCAPTCHA challenge was ignored - reCAPTCHA reports that your site private key is invalid.";
						} elseif (
							in_array('missing-input-response', $errorCodes) ||
							in_array('invalid-input-response', $errorCodes)
						) {
							$this->set_error("The reCAPTCHA wasn't entered correctly. Please try again.");
						} else {
							$triggered_error_message .= "The request to reCAPTCHA was invalid or malformed.";
						}

						if ($triggered_error_message !== '') {
							trigger_error($triggered_error_message);
						}
					} else {
						$this->set_error($this->standard_error_message);
					}
				}
			}
		 	else
		 	{
				$this->set_error($this->standard_error_message);
		 	}
		 	return 0;
		}
	}

	/**
	 * Are public and private keys available (needed for the element to work)?
	 *
	 * @return boolean
	 */
	protected function keys_available()
	{
		return ($this->get_public_key() && $this->get_private_key());
	}
	
	/**
	 * Retrieve public key - check type_valid_args, but fall back to a default if it exists.
	 *
	 * @return mixed string public key or boolean false if it cannot be determined.
	 */
	protected function get_public_key()
	{
		$public_key = $this->get_class_var('public_key');
		if (!empty($public_key)) return $public_key;
		else
		{
			$public_key = constant("RECAPTCHA_PUBLIC_KEY");
			if (!empty($public_key)) return $public_key;
		}
		return false;
	}

	/**
	 * Retrieve private key - check type_valid_args, but fall back to a default if it exists.
	 *
	 * @return mixed string private key or boolean false if it cannot be determined.
	 */
	protected function get_private_key()
	{
		$private_key = $this->get_class_var('private_key');
		if (!empty($private_key)) return $private_key;
		else
		{
			$private_key = constant("RECAPTCHA_PRIVATE_KEY");
			if (!empty($private_key)) return $private_key;
		}
		return false;
	}
}

class recaptcha_no_labelType extends recaptchaType
{
	var $_labeled = false;
}