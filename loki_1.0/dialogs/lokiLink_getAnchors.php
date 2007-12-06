<?php
/**
 * @package loki_1
 * @subpackage loki
 */

/**
 * Include the find_anchors_naive() function
 */
include_once('anchors_from_string.php');
 
 /**
 * Make sure request starts with http:// or https://
 */
 	if(strpos($_REQUEST['url'],'http://'.$_SERVER['HTTP_HOST']) === 0 || strpos($_REQUEST['url'],'https://'.$_SERVER['HTTP_HOST']) === 0)
	{
		$page = file($_REQUEST['url']);
	
		$html = implode( '',$page );
		$anchors = find_anchors_naive($html);
		$string = '';
		foreach($anchors as $a)
		{
			$string .= '<a name="'.$a.'"></a>';
		}
	}
	else
	{
		$string = 'not an http or https URL on the current host';
	}
	$string=addslashes($string);
?>
<script type="text/javascript">
	document.write("<?php print ($string); ?>");
	var namedAnchors = new Array();
	var j=0;

	for (i = 0; i < document.anchors.length; i++) {
		if ((document.anchors(i).name!="top")&&(document.anchors(i).name!="navBottom")&&(document.anchors(i).name!="content")) { 
			namedAnchors[j]=document.anchors(i).name;
			j++;
		}
	}
  	window.parent.handleResponse(namedAnchors);
</script>
