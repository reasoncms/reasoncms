<?php
	include_once('paths.php');
	include_once(CARL_UTIL_INC . 'basic/misc.php');

	/**
	 * Loads the XML Parser class appropriate for the version of PHP
	 */
	if (carl_is_php5()) require_once( XML_PARSER_INC . 'xmlparser5.php' );
	else require_once( XML_PARSER_INC . 'xmlparser4.php' );
?>
