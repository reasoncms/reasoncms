<?php include_once( 'reason_header.php' ); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<title>Insert Link to Asset</title>
<link rel="stylesheet" type="text/css" href="../css/modalStyles.css">
<script type="text/javascript">
function do_onLoad()
{
	try {
		var args = window.dialogArguments;

		var oAssets = document.getElementById('oAssets').options;
		for ( var i = 0; i < oAssets.length; i++ )
		{
			if ( oAssets[i].value == args['href'] )
			{
				oAssets.selectedIndex = i;
				var selectedTab = 2;
			}
		}

		if (oAssets.selectedIndex == -1)
 		oAssets.selectedIndex = 0;
	} catch(e) {}
}

function do_onSubmit()
{
	var arr = new Array();

	// 1. Determine which link is selected
	var oSelectedLink = document.getElementById('oAssets').options[document.getElementById('oAssets').selectedIndex];

	// 2. Copy the relevant attributes into arr
	arr['href'] = unescape(oSelectedLink.getAttribute('loki:href'));
	arr['name'] = unescape(oSelectedLink.getAttribute('loki:name'));
	arr['category'] = unescape(oSelectedLink.getAttribute('loki:category'));
	arr['filename'] = unescape(oSelectedLink.getAttribute('loki:filename'));
	arr['removeLink'] = false;

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

	window.returnValue = arr;
	window.close();
}
</script>
</head>

<body onload="do_onLoad()">

<!-- div style="position:absolute; top:0px; right:0px;" onclick="alert(document.body.innerHTML)">View source</div -->


<form id="oForm">

<table border="0" cellpadding="10" cellspacing="0" width="100%">
<tr>
<td>

	<fieldset>
	<legend>Choose Asset:</legend>
	<table border="0" cellpadding="7" cellspacing="0" width="100%">
	<tr>
	<td>
			<select name="oAssets" id="oAssets" class="txt" style="width:100%">
				<?php printOptions() ?>
			</select>
		</div>
	</td>
	</tr>
	</table>
	</fieldset>

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

</td>
</tr>
</table>

</form>


<?php

function printOptions()
{
	$site_id = isset( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : '';
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
?>

</body>
</html>
