<?php
/**
 * This file builds the html output of the generic media popup page
 * Please do not delete this file
 * If Reason can't find the custom template specified in MEDIA_POPUP_TEMPLATE_FILENAME
 * It will try to use this template
 *
 * If you want to customize your Reason instance,
 * Duplicate this file, name it something appropriate,
 * and edit that copy to suit.
 * To make Reason use your custom template,
 * identify the name of your new file if the constant 
 * MEDIA_POPUP_TEMPLATE_FILENAME in the Reason settings
 * 
 * @author Matt Ryan
 * @package reason
 * @subpackage popup_templates
 */
foreach($GLOBALS['_reason_media_popup_data'] as $key=>$val)
{
 	$$key = $val;
}
?><!DOCTYPE html>
<html>
<head>
<title><?php echo strip_tags($title); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="robots" CONTENT="none">
<?php
if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
{
	echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
}
?>
<style type="text/css">
	body {
		background-color:#333;
		color:#fff;
		padding:0;
		margin:0;
	}
	div#banner {
		text-align:right;
		padding:1em;
	}
	div#foot {
		text-align:center;
		padding:1.5em;
	}
	a.closer {
		padding:.5em .5em .33em .5em;
		background-color:#666;
		color:#fff;
		text-decoration:none;
		font-size:85%;
		border:1px solid #000;
	}
	a.closer:hover {
		background-color:#ccc;
		color:#000;
	}
	div#main {
		padding:1.5em;
		background-color:#ccc;
		color:#000;
		text-align:center;
	}
</style>
</head>
<body class="popup" onLoad="self.focus()">
	<div id="banner">
		<a href="javascript:parent.close()" class="closer">Close Window</a>
	</div>
<div id="main">
<?php echo $embed_markup ?>
<h4><?php echo $title ?></h4>
<?php if(!empty($desc)) echo '<div class="description">'.$desc.'</div>'."\n"; ?>
</div>
<div id="foot"><a href="javascript:parent.close()" class="closer">Close Window</a></div>
</body>
</html>