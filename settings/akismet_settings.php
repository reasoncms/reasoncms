<?php
/**
 * This file setups the following:
 *
 * - akismet API key
 *
 * If you provide an API key, Reason's publications module will consult akismet to try to determine 
 * whether comments submitted by anonymous individuals are spam and should be rejected.
 *
 * As of May 29, 2012, keys are free for personal blogs, and enterprise licenses cost $50 per month.
 *
 * http://akismet.com/
 *
 * @todo option to check authenticated comments?
 */

/**
 * Path to our akismet php library.
 */
define('AKISMET_INC', INCLUDE_PATH.'akismet/');

/**
 * Akismet API Key.
 */
domain_define('AKISMET_API_KEY', 'b1c0ec71e3bb' );
?>