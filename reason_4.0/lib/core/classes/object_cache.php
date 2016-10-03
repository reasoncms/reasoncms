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
	var $id;
	var $group_key = '';
	var $group_key_filename;
	var $error_msg = array();
	
	/**
	 * @param string $id unique identifier for cache object
	 * @param int $lifespan
	 */
	function ReasonObjectCache($id = '', $lifespan = '', $type = '', $group_key = '') // {{{
	{
		if ($group_key) $this->group_key = $group_key;
		
		if ($id)
		{
			$this->id = $id;
			$this->init($id, $lifespan, $type);
			
			if ($group_key) $this->add_to_group();
		}
	}
	
	function add_to_group()
	{
		// register this cache's $id against this $group_key
		if (@$cache_group = $this->_get_cached_group())
		{
			// if the pairing exists in the flat file, we're done
			if (in_array($this->id, $cache_group)) return;
			
			// if not, append this $id
			array_push($cache_group, $this->id);
		}
		else
			$cache_group = array($this->id); //start a new cache group
		
		$this->_set_cached_group($cache_group);
	}
	
	function clear_group()
	{
		// foreach cache $id registered for the given group's $key, $this->clear()
		if ($this->group_key)
		{
			if (@$cache_group = $this->_get_cached_group())
			{
				foreach ($cache_group as $id)
				{
					// clear each object in the group from the cache
					@$clear = new ReasonObjectCache($id);
					@$clear->clear();
				}
				$empty_group = array();
				$this->_set_cached_group($empty_group); // zero out the group
				
				if ($this->id)
				{
					// re-add the current cache request to the group, if there is one
					$this->add_to_group($this->id);
				}
				
				return true;
			}
			else
			{
				$this->error_msg[] = 'Could not retrieve cache group.';
				return false;
			}
		}
		$this->error_msg[] = 'Cache group was not set.';
		return false;
	}
	
	private function _get_group_key_filename()
	{
		if (isset($this->group_key_file)) return $this->group_key_filename;
		
		if ($this->group_key)
		{
			$cache_dir = $this->_get_cache_dir();
			$slash_if_needed = (substr($cache_dir, -1, 1) == "/") ? "" : "/";
			$this->group_key_filename = $cache_dir . $slash_if_needed . $this->group_key . '.obj.group';
			//echo $this->group_key_filename;
			return $this->group_key_filename;
		}
		else
		{
			$this->error_msg[] = 'Cache group was not set.';
			return null;
		}
	}
	
	private function _get_cached_group()
	{
		if (@$serialized_ids = file_get_contents($this->_get_group_key_filename()))
			return unserialize($serialized_ids);
		else
			return null;
	}
	
	private function _set_cached_group($cache_group)
	{
		//save the array in a flat file
		$cache_file = $this->_get_group_key_filename();
		if (!is_dir(dirname($cache_file))) mkdir_recursive(dirname($cache_file));
		if ($fh = fopen($cache_file, 'w'))
		{
			flock($fh, LOCK_EX);
			$result = fwrite($fh, serialize($cache_group));
			flock($fh, LOCK_UN);
			fclose($fh);
		}
	}
	
	private function _get_cache_dir()
	{
		if (isset($this->_cache_dir)) return $this->_cache_dir;
		elseif (defined("OBJECT_CACHE_DIR")) return OBJECT_CACHE_DIR;
		else return false;
	}
	
	// program me!!!
	function register_clear_condition()
	{
	}
}
?>
