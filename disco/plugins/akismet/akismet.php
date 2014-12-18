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
         * Disco form must be passed in -- callbacks will be attached to various process points in 
         * disco 
         * @param $disco_form The Disco form to check for spam
         */
        public function __construct($disco_form)
        {
                $this->disco_form = $disco_form;
                $this->disco_form->add_callback(array($this, 'detect_spam'), 'run_error_checks');
        }

        /**
         * Passes form content to the Akismet API. If spam is detected, sends an error message back to the user.
         */
        public function detect_spam()
        {
                $form_contents = implode($this->disco_form->get_values(), '\n\n');
                $akismet_api_key = constant("AKISMET_API_KEY");
                if (!empty($akismet_api_key)) {
                        $url = carl_construct_link();
                        // $akismet = new Akismet($url, $akismet_api_key, $is_test=1); // for testing
                        $akismet = new Akismet($url, $akismet_api_key);
                        $akismet->setCommentContent($form_contents);
			// $akismet->setCommentAuthor('viagra-test-123'); // for testing
                        if ($akismet->isCommentSpam()) {
                        	$this->disco_form->set_error(NULL,
                        		'Spam detected in this submission. If this message was made in error, please contact an administrator.',
                        		$element_must_exist = false
				);
                        }
                }
        }
}
?>

