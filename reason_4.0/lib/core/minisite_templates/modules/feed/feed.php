<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include the base class & dependencies, and register the module with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'minisite_templates/modules/mvc.php' );
$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__ ) ] = 'ReasonMVCFeedModule';

/**
 * Reason MVC Feed Module
 *
 * Deploy various feeds. In the page type, you should specify a model and view (and possibly a controller).
 *
 * Your model must implement a build method that returns the data.
 * 
 * Your view must implement a get method that returns content.
 *
 * @todo implement refresh capabilities
 *
 * @author Nathan White
 */
class ReasonMVCFeedModule extends ReasonMVCModule
{
	function run()
	{
		echo '<div id="reason_feed">';
		echo $this->content;
		echo '</div>';
	}
}
?>