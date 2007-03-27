<?php

/* This is the news feed for Loki */

include_once( 'reason_header.php' );
reason_include_once( 'feeds/news.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorNewsFeed';

class editorNewsFeed extends newsFeed
{
	function alter_feed()
	{
		if($this->site_specific)
		{
			$this->feed->set_item_field_map('title','id');
			$this->feed->set_item_field_handler( 'title', 'make_title', true );
			$this->feed->set_item_field_map('author','');
			$this->feed->set_item_field_map('description','');
			$this->feed->set_item_field_map('pubDate','');	
			
			$this->feed->es->add_relation( 'show_hide.show_hide = "show"' );
			$this->feed->es->set_order( 'datetime DESC' );
			$this->feed->es->add_relation( 'status.status != "pending"' );
			$this->feed->es->set_num( 100000 );
		}
		else
		{
			$this->feed->es->add_relation( '1 = 2' );
		}
	}
}

?>