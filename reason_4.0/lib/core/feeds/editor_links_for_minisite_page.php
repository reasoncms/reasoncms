<?php

include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
reason_include_once( 'minisite_templates/nav_classes/default.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorPageFeed';

class editorPageFeed extends defaultFeed
{
	var $page_tree;
	var $feed_class = 'editorPagesRSS';
	
	function alter_feed()
	{
		if($this->site_specific)
		{
			$this->feed->set_item_field_map('author','');
			$this->feed->set_item_field_map('description','');
			$this->feed->set_item_field_map('pubDate','');
			$this->feed->set_item_field_map('title','name');
			$this->feed->set_item_field_map('link','id');
			$this->feed->set_item_field_handler( 'link', 'make_page_link', true );
			
			$this->feed->es->set_num( 100000 );
			// make sure we don't grab any pages that are not really part of the page tree
			$this->feed->es->add_left_relationship_field( 'minisite_page_parent' , 'entity' , 'id' , 'parent_id' );
		}
		else
		{
			$this->feed->es->add_relation( '1 = 2' );
		}
	}
}

class editorPagesRSS extends ReasonRSS
{
	var $trees = array();
	var $page_type_id;
	var $site;
	
	function editorPagesRSS( $site_id, $type_id = '' ) // {{{
	{
		$this->page_type_id = id_of('minisite_page');
		$this->site = new entity($site_id);
		$this->init( $site_id, $type_id );
	} // }}}
	function make_page_link( $page_id )
	{
		if($this->items[ $page_id ]->get_value('url'))
		{
			return $this->items[ $page_id ]->get_value('url');
		}
		if(empty($this->site))
		{
			$owner = $this->items[ $page_id ]->get_owner();
		}
		else
		{
			$owner = $this->site;
		}
		
		if(empty( $this->trees[ $owner->id() ] ) )
		{
			$this->trees[ $owner->id() ] = new minisiteNavigation();
			$this->trees[ $owner->id() ]->site_info = $owner;
			$this->trees[ $owner->id() ]->init( $owner->id(), $this->page_type_id );
		}
		
		if(empty($this->pages[ $owner->id() ][ $page_id ]))
		{
			$this->pages[ $owner->id() ][ $page_id ] = 'http://'.$_SERVER['HTTP_HOST'].'/'.trim_slashes($owner->get_value("base_url")).$this->trees[ $owner->id() ]->get_nice_url($page_id).'/';
		}
		
		return $this->pages[ $owner->id() ][ $page_id ];
	}
}

?>
