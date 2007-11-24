<?php
/**
 * @package loki_1
 * @subpackage hel
 */

/**
 * Include the reason libraries
 */
include_once("reason_header.php");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Alert</title>
<?
echo "<link rel='stylesheet' type='text/css' href='".UNIVERSAL_CSS_PATH."' />";
?>
<script type="text/javascript">

function do_onload()
{
	var editor_obj = opener.<?php echo htmlspecialchars($_REQUEST['editor_obj'],ENT_QUOTES,'UTF-8'); ?>;
	var text = editor_obj.temp_modal_args.text;
	text = text.replace( /\&/g, "&amp;" );
	text = text.replace( /\>/g, "&gt;" );
	text = text.replace( /\</g, "&lt;" );
	text = text.replace( /\"/g, "&quot;" );
	document.getElementById('container').innerHTML = text;
}
</script>
</head>

<body onload="do_onload();">
<pre id="container">

</pre>
</body>
</html>
