<?php
include_once( 'reason_header.php' );

header('Content-Type: application/vnd.mozilla.xul+xml');
echo <<<FINIS
<?xml version="1.0"?>
<?xml-stylesheet href="chrome://global/skin/" type="text/css"?>

FINIS;
?>

<window id="xul_window" title="Insert Link to Asset" asdfdebug="true" asdforient="horizontal" xmlns:html="http://www.w3.org/1999/xhtml" xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul" onload="do_onload()">

<groupbox orient="vertical" flex="1">
<caption label="Link URI"/>

<tabbox id="link_tabs">
	<tabs>
		<tab label="Current Site Assets"/>
	</tabs>
	<tabpanels>
		<tabpanel id="custom_link_tab">
			<menulist id="asset_links" flex="1">
				<menupopup>
					<?php
						$site_id = isset( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : '';
						print_asset_links($site_id);
					?>
				</menupopup>
			</menulist>
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
	<button id="submit_button" label="OK" default="true" oncommand="do_onsubmit();"/>
	<button id="cancel_button" label="Cancel" oncommand="do_oncancel();"/>
	<spacer flex="1"/>
</hbox>

<groupbox orient="horizontal" style="border-left: 0px; border-right: 0px; border-bottom: 0px;">
	<spacer flex="1"/>
	<button id="unlink_button" label="Remove Link" oncommand="do_unlink();"/>
	<spacer flex="1"/>
</groupbox>

<spacer flex="100" />

<script type="text/javascript">
<![CDATA[

try {
	var editor_obj = opener.<?php echo $_REQUEST['editor_obj'] ?>;
} catch(e) {}

function do_onload()
{
	try {
		document.getElementById('title').value = editor_obj.temp_modal_args.link_title;
		document.getElementById('new_window').checked = editor_obj.temp_modal_args.link_new_window;

		var asset_links = document.getElementById('asset_links').firstChild.childNodes;
		for (var i = 0; i < asset_links.length; i++) {
			if ( asset_links.item(i).value == editor_obj.temp_modal_args.link_url ) {
				document.getElementById('asset_links').selectedIndex = i;
			}
		}
	} catch(e) {}
}
function do_onsubmit()
{
	var selected_url;

	selected_url = document.getElementById('asset_links').selectedItem.value;

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





<?php
	function print_asset_links( $site_id )
	{
		if( $site_id != 0 )
		{
			reason_include_once( 'classes/entity_selector.php' );
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'asset' ) );
			$es->set_order( 'name ASC'); //nwhite 6-8-2006
			$assets = $es->run_one();

			$site = new entity( $site_id );
			foreach( $assets AS $asset )
			{
				// changed by DH on 8-7-03 to work with updated asset code.
				// no longer do we have an 'asset_directory' - everything is in the assets directory
				$href = '/'.trim_slashes( $site->get_value( 'base_url' ) ).'/assets/'.trim_slashes( $asset->get_value( 'file_name' ) );

				echo '<menuitem value="'.$href.'" label="'.htmlentities($asset->get_value('name')).'"/>'."\n";

// 					printf('<option value="%s" loki:linkChooser="true" loki:href="%s" ' .
// 						   ' loki:name="%s" loki:filename="%s" loki:category="asset">%s (%s)</option>',
// 						   $href,$href,
// 						   $asset->get_value('name'),
// 						   $asset->get_value('file_name'),
// 						   $asset->get_value('name'),
// 						   $asset->get_value('file_name'));
			}
		}
	}
?>
