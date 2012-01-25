<?php
/**
 * Poll Sidebar Module
 * @author Amanda Frisbee
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include parent and thor classes and register module with Reason
 */
reason_include_once( 'minisite_templates/modules/poll/polling_graph.php' );

$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__) ] = 'PollingGraphSidebarModule';

/**
 * A few tweaks for sidebar display.
 *
 * @author Amanda Frisbee
 * @author Nathan White
 */
class PollingGraphSidebarModule extends PollingGraphModule
{
	var $sidebar = true;
}
?>