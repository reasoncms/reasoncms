<?php
/**
 * Recaptcha plasmature type settings.
 *
 * @package disco
 * @subpackage plasmature
 */
 
/**
 * This file setups the following:
 *
 * - recaptcha public key
 * - recaptcha private key
 *
 * It provides defaults for the recaptcha plasmature type, and should be setup if you use recaptcha at your domain.
 *
 * The type can also be passed the necessarily public and private keys as arguments.
 */

/**
 * Path to Recaptcha PHP libraries
 */
define('RECAPTCHA_INC', INCLUDE_PATH.'recaptcha/');

/**
 * Recaptcha private key for the domain.
 */
domain_define('RECAPTCHA_PRIVATE_KEY', '' );

/**
 * Recaptcha public key for the domain.
 */
domain_define('RECAPTCHA_PUBLIC_KEY', '' );
?>