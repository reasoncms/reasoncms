<?php
/**
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies & register feed with Reason
 */
include_once( 'reason_header.php' );
reason_include_once('feeds/default.php');
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorAssetsFeed';

/**
 * @todo lets try grabbing owner in the initial entity selector instead of calling it a bunch of times ... get_owner is slow.
 */
class editorAssetsFeed extends defaultFeed
{
	var $feed_class = 'editorAssetRSS';

	function alter_feed()
	{
		// Start with defaults
 		$this->do_default_field_mapping();

		// Then change only the link field
		$this->feed->set_item_field_map('link', 'id');
		$this->feed->set_item_field_map('pubDate', '');
		$this->feed->set_item_field_map('description', '');
		$this->feed->set_item_field_handler('link', 'make_link', true);
		$this->feed->es->set_num( 50000 );
		$this->feed->es->set_order( 'entity.name ASC' );
		$this->feed->es->add_right_relationship_field('site_owns_asset', 'entity', 'id', 'owner_id');
	}
	function do_default_field_mapping()
	{
		$this->feed->set_item_field_map('title', 'name');
	}
}

class editorAssetRSS extends ReasonRSS
{
	function make_link($id)
	{
		$asset = $this->items[$id];
		$owner_id = $asset->get_value('owner_id');
		$owner_site = new entity($owner_id);
		return 'http://' . REASON_HOST . $owner_site->get_value('base_url') .MINISITE_ASSETS_DIRECTORY_NAME.'/'.$asset->get_value( 'file_name' );
	}
}

?>