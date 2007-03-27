<?php include_once( 'reason_header.php' ); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<title>Insert Named Anchor</title>
<link rel="stylesheet" type="text/css" href="../css/modalStyles.css">
<script type="text/javascript">
function do_onLoad()
{
	try {
		var args = window.dialogArguments;
		document.getElementById('oName').value = args['name'];	
	} catch(e) {}
}

function do_onSubmit()
{
	var arr = new Array();
	arr['name'] = document.getElementById('oName').value;
	arr['removeLink'] = false;

	window.returnValue = arr;
	window.close();
}

function do_onRemoveLink()
{
	var arr = new Array();

	// Copy the relevant attributes into arr
	arr['name'] = '';
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
	<legend>Enter Name of Anchor:</legend>
	<table border="0" cellpadding="7" cellspacing="0" width="100%">
	<tr>
	<td>
		<div>
			<input name="oName" id="oName" class="txt" style="width:98%;" />
		</div>
	</td>
	</tr>
	<tr>
	<td>
<p class="txt">The name should begin with a Roman letter, and be followed by any number of digits, hyphens, underscores, colons, periods, and Roman letters. The name should include no other characters.</p>
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
		value="Remove Anchor"
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
