<?php
/**
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies & register feed with Reason
 */
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'sitewideNewsFeed';

include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
reason_include_once( 'helpers/publication_helper.php');
reason_include_once( 'classes/object_cache.php' );
reason_include_once( 'classes/page_types.php');
reason_include_once( 'classes/module_sets.php');

/* This is the sitewide news feed, which displays news across publications on a site. 
 *
 * This feed includes the following types of stories:
 *
 * - Owned or borrowed published stories in non-issued publications
 * - Owned or borrowed published stories in issued publications, when the issue is published
 *
 * This feed works a bit differently as most others
 * ...it does all its own entity work, and then sets the items in the reason rss class
 * 
 * @todo support variable number of items
 * @todo distinct cache for augmented and raw items
 *
 * @author Nathan White
 */

class sitewideNewsFeed extends defaultFeed
{
	/**
	 * @var int number of seconds that cache should live - set to 0 to disable caching
	 */
	var $cache_lifespan = 0; // seconds that cache should live - set to 0 to disable caching entirely

	/**
	 * @var array news item entities
	 * @access private	
	 */
	var $_items;
	
	/**
	 * How many items to show in the feed - fixed for now.
	 *
	 * @var int number of items to show
	 */
	var $num_to_display = 25;

	/**
	 * Grab our items, send them to the feed
	 */
	function alter_feed()
	{
		$items =& $this->get_items();
		$feed =& $this->get_feed();
		$feed->set_items($items);
		$this->augment_feed($feed);
	}
	
	function augment_feed(&$feed)
	{
		$feed->set_item_field_map('pubDate','datetime');
		$feed->set_item_field_map('link', 'url');
		$feed->set_item_field_map('author','author');											
		$feed->set_item_field_map('description','description');
		$feed->set_item_field_handler( 'description', 'strip_tags', false );
		if (isset($this->augment_feed_handlers[$this->site->get_value('unique_name')]))
		{
			$method_name = $this->augment_feed_handlers[$this->site->get_value('unique_name')];
			$this->$method_name($feed);
		}
	}

	/**
	 * Build the items and augment them into shape for the feed - here is a rough description of how we do this - it is not pretty
	 *
	 * - Grab all the pages that use the publication module and have a page_to_publication relationship (with the publication id)
	 * - Consider each publication id on each page
	 * - If the publication is issued, grab all news items along with relationships to published issues (handled in helper)
	 * - If the publication is not issued, grab all published news items (handled in helper)
	 * - Merge news items from each publication into a master array - build index of date_created
	 * - If there are news items in multiple places on the site, prefer the one that appears on the page deepest in the site
	 * - Create array from master array that consists of the correct number of most recent items
	 * - Loop through the items and augment with URLs and any other needed info that is not already present
	 *
	 * @todo consider alternative constructions to select all at once (performance enhancement)
	 * @return array augmented news item entities
	 * @access private;
	 */
	function &_build_items()
	{
		// grab all site publications
		$pages =& $this->_get_site_pages_with_valid_publications();
		foreach($pages as $k=>$v)
		{
			$page_to_pub_ids[$k] = $v->get_value('pub_id');
		}
		if (isset($page_to_pub_ids))
		{
			foreach ($page_to_pub_ids as $page_id => $pub_id)
			{
				$ph[$pub_id] = new publicationHelper($pub_id);
				$pub_items =& $ph[$pub_id]->get_published_items();
				foreach ($pub_items as $k=>$v)
				{
					// if this item is already in the raw_items array, check to see if the URL of the publication page
					// is shorter than what already is there. If so, continue to the next item.
					if (isset($raw_items[$k]))
					{
						if ($raw_items[$k]->has_value('url') == false)
						{
							$page =& $pages[$raw_items[$k]->get_value('page_id')];
							$raw_items[$k]->set_value('url', $this->get_item_url($k, $page));
						}
						$current_url = $raw_items[$k]->get_value('url');
						$pub_item_url = $this->get_item_url($k, $pages[$page_id]);
						if (strlen($pub_item_url) < strlen($current_url))
						{	
							continue;
						}
						else $pub_items[$k]->set_value('url', $pub_item_url);
					}
					$pub_items[$k]->set_value('pub_id', $pub_id);
					$pub_items[$k]->set_value('page_id', $page_id);
					$date = $v->get_value('datetime');
					$date_index[datetime_to_unix($date)] = $k;
					$raw_items[$k] =& $pub_items[$k];
				}
			}
			if ($raw_items)
			{
				krsort($date_index);
				$item_keys = (isset($this->num_to_display)) ? array_slice($date_index, 0, $this->num_to_display) : $date_index;
				foreach ($item_keys as $key)
				{
					$items[$key] =& $raw_items[$key];
					$pub_id = $items[$key]->get_value('pub_id');
					$page_id = $items[$key]->get_value('page_id');
					$this->augment_item($items[$key], $pages[$page_id], $ph[$pub_id]); // pass a reference to the item, its page, and its publication helper
				}
			}
		}
		else $items = false;
		$this->set_items($items);
		$this->_set_item_cache($items);
		return $items;
	}
	
