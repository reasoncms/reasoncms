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
	title="Insert Link"
	asdfdebug="true"
	asdforient="horizontal"
	xmlns:html="http://www.w3.org/1999/xhtml"
	xmlns="http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul"
	onload="do_onload()">


<groupbox orient="vertical" flex="1">
<caption label="Link URI"/>

<tabbox id="link_tabs">
	<tabs>
		<tab label="Custom"/>
		<tab label="Current Site Pages"/>
	</tabs>
	<tabpanels>
		<tabpanel id="custom_link_tab">
			<vbox flex="1">
				<label control="custom_link" value="Enter URI:" />
				<textbox id="custom_link" flex="1" style="min-width: 15em;"/>
				<spacer flex="100"/>
			</vbox>
		</tabpanel>
		<tabpanel id="page_links_tab">
			<vbox flex="1">
				<label control="page_links" value="Select page:"/>
				<menulist id="page_links" flex="1" oncommand="refresh_named_anchors(this.selectedItem.value, '');">
					<menupopup>
						<menuitem value="" label="(current page)"/>
						<?php
							$site_id = isset( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : '';
							settype($site_id,'integer');
							print_page_links($site_id);
						?>
					</menupopup>
				</menulist>
				<label control="page_named_anchors" value="Select named anchor (optional):"/>
				<menulist id="page_named_anchors" flex="1">
					<menupopup>
						<menuitem value="" label="(none selected)"/>
					</menupopup>
				</menulist>
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

<html:iframe id="iframe_for_getting_named_anchors" style="height:1px; width:1px; display:none;" src="blank.html"></html:iframe>

<script type="text/javascript">
<![CDATA[

try {
	var editor_obj = opener.<?php echo htmlspecialchars($_REQUEST['editor_obj'],ENT_QUOTES,'UTF-8'); ?>;
} catch(e) {}

function do_onload()
{
	try {
		document.getElementById('custom_link').value = editor_obj.temp_modal_args.link_url;
		if (document.getElementById('custom_link').value == '')
		document.getElementById('custom_link').value = 'http://';

		document.getElementById('title').value = editor_obj.temp_modal_args.link_title;
		document.getElementById('new_window').checked = editor_obj.temp_modal_args.link_new_window;

		var selected_link         = editor_obj.temp_modal_args.link_url.replace( /(.*)\#.*/, '$1' );
		var selected_named_anchor = editor_obj.temp_modal_args.link_url.replace( /.*\#(.*)/, '$1' );

		var page_links = document.getElementById('page_links').firstChild.childNodes;
		for (var i = 0; i < page_links.length; i++) {
			if ( page_links.item(i).value == selected_link ) {
				document.getElementById('page_links').selectedIndex = i;
				document.getElementById('link_tabs').selectedIndex = 1;
			}
		}

		refresh_named_anchors(document.getElementById('page_links').selectedItem.value, selected_named_anchor);

	}
	catch (e) {}
}
function do_onsubmit()
{
	var selected_url;

	if ( document.getElementById('link_tabs').selectedIndex == 0 ) {
		var custom_link = document.getElementById('custom_link').value;

		if ( custom_link.search( /\@/ ) > -1 &&
			 custom_link.search( /mailto:/i ) == -1 &&
			 custom_link.search( /(?:\?|\&)to=(.*)(?:\&|$)/i ) == -1 ) {
			var answer = confirm('It appears that you\'ve entered an email address, but haven\'t used the "mailto" protocol or a form mailer. (Note that there\'s a distinct dialog box, "Insert Link to Email Address", which you may use to create such links.) \n\nAre you sure you want to continue?');
			if ( answer == false )
				return false;
		}

		selected_url = custom_link;
	}
	else {
		//if ( document.getElementById('page_named_anchors').selectedItem.value == '' ) { // this actually returns "undefined" which is bad
		if ( document.getElementById('page_named_anchors').selectedIndex == 0 ) {
			selected_url = document.getElementById('page_links').selectedItem.value;
		}
		else {
			selected_url =
				document.getElementById('page_links').selectedItem.value +
				document.getElementById('page_named_anchors').selectedItem.value;
		}
	}

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
function refresh_named_anchors(uri, selected_named_anchor)
{
	var named_anchors_menu = document.getElementById('page_named_anchors');
	named_anchors_menu.removeAllItems();
	named_anchors_menu.appendItem('(loading)','');

	if (document.getElementById('page_links').selectedItem.value == '') {  // i.e., if it's the page being edited
		refresh_named_anchors_contd(editor_obj.temp_modal_args.named_anchors, selected_named_anchor);
	}
	else {
		var iframe = document.getElementById('iframe_for_getting_named_anchors');
		iframe.src = ( 'hel_link_get_anchors.php?uri=http://<?php echo $_SERVER["SERVER_NAME"]; ?>' + uri + '&' +
					   'selected_named_anchor=' + selected_named_anchor );
	}
}
function refresh_named_anchors_contd(all_anchors, selected_named_anchor) // called either from refresh_named_anchors(), or from the iframe's document
{
	var named_anchors_menu = document.getElementById('page_named_anchors');
	named_anchors_menu.removeAllItems();
	named_anchors_menu.appendItem('(none selected)','');
	named_anchors_menu.selectedIndex = 0;

	var named_anchors = Array();

	var j = 0;
	for (var i = 0; i < all_anchors.length; i++) {
		if ( all_anchors[i].name != '' &&
			 all_anchors[i].name != 'top' &&
			 all_anchors[i].name != 'navBottom' &&
			 all_anchors[i].name != 'content' ) { 

			named_anchors[j] = all_anchors[i];
			j++;
		}
	}

	for (var k = 0; k < named_anchors.length; k++) {
		named_anchors_menu.appendItem(named_anchors[k].name, '#' + named_anchors[k].name);

		if ( named_anchors[k].name == selected_named_anchor ) {
			document.getElementById('page_named_anchors').selectedIndex = k + 1; // +1 because of 'none selected' option
		}
	}
}

]]>
</script>

</window>





<?php
#					echo '<menuitem value="'.$url.'" label="'.htmlspecialchars($page[ 'name' ]).'"/>'."\n";
	function loki_1_build_reason_url( &$pages, $page_id )
	{
		if( isset( $pages[ $page_id ][ 'real_url' ] ) )
		{
			return $pages[ $page_id ][ 'real_url' ];
		}
		elseif( $page_id == $pages[ $page_id ][ 'parent' ] )
		{
			$pages[ $page_id ][ 'real_url' ] = $pages[ $page_id ][ 'url_fragment' ];
			return $pages[ $page_id ][ 'real_url' ];
		}
		else
		{
			return loki_1_build_reason_url( $pages, $pages[ $page_id ][ 'parent' ] ).'/'.$pages[ $page_id ][ 'url_fragment' ];
		}
	}

	function get_nice_url( $page, $sites )
	{
		$base_url = $sites[ $page['site_id'] ][ 'base_url' ];
		// strip prepended and/or appended slash
		if( substr( $base_url, 0, 1 ) == '/' )
			$base_url = substr( $base_url, 1 );
		if( substr( $base_url, -1 ) == '/' )
			$base_url = substr( $base_url, 0, -1 );
			
		return $base_url.$page['real_url'];
	}

	function print_page_links( $site_id )
	{
		if( $site_id != 0 )
		{
			reason_include_once( 'classes/entity_selector.php' );

		// get all site info
		$sites = get_entities_by_type_name( 'site' );

		// get all page_node pages
		$es = new entity_selector( $site_id );
		$es->add_type( id_of( 'minisite_page' ) );
		$es->add_left_relationship_field( 'minisite_page_parent','entity','id','parent');
		$es->add_right_relationship_field( 'owns','entity','id','site_id');	
		$es->set_order( 'sortable.sort_order' );

		$page_entity_array =  $es->run_one();

		$page_values = array();
		foreach($page_entity_array AS $page)
		{
			$page_values[ $page->id() ] = $page->get_values();
		}

		//get children of each page
		$main_page_id = null;
		$page_hierarchy = array();
		foreach($page_values AS $page){	
			$page_hierarchy[$page['id']] = get_page_hierarchy($page_values, $page['id']);
			if($page['id'] == $page['parent'])
			{
				$main_page_id = $page['id'];
			}
		}

		// get all nice urls for all page_node pages
		foreach( $page_values AS $page_id => $page )
			$page_values[ $page_id ][ 'real_url' ] = loki_1_build_reason_url( $page_values, $page_id );

		$url_array = array();	//[page_id][page_url]
		foreach( $page_values AS $page )
		{
			if (!empty($page['url']))
			{
				$url_array[$page['id']] = $page['url'];
			}
			elseif( $sites[ $page[ 'site_id' ] ][ 'base_url' ] )
			{
				$url_array[$page['id']] = '/'.get_nice_url( $page, $sites ).'/';
			}
		}
		
		traverse_children($page_hierarchy, $main_page_id, 0, $page_values, $url_array);
	}
}
	function get_page_hierarchy($page_values, $parent_id)
	{
		$children = array();		//[page_id][array_of_children]
		foreach($page_values as $page)
		{
			if($page['parent'] == $parent_id && $parent_id != $page['id'])
			{
				$children[] = $page['id'];				
			}			
		}
		return $children;
	}
	
	function traverse_children($page_hierarchy, $starting_point, $dashcount, $page_values, $url_array)
	{
		//echo option tag to current page
		$page = $page_values[$starting_point];
		$url = $url_array[$starting_point];
		echo '<menuitem value="'.$url.'" label="'.htmlspecialchars(str_repeat('- ',$dashcount).$page[ 'name' ]).'"/>'."\n";
		
		//perform traverse_children on current page's children, if any
		if(!empty($page_hierarchy[$starting_point]))
		{
			foreach($page_hierarchy[$starting_point] as $child_id)
			{
				traverse_children($page_hierarchy, $child_id, ($dashcount + 1), $page_values, $url_array);
			}
		}
		else
		{
			return;
		}
	}

/************************************************************

[2005-1-17 dh] - Fixed selecting off-site links in the last chunk of code.  It now checks to see if the page has a URL
set and if so uses that URL for the menuitem rather than using the nice Reason URL.


*************************************************************/
?>
