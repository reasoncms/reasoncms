<?php include_once( 'reason_header.php' ); ?>

<HTML>
<HEAD>
<TITLE>Add Hyperlink</TITLE>
<link rel='stylesheet' type='text/css' href='../css/modalStyles.css'>
<script type="text/javascript">
var args = window.dialogArguments;
var gCurrentTab = 0; //must be changed later, in do_onLoad
function do_onLoad() {
	try {
		var oSimpleLink = document.getElementById('oSimpleLink');
		var oSiteLinks = document.getElementById('oSiteLinks');
		var oSiteAnchors = document.getElementById('oSiteAnchors');
	
		document.getElementById('oTitle').value = args['title'];

		if ( args['target'] == null || args['target'] == '' ) { document.getElementById('oTarget').checked = false; }
		else { document.getElementById('oTarget').checked = true; }

		if ( args['href'] == null || args['href'] == '' ) { 
			gCurrentTab = 2; changeTab(1); // 1st tab shown
			oSimpleLink.value = 'http://www.'; // simple link shown
			oSiteLinks.selectedIndex = 0; // "Current Page" option selected
			handleResponse(args['namedAnchors']); // current page anchors shown
		} else {
			// args['href'] has some kind of value
			oSimpleLink.value = args['href'];
			// we have to now split args['href'] since it might contain an anchor (#)
			var splitURL = args['href'].split("#");
			window.anchorFromURL = splitURL[1]; // selected anchor
			// next figure out 1 & 2:
			// 1. if splitURL[0] == 1 of our pages or external url
			for (i = 0; i < oSiteLinks.options.length; i++ ) {
				if (oSiteLinks[i].value == splitURL[0]) {
					gCurrentTab = 1; changeTab(2); // 2nd tab shown
					oSiteLinks.selectedIndex = i;
					window.linkIsFamily=true; // linkIsFamily is global
					break;
				}
			}
			if (window.linkIsFamily!=true){ // must be external link, we don't care if it has an anchor or not
				gCurrentTab = 2; changeTab(1); // 1st tab shown
				handleResponse(args['namedAnchors']);
				return;
			}
			// 2. if splitURL[1] == 1 of our pages anchors
			if (oSiteLinks.selectedIndex==0) { // our "Current Page", query args['namedAnchors']
				document.getElementById('oLoading').style.visibility='visible';
				handleResponse(args['namedAnchors']);
			} else { // query the selected page for anchors
				document.getElementById('oLoading').style.visibility='visible';
				document.getElementById('oIframe').src=get_url(oSiteLinks[oSiteLinks.selectedIndex].value); // fires "handleResponse" function, causes "lokiLink_getAnchors.php" to get fired to get the anchors for selected page
			}
		}
	} catch(e) {}
}

function do_onSubmit()
{
	var arr = new Array();

	if ( gCurrentTab == 1 )
	{
		arr['href'] = document.getElementById('oSimpleLink').value;
		arr['name'] = document.getElementById('oSimpleLink').value;
 	}

	if ( gCurrentTab == 2 )
	{
		var oSelectedLink = document.getElementById('oSiteLinks').options[document.getElementById('oSiteLinks').selectedIndex];
		arr['href'] = oSelectedLink.value;
		arr['name'] = oSelectedLink.innerHTML;
		
		// new 08/21/2003 BK
		var oSelectedAnchor = document.getElementById('oSiteAnchors').options[document.getElementById('oSiteAnchors').selectedIndex];
		if(oSelectedAnchor.index!=0) {
			arr['href'] = arr['href'] + oSelectedAnchor.value;
		}
	}

	arr['title'] = document.getElementById('oTitle').value;

	if ( document.getElementById('oTarget').checked == true )   arr['target'] = true;
	else                                                        arr['target'] = false;
	
	// new 08/21/2003 BK
	if (arr['href']=="") {
		arr['removeLink'] = true;
	} else {
		arr['removeLink'] = false;
	}

	window.returnValue = arr;
	window.close();
}

function changeTab(newTab)
{
	document.getElementById('tab' + gCurrentTab).style.zIndex = '0';
 	document.getElementById('card' + gCurrentTab).style.display = 'none';
	document.getElementById('tab' + gCurrentTab + 'spacer').style.backgroundColor = 'white';

	document.getElementById('tab' + newTab).style.zIndex = '100';
 	document.getElementById('card' + newTab).style.display = 'block';
	document.getElementById('tab' + newTab + 'spacer').style.backgroundColor = 'buttonface';

	gCurrentTab = newTab;
}

