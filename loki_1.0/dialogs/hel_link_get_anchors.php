<?php
/**
 * @package loki_1
 * @subpackage hel
 */
/**
 * Include reason libraries
 */
include_once('reason_header.php');
reason_include_once( 'function_libraries/url_utils.php');

/**
 * Include the find_anchors_naive() function
 */
include_once('anchors_from_string.php');

if(strpos($_REQUEST['uri'],'http://'.$_SERVER['HTTP_HOST']) === 0 || strpos($_REQUEST['uri'],'https://'.$_SERVER['HTTP_HOST']) === 0)
{
	$html = get_reason_url_contents($_REQUEST['uri']);
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
echo $string;
?>
<script type="text/javascript">
window.addEventListener("load", function (event) {
	window.parent.refresh_named_anchors_contd( document.getElementsByTagName('a'),
											   '<?php if(!empty($_REQUEST['selected_named_anchor'])) echo addslashes(htmlspecialchars($_REQUEST['selected_named_anchor'], ENT_QUOTES)); ?>' )
} , true);
</script>
