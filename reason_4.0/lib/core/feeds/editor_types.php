<?php
/**
 * @package reason
 * @subpackage feeds
 */

/* This is the feed of types for Loki */

include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorTypeFeed';

class editorTypeFeed extends defaultFeed
{
	var $site_with_access_to_types;
	
	var $feed_class = 'editorTypeRSS';
	
	/* I'm having to fudge the owner class, since the site in question doesn't *own* the types -- the Master Admin does. A bit hacky. */
	function create_feed()
	{
		$this->feed = new $this->feed_class( id_of('master_admin'), $this->type->id() );
	}
	function alter_feed()
	{
		$this->feed->set_item_field_map('title','name');
		$this->feed->set_item_field_map('plural_title', 'plural_name');
		$this->feed->set_item_field_map('link','id');
		$this->feed->set_item_field_handler( 'link', 'make_link_to_feed_of_type', true );
		$this->feed->set_item_field_map( 'description', '' );
		$this->feed->set_item_field_map( 'pubDate', '' );
		$this->feed->set_item_field_map( 'author', '' );
		
		//$this->feed->es->add_relation( 'site.site_state = "Live"' );
		$this->feed->es->set_order( 'name ASC' );
		
		if(!empty($GLOBALS['_reason_types_with_editor_link_feeds']) && !empty($this->site))
		{
			$this->feed->es->add_relation('entity.unique_name IN ("'.implode('","',$GLOBALS['_reason_types_with_editor_link_feeds']).'")');
			$this->feed->es->add_right_relationship($this->site->id(),relationship_id_of('site_to_type'));
			$this->feed->restricted_site_id = $this->site->id();
		}
		else // make sure nothing is returned
		{
			$this->feed->es->add_relation('1 = 2');
		}
		$this->feed->es->set_num( 10000 );
	}
}

class editorTypeRSS extends ReasonRSS
{
	var $restricted_site_id;
	function make_link_to_feed_of_type( $id )
	{
		$type = new entity($id);
		
		return securest_available_protocol() . '://'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.$id.'&site_id='.$this->restricted_site_id.'&feed=editor_links_for_'.$type->get_value('unique_name');
	}
}

?>
