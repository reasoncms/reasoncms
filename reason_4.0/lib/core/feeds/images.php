<?php

include_once( 'reason_header.php' );
reason_include_once( 'feeds/page_tree.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'imagesFeed';

class imagesFeed extends defaultFeed
{
	var $feed_class = 'imagesRSS';

	function alter_feed()
	{
		// Start with defaults
 		$this->do_default_field_mapping();

		// Then change only the link field
		$this->feed->set_item_field_map('link', 'id');
		$this->feed->set_item_field_handler('link', 'make_link', true);
		
		// Modify entity selector
		$num = !empty($_REQUEST['num']) ? turn_into_int($_REQUEST['num']) : '0';
		$start = !empty($_REQUEST['start']) ? turn_into_int($_REQUEST['start']) : '0';
		if( !empty($_REQUEST['q']) )
		{
			$this->feed->es->add_relation('(entity.name LIKE "%'.addslashes($_REQUEST['q']) . '%"' .
						      ' OR meta.description LIKE "%' . addslashes($_REQUEST['q']) . '%"'.
							  ' OR meta.keywords LIKE "%' . addslashes($_REQUEST['q']) . '%"'.
							  ' OR chunk.content LIKE "%' . addslashes($_REQUEST['q']) . '%"'.
							  ')');
		}
		$this->feed->es->set_num( $num );
		$this->feed->es->set_start( $start );
		$this->feed->es->set_order( 'dated.datetime DESC' );
	}
}

class imagesRSS extends ReasonRSS
{
	function make_link($id)
	{
		return 'http://' . REASON_HOST . WEB_PHOTOSTOCK . $id . '_tn.' . $this->items[$id]->get_value('image_type');
	}
}

?>