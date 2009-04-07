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
reason_include_once( 'function_libraries/access_log_functions.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'regexFeed';

/**
 * This is the site feed that provides regular expressions for log analysis
 */
class regexFeed extends defaultFeed
{
	function create_feed()
	{
		$this->feed = new regexRSS( $this->site_id, $this->type->id() );
	}
	function alter_feed()
	{
		$this->feed->set_item_field_map('title','unique_name');
		$this->feed->set_item_field_map('link','id');
		$this->feed->set_item_field_handler( 'link', 'regex_server_link', false );
		$this->feed->set_item_field_map('description','id');
		$this->feed->set_item_field_map('pubDate', '');
		$this->feed->set_item_field_map('author', '');
		$this->feed->set_item_field_handler( 'description', 'make_regex', true );
		
		$this->feed->es->add_relation( 'site.site_state = "Live"' );
		$this->feed->es->add_relation( 'entity.unique_name != ""' );
		$this->feed->es->set_order( 'name ASC' );
		$this->feed->es->set_num( 10000 );
	}
	function get_site_link()
	{
		$uname = posix_uname();
		$this->site_link = strtolower($uname['nodename']);
	}
	function get_feed_description()
	{
		$host_parts = explode('.', $_SERVER['HTTP_HOST'] );
		reset($host_parts);
		$this->feed_description =  '/var/log/httpd/access_log'."\n".'/var/log/httpd/'.strtolower(current($host_parts)).'-access-ssl_log';
	}
	function get_feed_title()
	{
		$this->feed_title = 'regex descriptions of sites served by '.$_SERVER['HTTP_HOST'].' for access log analysis';
	}
}

class regexRSS extends ReasonRSS
{
	function make_regex( $id )
	{
		$base_urls = array();
		$base_urls[] = $this->items[$id]->get_value('base_url');
		if($this->items[$id]->get_value('other_base_urls'))
		{
			$other_urls = explode(',', $this->items[$id]->get_value('other_base_urls'));
			$base_urls = array_merge( $base_urls, $other_urls );
		}
		array_walk( $base_urls, 'trim' );
		return build_access_log_regex($base_urls);
	}
}

function regex_server_link( $id )
{
	return $_SERVER['HTTP_HOST'];
}

?>
