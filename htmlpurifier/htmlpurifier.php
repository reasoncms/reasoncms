<?php
	include_once('paths.php');
	include_once(CARL_UTIL_INC . 'basic/misc.php');

	/**
	 * Loads the HTML_Purifier class appropriate for the version of PHP
	 */
	if (carl_is_php5()) require_once( HTML_PURIFIER_INC . 'htmlpurifier-2.1.2-strict-standalone/HTMLPurifier.standalone.php' );
	else require_once( HTML_PURIFIER_INC . 'htmlpurifier-2.1.2-standalone/HTMLPurifier.standalone.php' );
?>
