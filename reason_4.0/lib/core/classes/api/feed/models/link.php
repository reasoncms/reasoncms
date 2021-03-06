<?php
/**
 * Provides various feeds for the reasonlink tool.
 *
 * @package reason
 * @subpackage classes
 */

/**
 * Include the reason libraries & setup
 */
include_once('reason_header.php');
reason_include_once('classes/api/feed/models/reason_json.php');
reason_include_once('function_libraries/user_functions.php');

/**
 * ReasonLinkTypeListJSON provides a list of what link feeds a particular site makes available.
 *
 * Right now we support just assets and pages. All sites provide a page feed. If the site owns or borrows
 * assets then we provide the asset feed as well.
 * 
 * @author Nathan White
 * @author Tom Brice
 */
class ReasonLinkTypeListJSON extends ReasonJSON implements ReasonFeedInterface
{

	function configured()
	{
		if ($site_id = $this->config('site_id'))
		{
			$site = new entity($site_id);
			if (reason_is_entity($site, 'site')) return true;
		}
		return false;
	}

	protected function get_items_selector()
	{
		$es = new entity_selector();
		$es->add_type(id_of('asset'));
		$es->limit_tables();
		$es->limit_fields();
		return $es;
	}

	protected function get_json()
	{
		$feeds['pages'] = 'pageList';
		$es = $this->get_items_selector();
		if ($results = $es->run_one())
		{
			$feeds['assets'] = 'assetList';
		}
		return $this->encoded_json_from($feeds);
	}
}

/**
 * ReasonSiteListJSON provides a list of Reason sites.
 *
 * Requires a site_id - checks "liveness" and uses these rules in making the list.
 *
 * - a non-live site gets the live and non-live site list.
 * - a live site gets the live site list.
 *
 * @todo implement caching (based on latest last_modified date of a site) -see ReasonImageJSON on how to implement
 * @author Nathan White
 */
class ReasonSiteListJSON extends ReasonJSON implements ReasonFeedInterface
{
	private $collection_key = 'sites';

	function configured()
	{
		if ($site_id = $this->config('site_id'))
		{
			$site = new entity($site_id);
			if (reason_is_entity($site, 'site')) return true;
		}
		return false;
	}

	protected function get_items_selector()
	{
		$site_id = $this->config('site_id');
		$site = new entity($site_id);
		$restrict_to_live = ($site->get_value('site_state') == 'Live');
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->limit_tables('site');
		$es->limit_fields('id', 'name');
		$es->add_relation('( (custom_url_handler = "") OR (custom_url_handler IS NULL) )');
		$es->set_order('name ASC');
		if ($restrict_to_live) $es->add_relation('site_state = "Live"');

		return $es;
	}

	protected function transform_item($item)
	{
		return array('name' => strip_tags($item->get_value('name')), 'id' => $item->id());
	}

	/**
	 * Get sites - exclude master_admin
	 */
	protected function get_json()
	{
		$items = $this->get_items($this->collection_key);

		$data = $this->make_chunk($items, $this->collection_key);

		return $this->encoded_json_from($data);
	}


}

/**
 * ReasonPageListJSON provides a list of page for a site.
 *
 * @author Nathan White
 */
class ReasonPageListJSON extends ReasonJSON implements ReasonFeedInterface
{
	function __construct()
	{
		reason_include_once( 'classes/object_cache.php' );
		reason_include_once( 'minisite_templates/nav_classes/default.php' );
	}

	function configured()
	{
		if ($site_id = $this->config('site_id'))
		{
			$site = new entity($site_id);
			if (reason_is_entity($site, 'site')) return true;
		}
		return false;
	}

	protected function get_json()
	{
		$pages = $this->get_pages();
		if ($id = $pages->root_node())
		{
			return $this->encoded_json_from($this->build_pages($id));
		}
		else return '{}';
	}
	
	/**
	 * Recursive function to build an array of the specific page info we want.
	 */
	function build_pages($id)
	{
		$pages = $this->get_pages();
		$children = $pages->children($id);
		$page['url'] = $pages->get_full_url($id);
		$page['name'] = strip_tags($pages->values[$id]->get_value('name'));
		$page['id'] = $id;
		if ($children)
		{
			foreach ($children as $id)
			{
				$child_pages[] = $this->build_pages($id);
			}
			$page['pages'] = $child_pages;
		}
		return $page;
	}
	
	/**
	 * Before we build a page tree, try to fetch a cached version which should usually be available.
	 *
	 * @todo should this build a cache if it wasn't found ... maybe implement later.
	 */
	function get_pages()
	{
		if (!isset($this->_pages))
		{
			$site_id = $this->config('site_id');
			$cache = new ReasonObjectCache($site_id . '_navigation_cache', -1);
			if ( ($result = $cache->fetch()) && isset($result['MinisiteNavigation']) )
			{
				$this->_pages = reset($result);
			}
			else
			{
				$site = new entity($site_id);
				$this->_pages = new MinisiteNavigation();
				$this->_pages->site_info = $site;
				$this->_pages->order_by = 'sortable.sort_order';
				$this->_pages->init( $site_id, id_of('minisite_page') );
			}
		}
		return $this->_pages;
	}
}

/**
 * ReasonAssetListJSON provides a list of assets on a site.
 *
 * @todo consider whether to not show assets that are behind authentication.
 *
 * @author Nathan White
 * @author Andrew Collins
 */
class ReasonAssetListJSON extends ReasonJSON implements ReasonFeedInterface
{
	private $collection_key = 'assets';

	function __construct()
	{
		reason_include_once( 'function_libraries/asset_functions.php' );
	}
	
	function configured()
	{
		if ( ($site_id = $this->config('site_id')) )
		{
			$site = new entity($site_id);
			if ( reason_is_entity($site, 'site') )return true;
		}
		return false;
	}

	protected function transform_item($asset)
	{
		$site = new entity($this->config('site_id'));
		return array(
			'name' => strip_tags($asset->get_value('name')),
			'url' => reason_get_asset_url($asset, $site),
			'id' => strip_tags($asset->get_value('id')),
			);
	}

	protected function get_json()
	{
		$assets = $this->get_items($this->collection_key);

		$data = $this->make_chunk($assets, $this->collection_key);

		return $this->encoded_json_from($data);
	}

	protected function get_items_selector()
	{
		$es = new entity_selector($this->config('site_id'));
		$es->add_type(id_of('asset'));
		$es->set_order('entity.name ASC');
		$es->limit_tables();
		$es->limit_fields('name');
		return $es;
	}
}
