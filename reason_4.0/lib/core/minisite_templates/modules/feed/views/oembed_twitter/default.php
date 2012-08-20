<?php
/**
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include the base class
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_view_class_names' ][ reason_basename(__FILE__) ] = 'ReasonOembedTwitterFeedView';

/**
 * ReasonOembedTwitterFeedView displays an ombed tweet.
 *
 * @author Nathan White
 */
 
class ReasonOembedTwitterFeedView extends ReasonMVCView
{
	function get()
	{
		$tweet_json = $this->data();
		$str = json_decode($tweet_json);
		return $str->html;
	}
}
?>