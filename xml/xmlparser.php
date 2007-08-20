<?php
	include_once('reason_header.php');

	/**
	 * Loads the XML Parser class appropriate for the version of PHP
	 */
	if (carl_is_php5()) require_once( INCLUDE_PATH . 'xml/xmlparser5.php' );
	else require_once( INCLUDE_PATH . 'xml/xmlparser4.php' );
?>
