<?php
/**
 * Hook for the Reason 404 error page script
 *
 * This file exists in the web tree so that the "real" script can exist outside the web tree.
 * It is a simple reason include of lib/[core|local]/errors/404.php
 *
 * @package reason
 * @subpackage errors
 */
	include_once('reason_header.php');
	reason_include('errors/404.php');
?>