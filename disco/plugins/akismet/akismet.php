<?php

/**
 * @package disco
 * @author Liam Everett
 *
 */
/**
 * Include Disco, Akismet
 */
include_once( 'paths.php');
include_once( DISCO_INC . 'disco.php' );
include_once( CARL_UTIL_INC . 'basic/url_funcs.php' );
include_once( SETTINGS_INC . 'akismet_settings.php' );
include_once( AKISMET_INC . 'Akismet.class.php' );

/**
 * Class to support filtering form submissions using the Akistmet API.
 */
class AkismetFilter
{

	/**
	 * The Disco form containing fields to check
	 * @var object
	 */
	private $disco_form;

	/**
	 * Map of Akismet class method names to Disco form field names
	 * If the array is used it is parsed similar to:
	 *    $akismet->KEY($this->disco_form->get_value(VALUE)) 
	 * @var array 
	 */
	public $akismet_method_to_field;

	/**
	 * Disco form must be passed in -- callbacks will be attached to various process points in 
	 * disco 
	 * @param Disco $disco_form The Disco form to check for spam
	 * @param array $akismet_method_to_field optional, provide keys which are Akismet
	 *     method names and values which are Disco form field names to manually
	 *     classify data as it's passed to Akismet
	 */
	public function __construct($disco_form, $akismet_method_to_field = array())
	{
		$this->disco_form = $disco_form;
		$this->akismet_method_to_field = $akismet_method_to_field;
		$this->disco_form->add_callback(array($this, 'detect_spam'), 'run_error_checks');
	}

	public function extract_form_contents()
	{
		$form_contents = '';
		foreach ($this->disco_form->get_values() as $k => $v) {
			if (is_array($v)) {
				$form_contents .= implode($v, ' ') . ' ';
			} else {
				// don't include hidden elements which contain objects as values
				if (!((get_class($this->disco_form->get_element($k)) == 'hiddenType') && (substr($v, 0, 3) == 'id_'))) {
					$form_contents .= $v . ' ';
				}
			}
		}

		return $form_contents;
	}

	/**
	 * Passes form content to the Akismet API. If spam is detected, sends an error message back to the user.
	 */
	public function detect_spam()
	{
		$akismet_api_key = constant("AKISMET_API_KEY");
		if (!empty($akismet_api_key)) {
			$url = carl_construct_link();
			$akismet = new Akismet($url, $akismet_api_key);
			//$akismet->setCommentAuthor('viagra-test-123'); // for testing

			if (empty($this->akismet_method_to_field)) {
				// No user-provided values to provide to Akismet.
				// Use a combination of all form values
				$form_contents = $this->extract_form_contents();
				$akismet->setCommentContent($form_contents);
			} else {
				// Classify information by type (name, email, ip address, comment, etc)
				foreach ($this->akismet_method_to_field as $method_name => $value_name) {
					$value = $this->disco_form->get_value($value_name);
					call_user_func(array($akismet, $method_name), $value);
				}
			}

			$userip = get_user_ip_address();
			$akismet->setUserIP($userip);

			if ($akismet->isCommentSpam()) {
				$error_msg = 'Spam detected in this submission. If this message was made in error, please contact an administrator.';
				$this->disco_form->set_error(NULL, $error_msg, false);
			}
		}
	}

}
