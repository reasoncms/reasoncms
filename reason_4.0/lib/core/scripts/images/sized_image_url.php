<?php

include_once("reason_header.php");
reason_include_once("classes/sized_image.php");

if( empty($_GET['id']) )
{
	// header an error code
	die();
}

if( empty($_GET['width']) && empty($_GET['height']) )
{
	// header a different error code
	die();
}

$rsi = new reasonSizedImage();
$rsi->use_absolute_urls();
$id = (int) $_GET['id'];
$rsi->set_id($id);
if(!empty($_GET['width']))
{
	$rsi->set_width( (int) $_GET['width'] );
}

if(!empty($_GET['height']))
{
	$rsi->set_height( (int) $_GET['height'] );
}
$crop_style = 'fill';
if(!empty($_GET['crop_style']) && in_array($_GET['crop_style'], array('fit','crop_x','crop_y')))
{
	$rsi->set_crop_style((string) $_GET['crop_style']);
}

echo $rsi->get_url();
