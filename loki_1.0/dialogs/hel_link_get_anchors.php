<?php

include_once('reason_header.php');
reason_include_once( 'function_libraries/url_utils.php');

$string = get_reason_url_contents($_REQUEST['uri']);
$string=eregi_replace("\r","",$string);
$string=eregi_replace("\n","",$string);
$string=strip_tags($string, "<a>");
//$string=addslashes($string);

echo $string;

?>
<script type="text/javascript">
window.addEventListener("load", function (event) {
	window.parent.refresh_named_anchors_contd( document.getElementsByTagName('a'),
											   '<?php echo addslashes($_REQUEST['selected_named_anchor']); ?>' )
} , true);
</script>
