<?php
/**
 * include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'classes/object_cache.php' );

/**
 * ReasonJSON is a JSON API that supports caching and chunking.
 *
 * It is used by TinyMCE but could be used by other things.
 *
 * @todo right now we transform the whole set even with caching off - think about this
 * @todo rework this into an abstract ReasonMVCModel that isn't so dependent on Reason types.
 *
 * @author Andrew Bacon and Nathan White
 */
class ReasonJSON
{
	var $requires_authentication = true;
	var $caching_enabled = true;

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
		$data = $this->make_chunk($items);
		$json_data = $this->safe_json_encode($data);
		if (!$json_data) {
			// we have no data, encoding returned "false"
			$error_message = json_last_error_msg();
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					break;
				default:
					// handle any JSON encoding error message and return "Internal Server Error" with more info
					$json_data = json_encode(array('status' => '500', 'error' => 'JSON encoding error: ' . $error_message));
					break;
			}
		}
		return $json_data;
	}

	function safe_json_encode($value)
	{
		$encoded = json_encode($value);
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				return $encoded;
			case JSON_ERROR_UTF8:
				$clean = $this->utf8ize($value);
				return $this->safe_json_encode($clean);
			default:
				return $encoded;

		}
	}

	final function utf8ize($mixed)
	{
		if (is_array($mixed)) {
			foreach ($mixed as $key => $value) {
				$mixed[$key] = $this->utf8ize($value);
			}
		} else if (is_string($mixed)) {
			return utf8_encode($mixed);
		}
		return $mixed;
	}
}