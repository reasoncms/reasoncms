<?php
include_once(CARL_UTIL_INC . 'cache/cache_types/default.php');

/**
 *	Cache type that uses memcached
 *
 *	@package carl_util
 * 	@subpackage cache
 *  @author Nathan White
 *  @todo support multiple servers
 */

class MemcachedObjectCache extends DefaultObjectCache
{	
	/**
	 * Fetches a cached object using memcached
	 */		
	function &fetch()
	{
		$cache_id = $this->get_cache_id();
		$lifespan = ($this->get_cache_lifespan()) ? $this->get_cache_lifespan() : 0;
		$memcached =& $this->_get_memcached_conn();	
		$ret = $memcached->get($cache_id, $lifespan);
		return $ret;
	}
	
	/**
	 * Saves a cache using memcached
	 */	
	function set(&$object)
	{
		$cache_id = $this->get_cache_id();
		$lifespan = ($this->get_cache_lifespan()) ? $this->get_cache_lifespan() : 0;
		$memcached =& $this->_get_memcached_conn();
		return $memcached->set($cache_id, $object, MEMCACHE_COMPRESSED, $lifespan);
	}

	/**
	 * Clear a cache using memcached
	 */	
	function clear()
	{
		$cache_id = $this->get_cache_id();
		$memcached =& $this->_get_memcached_conn();
		return $memcached->delete($cache_id);
	}
	
	// HELPER
	/**
	 * Returns a reference to the memcached connection
	 * @access private
	 * @return object memcache
	 */
	function &_get_memcached_conn()
	{
		$memcached = new Memcache;
		$memcached->connect(OBJECT_CACHE_MEMCACHED_SERVER, OBJECT_CACHE_MEMCACHED_PORT);
		return $memcached;
	}
	
	function validate()
	{
		$memcached_server_test = (defined('OBJECT_CACHE_MEMCACHED_SERVER') && OBJECT_CACHE_MEMCACHED_SERVER );
		$memcached_port_test = (defined('OBJECT_CACHE_MEMCACHED_PORT') && OBJECT_CACHE_MEMCACHED_PORT );
		if (!$memcached_server_test) trigger_error('You need to populate OBJECT_CACHE_MEMCACHED_SERVER in the memcached settings in object_cache_settings.php');
		if (!$memcached_port_test) trigger_error('You need to populate OBJECT_CACHE_MEMCACHED_PORT in the memcached settings in object_cache_settings.php');
		return ($memcached_server_test && $memcached_port_test);
	}
}
?>
