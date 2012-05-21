<?php
/**
 * Recaptcha type library.
 *
 * @package disco
 * @subpackage plasmature
 */
require_once PLASMATURE_TYPES_INC."default.php";
include_once(SETTINGS_INC . 'recaptcha_settings.php' );
include_once(RECAPTCHA_INC . 'recaptchalib.php');

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

	/**
	 * If recaptcha public and private keys are not available - lets trigger an error.
	 */
	function additional_init_actions($args = array())
	{
		 if (!$this->keys_available())
		 {
		 	trigger_error('recaptcha plasmature type element is not setup correctly - make sure public and private keys are provided as an argument or defined as defaults in recaptcha_settings.php');
		 }
	}
	
	function get_display()
	{
		if ($this->keys_available())
		{
			return recaptcha_get_html($this->get_public_key());
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
			$request = $this->get_request();
			if (!empty($request["recaptcha_challenge_field"]) && !empty($request["recaptcha_response_field"]))
			{
				$resp = recaptcha_check_answer(
					$this->get_private_key(),
					$_SERVER["REMOTE_ADDR"],
					$request["recaptcha_challenge_field"],
					$request["recaptcha_response_field"]
				);
				if ($resp->is_valid)
				{
					return 1;
				}
				else
				{
					if ($resp->error == 'invalid-site-private-key')
					{
						trigger_error ("The reCAPTCHA challenge was ignored - reCAPTCHA reports that your site private key is invalid.");
					}
					elseif ($resp->error == 'invalid-request-cookie')
					{
						trigger_error ("Recaptcha challenge parameter was not correctly passed - make sure reCAPTCHA is properly configured.");
						$this->set_error("The reCAPTCHA could not be verified. Please try again again later.");
					}
					elseif ($resp->error == 'incorrect-captcha-sol')
					{
						$this->set_error("The reCAPTCHA wasn't entered correctly. Please try again.");
					}
				}
			}
		 	else
		 	{
		 		$this->set_error("You must respond to the reCAPTCHA challenge question to complete the form");
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