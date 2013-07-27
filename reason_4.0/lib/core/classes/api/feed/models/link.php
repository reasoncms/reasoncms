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
reason_include_once('classes/mvc.php');
reason_include_once('function_libraries/user_functions.php');


/**
 * ReasonSiteListJSON provides a list of Reason sites.
 *
 * @author Nathan White
 */
abstract class ReasonLinksJSON extends ReasonMVCModel
{
	function authorized()
	{
		return (reason_check_authentication());
	}
	
	function build()
	{
		$this->configure();
		if ($this->configured())
		{
			return $this->get_json();
		}
		else return FALSE;
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
 * @todo implement caching (based on latest last_modified date of a site)
 * @author Nathan White
 */
class ReasonSiteListJSON extends ReasonLinksJSON implements ReasonFeedInterface
{
	function configure()
	{
		if (!$this->config('site_id'))
		{
			if (isset($_GET['site_id'])) $this->config('site_id', intval($_GET['site_id']));
		}
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
	
	/**
	 * Get sites - exclude master_admin
	 */
	function get_json()
	{
		$site_id = $this->config('site_id');
		$site = new entity($site_id);
		$restrict_to_live = ($site->get_value('site_state') == 'Live');
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->limit_tables('site');
		$es->limit_fields('id', 'name');
		$es->add_relation('custom_url_handler = "" OR custom_url_handler IS NULL');
		$es->set_order('name ASC');
		if ($restrict_to_live) $es->add_relation('site_state = "Live"');
		if ($results = $es->run_one())
		{
			$sites['count'] = 0;
			foreach($results as $result)
			{
				$sites['sites'][] = array('name' => $result->get_value('name'), 'id' => $result->id());
				$sites['count']++;
			}
			return json_encode($sites);
		}
		else return json_encode(array('count' => 0, 'sites' => array()));
	}
}

/**
 * ReasonPageListJSON provides a list of page for a site.
 *
 * @author Nathan White
 */
class ReasonPageListJSON extends ReasonLinksJSON implements ReasonFeedInterface
{
	function __construct()
	{
		reason_include_once( 'classes/object_cache.php' );
		reason_include_once( 'minisite_templates/nav_classes/default.php' );
	}
	
	function configure()
	{
		if (!$this->config('site_id'))
		{
			if (isset($_GET['site_id'])) $this->config('site_id', intval($_GET['site_id']));
		}
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
	
	function get_json()
	{
		$pages = $this->get_pages();
		if ($id = $pages->root_node())
		{
			return json_encode($this->build_pages($id));
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
		$page['name'] = $pages->values[$id]->get_value('name');
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
			if ($result = $cache->fetch())
			{
				$this->_pages = (is_array($result)) ? reset($result) : $result;
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
 */
class ReasonAssetListJSON extends ReasonLinksJSON implements ReasonFeedInterface
{
	function __construct()
	{
		reason_include_once( 'function_libraries/asset_functions.php' );
	}
	
	function configure()
	{
		if (!$this->config('site_id'))
		{
			if (isset($_GET['site_id'])) $this->config('site_id', intval($_GET['site_id']));
		}
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
	
	function get_json()
	{
		if ($assets = $this->get_assets())
		{
			$site = new entity($this->config('site_id'));
			foreach($assets as $asset)
			{
				$asset_list['name'] = $asset->get_value('name');
				$asset_list['url'] = reason_get_asset_url($asset, $site);
			}
			return json_encode($asset_list);
		}
		else return '{}';
	}
	
	function get_assets()
	{
		if (!isset($this->_assets))
		{
			$es = new entity_selector($this->config('site_id'));
			$es->add_type(id_of('asset'));
			$es->limit_tables();
			$es->limit_fields('name');
			$this->_assets = $es->run_one();
		}
		return $this->_assets;
	}
}