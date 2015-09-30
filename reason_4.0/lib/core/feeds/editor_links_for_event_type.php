<?php
/**
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies & register feed with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'feeds/page_tree.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorEventsFeed';

class editorEventsFeed extends pageTreeFeed
{
	var $page_types = array('events','events_verbose','events_nonav','events_academic_calendar','event_registration','events_verbose_nonav');
	var $module_sets = array('event_display');
	var $query_string = 'event_id';
	
	function alter_feed()
	{
		if($this->site_specific)
		{
			$this->feed->set_item_field_map('author','');
			$this->feed->set_item_field_map('description','');
			$this->feed->set_item_field_map('pubDate','');
			$this->feed->set_item_field_map('title','id');
			$this->feed->set_item_field_handler( 'title', 'make_event_title', false );
			
			$this->feed->es->add_relation( 'show_hide = "show"' );
			$this->feed->es->set_order( 'datetime DESC' );
			$this->feed->es->set_num( 100000 );
		}
		else
		{
			$this->feed->es->add_relation( '1 = 2' );
		}
	}
}

function make_event_title($id)
{
	$event = new entity($id);
	return $event->get_value('name').' ('.prettify_mysql_datetime($event->get_value('datetime')).')';
}

?>