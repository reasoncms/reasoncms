<?php
/**
 * @package carl_util
 * @subpackage cache
 */

/**
 * Include dependencies
 */
require_once( 'paths.php');
require_once(CARL_UTIL_INC.'cache/object_cache.php');

/**
 * Defines the interface for object cache types - these should not be instantiated directly, but rather through the object_cache.php class.
 *
 * Any object cache type should define these methods.
 *
 * -fetch
 * -set
 * -clear
 * -validate
 *
 * Settings for an object cache type should be defined in SETTINGS_INC/object_cache_settings.php.
 *
 * A method setup_custom can be defined to provide additional setup based on settings in the object_cache_settings.php file
 *
 * A method setup_params can be defined to handle user params and is run by each instance of the cache type.
 * @package carl_util
 * @subpackage cache
 * @author Nathan White
 */

abstract class DefaultObjectCache
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
	 * @var string human-readable identifier for the cache
	 */
	var $cache_name;

	final function __construct($dumb_key, $settings = NULL)
	{
		if ($dumb_key == 'do_not_instantiate_me_directly')
		{
			if (isset($settings['constants']))
			{
				foreach ($settings['constants'] as $k=>$v) if (!defined($k)) define($k, $v);
			}
			if (isset($settings)) $this->setup_custom($settings);
			$this->validated = $this->validate();
		}
		else
		{
			trigger_warning('A cache type cannot be instantiated directly - instantiate an ObjectCache object, and set its type appropriately.', 1);
		}
	}
	
	/**
	 * Fetches an object or array from the cache
	 * @return mixed object/array from cache or false if it was not found
	 */	
	abstract public function &fetch();

	/**
	 * Saves an object or array to the cache
	 * @param object or array to cache
	 * @return boolean success or failure
	 */
	abstract public function set(&$object);
	
	/**
	 * Removes an object or array from the cache
	 * @return boolean success or failure
	 */
	abstract public function clear();

	/**
	 * Sets a lock on a cache to indicate that it's being modified
	 * @param How long the lock should be honored (seconds)
	 * @return boolean success or failure
	 */
	abstract public function lock($expire_seconds);

	/**
	 * Clears a lock on a cache
	 * @return boolean success or failure
	 */
	abstract public function unlock();

	/**
	 * Checks to see if a lock exists on a cache
	 * @return boolean
	 */
	abstract public function is_locked();

	/**
	 * Run once per page load to verify settings, constants, and the basic cache type setup.
	 *
	 * Note this runs before any user params are provided for a type.
	 *
	 * @return boolean true or false;
	 */
	abstract protected function validate();
	
	/**
	 * Runs once per page load - custom setup based upon the object_cache_settings settings for a type
	 * @param 
	 */
	protected function setup_custom($settings)
	{
	}
	
	/**
	 * Runs once for any instance of a cache - allows arbitrary user params for a cache type
	 */
	protected function setup_params($params)
	{	
	}
	
	/**
	 * @return int cache lifespan in seconds - -1 means forever
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
	 * @return string cache name
	 */
	 
	function get_cache_name()
	{
		return $this->cache_name;
	}
	
	/**
	 * @return true
	 */
	function set_cache_name($name)
	{
		$this->cache_name = $name;
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
