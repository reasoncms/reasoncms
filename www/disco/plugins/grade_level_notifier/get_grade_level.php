<?php
/**
 * Simple grade level determining script that responds to AJAX POST
 *
 * This is used to maintain consistency in reading level determination between the browser
 * and in PHP.
 *
 * @package disco
 * @subpackage plugins
 * @author Nathaniel MacArthur-Warner
 */

// Uncomment to debug
function grade_level_debug($label, $str)
{
	if(!empty($_REQUEST['debug']))
	{
		//echo $label."\n".$str."\n\n";
	}
}

include_once( 'paths.php' );

header("Content-Type: text/plain");

if ( isset( $_REQUEST['text']))
{
	$text = $_REQUEST['text'];
	grade_level_debug('Original', $text);
	
	// strip tags so they don't become part of the calculation
	$text = strip_tags( $text );
	grade_level_debug('strip_tags', $text);
	
	// decode html entities so they become normal characters
	$text = html_entity_decode( $text, ENT_HTML5, 'UTF-8' );
	grade_level_debug('html_entity_decode', $text);
	
	// Replace multiple whitepace chars with single spaces again
	mb_internal_encoding('UTF-8');
	$text = preg_replace('/\p{Z}/u', ' ', $text);
	grade_level_debug('multiple to single spaces', $text);
	
	// trim
	$text = trim($text);
	grade_level_debug('trim', $text);
	
	$textStatistics = new DaveChild\TextStatistics\TextStatistics;
	echo $textStatistics->fleschKincaidGradeLevel( $text );
}
else
{
	echo 0;
}