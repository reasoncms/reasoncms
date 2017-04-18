<?php
/**
 * include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once('classes/mvc.php');
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'classes/object_cache.php' );

/**
 * ReasonJSON is a JSON API that supports caching and chunking.
 *
 * It is used by TinyMCE but could be used by other things.
 *
 * @todo right now we transform the whole set even with caching off - think about this
 * @todo this now extends ReasonMVCModel, look to make it more flexible with regard to Reason types
 *
 * @author Andrew Bacon and Nathan White
 * @author Tom Brice
 */
abstract class ReasonJSON extends ReasonMVCModel
{
	var $requires_authentication = true;
	var $caching_enabled = true;
	var $response_status_code;

	/**
	 * Any subclass must provide a way to get the JSON it encapsulates
	 * @return string returns a JSON string
	 */
	abstract protected function get_json();

	/**
	 * builds the EntitySelector that is uses to get the collection of items for this model
	 * @return entity_selector the entity_selector tha can be used to get a collection of items
	 */
	protected function get_items_selector()
	{
		// default implementation is a no-op
	}

	/**
	 *	Sets the model up based on config() setting or query parameters
	 */
	function configure()
	{
		if (!$this->config('site_id')) {
			if (isset($_GET['site_id'])) $this->config('site_id', intval($_GET['site_id']));
		}
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

	function authorized()
	{
		return (reason_check_authentication());
	}

	/**
	 * @return entity_selector used to get the last modified item for this site and type
	 */
	function get_last_modified_item_selector()
	{
		$es = new entity_selector($this->config('site_id'));
		$es->add_type($this->config('type'));
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


	final function caching($caching = NULL)
	{
		return $this->config('caching');
	}

	/**
	 * @return string a cache key based on type, site_id and last mod
	 */
	final function get_cache_key()
	{
		return 'jsongen_' . $this->config('type') . '_' . $this->config('site_id') . '_' . $this->config('last_mod');
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

	/**
	 * @param $obj
	 * @param $collection_key
	 * @return array
	 */
	final function make_chunk($obj, $collection_key)
	{
		$chunk = Array();
		$chunk['count'] = isset($obj['count']) ? $obj['count'] : 0;
		$chunk['site_id'] = isset($obj['site_id']) ? $obj['site_id'] : null;
		if (isset($obj[$collection_key])) {
			$sliced = array_slice($obj[$collection_key], $this->config('offset'), $this->config('num'));
			$chunk[$collection_key] = $sliced;
		} else {
			$chunk[$collection_key] = null;
		}
		return $chunk;
	}
	
	protected function get_items($collection_key)
	{
		if (!isset($this->_items))
		{
			$items = $this->get_items_selector();
			if ($items = $items->run_one())
			{
				$this->_items['count'] = count($items);
				$this->_items['site_id'] = $this->config('site_id');
				foreach ($items as $k => $v)
				{
					$this->_items[$collection_key][] = $this->transform_item($v);
				}
			}
			else $this->_items['count'] = 0;
		}	
		return $this->_items;
	}

	/**
	 * Used to prepare objects from JSON encoding
	 * default implementation takes an object and transforms it into an associative array of its attributes
	 * @param $item
	 * @return array
	 */
	protected function transform_item($item)
	{
		return $this->object_to_array($item);
	}

	function object_to_array($object) {
		if(!is_object($object) && !is_array($object))
			return $object;

		return array_map(array($this, 'object_to_array'), (array) $object);
	}

	/**
	 * Provided data it will attempt to encode it as JSON. In the event of an JSON error it will
	 * return JSON with a 'status' of 500 and the error message as 'error'
	 * @param $data
	 * @return mixed|string
	 */
	final function encoded_json_from($data)
	{
		$json_data = $this->safe_json_encode($data);
//		$json_data = json_encode($data);
		$this->set_response_status_code('200');
		if (!$json_data) {
			// we have no data, encoding returned "false"
			$error_message = json_last_error_msg();
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					break;
				default:
					$this->set_response_status_code('500');
					// handle any JSON encoding error message and return "Internal Server Error" with more info
					$json_data = json_encode(array('status' => $this->get_response_status_code(), 'error' => 'JSON encoding error: ' . $error_message));
					break;
			}
		}
		return $json_data;
	}

	/**
	 * This function will handle encoding data into JSON. The data can contain chars that
	 * might not properly be utf8.
	 *
	 * @param $value
	 * @return mixed|string encoded JSON string
	 */
	final function safe_json_encode($value)
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

	/**
	 * @param $code string that represents the response code
	 */
	function set_response_status_code($code)
	{
		$this->response_status_code = $code;
	}

	/**
	 * @return string the response code
	 */
	function get_response_status_code()
	{
		return $this->response_status_code;
	}

}