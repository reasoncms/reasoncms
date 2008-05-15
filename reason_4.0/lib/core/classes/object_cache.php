<?php

include_once(CARL_UTIL_INC . 'cache/object_cache.php');

/**
 *	Reason Object Cache - Adds Reason specific functionality to carl_util/cache/object_cache.php
 * 
 *  Details:
 *  - adds method register_clear_condition
 *
 *  Sample usage - $obj will equal false or the cache object with id $unique_id that is not more than one hour old
 *
 *  <code>
 *  	$cache = new ReasonObjectCache();
 *  	$cache->init($unique_id);
 *		if ($obj =& $cache->fetch())
 *		{
 *			$cache->register_clear_condition($site_id, $type_id); // hmmmmm
 *		}
 *  </code>
 *
 *	@package reason
 * 	@subpackage classes
 *  @author Nathan White
 */

class ReasonObjectCache extends ObjectCache
{
	
	/**
	 * @param string $id unique identifier for cache object
	 * @param int $lifespan
	 */
	function ReasonObjectCache($id = '', $lifespan = '', $type = '') // {{{
	{
		if ($id) $this->init($id, $lifespan, $type);
	}
	
	// program me!!!
	function register_clear_condition()
	{
	}
}
?>
