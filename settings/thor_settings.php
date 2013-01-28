<?php
/**
 * Connection configuration for Thor form engine
 * @package thor
 */

/**
 * Use JavaScript based thor form editor
 * 
 * As of Reason 4.3 the JavaScript editor is in beta and this defaults to FALSE.
 * 
 * In future versions of Reason addition UI and testing work will be done and this setting will default to TRUE. 
 */
define ('USE_JS_THOR', false);

if (!defined('THOR_FORM_DB_CONN'))
{
	define ('THOR_FORM_DB_CONN', 'thor_connection');
}
?>
