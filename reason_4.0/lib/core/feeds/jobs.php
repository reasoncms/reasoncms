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
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'jobsFeed';

class jobsFeed extends pageTreeFeed
{
	var $page_types = array('jobs','jobs_faculty', 'jobs_student',);
	var $query_string = 'job_id';
	
	function alter_feed()
	{
		$this->feed->set_item_field_map('title','name');
		$this->feed->set_item_field_map('description','title_extension');
		$this->feed->set_item_field_map('pubDate','posting_start');
		
		$this->feed->es->add_relation( 'show_hide.show_hide = "show"' );
		$this->feed->es->add_relation( 'job.posting_start <= "'.date( 'Y-m-d' ).'"' );
		$this->feed->es->add_relation( 'adddate( job.posting_start, interval duration.duration day ) >= "'.date( 'Y-m-d' ).'"' );
		$this->feed->es->set_order( 'name ASC' );
		$this->feed->es->set_num( 10000 );
	}
}

?>
