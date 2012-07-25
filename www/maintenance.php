<?php
/**
 * Default maintenance page
 * 
 * This page is displayed when maintenance mode is on in the error handler settings.
 *
 * You can then edit this page to reflect your design/branding. You probably want the page to
 * be self-contained, and not rely on any other scripts -- maintenance mode is often
 * used when databases are unavailable, or an update is underway, and you don't want the
 * maintenance page to fail!
 *
 * @package carl_util
 * @subpackage error_handler
 */
 
 /**
  * Begin script
  */
 ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Under Maintenance</title>
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
  <h3>Scheduled Maintenance</h3>
  <p>This site is temporarily down while we perform maintenance.  We apologize for any disruption.</p>
  <?php
	if( !empty( $_REQUEST['estimate'] ) )
	{
		$seconds = $_REQUEST['estimate'] - time();
		$minutes = round($seconds / 60);
		if( $seconds > 0 )
		{
			//echo '<p>We estimate maintenance should be complete in about '.($minutes).' minutes.</p>';
			echo '<p>We estimate maintenance should be completed by '.date( 'g:i a T', $_REQUEST['estimate'] ).'.
			Thanks for your patience.</p>';
		}
		else
		{
			echo '<p>Thanks for your patience.</p>';
		}
	}
  ?>
</div>

</body>
</html>
