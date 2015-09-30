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
reason_include_once( 'helpers/publication_helper.php');
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'newsFeed';

/**
 * This is the old style news feed
 *
 * These old style feeds are deprecated - feed links that use this feed will not be produced on sites that use the publications module. 
 * The URL manager continues to create rewrite rules that use this feed for backwards compatibility. 
 *
 * This module, whenever possible, will redirect to the feed for the oldest publication on the site.
 */
class newsFeed extends pageTreeFeed
{
	var $home_url = REASON_PRIMARY_NEWS_PAGE_URI;
	var $feed_class = 'newsRSS';
	var $page_types = array('news','news_doc','news_currently','news_random_aaf','news_insideCarleton','recruit_center_profile','news_with_sidebar_blurbs',);
	var $query_string = 'story_id';
	
	function newsFeed($type, $site = false)
	{
		if ($site) $this->publication_check($site);
		$this->init($type, $site);
	}
	
	/**
	 * If the current site has the publication type, find the feed url for the oldest publication and redirect
	 */
	function publication_check($site)
	{
		$es = new entity_selector($site->id());
		$es->add_type(id_of('publication_type'));
		$es->add_right_relationship_field('news_to_publication', 'entity', 'id', 'news_id');
		$es->limit_tables('entity');
		$es->limit_fields('entity.creation_date');
		$es->set_num(1);
		$es->set_order('entity.creation_date ASC');
		$result = $es->run_one();
		if ($result)
		{
			$id_array = array_keys($result);
			$ph = new publicationHelper(reset($id_array)); // should I bother with the helper or just do it here?
			$feed_url = $ph->get_feed_url($site->id());
			if ($feed_url)
			{
				header("Location: ".$feed_url, true, 301);
			}
		}		
	}
	
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
		$this->special_sites['news'] = reason_unique_name_exists('media_relations') ? id_of('media_relations') : '';
		$this->special_sites['athletics'] = reason_unique_name_exists('athletics') ? id_of('athletics') : '';
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
