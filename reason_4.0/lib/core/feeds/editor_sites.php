<?php
/**
 * @package reason
 * @subpackage feeds
 */

/* This is the site feed for Loki */

include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorSiteFeed';

class editorSiteFeed extends defaultFeed
{
	var $feed_class = 'editorSiteRSS';
	
	function alter_feed()
	{
		$this->feed->set_item_field_map('title','name');
		$this->feed->set_item_field_map('link','id');
		$this->feed->set_item_field_handler( 'link', 'make_link_to_site_feed', true );
		$this->feed->set_item_field_map( 'description', '' );
		$this->feed->set_item_field_map( 'pubDate', '' );
		$this->feed->set_item_field_map( 'author', '' );
		
		//$this->feed->es->add_relation( 'site.site_state = "Live"' );
		$this->feed->es->add_relation( '((site.custom_url_handler = "") OR (site.custom_url_handler IS NULL))' );
		$this->feed->es->set_order( 'name ASC' );
		$this->feed->es->set_num( 10000 );
	}
}
class editorSiteRSS extends ReasonRSS
{
	function make_link_to_site_feed( $id )
	{
		return securest_available_protocol() . '://'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('type').'&site_id='.$id.'&feed=editor_types';
	}
}
?>
