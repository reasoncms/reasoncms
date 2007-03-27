<?php 
	reason_include_once( 'minisite_templates/modules/events_mini.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'moreMiniEventsModule';

class moreMiniEventsModule extends miniEventsModule
{
	var $ideal_count = 5;
}
?>
