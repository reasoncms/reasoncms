<?php
/**
 * This file should be used to bring in the Reason libraries.
 * It must be included in any script execution for Reason to work.
 *
 * @package reason
 */
	if (ob_get_level() == 0) ob_start();
	include_once('paths.php');
	include_once( REASON_INC.'header.php' );
?>
