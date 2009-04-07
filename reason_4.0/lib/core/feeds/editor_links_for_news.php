<?php
/**
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies & register feed with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'feeds/sitewide_news.php' );
reason_include_once( 'function_libraries/url_utils.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorNewsFeed';

/**
 * This is the news feed for Loki
 *
 * It uses the sitewide news feed, but adds a 3 minute cache to help with sites 
 * that have a large number of news items, and also does not limit the number of items shown
 */
class editorNewsFeed extends sitewideNewsFeed
{
	var $num_to_display;
	var $cache_lifespan = 180;
}

?>
