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
include_once( DISCO_INC . 'plugins/grade_level_notifier/grade_level_notifier.php' );

header("Content-Type: text/plain");

if ( isset( $_REQUEST['text'] ) )
{
	echo DiscoGradeLevelNotifier::get_grade_level($_REQUEST['text']);
}
else
{
	echo 0;
}