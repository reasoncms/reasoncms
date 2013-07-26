<?php
/**
 * Declare HTMLPurifier configurations based on a string key.
 *
 * HTMLPurifier configs should be located in the ./config folder, and named according to the keys
 * in $GLOBALS['_reason_htmlpurifier_config'].
 *
 * To setup a default configuration for your instance of Reason CMS, perform the following steps:
 *
 * 1. create a class at ../../../local/config/htmlpurifier/configs/your_default.php that extends ../../../core/config/htmlpurifier/configs/default.php
 * 2. overload class defaults, register the class name, and/or add your own rules using the custom_config method to your specificiations ... 	
 * 3. copy ../../../core/config/htmlpurifier/setup_local.php to ../../../local/config/htmlpurifier/setup_local.php
 * 4. add default => your_default to $GLOBALS['_reason_htmlpurifier_config_local']
 * 
 * While you could skip the above and just customize ./configs/default.php, or copy the file to your local area and configure it, the above
 * process makes sure that you stay current with possible updates to ./configs/default.php.
 *  
 * @package reason 
 * @subpackage config
 */
 
/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(HTML_PURIFIER_INC . 'htmlpurifier.php');
reason_include_once('config/htmlpurifier/configs/abstract.php');
 
$GLOBALS['_reason_htmlpurifier_config'] = array(
	'default' => 'default',
);

if (reason_file_exists('config/htmlpurifier/setup_local.php'))
{
	reason_include_once('config/htmlpurifier/setup_local.php');
	if(!empty($GLOBALS['_reason_htmlpurifier_config_local']))
	{
		$GLOBALS['_reason_htmlpurifier_config'] = array_merge($GLOBALS['_reason_htmlpurifier_config'],$GLOBALS['_reason_htmlpurifier_config_local']);
	}
}