function do_onRemoveLink()
{
	var arr = new Array();

	// Copy the relevant attributes into arr
	arr['href'] = '';
	arr['name'] = '';
	arr['category'] = '';
	arr['filename'] = '';
	arr['removeLink'] = true;

	window.returnValue = arr;
	window.close();
}

// new 08/21/2003 BK
function handleResponse(namedAnchors) { // this usually gets fired from "lokiLink_getAnchors.php"
	//alert("anchors queried");
	oSiteAnchors.options.length=1; // this removes previous options but keeps the "Available named anchors" option
	var j=1;
  	for (i = 0; i < namedAnchors.length; i++) {
		var oOption = document.createElement("OPTION");
		oSiteAnchors.options.add(oOption,j);
		oOption.text = namedAnchors[i];
		oOption.value = "#"+namedAnchors[i];
		j++;
	}
	document.getElementById('oLoading').style.visibility='hidden';
	getSelectedAnchor();
}
function get_url(optionsValues) {
	return "lokiLink_getAnchors.php?url=http://<?php print ($_SERVER["SERVER_NAME"]); ?>"+optionsValues;
}
function oSiteLinks_onchange(ref) {
	if (ref.options[ref.selectedIndex].index==0) {
		handleResponse(args['namedAnchors']);
	} else {
		document.getElementById('oIframe').src=get_url(ref.options[ref.selectedIndex].value);
		document.getElementById('oLoading').style.visibility='visible';
	}
}
function oSiteAnchors_onchange(ref) {
	if (ref.options[ref.selectedIndex].index==0) {
		document.getElementById('oRemoveAnchor').style.visibility='hidden';
	} else {
		document.getElementById('oRemoveAnchor').style.visibility='visible';
	}
}
function getSelectedAnchor() {
	var oSiteAnchors = document.getElementById('oSiteAnchors');
	for (i = 0; i < oSiteAnchors.options.length; i++) {
		// "anchorFromURL" variable becomes global in "do_onLoad()" function
		if (window.anchorFromURL==oSiteAnchors.options[i].value.substr(1)/* strips off the "#" */) {
			oSiteAnchors.selectedIndex = i;
			document.getElementById('oRemoveAnchor').style.visibility='visible';
		}
	}
}
</script>
</HEAD>
<BODY onload="do_onLoad();">
<!-- div style="position:absolute; top:0px; right:0px;" onclick="alert(document.body.innerHTML)">View source</div -->
<!-- // new 08/21/2003 BK -->
<div id="oLoading" class="txt" style="position:absolute; left:145px; top:155px; visibility: hidden; z-index: 10;">Loading ...</div>
<div id="oRemoveAnchor" style="position:absolute; left:260px; top:147px; visibility: hidden; z-index: 11;"><input type="button" style="padding: 2px; width: 125px; font-family: MS Sans Serif; font-size: 8px;" id="removeAnchorButton" value="Remove Page Anchor" onclick="document.getElementById('oSiteAnchors').selectedIndex=0;" /></div>
<DIV>
  <div id="tabs">
	
	<div id="tab1spacer"><img src="../images/nav/stp.gif" width="82" height="1" border="0"></div>
	<div id="tab1" onclick="changeTab(1)" style="z-index: 0; position: absolute; left: 10px; top: 11px;"><img src="../images/nav/tabCreateLink.gif" width="84" height="22" border="0"></div>
	<?php if ( isset($_REQUEST['site_id']) && trim($_REQUEST['site_id']) != '' ): ?>
	<div id="tab2spacer"><img src="../images/nav/stp.gif" width="110" height="1" border="0"></div>
	<div id="tab2" onclick="changeTab(2)" style="z-index: 0; position: absolute; left: 94px; top: 11px;"><img src="../images/nav/tabCurrentSiteLinks.gif" width="113" height="22" border="0"></div>
	<?php endif; ?>
	
	<div style="z-index: 1; position: absolute; width: 410; left: 10px; top: 32px; border-bottom: #000000 solid 1px; border-left: buttonhighlight solid 1px; border-right: buttonshadow solid 1px; border-top:  buttonhighlight solid 1px;">
	<div id="card1">
	<div style="margin: 20px 20px 20px 20px;">
	<FIELDSET>
		<LEGEND>Enter URL</LEGEND>
		<input class='txt' type='text' id='oSimpleLink' name='oSimpleLink' size='60' style="margin: 10px 0px 10px 0px;">
	</FIELDSET>
	</div>
	</div>

	<?php if ( isset($_REQUEST['site_id']) && trim($_REQUEST['site_id']) != '' ): ?>
	<div id="card2" style="display:none;">
	<div style="margin: 20px 20px 20px 20px;">
	<FIELDSET>
		<LEGEND>Select Page</LEGEND>
		<select id='oSiteLinks' name='oSiteLinks' class='txt'  style="margin: 11px 0px 11px 15px;" onchange="oSiteLinks_onchange(this);">
			<option value="">Current Page</option>
			<?php print_minisite_links() ?>
		</select>
	</FIELDSET>
	<br />
	<FIELDSET>
		<LEGEND>Available Page Anchors</LEGEND>
		<select id='oSiteAnchors' name='oSiteAnchors' class='txt' style="margin: 11px 0px 11px 15px;" onchange="oSiteAnchors_onchange(this);">
			<option value="">Select</option>
		</select>
	</FIELDSET>
	</div>
	</div>
	<?php endif; ?>
	</div>

	<?php /* 
	<mpc:page ID="tab3" TABTITLE="Campus Links" TABTEXT="Campus Links">
	<div style="margin: 15px 15px 15px 15px;">
	<FIELDSET style="margin-bottom: 10px;">
		<LEGEND>Academic Departments</LEGEND>
		<select name='oLocalLinks' class='txt' style="margin: 11px 0px 11px 15px;">
		<option>Flourishing Opportunity or Failing Grade?</option>
		</select>
	</FIELDSET>
	<FIELDSET style="margin-bottom: 10px;">
		<LEGEND>Administrative Offices</LEGEND>
		<select name='oLocalLinks' class='txt' style="margin: 11px 0px 11px 15px;">
		<option>Flourishing Opportunity or Failing Grade?</option>
		</select>
	</FIELDSET>
	<FIELDSET>
		<LEGEND>Other Campus Organizations/Entities</LEGEND>
		<select name='oLocalLinks' class='txt' style="margin: 11px 0px 11px 15px;">
		<option>Flourishing Opportunity or Failing Grade?</option>
		</select>
	</FIELDSET>
	</div>
	</mpc:page>
	*/ ?>

  </div>
