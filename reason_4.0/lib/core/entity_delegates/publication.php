<?php

reason_include_once( 'entity_delegates/abstract.php' );
reason_include_once( 'function_libraries/util.php' );

$GLOBALS["entity_delegates"]["entity_delegates/publication.php"] = 'publicationDelegate';

/**
 */
class publicationDelegate extends entityDelegate
{
	var $start_date = false;
	var $end_date = false;
	var $date_changed = false;
	var $limit = false;
	var $limit_changed = false;
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
			$blog_feed_string = $this->entity->get_value('blog_feed_string');
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
		return $this->site_id;
	}
	
	function get_start_date()
	{
		return $this->start_date;
	}
	
	function get_end_date()
	{
		return $this->end_date;
	}
	
	/**
	 * Return published items - published items are defined as:
	 *
	 * 1. the news item is marked as published
	 * 2. if the publication is issued, the news item must be related to at least one published issue
	 * @param limit_num int set a max number of items to return from the publicaiton
	 * @param force_refresh force a new query
	 *
	 * @return array news/post entities that are published
	 */
	function &get_published_items( $limit_num = false, $force_refresh = false)
	{
		if ($limit_num != $this->limit) $this->limit_changed = true;
		if ( !isset($this->published_items) || $this->date_changed || $this->limit_changed || $force_refresh )
		{
			$issued = ($this->entity->get_value('has_issues') == 'yes');
			$issues = ($issued) ? $this->get_published_issues() : false;
			if ($issued && !$issues) $this->published_items = array();
			else
			{
				$es = new entity_selector();
				$es->description = 'Selecting published news items for this publication';
				$es->add_type( id_of('news') );
				$es->limit_tables(array('dated','show_hide', 'status'));
				$es->limit_fields(array('dated.datetime', 'status.status', 'show_hide.show_hide'));
				$es->add_left_relationship( $this->entity->id(), relationship_id_of('news_to_publication') );
				$es->set_order('dated.datetime DESC');
				if ($this->get_start_date()) $es->add_relation('dated.datetime >= "' . $this->get_start_date() .'"');
				if ($this->get_end_date()) $es->add_relation('dated.datetime <= "' . $this->get_end_date() .'"'); 
				if ($limit_num) $es->set_num($limit_num);
				$es->add_relation("status.status != 'pending'");
				if ($issues) $es->add_left_relationship_field( 'news_to_issue', 'entity', 'id', 'issue_id', array_keys($issues) );
				$this->published_items = $es->run_one();
			}
			$this->date_changed = false;
			$this->limit_changed = false;
			$this->limit = $limit_num;
		}
		return $this->published_items;
	}

	/**
	 * Specify a start date that limits what you care about for the publication
	 *
	 * @param string mysql_datetime format
	 * @param normalize time to the start of the day (12:00 AM) - default true
	 */
	function set_start_date($start_date, $normalize = true)
	{
		if (is_mysql_datetime($start_date) || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $start_date))
		{
			if ($normalize) $start_date = carl_date('Y-m-d 00:00:00', get_unix_timestamp($start_date));
			$this->start_date = $start_date;
			$this->date_changed = true;
		}
		else trigger_error('publication helper method set_start_date given a value that is not in mysql_datetime format - did not set the start date');
	}
	
	/**
	 * Specify an end date that limits what you care about for the publication
	 *
	 * @param string mysql_datetime format
	 * @param normalize time to end of the day (11:59 PM) - default true
	 */
	function set_end_date($end_date, $normalize = true)
	{
		if (is_mysql_datetime($end_date) || preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $end_date))
		{
			if ($normalize) $end_date = carl_date('Y-m-d 11:59:59', get_unix_timestamp($end_date));
			$this->end_date = $end_date;
			$this->date_changed = true;
		}
		else trigger_error('publication helper method set_end_date given a value that is not in mysql_datetime format - did not set the end date');
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
			$es->add_left_relationship( $this->entity->id(), relationship_id_of('issue_to_publication') );
			$this->issues = $es->run_one();
		}
		return $this->issues;
	}
}