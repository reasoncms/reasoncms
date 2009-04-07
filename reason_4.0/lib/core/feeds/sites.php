<?php
/**
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies & register feed with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'siteFeed';

function make_site_link( $base_url )
{
	return 'http://'.REASON_HOST.$base_url;
}
/**
 * This is the site feed
 */
class siteFeed extends defaultFeed
{
	function alter_feed()
	{
		$this->feed->set_item_field_map('title','name');
		$this->feed->set_item_field_map('description','description');
		$this->feed->set_item_field_map('link','base_url');
		$this->feed->set_item_field_map('author','email_cache');
		$this->feed->set_item_field_handler( 'link', 'make_site_link' );
		
		$this->feed->es->add_relation( 'site.site_state = "Live"' );
		$this->feed->es->set_order( 'name ASC' );
		$this->feed->es->set_num( 10000 );
	}
}

?>