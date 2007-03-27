<html>
<head>
<title>Table Cell Properties</title>
<link rel='stylesheet' type='text/css' href='../css/modalStyles.css'>
<script language='JavaScript' src='../js/loki.js'></script>
<script language='JavaScript'>
function onLoad() {

	var args = window.dialogArguments;
	if (args["align"] != "") { document.all.oAlign.value = args["align"]; }
	if (args["vAlign"] != "") { document.all.oValign.value = args["vAlign"]; }
	if (args["nowrap"]==true) { document.all.oNowrap.checked=false; }
	else { document.all.oNowrap.checked=true; }

}
function onSubmit() {

	var arr = new Array();
	arr["oAlign"]=document.all.oAlign.value
	arr["oValign"]=document.all.oValign.value
	arr["oColor"]=getRadioValue(document.all.oColor);
	
	if (document.all.oNowrap.checked==true) { arr["oNowrap"]=false; }
	else { arr["oNowrap"]=true; }
	window.returnValue=arr;
	window.close();

}
</script>
</head>
<body bottommargin="0" leftmargin="0" marginheight="0" marginwidth="0" rightmargin="0" topmargin="0" onload="onLoad();">

<FORM id="oForm">
<table border="0" cellpadding="10" cellspacing="0">
<tr align="left" valign="top">
<td>
	
<!-- The fieldSet element groups form controls into a bordered field.  -->
<!-- A legend element is used to specify the title of the field. -->
<FIELDSET>
<LEGEND>Table Cell Properties:</LEGEND>
<table border="0" cellpadding="7" cellspacing="0">
<tr align="left">
<td valign="middle" class="txt">Alignment:</td>
<td valign="middle"><select name="oAlign" id="oAlign" class="slctBox">
					<option value="left" selected>&nbsp;Left</option>
					<option value="center">&nbsp;Center</option>
					<option value="right">&nbsp;Right</option>
					</select></td>
<td rowspan="3"><FIELDSET>
<LEGEND>Background Color:</LEGEND><table border="0" cellpadding="2" cellspacing="5">
	<tr align="center" valign="top">
	<td><img src="../images/nav/ffffcc.gif" alt="ffffcc" width="10" height="10" border="1"></td>
	<td><img src="../images/nav/e9eef8.gif" alt="e9eef8" width="10" height="10" border="1"></td>
	<td><img src="../images/nav/d9e2f4.gif" alt="d9e2f4" width="10" height="10" border="1"></td>
	<td><img src="../images/nav/cccccc.gif" alt="cccccc" width="10" height="10" border="1"></td>
	<td><img src="../images/nav/e8e8e8.gif" alt="e8e8e8" width="10" height="10" border="1"></td>
	</tr>
	<tr align="center" valign="top">
	<td><input type='radio' name='oColor' value='bgFFFFCC'></td>
	<td><input type='radio' name='oColor' value='bgFFFF99'></td>
	<td><input type='radio' name='oColor' value='bg99CCFF'></td>
	<td><input type='radio' name='oColor' value='bgCCCCCC'></td>
	<td><input type='radio' name='oColor' value='bgE8E8E8'></td>
	</tr>
	<tr valign="middle">
	<td align="center"><input type='radio' name='oColor' value='none'></td>
	<td align="left" colspan="4" class="txt">Use No Background Color</td>
	</tr>
	<tr valign="middle">
	<td align="center"><input type='radio' name='oColor' value='current' checked></td>
	<td align="left" colspan="4" class="txt" nowrap>Keep Current Background Color</td>
	</tr>
	</table></FIELDSET></td>
</tr>
<tr align="left" valign="middle">
<td class="txt" nowrap>Vertical Alignment:</td>
<td valign="middle"><select name="oValign" class="slctBox">
					<option value="top" selected>&nbsp;Top</option>
					<option value="middle">&nbsp;Middle</option>
					<option value="bottom">&nbsp;Bottom</option>
					</select></td>
</tr>
<tr align="left" valign="top">
<td class="txt">Wrap Text:</td>
<td class="txt"><input type='checkbox' name='oNowrap' value='nowrap' checked></td>
</tr>
</table>
</FIELDSET>
</td>
</tr>
<tr align="center" valign="middle">
<td><INPUT TYPE="button" class="inputButton" id="okButton" VALUE="      OK      " onclick="onSubmit();">&nbsp;&nbsp;<INPUT TYPE="button" class="inputButton" id="cancelButton" VALUE="   Cancel   " onclick="window.close();"></td>
</tr>
</table>
</FORM>

</body>
</html>
