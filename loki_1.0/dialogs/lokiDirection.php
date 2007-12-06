<?php
/**
 * Direction Options
 * @package loki_1
 * @subpackage loki
 */

/**
 * Start page
 */
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>Direction Options</title>
<link rel="stylesheet" type="text/css" href="../css/modalStyles.css">
<script type="text/javascript">
function do_onload()
{
	var args = window.dialogArguments;

	document.getElementById('direction_'+args['direction']).setAttribute('checked','checked');
}
function do_onsubmit()
{
	var arr = new Array();

	arr['direction'] = (document.getElementById('direction_ltr').checked == true) ? 'ltr' : 'rtl';

	window.returnValue = arr;
	window.close();
}
</script>
</head>

<body onload="do_onload()">

<!-- div style="position:absolute; top:0px; right:0px;" onclick="alert(document.body.innerHTML)">View source</div -->

<form id="the_form">

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
<td>
	<!-- The fieldSet element groups form controls into a bordered field.  -->
	<!-- A legend element is used to specify the title of the field. -->
	<fieldset>
	<legend>Direction</legend>

	<div class="txt">
 		<input type="radio" value="ltr" name="direction" id="direction_ltr" />
 		<label for="direction_ltr">Left to right</label>
 	</div>

	<div class="txt">
		<input type="radio" value="rtl" name="direction" id="direction_rtl" />
		<label for="direction_rtl">Right to left</label>
	</div>

	</fieldset>
</td>
</tr>
<tr>
<td>
	<div style="text-align:center">
		<input type="button"
		class="inputButton"
		onclick="do_onsubmit();"
		value="OK"
		/>

		<input type="button"
		class="inputButton"
		onclick="window.close();"
		value="Cancel"
		/>
	</div>
</td>
</tr>
</table>

</form>


</body>
</html>
