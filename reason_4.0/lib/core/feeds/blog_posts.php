<?php

/* This is the news feed */

include_once( 'reason_header.php' );
reason_include_once( 'feeds/page_tree.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'blogPostsFeed';

class blogPostsFeed extends pageTreeFeed
{
	var $query_string = 'story_id';
	var $blog; // entity
	var $modules = array('publication');
	
	function grab_blog()
	{
		if(empty($this->blog))
		{
			if($this->request['blog_id'])
			{
				$this->blog = new entity($this->request['blog_id']);
			}
			else
			{
				trigger_error('No publication id set on publication feed');
			}
		}
	}
	function get_feed_description()
	{
		$this->grab_blog();
		
		if($this->blog->get_value('description'))
		{
			$this->feed_description = $this->blog->get_value('description');
		}
		else
		{
			$this->feed_description = 'The latest posts from '.$this->blog->get_value('name');
		}
	}
	function get_feed_title()
	{
		$this->grab_blog();
		
		$this->feed_title = $this->blog->get_value('name').' :: '.$this->institution;
	}
	function get_site_link()
	{
		$this->grab_blog();
		
		if($this->site_specific)
		{
			$this->create_page_tree();
			$this->site_link = get_blog_page_link($this->site, $this->page_tree, $this->page_types, $this->blog);
		}
		else
			$this->site_link = $this->home_url;
	}
	function alter_feed()
	{
		$this->grab_blog();
		
		$this->feed->set_item_field_map('title','release_title');
		$this->feed->set_item_field_map('author','author');
		$this->feed->set_item_field_map('description','description');
		$this->feed->set_item_field_map('pubDate','datetime');
		
		$this->feed->set_item_field_handler( 'description', 'strip_tags', false );
		$this->feed->es->add_relation( 'show_hide.show_hide = "show"' );
		$this->feed->es->set_order( 'datetime DESC' );
		$this->feed->es->add_relation( 'status.status != "pending"' );
		$this->feed->es->add_relation( 'dated.datetime <= NOW()' );
		
		$this->feed->es->add_left_relationship( $this->blog->id() , relationship_id_of( 'news_to_publication' ) );
	}
}

function get_blog_page_link( $site, $tree, $page_types, $blog ) // {{{
{
	$relations = array();
	$es = new entity_selector($site->id());
	$es->add_type( id_of( 'minisite_page' ) );
	foreach($page_types as $page_type)
	{
		$relations[] = 'page_node.custom_page = "'.$page_type.'"';
	}
	$es->add_relation( '('.implode(' or ', $relations).')' );
	$es->add_left_relationship( $blog->id(), relationship_id_of('page_to_publication') );
	$es->set_num( 1 );
	$pages = $es->run_one();
	
	if (!empty($pages))
	{
		$page = current($pages);
		return $tree->get_full_url($page->id(), true);
	}
	else
	{
		return false;
	}
}

?>