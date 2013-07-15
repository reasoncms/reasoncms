<?php
/**
 * include dependencies
 * TODO: require authentication.
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'function_libraries/image_tools.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'classes/object_cache.php' );

/**
 * ReasonJSON is just a stub that you can extend for some JSON-handling goodness.
 *
 * @todo implement as an api
 * @todo require authentication as appropriate
 * @todo right now we transform the whole set even with caching off - think about this
 *
 * @author Andrew Bacon and Nathan White
 **/
class ReasonJSON
{
	/**
	 * The constructor instantiates an entity and adds some
	 * defaults.
	 *
	 **/
	function __construct($type, $site_id)
	{
		if (empty($type) || empty($site_id))
		{
			trigger_error("A type or ID was not given for the json generator.", E_ERROR);
		}
		$this->type($type);
		$this->site_id($site_id);
	}

	function get_items_selector()
	{
		$es = new entity_selector($this->site_id());
		$es->add_type($this->type());
		$es->set_order('last_modified DESC');
		return $es;
	}
	
	function get_last_modified_item_selector()
	{
		$es = new entity_selector($this->site_id());
		$es->add_type($this->type());
		$es->limit_tables();
		$es->limit_fields('last_modified');
		$es->set_num(1);
		$es->set_order('last_modified DESC');
		return $es;
	}
	
	final function get_last_modified_item()
	{
		if (!isset($this->_last_modified_item))
		{
			$es = $this->get_last_modified_item_selector();
			if ($result = $es->run_one())
			{
				$this->_last_modified_item = array_shift($result);
			}
			else $this->_last_modified_item = false;
		}
		return $this->_last_modified_item;
	}

	final function type($type = NULL)
	{
		if ($type !== NULL) $this->_type = $type;
		return $this->_type;
	}
	
	final function site_id($site_id = NULL)
	{
		if ($site_id !== NULL) $this->_site_id = $site_id;
		return $this->_site_id;
	}

	final function num($num = NULL)
	{
		if ($num !== NULL) $this->_num = $num;
		return $this->_num;
	}

	final function offset($offset = NULL)
	{
		if ($offset !== NULL) $this->_offset = $offset;
		return $this->_offset;			
	}
	
	final function caching($caching = NULL)
	{
		if (isset($caching)) $this->_caching = $caching;
		return $this->_caching;
	}
	
	final function get_cache_key()
	{
		return 'jsongen_' . $this->type() . '_' . $this->site_id() . '_' . $this->last_mod();
	}
	
	final function cache($obj = NULL)
	{
		if ($obj === NULL && isset($this->_cache))
		{
			return $this->_cache;
		}
		$cache_key = $this->get_cache_key();
		$cache = new ReasonObjectCache($cache_key);
		if ($obj !== NULL) // request to cache
		{
			$cache->set($obj);
		}
		$this->_cache = $cache->fetch($cache_key);
		return $this->_cache;
	}

	final function last_mod($last_mod = NULL)
	{
		if ($last_mod !== NULL) $this->_last_mod = $last_mod;
		if ($last_mod === NULL)
		{
			if ($item = $this->get_last_modified_item())
			{
				$this->_last_mod = $item->get_value('last_modified');
			}
		}
		return $this->_last_mod;
	}

	final function make_chunk($obj)
	{
		$chunk = Array();
		$chunk['count'] = $obj['count'];
		$chunk['items'] = isset($obj['items']) ? array_slice($obj['items'], $this->offset(), $this->num()) : null;
		return $chunk;
	}
	
	final function get_items()
	{
		if (!isset($this->_items))
		{
			$items = $this->get_items_selector();
			if ($items = $items->run_one())
			{
				$this->_items['count'] = count($items);
				foreach ($items as $k => $v)
				{
					$this->_items['items'][] = $this->transform_item($v);
				}
			}
			else $this->_items['count'] = 0;
		}	
		return $this->_items;
	}
	
	final function run()
	{
		// if caching is off or the items are not yet in the cache.
		$items = ($this->caching()) ? $this->cache() : FALSE;
		if (!$items)
		{
			$items = $this->get_items();
			if ($this->caching()) $this->cache($items);
		}
		return json_encode($this->make_chunk($items));
	}
}

class ReasonImagesJSON extends ReasonJSON
{
	//function __construct($site, $type)
	//{
	//	parent::__construct($site, $type);
	//	$this->site_id($site);
	//	$this->type($type);
	//	if( !empty($_REQUEST['q']) )
	//	{
	//		$this->es->add_relation('(entity.name LIKE "%'.addslashes($_REQUEST['q']) . '%"' .
	//					      ' OR meta.description LIKE "%' . addslashes($_REQUEST['q']) . '%"'.
	//						  ' OR meta.keywords LIKE "%' . addslashes($_REQUEST['q']) . '%"'.
	//						  ' OR chunk.content LIKE "%' . addslashes($_REQUEST['q']) . '%"'.
	//						  ')');
	//	}
	//}

	/**
	 * This function should be overloaded in each new ReasonJSON type. It is the
	 * mapping of values from the Reason entities to the JSON object.
	 */
	function transform_item($v)
	 {
		$newArray = array();
		$newArray['id'] = $v->get_value('id');
		$newArray['name'] = $v->get_value('name');
		$newArray['description'] = $v->get_value('description');
		$newArray['pubDate'] = $v->get_value('creation_date');
		$newArray['lastMod'] = $v->get_value('last_modified');
		$newArray['link'] = 'http://' . REASON_HOST.WEB_PHOTOSTOCK . reason_format_image_filename($v->id(), $v->get_value('image_type'), 'standard');
		$newArray['thumbnail'] = 'http://' . REASON_HOST.WEB_PHOTOSTOCK . reason_format_image_filename($v->id(), $v->get_value('thumbnail_image_type'), 'thumbnail');
		$newArray['content'] = $v->get_value('content');
		$newArray['keywords'] = $v->get_value('keywords');
		return $newArray;
	}
}

if (!reason_check_authentication())
{
	http_response_code(403);
	echo json_encode(array("error" => 403));
}
elseif (isset($_GET['type']) && isset($_GET['site_id']))
{
	$type = turn_into_string($_GET['type']);
	$site_id = turn_into_int($_GET['site_id']);
	$last_mod = (isset($_GET['lastmod'])) ? $_GET['lastmod'] : false;
	if (id_of($type) == id_of('image'))
	{
		$reasonImagesJson = new ReasonImagesJSON(id_of($type), $site_id);
		$num = !empty($_REQUEST['num']) ? turn_into_int($_REQUEST['num']) : '500';
		$offset = !empty($_REQUEST['offset']) ? turn_into_int($_REQUEST['offset']) : '0';
		$reasonImagesJson->num($num);
		$reasonImagesJson->offset($offset);
		$reasonImagesJson->last_mod($last_mod);
		$reasonImagesJson->caching((isset($_GET['caching']))? turn_into_boolean($_GET['caching']) : true);
		print($reasonImagesJson->run());
	}
} 
else
{
	http_response_code(400);
	echo json_encode(array("error" => 400));
}
?>