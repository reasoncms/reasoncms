<?php
/**
 * @package reason
 * @subpackage classes
 * @author Nathan White
 */

include_once( 'reason_header.php' );
reason_include_once( 'classes/entity.php' );
reason_include_once( 'function_libraries/util.php' );
	
/**
 * The publication helper provides useful extra methods for publication entities 
 */

class PublicationHelper extends entity
{
	var $site_id;
	var $issues;
	
	function get_feed_url($site_id = '')
	{
		$site_id = ($site_id) ? $site_id : $this->get_site_id();
		$publication_type = new entity(id_of('publication_type'));
		$feed_url_string = $publication_type->get_value('feed_url_string');
		if($feed_url_string)
		{
			$site = new entity($site_id);
			$base_url = $site->get_value('base_url');
			$blog_feed_string = $this->get_value('blog_feed_string');
			$feed_url = $base_url . MINISITE_FEED_DIRECTORY_NAME . '/' . $feed_url_string . '/' . $blog_feed_string;
		}
		return (isset($feed_url)) ? $feed_url : false;
	}
	
	/**
	 * The site id of a publication is the id of its owner
	 */
	function get_site_id()
	{
		if (!isset($this->site_id))
		{
			$owner = $this->get_owner();
			$this->site_id = $owner->id();
		}
	}
	
	/**
	 * Return published items - published items are defined as:
	 *
	 * 1. the news item is marked as published
	 * 2. if the publication is issued, the news item must be related to at least one published issue
	 *
	 * @return array news/post entities that are published
	 */
	function &get_published_items()
	{
		if (!isset($this->published_items))
		{
			$issued = ($this->get_value('has_issues') == 'yes');
			$issues = ($issued) ? $this->get_published_issues() : false;
			if ($issued && !$issues) $this->published_items = array();
			else
			{
				$es = new entity_selector();
				$es->description = 'Selecting published news items for this publication';
				$es->add_type( id_of('news') );
				$es->limit_tables(array('dated','show_hide'));
				$es->limit_fields(array('dated.datetime', 'show_hide.show_hide'));
				$es->add_left_relationship( $this->id(), relationship_id_of('news_to_publication') );
				$es->set_order('dated.datetime DESC');
				if ($issues) $es->add_left_relationship_field( 'news_to_issue', 'entity', 'id', 'issue_id', array_keys($issues) );
				$this->published_items = $es->run_one();
			}
		}
		return $this->published_items;
	}
	
	function &get_published_issues()
	{
		$issues =& $this->get_issues();
		$published_issues = false;
		if ($issues) foreach ($issues as $id => $issue)
		{
			if ($issue->get_value('show_hide') == 'show') $published_issues[$id] =& $issues[$id];
		}
		return $published_issues;
	}
	
	function &get_unpublished_issues()
	{
		$issues =& $this->get_issues();
		$unpublished_issues = false;
		if ($issues) foreach ($issues as $id => $issue)
		{
			if ($issue->get_value('show_hide') == 'hide') $unpublished_issues[$id] =& $issues[$id];
		}
		else $unpublished_issues = false;
		return $unpublished_issues;
	}
	
	function &get_issues()
	{
		if (!isset($this->issues))
		{
			$es = new entity_selector( $this->get_site_id() );
			$es->description = 'Selecting issues for this publication';
			$es->add_type( id_of('issue_type') );
			$es->limit_tables(array('dated','show_hide'));
			$es->limit_fields(array('dated.datetime', 'show_hide.show_hide'));
			$es->set_order('dated.datetime DESC');
			$es->add_left_relationship( $this->id(), relationship_id_of('issue_to_publication') );
			$this->issues = $es->run_one();
		}
		return $this->issues;
	}
}
?>
