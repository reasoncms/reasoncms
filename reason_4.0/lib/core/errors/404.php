<?php
/**
 * A generic 404 page that can be used in place of the default apache 404
 *
 * This 404 page includes the 404action script, which takes care of redirecting requests to Reason pages that have moved.
 *
 * @package reason
 * @subpackage errors
 */
 
 /**
  * Include reason header and reason's moved page handler
  */
	include_once( 'reason_header.php' );
   	reason_include( 'scripts/urls/404action.php' );
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title><?php echo FULL_ORGANIZATION_NAME; ?>: File Not Found</title>
<?php
	if(defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
	{
		echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
	}
	if(defined('REASON_DEFAULT_FAVICON_PATH') && REASON_DEFAULT_FAVICON_PATH )
	{
		echo '<link rel="shortcut icon" href="'.REASON_DEFAULT_FAVICON_PATH.'" type="image/x-icon"/>';
	}
?>
<style type="text/css">
<!--
.maintained {
	text-align: right;
}
.content {
	padding: 20px;
}
-->
</style>
</head>
<body>
<div class="content">
<?php
	echo '<h1><a href="'.ORGANIZATION_HOME_PAGE_URI.'">'.FULL_ORGANIZATION_NAME.'</a></h1>'."\n";
?>
<h3>Oops.</h3>
  <p>We're sorry. The link you clicked or the URL you typed in didn't work for 
    some reason. Techies call this a 404 error. Here are some of the reasons why 
    that might have happened:</p>
	<ul>
    <li>We might have a bad link on one of our pages, which sent you to the wrong 
      place when you clicked it.</li>
	<li>You might have typed in the web address incorrectly.</li>
	<li>Someone might have sent you an email that had an incorrect web address.</li>
	<li>Our server could be having problems.</li>
	</ul>
<p>If you think that there's a bad link on a page, <?php if (!empty($_SERVER['HTTP_REFERER'])) echo "<a href='" . $_SERVER['HTTP_REFERER'] . "'>"; ?> go back to the page that 
    had the link<?php if (!empty($_SERVER['HTTP_REFERER'])) echo "</a>"; ?>, and there should be contact information for the person who maintains 
    it on the page. Please contact them (email is always good) and let them know about the problem!</p>
<p>If you can't figure out who to contact, you can send an email to <a href="mailto:<?php
	echo WEBMASTER_EMAIL_ADDRESS;
  	if (!empty($_SERVER['HTTP_REFERER']))
	{ ?>?subject=Bad Link&body=Hello.%0D%0DI've%20found%20a%20bad%20link%20at%20<?php echo (urlencode($_SERVER['HTTP_REFERER'])); ?>%20%0D%20%0DThe link tried to take me to <?php echo (urlencode($_SERVER['SCRIPT_URI'])); ?>, which does not exist!%0D%20%0DCould%20you%20fix%20it,%20please?%0D%0DThank%20You!<?php 
	} ?>"><?php echo WEBMASTER_EMAIL_ADDRESS; ?></a>. 
    Make sure to let us know where the bad link was located. Thanks!</p>
    <p class="maintained">Maintained by <a href="mailto:<?php echo WEBMASTER_EMAIL_ADDRESS; ?>"><?php echo WEBMASTER_NAME; ?></a></p>
</div>
</body>
</html>