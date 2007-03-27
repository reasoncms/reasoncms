<?php

if ( !empty($_REQUEST['dialog']) )
	$dialog = $_REQUEST['dialog'];
else
	die('Please provide a <em>dialog</em> to be displayed.');

?>
<html>
<head>
<link rel='stylesheet' type='text/css' href='../css/modalStyles.css'>
<title>Loading ...</title>
</head>

<body onload="window.location = '<?php echo $dialog; ?>'">
<table width="100%" height="100%">
<tr>
<td class="txt" align="center" valign="middle">Loading ...</td>
</tr>
</table>
</body>
