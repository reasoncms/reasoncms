<?php
/**
 * Settings for the tyr emailer library
 * @package tyr
 */
	include_once('paths.php');
	define('TYR_REPLY_TO_EMAIL_ADDRESS',WEBMASTER_EMAIL_ADDRESS);
	define('TYR_ADMIN_EMAIL',WEBMASTER_EMAIL_ADDRESS); // this can be changed if a different person from the webmaster should be considered the administrator of Thor
	define('TYR_THANKYOU_TEMPLATE_PATH',TYR_INC.'thankyou_templates/default.php');
?>
