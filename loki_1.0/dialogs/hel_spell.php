<?php
/**
 * @package loki_1
 * @subpackage hel
 */
/**
 * Include reason libraries
 */
include_once("reason_header.php");

header('Content-Type: application/vnd.mozilla.xul+xml');
echo <<<FINIS
<?xml version="1.0"?>
<?xml-stylesheet href="chrome://global/skin/" type="text/css"?>

FINIS;
?>

<window
	id="xul_window"
	title="Check Spelling"
	asdfdebug="true"
	asdforient="horizontal"
	xmlns:html="http://www.w3.org/1999/xhtml"
	xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	onload="do_onload()">


<vbox flex="1">
	<hbox>
		<spacer flex="1" />
		<vbox>
			<spacer flex="1" />

			<label for="misspelled_textbox">Misspelled Word:</label>
			<textbox id="misspelled_textbox" disabled="disabled" value="" />

			<label for="replacement_textbox">Replacement:</label>
			<textbox id="replacement_textbox" flex="1" style="min-width: 15em; min-height: 2em;" value="" />

			<label for="suggestions_listbox">Suggestions:</label>
			<listbox id="suggestions_listbox" rows="5" onselect="try { document.getElementById('replacement_textbox').value = document.getElementById('suggestions_listbox').getSelectedItem(0).value; } catch(e) {}">
			</listbox>

			<spacer flex="1" />
		</vbox>
		<vbox>
			<spacer flex="1" />
			<button id="replace_button" label="Replace" oncommand="sc.replace();" />
			<button id="replace_all_button" label="Replace All" oncommand="sc.replace_all();" />
			<button id="ignore_button" label="Ignore" oncommand="sc.ignore();" />
			<button id="ignore_all_button" label="Ignore All" oncommand="sc.ignore_all();" />
			<spacer flex="1" />
		</vbox>
		<vbox>
			<spacer flex="1" />
			<label>Document:</label>
			<html:iframe id="text_iframe" src="blank.html"></html:iframe>
			<spacer flex="1" />
		</vbox>
		<spacer flex="1" />
	</hbox>
	<hbox style="margin-top:2ex;">
		<spacer flex="2" />
		<button flex="1" id="done_button" label="Done" default="true" oncommand="do_onsubmit();" />
		<button flex="1" id="done_button" label="Cancel" oncommand="do_oncancel();" />
		<spacer flex="2" />
	</hbox>
</vbox>

	


<script type="text/javascript">
<![CDATA[

try {
	var editor_obj = opener.<?php echo htmlspecialchars($_REQUEST['editor_obj'],ENT_QUOTES,'UTF-8'); ?>;
	var sc;
} catch(e) {}

<?php include_once('spell_check.js'); ?>

function hel_spell_check() {}
hel_spell_check.prototype = new spell_check;
hel_spell_check.prototype._remove_all_items_from_listbox = function(listbox)
{
	while ( listbox.removeItemAt(0) != undefined );
};
hel_spell_check.prototype._append_item_to_listbox = function(listbox, label, value)
{
	listbox.appendItem(label, value);
};
hel_spell_check.prototype._get_value_of_listbox = function(listbox)
{
	return listbox.getSelectedItem(0).value;
};
hel_spell_check.prototype._set_class_of_element = function(the_element, the_class_name)
{
	the_element.setAttribute('class', the_class_name);
};
hel_spell_check.prototype._remove_class_of_element = function(the_element)
{
	the_element.removeAttribute('class');
};


function do_onload()
{
	try {
		document.getElementById('text_iframe').src = "hel_spell_loading.php";
	} catch(e) {}
}
function do_onframeload(the_suggestion_list)
{
	sc = new hel_spell_check;
	sc.init(the_suggestion_list,
			document.getElementById('text_iframe').contentDocument.getElementsByTagName('spell:word'));
	//editor_obj.alert(document.getElementById('text_iframe').contentDocument.getElementsByTagName('body').item(0).innerHTML);
}
function do_onsubmit()
{
	var text = document.getElementById('text_iframe').contentDocument.getElementsByTagName('body').item(0).innerHTML;
	text = text.replace(/<spell:word(\W[^>]*)>/gi, '');
	text = text.replace(/<\/spell:word>/gi, '');
	editor_obj.copy_from_spell_into_hel(text);
	window.close();
}
function do_oncancel()
{
	window.close();
}
function do_unlink()
{
	editor_obj.insert_named_anchor('');
	window.close();
}
]]>
</script>

</window>
