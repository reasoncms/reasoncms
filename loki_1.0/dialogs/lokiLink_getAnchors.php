<?php

	$page = file($url);
	
	$string = implode( '',$page );
	
	$string=eregi_replace("\r","",$string);
	$string=eregi_replace("\n","",$string);
	$string=strip_tags($string, "<a>");
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
