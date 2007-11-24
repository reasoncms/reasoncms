<?
/**
 * @package loki_1
 * @subpackage hel
 */
/**
 * Include paths info
 */
	include_once ( 'paths.php' );
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>redirect</title>
<?
echo "<link rel='stylesheet' type='text/css' href='".UNIVERSAL_CSS_PATH."' />";
?>
<style type="text/css">
body
{
	color:black;
	background:white;
	padding:.5ex;
	margin:0;
}
</style>
<script type="text/javascript" language="javascript">
function do_onload()
{
	document.getElementById('text_textarea').value = parent.editor_obj.temp_modal_args.text;
	document.getElementById('the_form').submit();
}
</script>
</head>
<body onload="do_onload()">
<table>
<tr>
    <td valign="top"><img src="../images/loading.gif" alt="Loading ..." align="left" /></td>
    <td valign="top">
        <div>Checking for misspelled words...</div>
        <div>(If your document is long, this may take some time.)</div>
    </td>
</tr>
</table>
<form id="the_form" method="post" action="hel_spell_check.php">
<textarea id="text_textarea" name="text" style="position:absolute; width:1px; left:-500px;">
</textarea>
</form>
</body>
</html>
