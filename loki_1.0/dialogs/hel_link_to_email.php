<?php
/**
 * @package loki_1
 * @subpackage hel
 */
/**
 * Include reason libraries
 */
include_once( 'reason_header.php');

header('Content-Type: application/vnd.mozilla.xul+xml');
echo <<<FINIS
<?xml version="1.0"?>
<?xml-stylesheet href="chrome://global/skin/" type="text/css"?>

FINIS;
?>

<window
	id="xul_window"
	title="Insert Link to Email Address"
	asdfdebug="true"
	asdforient="horizontal"
	xmlns:html="http://www.w3.org/1999/xhtml"
	xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	onload="do_onload()">


<groupbox orient="vertical" flex="1">
<caption label="Link URI"/>

<tabbox id="link_tabs">
	<tabs>
		<?php /*
		<tab label="Form Mailer"/>
			  */ ?>
		<tab label="Mailto"/>
	</tabs>
	<tabpanels>
		<?php /*
		<tabpanel id="formmail_tab">
			<vbox flex="1">
				<label control="custom_link" value="Enter netid or email address:" />
				<textbox id="formmail_email_address" flex="1" style="min-width: 15em; min-height: 2em;"/>
				<description flex="1" style="width:30em;">If the recipient is a Carleton student, staff, or faculty member, please enter only her netid (e.g., "fillmorn"). Otherwise, enter her whole email address (e.g., "natefillmore@yahoo.com").</description>
				<spacer flex="100"/>
			</vbox>
		</tabpanel>
			  */ ?>
		<tabpanel id="mailto_tab">
			<vbox flex="1">
				<label control="custom_link" value="Enter email address:" />
				<textbox id="mailto_email_address" flex="1" style="min-width: 15em; min-height: 2em;"/>
				<description flex="1" style="width:30em;">Please enter the recipient's whole email address.</description><!-- ' -->
			</vbox>
		</tabpanel>
	</tabpanels>
</tabbox>
</groupbox>

<groupbox orient="vertical">
	<caption label="Link Information"/>
    <checkbox id="new_window" label="Open in new browser window"/>
	<hbox>
		<label control="title" value="Link title:"/>
		<textbox id="title" flex="1" style="min-width: 15em;"/>
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
	<button id="unlink_button" label="Remove Link" oncommand="do_unlink();"/>
	<spacer flex="1"/>
</groupbox>

<spacer flex="100"/>

<script type="text/javascript">
<![CDATA[

try {
	var editor_obj = opener.<?php echo htmlspecialchars($_REQUEST['editor_obj'], ENT_QUOTES, 'UTF-8'); ?>;
} catch(e) {}

function do_onload()
{
	try {
		var link_url = editor_obj.temp_modal_args.link_url;
		var use_radiogroup = document.getElementById('use_radiogroup');

		//      N.B.: If you change the following two reg.expressions, be
		//      sure also to change the corresponding ones in hel_link.php
		//
// 	// 1. Handle "form mail" url
// 	var matches = link_url.match( /(?:\?|\&)to=(.*)(?:\&|$)/i );
// 	if ( matches ) {
// 		document.getElementById('formmail_email_address').value = matches[1];
// 		document.getElementById('link_tabs').selectedIndex = 0;
// 	}

		// 2. Handle "mailto" url
		if ( link_url.match(/mailto\:/i) != null ) {
			var address = link_url.replace( /mailto\:/i, '' );
			document.getElementById('mailto_email_address').value = address;
			document.getElementById('link_tabs').selectedIndex = 1;
		}

		// 3. Handle non-empty, apparently non-mailto url
		else if ( link_url != '' ) {
			confirm('It appears that you\'re trying to edit an existing link, but one that\'s not to an email address. If you continue, the link you create with this dialog box will overwrite that link. \n\nAre you sure you want to continue?');
			if ( !confirm )
			do_oncancel();
		}

		// 4. Handle empty url
		else {
		}


		document.getElementById('title').value = editor_obj.temp_modal_args.link_title;
		document.getElementById('new_window').checked = editor_obj.temp_modal_args.link_new_window;
	} catch(e) {}
}
function do_onsubmit()
{
	var selected_url = '';

	// 1. Handle form mail
// 	if ( document.getElementById('link_tabs').selectedIndex == 0 ) {
// 		var formmail_email_address = document.getElementById('formmail_email_address').value;
// 		if ( formmail_email_address.search( /\@carleton.edu/ ) > -1 ||
// 			 formmail_email_address.search( /\@acs.carleton.edu/ ) > -1) {
// 			var answer = confirm('It appears that you\'ve entered the complete email address of a recipient that has a Carleton netid. But the form mailer only requires a Carleton netid; and a netid, in contrast to a full email address, is unlikely to be harvested by a spam-bot. \n\nAre you sure you want to continue?');
// 			if ( answer == false )
// 				return false;
// 		}
// 		selected_url = '/fillmorn/feedback/?to=' + formmail_email_address;
// 	}

	// 2. Handle mailto
// 	else {
		var mailto_email_address = document.getElementById('mailto_email_address').value;
		if ( mailto_email_address.search( /\@/ ) == -1 ) {
			var answer = confirm('It appears that you haven\'t entered a valid email address. But a mailto link will not work properly without a valid email address. (An example of a valid email address is "fillmorn@carleton.edu", in contrast to simply "fillmorn".) \n\nAre you sure you want to continue?');
			if ( answer == false )
				return false;
		}
		selected_url = 'mailto:' + mailto_email_address;
// 	}

	editor_obj.insert_link( selected_url,
							document.getElementById('new_window').checked,
							document.getElementById('title').value,
							'' );
	window.close();
}
function do_oncancel()
{
	window.close();
}
function do_unlink()
{
	editor_obj.insert_link('', false, '', '');
	window.close();
}
]]>
</script>

</window>
