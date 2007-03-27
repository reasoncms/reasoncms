<?php

/* This is the news feed */

include_once( 'reason_header.php' );
reason_include_once( 'feeds/page_tree.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'newsFeed';

class newsFeed extends pageTreeFeed
{
	var $home_url = REASON_PRIMARY_NEWS_PAGE_URI;
	var $feed_class = 'newsRSS';
	var $page_types = array('news','news_doc','news_currently','news_random_aaf','news_insideCarleton',);
	var $query_string = 'story_id';
	
	function get_site_link()
	{
		if($this->site_specific)
		{
			if($this->site->get_value('unique_name') == 'media_relations' || $this->site->get_value('unique_name') == 'athletics' )
				$this->site_link = 'http://'.HTTP_HOST_NAME.$this->site->get_value('base_url');
			else
			{
				$this->create_page_tree();
				$this->site_link = get_page_link($this->site, $this->page_tree, $this->page_types, true);
			}
		}
		else
			$this->site_link = $this->home_url;
	}
	function alter_feed()
	{
		$this->feed->set_item_field_map('title','id');
		$this->feed->set_item_field_map('author','author');
		$this->feed->set_item_field_map('description','description');
		$this->feed->set_item_field_map('pubDate','datetime');
		
		$this->feed->set_item_field_handler( 'title', 'make_title', true );
		$this->feed->set_item_field_handler( 'description', 'strip_tags', false );
		$this->feed->es->add_relation( 'show_hide.show_hide = "show"' );
		$this->feed->es->set_order( 'datetime DESC' );
		$this->feed->es->add_relation( 'status.status != "pending"' );
		$this->feed->es->add_relation( 'dated.datetime <= NOW()' );
		$this->feed->es->set_num( 10 );
		
		if($this->site_specific)
		{
			// grab the most recent issue
			$es = new entity_selector( $this->site_id );
			$es->add_type( id_of( 'issue_type' ) );
			$es->add_relation( 'show_hide.show_hide = "show"' );
			$es->set_order( 'dated.datetime DESC' );
			$es->set_num( 1 );
			$issues = $es->run_one();
			
			// Only grab related news items if there is a most recent issue available
			if(!empty($issues))
			{
				$issue = current($issues);
				$this->feed->es->add_left_relationship( $issue->id() , relationship_id_of( 'news_to_issue' ) );
				$this->feed->es->set_num( 10000 ); // show the entiure issue, not just the top 10
			}
			
			// Otherwise just show the most recent news items
		}
	}
}

class newsRSS extends pageTreeRSS
{
	var $special_sites;
	
	function newsRSS( $site_id, $type_id = '' ) // {{{
	{
		$this->special_sites['news'] = id_of('media_relations');
		$this->special_sites['athletics'] = id_of('athletics');
		$this->page_type_id = id_of('minisite_page');
		$this->site = new entity($site_id);
		$this->init( $site_id, $type_id );
	} // }}}
	function site_specific_item_link( $item_id )
	{
		if($this->site_id == $this->special_sites['news']) // if it's the news site
		{
			return $this->make_news_site_link( $this->items[ $item_id ], $this->site );
		}
		elseif($this->site_id == $this->special_sites['athletics']) // if it's the athletics site
		{
			return $this->make_athletics_site_link( $this->items[ $item_id ], $this->site );
		}
		else // normal minisite
			return parent::site_specific_item_link( $item_id );
	}
	function non_site_specific_item_link( $item_id )
	{
		$owner = $this->items[ $item_id ]->get_owner();
		if($owner->id() == $this->special_sites['news']) // if it's the news site
		{
			return $this->make_news_site_link( $this->items[ $item_id ], $owner);
		}
		elseif($owner->id() == $this->special_sites['athletics']) // if it's the athletics site
		{
			return $this->make_athletics_site_link( $this->items[ $item_id ], $owner );
		}
		else // normal minisite
		{
			return parent::non_site_specific_item_link( $item_id );
		}
		//return $this->get_channel_attr( 'link' ).'?content=content&module=news&id='.$item_id;
	}
	function make_news_site_link( &$entity, &$site )
	{
		$url = 'http://'.REASON_HOST.$site->get_value('base_url');
		$url .= '?content=content&module=news&id='.$entity->id();
		return $url;
	}
	function make_athletics_site_link( &$entity, &$site )
	{
		$sports = $entity->get_left_relationship( 'news_to_sport' );
		if(!empty($sports))
			$sport = current($sports);
		$url = 'http://'.REASON_HOST.$site->get_value('base_url');
		$url .= '?module=content';
		if(!empty($sport))
			$url .= '&sport='.$sport->id();
		$url .= '&id='.$entity->id();
		return $url;
	}
	function make_title( $id )
	{
		$owner = $this->items[ $id ]->get_owner();
		if($owner->id() == $this->special_sites['athletics']) // if it's the athletics site
		{
			$sports = $this->items[ $id ]->get_left_relationship( 'news_to_sport' );
			$title = '';
			if(!empty($sports))
			{
				$sport = current($sports);
				reason_include_once( 'display_name_handlers/sport.php3' );
				$title .= $sport->get_display_name().': ';
			}
			$title .= $this->items[ $id ]->get_value('release_title');
			return $title;
		}
		else // normal minisite
			return $this->items[ $id ]->get_value('release_title');
	}
}

?>