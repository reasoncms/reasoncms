<?php

/**
 * Defines the interface for object cache types
 *
 * Any object cache type should define these methods
 *
 * -fetch
 * -set
 * -clear
 * -validate
 *
 * A method setup_custom can be used to refer to additional settings for the type present in the object_cache_settings.php file
 *
 * @package carl_util
 * @subpackage cache
 * @author Nathan White
 */

class DefaultObjectCache
{

	/**
	 * @var int lifespan in cache in seconds;
	 */
	var $cache_lifespan = OBJECT_CACHE_DEFAULT_LIFESPAN;

	/**
	 * @var string unique identifier for the cache
	 */
	var $cache_id;

	/**
	 * Fetches an object or array from the cache
	 * @return mixed object/array from cache or false if it was not found
	 */	
	function &fetch()
	{
	}

	/**
	 * Saves an object or array to the cache
	 * @param object or array to cache
	 * @return boolean success or failure
	 */
	function set(&$object)
	{
	}
	
	/**
	 * Removes an object or array from the cache
	 * @return boolean success or failure
	 */
	function clear()
	{
	}
	
	/**
	 * Run once per page load to setup constants needed by the class
	 */
	function setup_constants(&$constants)
	{
		foreach ($constants as $k=>$v)
		{
			if (!defined($k)) define($k, $v);
		}
	}
	
	/**
	 * Run once per page load - custom setup based upon the object_cache_settings settings for a type
	 */
	function setup_custom(&$settings)
	{
	}
	
	/**
	 * Run once per page load to verify settings and the basic cache type setup
	 */
	function validate()
	{
		return true;
	}
	
	/**
	 * @return int cache lifespan in seconds
	 */
	function get_cache_lifespan()
	{
		return $this->cache_lifespan;
	}
	
	/**
	 * @return string cache id
	 */
	 
	function get_cache_id()
	{
		return $this->cache_id;
	}
	
	/**
	 * @return true
	 */
	function set_cache_id($hash)
	{
		$this->cache_id = $hash;
	}
	
	/**
	 * @return true
	 */	
	function set_cache_lifespan($seconds)
	{
		$this->cache_lifespan = $seconds;
	}
}
?>
