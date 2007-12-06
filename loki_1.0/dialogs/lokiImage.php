<?php
/**
* Insert Image or Link to Image dialog
 * @package loki_1
 * @subpackage loki
 */

/**
 * Include reason libraries
 */
include_once( 'reason_header.php' ); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<title>Insert Image or Link to Image</title>
<link rel="stylesheet" type="text/css" href="../css/modalStyles.css" />
<?php if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
{
	echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />';
}
?>
<style type="text/css">
.bg1 { background: #E8EDF6; border-top: 1px dashed #8AA3D0; }
.bg2 { background: #D1DBEC; border-top: 1px dashed #8AA3D0; }
</style>
<script type="text/javascript" src="../js/loki.js"></script>
<script type="text/javascript">
var gCurrentTab = 1;
var rng;
var doc;
function do_onLoad()
{
	var args = window.dialogArguments;
	rng = args['rng'];
	doc = args['doc'];

	// 0. Change tabs forth and back so that their bottom borders render properly
	changeTab(2);
	changeTab(1);

	// 1. Change focus to image table (to facilitate mouse-wheel scrolling)
	document.getElementById('oImageTable').focus();

	// 2. Determine whether the highlighted link is among the images. If so, then
	//    a. select that image option, and 
	//    b. select the image tab
	var oImages = document.getElementById('oImages').options;
	
	for ( var i = 0; i < oImages.length; i++ )
	{
		if ( oImages[i].value == args['href'] )
		{
			oImages.selectedIndex = i;
			changeTab(2);
		}
	}
	if (oImages.selectedIndex == -1)
		oImages.selectedIndex = 0;
}

function do_onSubmit()
{
	var arr = new Array();

	if ( gCurrentTab == 2 )
	{
		// 1. Determine which link is selected
		var oSelectedLink = document.getElementById('oImages').options[document.getElementById('oImages').selectedIndex];

		// 2. Copy the relevant attributes into arr
		arr['href'] = unescape(oSelectedLink.getAttribute('loki:href'));
		arr['onclick'] = unescape(oSelectedLink.getAttribute('loki:onclick'));
		arr['removeLink'] = false;
		arr['rng'] = rng;
		arr['doc'] = doc;

		insertImageLink(arr); // in loki.js
	}

	window.returnValue = arr;
	window.close();
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
	arr['rng'] = rng;
	arr['doc'] = doc;

	insertImageLink(arr); // in loki.js

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
</script>
</head>

<body onload="do_onLoad()">

<!-- div style="position:absolute; top:0px; right:0px;" onclick="alert(document.body.innerHTML)">View source</div -->
<div>
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
	<fieldset>
		<legend>Drag-and-drop the image into the editor</legend>
			<div id="oImageTable" style="width:400px; height:250px; overflow:scroll; margin:10px; padding:0px;">
				<?php printImageTable() ?>
			</div>
	</fieldset>
	</div>
	</div>

	<?php if ( isset($_REQUEST['site_id']) && trim($_REQUEST['site_id']) != '' ): ?>
	<div id="card2" style="display:none;">
	<div style="margin: 20px 20px 20px 20px;">
	<fieldset>
		<legend>Choose Image</legend>
		<div style="padding:10px;">
			<div>
				<select name="oImages" id="oImages" class="txt" style="width:100%">
					<?php printImageOptions() ?>
				</select>
			</div>

			<div align="center" style="padding:3px; padding-top: 7px;">
				<input type="button"
				class="inputButton"
				onclick="do_onSubmit();"
				value="Insert"
				/>

				<input type="button"
				class="inputButton"
				onclick="window.close();"
				value="Cancel"
				/>
			</div>

			<hr />

			<div align="center" style="padding:3px;">
				<input type="button"
				class="inputButton"
				onclick="do_onRemoveLink();"
				value="Remove Link"
				/>
			</div>
		</div>
	</fieldset>
	</div>
	</div>
	<?php endif; ?>
	</div>
  </div>
</div>

<?php

function printAssetOptions()
{
	$site_id = isset( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : '';
	settype($site_id, 'integer');
	echo "<h3>$site_id</h3>";
	if( $site_id )
	{
		reason_include_once( 'classes/entity_selector.php' );
		$es = new entity_selector( $site_id );
		$es->add_type( id_of( 'asset' ) );
		$assets = $es->run_one();

		$site = new entity( $site_id );
		foreach( $assets AS $asset )
		{
			// changed by DH on 8-7-03 to work with updated asset code.
			// no longer do we have an 'asset_directory' - everything is in the assets directory
			$href = '/'.trim_slashes( $site->get_value( 'base_url' ) ).'/assets/'.trim_slashes( $asset->get_value( 'file_name' ) );

			printf('<option value="%s" loki:linkChooser="true" loki:href="%s" ' .
				   ' loki:name="%s" loki:filename="%s" loki:category="asset">%s (%s)</option>',
				   $href,$href,
				   $asset->get_value('name'),
				   $asset->get_value('file_name'),
				   $asset->get_value('name'),
				   $asset->get_value('file_name'));
		}
	}
}

// Adapted from DH's code at webdev/admin/scripts/image_list.php
function printImageTable()
{
	echo '<table cellpadding="15" cellspacing="0" border="0">' . "\n";

	$site_id = isset( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : '';
	settype($site_id, 'integer');
	if( $site_id )
	{
		reason_include_once( 'classes/entity_selector.php' );
		$es = new entity_selector( $site_id );
		$es->add_type( id_of( 'image' ) );
		$images = $es->run_one();

		foreach( $images AS $image )
		{
			$tn_info = $image_info = '';
			// get filenames for the thumbnail and full size
			$tn_name = $image->id().'_tn.'.$image->get_value('image_type');
			$image_name = $image->id().'.'.$image->get_value('image_type');

			// sometimes images aren't in the filesystem.  hence, the error supressing @
			// this gets image size.  strings ready to placed in image tag are located in index 3
			if(file_exists( PHOTOSTOCK.$tn_name ) )
			{
				@$tn_info = getimagesize( PHOTOSTOCK.$tn_name );
			}
			if(file_exists( PHOTOSTOCK.$image_name ) )
			{
				@$image_info = getimagesize( PHOTOSTOCK.$image_name );
			}
			
			// I suppose there could be a thumbnail with no image.  This code takes care of all possibilities
			if( !empty( $image_info ) OR !empty( $tn_info ) )
			{
				if( !empty( $tn_info ) )
				{
					echo '<tr><td align="left" valign="top" class="bg1"><img src="'.WEB_PHOTOSTOCK.$tn_name.'?blah='.time().'" '.$tn_info[3].' alt="'.htmlentities($image->get_value("description")).'" border="0" hspace="10" vspace="10" /><br />'."\n";
					echo '<strong>&nbsp;&nbsp;&nbsp;^&nbsp;Alternate Text:</strong> ' . htmlentities($image->get_value("description")) . '<br />'."\n";
					echo '<strong>&nbsp;&nbsp;&nbsp;^&nbsp;Width:</strong> ' . $tn_info[0] . ' px<br />'."\n";
					echo '<strong>&nbsp;&nbsp;&nbsp;^&nbsp;Height:</strong> ' . $tn_info[1] . ' px</td></tr>'."\n";
				}
				if( !empty( $image_info ) )
				{
					echo '<tr><td align="left" valign="top" class="bg2"><img src="'.WEB_PHOTOSTOCK.$image_name.'?blah='.time().'" '.$image_info[3].' alt="' . htmlentities($image->get_value("description")) . '" border="0" hspace="10" vspace="10" /><br />'."\n";
					echo '<strong>&nbsp;&nbsp;&nbsp;^&nbsp;Alternate Text:</strong> ' . htmlentities($image->get_value("description")) . '<br />'."\n";
					echo '<strong>&nbsp;&nbsp;&nbsp;^&nbsp;Width:</strong> ' . $image_info[0] . ' px<br />'."\n";
					echo '<strong>&nbsp;&nbsp;&nbsp;^&nbsp;Height:</strong> ' . $image_info[1] . ' px</td></tr>'."\n";
				}
			}
		}
	}
	
	echo '</table>' . "\n";
}

function printImageOptions()
{
	$site_id = isset( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : '';
	settype($site_id, 'integer');
	if( $site_id )
	{
		reason_include_once( 'classes/entity_selector.php' );
		$es = new entity_selector( $site_id );
		$es->add_type( id_of( 'image' ) );
		$images = $es->run_one();

		foreach ( $images as $image )
		{
			$url = WEB_PHOTOSTOCK . $image->id() . '.' . $image->get_value('image_type');
			$name = $image->get_value('name');
			$size = $image->get_value('size');
			if ( !empty($size) )
				$name .= ' (' . $size . 'kb)';

			$window_width = $image->get_value('width') < 340 ? 340 : 40 + $image->get_value('width');
			$window_height = 170 + $image->get_value('height');
			$onclick_url = WEB_PHOTOSTOCK . "image.php3?id=" . $image->id();
			$onclick = "window.open('" . $onclick_url . "', 'PopupImage', 'menubar,scrollbars,resizable,width=" . $window_width . ",height=" . $window_height . "'); return false;";

			echo '<option value="' . $url . '" loki:href="' . $url . '" loki:onclick="' . $onclick . '">' . $name . '</option>';
		}
	}
}
?>

</body>
</html>