</DIV>

<div style="position: absolute; width:410; height:50; left: 10px; top: 210px; tdop: 260px;">
<FIELDSET style="padding: 5px 5px 5px 5px;">
<LEGEND>Link Information</LEGEND>

<table cellpadding='0' cellspacing='5' border='0'>
<tr valign="middle" align="left">
<td colspan="2" class="txt">Open in new Browser Window:&nbsp;&nbsp;<input type='checkbox' id='oTarget' name='oTarget' value='1'></td>
</tr>
<tr valign="top">
<td align="left" class="txt" valign="middle">Link Description:</td>
<td align="left"><input type="text" id='oTitle' name="oTitle" class="txt" size="50"/></td>
</tr>
</table>

</FIELDSET>
</div>

<div style="position: absolute; left: 0px; top: 300px; width: 100%; text-align: center;">
	<input type="button" class="inputButton" id="okButton" value="OK" onclick="do_onSubmit();" />&nbsp;&nbsp;<input type="button" class="inputButton" id="removeLinkButton" value="Remove Link" onclick="do_onRemoveLink();" />&nbsp;&nbsp;<input type="button" class="inputButton" id="cancelButton" value="Cancel" onclick="window.close();" style="margin-top: 5px;" />
</div>

<iframe id="oIframe" name="oIframe" style="display: none; width:0px; height:0px; border: 0px;" src="blank.html"></iframe>

</BODY>
</HTML>


<?php //echo '<option value="blah">' . $_REQUEST[ 'site_id' ] . '</option>'."\n";

function loki_1_build_reason_url( &$pages, $page_id ) // {{{
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
} // }}}

function get_nice_url( $page, $sites ) // {{{
{
	$base_url = $sites[ $page['site_id'] ][ 'base_url' ];
	// strip prepended and/or appended slash
	if( substr( $base_url, 0, 1 ) == '/' )
		$base_url = substr( $base_url, 1 );
	if( substr( $base_url, -1 ) == '/' )
		$base_url = substr( $base_url, 0, -1 );
			
	return $base_url.$page['real_url'];
} // }}}


function print_minisite_links()
{
	$site_id = isset( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : '';

	if( $site_id )
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
		echo '<option value="'.$url.'">'.str_repeat('- ',$dashcount).$page[ 'name' ].'</option>'."\n";
		
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
?>
