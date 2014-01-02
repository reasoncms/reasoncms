<?php
/**
 * @package carl_util
 * @subpackage basic
 */

/**
 * Implementation of the YIQ color contrast algorithm for php-less
 *
 * See http://24ways.org/2010/calculating-color-contrast/ for details
 */
function less_contrast_color_yiq($arg)
{
	list($type) = $arg;
	if('color' == $arg[0])
	{
		$color = $arg;
	}
	elseif('list' == $type)
	{
		if(isset($arg[2][0]))
			$color = $arg[2][0];
		if(isset($arg[2][1]))
			$dark = $arg[2][1];
		if(isset($arg[2][2]))
			$light = $arg[2][2];
		if(isset($arg[2][3]))
		{
			$threshold = $arg[2][3];
		}
	}
	if(empty($color))
		return;
	if(empty($dark) || 'color' != $dark[0])
		$dark = array('color', 0, 0, 0);
	if(empty($light) || 'color' != $light[0])
		$light = array('color', 255, 255, 255);
	if(empty($threshold) || 'number' != $threshold[0])
	{
		$dark_yiq = yiq($dark[1],$dark[2],$dark[3]);
		$light_yiq = yiq($light[1],$light[2],$light[3]);
		$threshold = array('number', round( ( $dark_yiq + $light_yiq ) / 2 ) );
	}
	list($type, $r, $g, $b) = $color;
	$yiq = yiq($r,$g,$b);
	return ($yiq >= $threshold[1]) ? $dark : $light;
}
function yiq($r,$g,$b)
{
	return (($r*299)+($g*587)+($b*114))/1000;
}