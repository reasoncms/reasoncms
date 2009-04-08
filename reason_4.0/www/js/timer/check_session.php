<?php
/**
 * A script that reports (in XML form)  in whether the current user is logged in or not
 * @package reason
 * @subpackage js
 */
include ('reason_header.php');
reason_include_once('function_libraries/user_functions.php');
header('Content-Type: text/xml');
header("Cache-Control", "no-cache");
if (get_authentication_from_session())
{
	$xml_str = 'true';
}
else
{
	$xml_str = 'false';
}
echo '<' . '?' . 'xml version="1.0"?>';
echo '<root>';
echo '<status>';
echo $xml_str;
echo '</status>';
echo '</root>';
?>
