<?php
/**
 * The base feed class
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies and register feed with Reason
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/reason_rss.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'defaultFeed';

/**
 * This is the default feed.
 *
 * It will produce a very simple rss feed for any type.  If given a site, it will limit the rss
 * feed to that site only.
 *
 * Reason-wide feeds are limited to shared entities on live sites.
 *
 * It is meant to be extended by type-specific feeds.
 *
 * Built February 2004 by Matt Ryan
 *
 * @author Matt Ryan
 * @todo Fully document this class
 */
class defaultFeed
{
	var $institution = FULL_ORGANIZATION_NAME;
	var $feed_generator = 'Reason';
	var $home_url = ORGANIZATION_HOME_PAGE_URI;
	var $type; // expects an entity
	var $site; // expects an entity
	var $site_specific = false;
	var $feed; // will be an object
	var $feed_description;
	var $feed_title;
	var $feed_class = 'ReasonRSS';
	var $site_link;
	var $managing_editor;
	var $site_id;
	var $request;
	var $cleanup_rules = array();
	
	function defaultFeed( $type, $site = false )
	{
		$this->init( $type, $site );
	}
	function init( $type, $site = false )
	{
		$this->type = $type;
		if(!empty($site))
		{
			$this->site = $site;
			$this->site_specific = true;
		}
	}
	/**
	 * Get the appropriate X-Robots-Tag header for this feed
	 *
	 * Defaults to "none" -- inclusion/spidering by search engines should be
	 * explicitly enabled for feeds where that is desired
	 *
	 * @return string
	 */
	function get_robots_http_header()
	{
		return 'none';
	}
	function set_request_vars( $request_vars )
	{
		$this->request = $request_vars;
	}
	function get_cleanup_rules()
	{
		return $this->cleanup_rules;
	}
	function run($send_header = true)
	{
		$this->get_site_id();
		$this->get_feed_description();
		$this->get_feed_title();
		$this->get_site_link();
		
		$this->create_feed();

		$this->set_channel_attributes();
		$this->set_general_feed_rules();
		$this->alter_feed();

		$this->run_feed($send_header);
	}
	function get_site_id()
	{
		if($this->site_specific)
			$this->site_id = $this->site->id();
		else
			$this->site_id = '';
	}
	function get_feed_description()
	{
		if($this->type->get_value('plural_name'))
			$what = $this->type->get_value('plural_name');
		else
			$what = $this->type->get_value('name');
		
		if($this->site_specific)
		{
			if($this->site->get_value( 'department' ))
				$who = $this->site->get_value( 'department' );
			else
				$who = $this->site->get_value( 'name' );
		}
		else
			$who = 'across campus';
		$this->feed_description = $what.' from '.$who;
	}
	function get_feed_title()
	{
		$site_name = $this->institution.' ';
		
		if($this->site_specific)
		{
			$site_name .= $this->site->get_value( 'name' ).' ';
		}
		if($this->type->get_value( 'plural_name' ))
			$type_name = $this->type->get_value( 'plural_name' );
		else
			$type_name = $this->type->get_value( 'name' );
			
		$this->feed_title = $site_name;
		if(!$this->site_specific || $this->site->get_value( 'name' ) != $type_name)
			$this->feed_title .= $type_name;
	}
	function get_site_link()
	{
		if($this->site_specific)
		{
			$this->site_link = 'http://'.$_SERVER['HTTP_HOST'].'/'.trim_slashes($this->site->get_value("base_url")).'/';
		}
		else
			$this->site_link = $this->home_url;
	}
	function create_feed()
	{
		$this->feed = new $this->feed_class( $this->site_id, $this->type->id() );
	}
	function set_channel_attributes()
	{
		// set up the required channel attributes for the RSS feed
		// these are not attributes for the items, these are for the channel itself
		$this->feed->set_channel_attribute( 'title', $this->feed_title );
		$this->feed->set_channel_attribute( 'description', $this->feed_description );
		$this->feed->set_channel_attribute( 'link', $this->site_link );
		$this->feed->set_channel_attribute( 'generator', $this->feed_generator );
		$this->feed->set_channel_attribute( 'copyright', $this->institution.', '.date('Y') );
	}
	function set_general_feed_rules()
	{
		if(!$this->site_specific)
		{
			$this->feed->es->add_right_relationship_field( 'owns', 'site' , 'site_state' , 'site_state' );
			$this->feed->es->add_relation( 'site_state = "Live"' );
			$this->feed->es->add_relation( 'entity.no_share = "0"' );
		}
	}
	function do_default_field_mapping()
	{
		// here, we set up some mapping between RSS item fields and 
		// the corresponding fields in Reason.  
		
		// title of the RSS item is the name of the entity
		$this->feed->set_item_field_map('title','name');
		
		// author maps one to one, but RSS requires an email
		// address so this will more than likely be blank
		$this->feed->set_item_field_map('author','author');
													
		$this->feed->set_item_field_map('description','description');
		
		// set the pubDate to the last_modified date.
		// In the class, pubDate is automatically
		// set to try and convert whatever date
		// comes in to the proper RSS date format,
		// RFC822. 
		$this->feed->set_item_field_map('pubDate','last_modified');	
	}
	function alter_feed()
	{
		$this->do_default_field_mapping();
	}
	function run_feed($send_header = true)
	{
		// print out the generated rss feed
		if($send_header)
		{
			header('Content-type: text/xml');
			if($robots = $this->get_robots_http_header())
				header('X-Robots-Tag: '.$robots);
		}
		echo $this->feed->get_rss();
	}
}

?>
