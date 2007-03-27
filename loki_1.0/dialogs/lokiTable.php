<html>
<head>
<title>Insert Table</title>
<link rel='stylesheet' type='text/css' href='../css/modalStyles.css'>
<script language='JavaScript' src='../js/loki.js'></script>
<script language='JavaScript'>
<!--
function onLoad() {

	var args = window.dialogArguments;
	if (args["cols"] != null) { document.all.oCol.value = args["cols"]; document.all.oCol.disabled = true; }
	if (args["rows"] != null) { document.all.oRow.value = args["rows"]; document.all.oRow.disabled = true; }
	if (args["summary"] != null) { document.all.oSummary.value = args["summary"]; }
	if (args["border"] != null) { 
		if (args["border"]==0) { document.all.oBorder.checked=false; }
		else { document.all.oBorder.checked=true; }
	}
	
	if (args["className"]!=null) {

		switch (args["className"]) {
		
			case "bgFFFFCC": document.all.oColor[1].checked=true; break;
			case "bgFFFF99": document.all.oColor[2].checked=true; break;
			case "bg99CCFF": document.all.oColor[3].checked=true; break;
			case "bgCCCCCC": document.all.oColor[4].checked=true; break;
			case "bgE8E8E8": document.all.oColor[5].checked=true;  break;
			default: document.all.oColor[0].checked=true;
		}
	
	}
	
	if (args["row1"]!=null) {
		
		if (args["row1"]=="bgFFFFCC" || args["row1"]=="bg99CCFF") { document.all.oColor[6].checked=true; }
		else { document.all.oColor[7].checked=true; }
	
	}

}
function test() {

	if ((document.all.oRow.value=="") ||
		(document.all.oCol.value=="") ||
		(document.all.oSummary.value=="")) {
		
		alert("Please fill in Rows, Columns, & Description.");
	
	
	} else {
	
		var arr = new Array();
		arr["oRow"]=document.all.oRow.value;
		arr["oCol"]=document.all.oCol.value;
		//arr["oBorder"]=getRadioValue(document.all.oBorder);
		
		if (document.all.oBorder.checked==true) { arr["oBorder"]=1; }
		else { arr["oBorder"]=0; }
		
		document.all.oSummary.value=cleanApostrophes(document.all.oSummary.value);
		arr["oSummary"]=document.all.oSummary.value;
		
		if (getRadioValue(document.all.oColor)!= "none") {
		
			arr["oColor"]=getRadioValue(document.all.oColor);
			
			if (getRadioValue(document.all.oColor)=="altrows_1") {
				
				arr["oAltrows"]=true;
				arr["oRow1"]="bg99CCFF";
				arr["oRow2"]="bgFFFF99";
			
			} else if (getRadioValue(document.all.oColor)=="altrows_2") {
			
				arr["oAltrows"]=true;
				arr["oRow1"]="bgCCCCCC";
				arr["oRow2"]="bgE8E8E8";
			
			}
		}
		window.returnValue=arr;
		window.close();
	
	}
	
}
// -->
</script>
</head>
<body bottommargin="0" leftmargin="0" marginheight="0" marginwidth="0" rightmargin="0" topmargin="0" onload="onLoad();">

<FORM id="oForm">
<table border="0" cellpadding="10" cellspacing="0">
<tr align="center" valign="top">
<td class="txt">
	
<!-- The fieldSet element groups form controls into a bordered field.  -->
<!-- A legend element is used to specify the title of the field. -->
<FIELDSET>
<LEGEND>Table Properties:</LEGEND>
<table border="0" cellpadding="7" cellspacing="0">
<tr align="left">
<td valign="middle" class="txt">Rows:</td>
<td valign="middle"><INPUT class="txt" TYPE="text" ID="oRow" value="2" size="3" maxlength="3" ONKEYPRESS="event.returnValue=isDigit();"></td>
<td rowspan="3">&nbsp;</td>
<td valign="top" class="txt" rowspan="3">Description:<br /><br /><textarea cols='25' rows='5' id='oSummary' name='oSummary'></textarea></td>
</tr>
<tr align="left" valign="middle">
<td class="txt">Columns:</td>
<td><INPUT class="txt" TYPE="text" ID="oCol" value="2" size="3" maxlength="3" ONKEYPRESS="event.returnValue=isDigit();"></td>
</tr>
<tr align="left" valign="top">
<td class="txt" nowrap>Show Border:</td>
<td class="txt"><input type='checkbox' name='oBorder' value='1' checked><!-- <input type='radio' name='oBorder' value='1' checked>&nbsp;Yes<br /><input type='radio' name='oBorder' value='0'>&nbsp;No --></td>
</tr>
</table>
</FIELDSET>
<br />
<FIELDSET>
<LEGEND>Table Color Properties:</LEGEND>
<table border="0" cellpadding="7" cellspacing="0">
<tr align="left" valign="middle">
<td class="txt" colspan="2"><input type='radio' name='oColor' value='none' checked>&nbsp;Use No Background Color</td>
</tr>
<tr align="left" valign="top">
<td class="txt"><FIELDSET>
	<LEGEND>Use Background Color:</LEGEND>
	<table border="0" cellpadding="2" cellspacing="5">
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
	</table>
	</FIELDSET></td>
<td class="txt"><FIELDSET>
	<LEGEND>Use Alternating Row Colors:</LEGEND>
	<table border="0" cellpadding="2" cellspacing="5">
	<tr align="center" valign="top">
	<td><img src="../images/nav/d9e2f4.gif" alt="d9e2f4" width="10" height="10" border="1" vspace="1"><br /><img src="../images/nav/e9eef8.gif" alt="e9eef8" width="10" height="10" border="1" vspace="1"></td>
	<td><img src="../images/nav/cccccc.gif" alt="cccccc" width="10" height="10" border="1" vspace="1"><br /><img src="../images/nav/e8e8e8.gif" alt="e8e8e8" width="10" height="10" border="1" vspace="1"></td>
	</tr>
	<tr align="center" valign="top">
	<td><input type='radio' name='oColor' value='altrows_1'></td>
	<td><input type='radio' name='oColor' value='altrows_2'></td>
	</tr>
	</table></td>
</tr>
</table>
</FIELDSET>
</td>
</tr>
<tr align="center" valign="middle">
<td><INPUT TYPE="button" class="inputButton" id="okButton" VALUE="      OK      " onclick="test();">&nbsp;&nbsp;<INPUT TYPE="button" class="inputButton" id="cancelButton" VALUE="   Cancel   " onclick="window.close();"></td>
</tr>
</table>
<input type='hidden' name='color1' value='false'>
<input type='hidden' name='color2' value='false'>
</FORM>

</body>
</html>
