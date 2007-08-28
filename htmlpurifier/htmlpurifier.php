<?php
	include_once('paths.php');
	include_once(CARL_UTIL_INC . 'basic/misc.php');

	/**
	 * Loads the HTML_Purifier class appropriate for the version of PHP
	 */
	if (carl_is_php5()) require_once( INCLUDE_PATH . 'htmlpurifier/htmlpurifier-2.1.1-strict/library/HTMLPurifier.auto.php' );
	else require_once( INCLUDE_PATH . 'htmlpurifier/htmlpurifier-2.1.1/library/HTMLPurifier.auto.php' );
?>
