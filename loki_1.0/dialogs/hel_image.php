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

<!DOCTYPE window [
<?php include_once("hel_entities.ent"); ?>
]>

<window
	id="xul_window"
	title="Insert Image or Link to Image"
	asdfdebug="true"
	asdforient="horizontal"
	xmlns:hel="http://www.carleton.edu/hel"
	xmlns:html="http://www.w3.org/1999/xhtml"
	xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	onload="do_onload()">


<groupbox orient="vertical" flex="1">
<caption label="Link URI"/>

<tabbox id="link_tabs">
	<tabs>
		<tab label="Insert Image"/>
		<tab label="Insert Link to Images"/>
	</tabs>
	<tabpanels>
		<tabpanel id="images_button_tab" orient="vertical">
			<groupbox orient="vertical">
				<caption label="Click on an image to insert it"/>
				<vbox style="overflow: scroll; height: 250px;" id="image_buttons_container">
					<?php
						$site_id = isset( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : 0;
						settype($site_id, 'integer');
						print_image_buttons($site_id);
					?>
				</vbox>
			</groupbox>
			<hbox>
				<button id="cancel-button" label='Cancel' oncommand="do_oncancel()" />
			</hbox>
		</tabpanel>
		<tabpanel id="images_link_tab" orient="vertical">

			<groupbox orient="vertical">
				<caption label="Select an image to link to"/>
				<menulist id="image_links" flex="0">
					<menupopup>
						<?php
							$site_id = isset( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : 0;
							settype($site_id, 'integer');
							print_image_links($site_id);
						?>
					</menupopup>
				</menulist>
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
				<button id="submit_button" label="OK" default="true" oncommand="do_onsubmit(this);"/>
				<button id="cancel_button" label="Cancel" oncommand="do_oncancel();"/>
				<spacer flex="1"/>
			</hbox>

			<groupbox orient="horizontal" style="border-left: 0px; border-right: 0px; border-bottom: 0px;">
				<spacer flex="1"/>
				<button id="unlink_button" label="Remove Link" oncommand="do_unlink();"/>
				<spacer flex="1"/>
			</groupbox>

			<spacer flex="10"/>

		</tabpanel>
	</tabpanels>
</tabbox>
</groupbox>

<spacer flex="100" />

<script type="text/javascript">
<![CDATA[

try {
	var editor_obj = opener.<?php echo htmlspecialchars($_REQUEST['editor_obj'],ENT_QUOTES,'UTF-8'); ?>;
} catch(e) {}

function do_onload()
{
	document.getElementById('image_buttons_container').firstChild.focus();

	try {
		document.getElementById('title').value = editor_obj.temp_modal_args.link_title;
		document.getElementById('new_window').checked = editor_obj.temp_modal_args.link_new_window;

		var image_links = document.getElementById('image_links').firstChild.childNodes;
		for (var i = 0; i < image_links.length; i++) {
			if ( image_links.item(i).value == editor_obj.temp_modal_args.link_url ) {
				document.getElementById('image_links').selectedIndex = i;
				document.getElementById('link_tabs').selectedIndex = 1;
			}
		}
	} catch(e) {}
}
function do_onsubmit(image_button)
{
	if ( document.getElementById('link_tabs').selectedIndex == 0 )
	{
		editor_obj.insert_image( image_button.getAttribute('hel:src'),
								 image_button.getAttribute('hel:height'),
								 image_button.getAttribute('hel:width'),
								 image_button.getAttribute('hel:alt') );
	}
	else
	{
		var selected_url;
		selected_url = document.getElementById('image_links').selectedItem.value;
		selected_onclick = document.getElementById('image_links').selectedItem.getAttribute('hel:onclick');
		editor_obj.insert_link( selected_url,
								document.getElementById('new_window').checked,
								document.getElementById('title').value,
								selected_onclick );
	}

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
// Adapted from /usr/local/webapps/www-dev/admin/scripts/image_list.php , which is used by Loki
function print_image_buttons( $site_id )
{
	if( $site_id != 0 )
	{
		reason_include_once( 'classes/entity_selector.php' );
		$es = new entity_selector( $site_id );
		$es->add_type( id_of( 'image' ) );
		$es->set_order('entity.last_modified DESC');
		$images = $es->run_one();
		
		foreach ( $images as $image )
		{
			// get filenames for the thumbnail and full size
			$tn_name = $image->id().'_tn.'.$image->get_value('image_type');
			$image_name = $image->id().'.'.$image->get_value('image_type');

			// sometimes images aren't in the filesystem.  hence, the error supressing @
			// this gets image size.  [0] contains width, [1] contains height.
			// OK, the error suppression does not work.  Instead I am wrapping the getimagesize function in a file existence check
			if(file_exists( PHOTOSTOCK.$tn_name ))
			{
				$tn_info = @getimagesize( PHOTOSTOCK.$tn_name );
			}
			else
			{
				$tn_info = false;
			}
			if(file_exists( PHOTOSTOCK.$image_name ))
			{
				$image_info = @getimagesize( PHOTOSTOCK.$image_name );
			}
			else
			{
				$image_info = false;
			}

			$button_string = <<<FINIS
				<button onclick="do_onsubmit(this);" hel:src="%s" hel:width="%s" hel:height="%s" hel:alt="%s"
						style="overflow: hidden;  width: %spx" orient="vertical">
					<image src="%s"/>
					<description style="text-align:center;">
						<html:span style="font-weight:bold">Alternate text:</html:span> %s
					</description>
					<description style="text-align:center">
						<html:span style="font-weight:bold">Width:</html:span> %s
					</description>
					<description style="text-align:center">
						<html:span style="font-weight:bold">Height:</html:span> %s
					</description>
				</button>

FINIS;
			// I suppose there could be a thumbnail with no image.  This code takes care of all possibilities
			if( $tn_info )
			{
				printf( $button_string,
						'https://'.$_SERVER["SERVER_NAME"].WEB_PHOTOSTOCK.$tn_name,
						$tn_info[0],
						$tn_info[1],
						htmlspecialchars($image->get_value('description')),
						$tn_info[0],
						'https://'.$_SERVER["SERVER_NAME"].WEB_PHOTOSTOCK.$tn_name,
						htmlspecialchars($image->get_value('description')),
						$tn_info[0],
						$tn_info[1] );
			}
			if ( $image_info )
			{
				printf( $button_string,
						'https://'.$_SERVER["SERVER_NAME"].WEB_PHOTOSTOCK.$image_name,
						$image_info[0],
						$image_info[1],
						htmlspecialchars($image->get_value('description')),
						$image_info[0],
						'https://'.$_SERVER["SERVER_NAME"].WEB_PHOTOSTOCK.$image_name,
						htmlspecialchars($image->get_value('description')),
						$image_info[0],
						$image_info[1] );
			}
		}
	}
}
function print_image_links( $site_id )
{
	if( $site_id != 0 )
	{
		reason_include_once( 'classes/entity_selector.php' );
		$es = new entity_selector( $site_id );
		$es->add_type( id_of( 'image' ) );
		$es->set_order('entity.last_modified DESC');
		$images = $es->run_one();
		
		$site = new entity( $site_id );
		foreach( $images AS $image )
		{
			$url = WEB_PHOTOSTOCK . $image->id() . '.' . $image->get_value('image_type');
			$name = htmlspecialchars($image->get_value('name'));
			$size = $image->get_value('size');
			if ( !empty($size) )
				$name .= ' (' . $size . 'kb)';
			
			$window_width = $image->get_value('width') < 340 ? 340 : 40 + $image->get_value('width');
			$window_height = 170 + $image->get_value('height');
			$onclick_url = WEB_PHOTOSTOCK . "image.php3?id=" . $image->id();
			$onclick = "window.open('" . $onclick_url . "', 'PopupImage', 'menubar,scrollbars,resizable,width=" . $window_width . ",height=" . $window_height . "'); return false;";

			echo '<menuitem value="'.$url.'" label="'.$name.'" hel:onclick="'.$onclick.'"/>'."\n";
		}
	}
}
?>
