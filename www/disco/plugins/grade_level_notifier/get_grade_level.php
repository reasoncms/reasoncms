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
 
include_once( 'paths.php' );
if ( isset( $_REQUEST['text']))
{
	$textStatistics = new DaveChild\TextStatistics\TextStatistics;
	echo $textStatistics->fleschKincaidGradeLevel( $_REQUEST['text'] );
}
else
{
	echo 0;
}