<?php

include_once( 'reason_header.php' );
reason_include_once('feeds/default.php');
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorAssetsFeed';

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
		$asset = new entity($id);
		$owner_site = $asset->get_owner();
		
		return 'http://' . REASON_HOST . $owner_site->get_value('base_url') .MINISITE_ASSETS_DIRECTORY_NAME.'/'.$asset->get_value( 'file_name' );
	}
}

?>