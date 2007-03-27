<?php include_once("reason_header.php"); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Check Spelling</title>
<link rel="stylesheet" type="text/css" href="../css/modalStylesNew.css">
<style type="text/css">
.left_controls, .center_controls, .right_controls
{
	width:100%;
}
input.left_controls
{
	width:95%;
}
.center_controls
{
	margin-top:.5ex;
	margin-bottom:.5ex;
}
.bottom_controls
{
	width:12em;
	margin-right:.5em;
	margin-left:.5em;
}
</style>
</head>

<body onload="do_onload()">

<!-- div style="position:absolute; top:0px; right:0px;" onclick="alert(document.body.innerHTML)">View source</div -->

<form>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
<td>
	<div><label for="misspelled_textbox">Misspelled Word:</label></div>
	<div><input class="left_controls" id="misspelled_textbox" disabled="disabled" value="" /></div>

	<div><label for="replacement_textbox">Replacement:</label></div>
	<div><input class="left_controls" id="replacement_textbox" value="" /></div>

	<div><label for="suggestions_listbox">Suggestions:</label></div>
	<div>
		<select class="left_controls" id="suggestions_listbox" size="5" onchange="try { document.getElementById('replacement_textbox').value = document.getElementById('suggestions_listbox').value; } catch(e) {}">
		</select>
	</div>
</td>
<td>
	<div><button class="center_controls" id="replace_button" default="true" onclick="sc.replace();">Replace</button></div>
	<div><button class="center_controls" id="replace_all_button" onclick="sc.replace_all();" />Replace All</button></div>
	<div><button class="center_controls" id="ignore_button" onclick="sc.ignore();">Ignore</button></div>
	<div><button class="center_controls" id="ignore_all_button" onclick="sc.ignore_all();">Ignore All</button></div>
</td>
	
<td valign="top">
	<div>Document:</div>
	<div><iframe class="right_controls" id="text_iframe" src="blank.html"></iframe></div>
</td>
</tr>
<tr>
<td colspan="3" align="center">
	<button class="bottom_controls" id="done_button" onclick="do_onsubmit();" />Done</button>
	<button class="bottom_controls" id="done_button" onclick="do_oncancel();" />Cancel</button>
</td>
</table>

</body>

<script type="text/javascript">

var sc;
var args = window.dialogArguments;

<?php include_once('spell_check.js'); ?>

function loki_spell_check() {}
loki_spell_check.prototype = new spell_check;
loki_spell_check.prototype._remove_all_items_from_listbox = function(listbox)
{
	try {
		while ( listbox.removeChild(listbox.childNodes.item(0)) != undefined );
	} catch(e) {}
};
loki_spell_check.prototype._append_item_to_listbox = function(listbox, label, value)
{
	var the_item = document.createElement('<option>');
	listbox.add(the_item);
	the_item.setAttribute('value', value);
	the_item.innerText = label;
};
loki_spell_check.prototype._get_value_of_listbox = function(listbox)
{
	return listbox.value;
};
loki_spell_check.prototype._set_class_of_element = function(the_element, the_class_name)
{
	the_element.className = the_class_name;
};
loki_spell_check.prototype._remove_class_of_element = function(the_element)
{
	the_element.className = '';
};

function do_onload()
{
	try {
		document.getElementById('text_iframe').src = "lokiSpellLoading.php";
	} catch(e) {}
}
function do_onframeload(the_suggestion_list)
{
	var the_words = document.frames('text_iframe').document.getElementsByTagName('word');
	sc = new loki_spell_check;
	sc.init(the_suggestion_list, the_words);
}
function do_onsubmit()
{
	var text = document.frames('text_iframe').document.getElementsByTagName('body').item(0).innerHTML;
	text = text.replace(/<spell:word(\W[^>]*)>/gi, '');
	text = text.replace(/<\/spell:word>/gi, '');
	text = text.replace(/<\?xml(\W[^>]*)spell(\W[^>]*)>/gi, ''); // replaces xml namespace info

	var arr = new Array();
	arr['text'] = text;
	window.returnValue = arr;
	window.close();
}
function do_oncancel()
{
	window.close();
}
</script>

</window>
