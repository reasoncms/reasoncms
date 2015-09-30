<?php
/**
 * A nice wrapper for print_r()
 * @package carl_util
 * @subpackage dev
 */

/**
 * Print_r Pre
 *
 * This function is meant primarly to overcome the tedium of writing
 * "echo '<pre>'" etc. all the time, as well as to make the output look a little nicer.
 *
 * (Designed to combine the advantages of pray and print_r.)
 *
 * @author Nathanael Fillmore, 2003-12-23
 *
 * Modified by Dave Hendler, 2004-09-03
 * I modified the structure.  sprp() is the base function which returns a string instead of echoing
 * osprp() returns a string and also gets rid of the HTML and unhtmlentities the whole thing for console output
 * @param $v array to print
 * @param $k label for array
 */
function sprp( $v, $k = 'sprp' )
{
	$str = '';
	$str .= "\n";
	$str .= "<div><pre><strong>[$k]</strong>\n";
	ob_start();
	if ( is_array($v) || is_object($v) )
		echo "\n";
	print_r($v);
	$print_r = ob_get_contents();
	ob_end_clean();
	$str .= htmlspecialchars($print_r);
	$str .= "\n<strong>[/$k]</strong></pre></div>";
	$str .= "\n";
	return $str;
}
function osprp( $v, $k = 'osprp' )
{
	return unhtmlentities( strip_tags( sprp( $v, $k ) ) );
}
function prp($v, $k = 'prp')
{
	echo sprp( $v, $k );
}
function eprp( $v, $k = 'eprp' )
{
	trigger_error( sprp( $v, $k ) );
}
// prp that checks to see if the user is a developer
function dprp( $v, $k ='dprp' )
{
	if( is_developer() )
	{
		echo '<div style="border: 1px #f00 dashed; background-color: #ccc">';
		prp( $v, $k );
		echo '</div>';
	}
}

?>
