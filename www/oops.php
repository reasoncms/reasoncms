<?php
/**
 * Default oops/oshi page
 * 
 * The error handler headers the browser to this page if/when there is a fatal error 
 * that can be caught by PHP (e.g. not compliation errors, but other fatal-level 
 * errors reported to PHP's error handling system)
 *
 * You can edit this to reflect your design/branding. You probably want the page to
 * be self-contained, and not rely on any other scripts -- fatal errors are likely to occur 
 * if you have misconfiguration problems, and you don't want this page to fail for the same reasons!
 *
 * @package carl_util
 * @subpackage error_handler
 */
 
/**
 * Begin script
 */
?><!DOCTYPE html>
<html>
	
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Server Error</title>
<style type="text/css">
<!--
.content {
	padding: 20px;
}
-->
</style>
</head>
<body>
<div class="content">
  <h3>Oops.</h3>
  <p>We're sorry. A server error has occurred.  The administrators have been notified and will fix the problem soon.</p>
</div>

</body>
</html>
