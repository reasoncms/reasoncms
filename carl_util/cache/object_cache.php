<?php
/**
 * @package carl_util
 * @subpackage cache
 */

/**
 * Include dependencies
 */
require_once( 'paths.php');
require_once(SETTINGS_INC.'object_cache_settings.php');
require_once(CARL_UTIL_INC.'basic/misc.php');

/**
 *  Object cache system that fetches and sets serialized objects by id
 * 
 *  Details:
 *  - provides user configurable lifespan for cached objects
 *  - saves and fetches cached objects
 *  - supports creation of additional cache types
 *  - configure default caching type or reference custom caching types in object_cache_settings.php
 *
 *  Sample usage - $obj will equal false or the cache object with id $unique_id that is not more than one hour old
 *
 *  <code>
 *  	$cache = new ObjectCache();
 *  	$cache->init($unique_id, 3600);
 *  	$obj =& $cache->fetch();
 *  </code>
 *
 *  @author Nathan White
 *  @todo implement smart fallbacks so that alternate caching can be used if the specified type fails
 */

class ObjectCache
{		

	/**
	 * Supported options include file, db, and memcache
	 * @var string defines which cache type to use - defaults to file
	 */
	var $cache_type = OBJECT_CACHE_DEFAULT_TYPE;
	
	/**
	 * @var object cache type object
	 */
	var $_cache = false;
	
	/**
	 * @param string $id unique identifier for cache object
	 * @param int $lifespan time in seconds
	 */
	function ObjectCache($id = '', $lifespan = '', $type = '') // {{{
	{
		if ($id) $this->init($id, $lifespan, $type);
	} 
	
	function init($id = '', $lifespan = '', $type = '')
	{
		if (!empty($type)) $this->set_cache_type($type);
		$cache =& $this->set_cache();
		if ($id && $cache)
		{
			$cache->set_cache_id(md5($id));
			if ($lifespan) $cache->set_cache_lifespan($lifespan);
		}
		elseif (!$id) trigger_error('You must provide an id in order to init the cache');
	}

	function set_cache_type($type)
	{
		$cache =& $this->get_cache();
		if ($cache === false) $this->cache_type = $type;
		else
		{
			trigger_error('You cannot change the type after the cache has already been initialized - using type ' . $this->get_cache_type());
		}
	}
	
	function get_cache_type()
	{
		return $this->cache_type; 
	}
	
	function &set_cache()
	{
		if ($cache_class =& $this->get_cache_class())
		{
			$this->_cache = carl_clone($cache_class); // localize a clone of the cache_class
		}
		elseif ($cache_class === NULL) trigger_error('The cache type you requested (' . $this->get_cache_type() . ') in not defined in object_cache_settings.php');
		return $this->_cache;
	}
	
	/**
	 * Includes the cache_class file, returns a verified cache class
	 */
	function &get_cache_class()
	{
		static $settings;
		static $class;
		$type = $this->get_cache_type();
		if (!isset($class[$type]))
		{
			if (!isset($settings))
			{
				$cts = new CacheTypeSettings();
				$settings =& $cts->get_settings();
			}
			$is_defined = (isset($settings[$type]));
			$file_path = (isset($settings[$type]['path'])) ? ($settings[$type]['path']) : false;
			if ($is_defined && $file_path)
			{
				include_once($file_path);
				$cache_class_name[$type] = (isset($settings[$type]['classname'])) 
							   			 ? $settings[$type]['classname'] 
							   			 : ucfirst(basename($file_path, ".php")) .'ObjectCache';
							 	   
				// create an instance and run setup methods
				$cache = new $cache_class_name[$type];
				$cache->setup_constants($settings[$type]['constants']);
				$cache->setup_custom($settings[$type]);
			}
			if (!$is_defined) $class[$type] = NULL;
			elseif ($file_path && $cache->validate()) $class[$type] =& $cache;
			else $class[$type] = false;
		}
		return $class[$type];
	}
	
	function &get_cache()
	{
		return $this->_cache;
	}
	
	function &fetch()
	{
		$cache =& $this->get_cache();
		$result = ($cache) ? $cache->fetch() : false;
		return $result;
	}
	
	function set($object)
	{
		$cache =& $this->get_cache();
		$result = ($cache) ? $cache->set($object) : false;
		return $result;
	}
	
	function clear()
	{
		$cache =& $this->get_cache();
		$result = ($cache) ? $cache->clear() : false;
		return $result;
	}
}
?>