	function get_item_url($k, &$page)
	{
		return reason_get_page_url($page) . '?story_id=' . $k;
	}
	
	function augment_item(&$item, &$page, &$pub_helper)
	{
		if (!$item->has_value('url'))
		{
			$url = $this->get_item_url($item->id(), $page);
			$item->set_value('url', $url);
		}
		if (isset($this->augment_item_handlers[$this->site->get_value('unique_name')]))
		{
			$method_name = $this->augment_item_handlers[$this->site->get_value('unique_name')];
			$this->$method_name($item, $page, $pub_helper);
		}
	}
	
	// grab all the publications on the site that are placed on a page owned by the site with page type publication that is NOT in related mode.
	function &_get_site_pages_with_valid_publications()
	{
		$rpts =& get_reason_page_types();
		$ms =& reason_get_module_sets();
		$publication_modules = $ms->get('publication_item_display');
		$page_types = $rpts->get_page_type_names_that_use_module($publication_modules);
		
		// this logic to exclude publication page types with related mode set to true is a bit silly.
		// perhaps we should have in the page types class something that lets us filter a set of page types according to parameter values or something
		foreach ($page_types as $page_type_name)
		{
			$pt = $rpts->get_page_type($page_type_name);
			$pt_props = $pt->get_properties();
			foreach ($pt_props as $region => $region_info)
			{
				if ( (in_array($region_info['module_name'], $publication_modules) && !(isset($region_info['module_params']['related_mode']) && ( ($region_info['module_params']['related_mode'] == "true") || ($region_info['module_params']['related_mode'] == true)))))
				{
					$valid_page_types[] = $page_type_name;
				}
			}
		}
		if (isset($valid_page_types))
		{
			// check each page type to make sure publication is NOT in related mode
			foreach (array_keys($valid_page_types) as $k) quote_walk($valid_page_types[$k], NULL);
			$es = new entity_selector($this->site->id());
			$es->add_type(id_of('minisite_page'));
			$es->limit_tables(array('page_node'));
			$es->limit_fields(array('custom_page'));
			$es->add_left_relationship_field('page_to_publication', 'entity', 'id', 'pub_id');
			$es->add_relation('page_node.custom_page IN ('.implode(",", $valid_page_types).')');
			$result = $es->run_one();
		}
		else $result = false;
		return $result;
	}

	/**
	 * @return array augmented news item entities.
	 */
	function &get_items()
	{
		if (!isset($this->_items))
		{
			if ($items =& $this->_get_items_from_cache())
			{
				$this->set_items($items);
			}
			elseif ($items =& $this->_build_items())
			{
				$this->set_items($items);
			}
			else
			{
				$items = false;
				$this->set_items($items);
			}
		}
		return $this->_items;
	}

	function set_items(&$items)
	{
		$this->_items =& $items;
	}
	
	function &_get_items_from_cache()
	{
		if ($this->get_cache_lifespan() > 0)
		{
			$item_cache = new ReasonObjectCache($this->get_cache_id(), $this->get_cache_lifespan());
			$items =& $item_cache->fetch();
		}
		else $items = false;
		return $items;
	}
	
	function _set_item_cache(&$items)
	{
		if ($this->get_cache_lifespan() > 0)
		{
			$item_cache = new ReasonObjectCache($this->get_cache_id(), $this->get_cache_lifespan());
			$item_cache->set($items);
			return true;
		}
		else return false;
	}

	/**
	 * Grab the feed that was created in create_feed
	 */
	function &get_feed()
	{
		return $this->feed;
	}

	/**
	 * @return int cache_lifespan
	 */
	function get_cache_lifespan()
	{
		return $this->cache_lifespan;
	}
	
	/**
	 * @return string cache_id
	 */
	function get_cache_id()
	{
		return md5('sitewide_news_feed_cache_site_' . $this->site->id() . '_type_' . $this->type->id());
	}

}

?>
