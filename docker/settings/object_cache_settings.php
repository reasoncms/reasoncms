<?php
/**
 * Object Cache Type Settings - defines constants and paths for Cache Types
 *
 * To define settings for a new type, add an entry to the object_cache_types array
 * @author Nathan White
 * @package carl_util
 * @subpackage cache
 */

/**
 * OBJECT_CACHE_DEFAULT_TYPE
 *
 * Sets the type of cache you want to use by default
 *
 * - file
 * - db (requires some setup)
 * - memcached (requires some setup)
 *
 */
define( 'OBJECT_CACHE_DEFAULT_TYPE', 'file' );

/**
 * OBJECT_CACHE_DEFAULT_LIFESPAN
 *
 * How long cached objects should be valid when a lifespan is not explicitly passed (in seconds)
 */
define( 'OBJECT_CACHE_DEFAULT_LIFESPAN', 3600 );
 
class CacheTypeSettings
{
	/**
	 * @return array settings
	 */
	function &get_settings()
	{
		static $settings;
		if (!isset($settings))
		{
			$cache_dir = (defined('REASON_CACHE_DIR')) ? REASON_CACHE_DIR : '/tmp/';
			$settings = array ('file' => 	array('path' => CARL_UTIL_INC . 'cache/cache_types/file.php',
												  'constants' => array('OBJECT_CACHE_DIR' => $cache_dir)),
							 						 
							   'db' => 		array('path' => CARL_UTIL_INC . 'cache/cache_types/db.php',
							 	 	 			  'constants' => array('OBJECT_CACHE_DB_CONN' => '',
							 	 	 									  'OBJECT_CACHE_DB_TABLE' => '')),							 	 	 									  
							   
							   'memcached' => array('path' => CARL_UTIL_INC . 'cache/cache_types/memcached.php',
							 	 	 				'constants' => array('OBJECT_CACHE_MEMCACHED_SERVER' => '',
							 	 	 									  'OBJECT_CACHE_MEMCACHED_PORT' => '')));
		}
		return $settings;
	}
}
?>
