<?php
include_once('reason_header.php');
reason_include_once('classes/entity.php');
$id = $_GET['id'];

$e = new entity($id);
if (is_object($e))
{
	$base_url = $e->get_value('base_url');
	$state = $e->get_value('state');
	if ($state == 'Live')
	{
		$url = HTTP_HOST_NAME . $base_url;
		$url_arr = parse_url( get_current_url() );
		if(!empty($url_arr['query']))
		{
			$url .= '?'.$url_arr['query'];
		}
	}
}
if (isset($url))
{
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: http://' . $url);
}
else
{
	include(ERROR_403_PAGE);
}
?>
