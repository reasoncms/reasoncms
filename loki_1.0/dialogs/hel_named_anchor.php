<?php
/**
 * @package loki_1
 * @subpackage hel
 */
/**
 * Include reason libraries
 */
include_once( 'reason_header.php' );

header('Content-Type: application/vnd.mozilla.xul+xml');
echo <<<FINIS
<?xml version="1.0"?>
<?xml-stylesheet href="chrome://global/skin/" type="text/css"?>

FINIS;
?>

<window
	id="xul_window"
	title="Insert Named Anchor"
	asdfdebug="true"
	asdforient="horizontal"
	xmlns:html="http://www.w3.org/1999/xhtml"
	xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	onload="do_onload()">


<groupbox orient="vertical" flex="1">
	<caption label="Name of Anchor"/>
	<textbox id="anchor_name" flex="0" style="min-width: 15em; min-height: 2em;"/>
	<hbox>
		<spacer flex="1"/>
		<description style="width:30em;">The name should begin with a Roman letter, and be followed by any number of digits, hyphens, underscores, colons, periods, and Roman letters. The name should include no other characters.</description>
		<spacer flex="1"/>
	</hbox>
</groupbox>

<hbox>
	<spacer flex="1"/>
	<button id="submit_button" label="Insert" default="true" oncommand="do_onsubmit();"/>
	<button id="cancel_button" label="Cancel" oncommand="do_oncancel();"/>
	<spacer flex="1"/>
</hbox>

<groupbox orient="horizontal" style="border-left: 0px; border-right: 0px; border-bottom: 0px;">
	<spacer flex="1"/>
	<button id="unlink_button" label="Remove Anchor" oncommand="do_unlink();"/>
	<spacer flex="1"/>
</groupbox>

<spacer flex="100" />

<script type="text/javascript">
<![CDATA[

try {
	var editor_obj = opener.<?php echo htmlspecialchars($_REQUEST['editor_obj'], ENT_QUOTES, 'UTF-8'); ?>;
} catch(e) {}

function do_onload()
{
	try {
		document.getElementById('anchor_name').value = editor_obj.temp_modal_args.anchor_name;
	} catch(e) {}
}
function do_onsubmit()
{
	editor_obj.insert_named_anchor( document.getElementById('anchor_name').value );
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
