<?php

/**
 *	Super basic object cache system that fetches and sets serialized objects by id
 * 
 *  Details:
 *  - provides user configurable oldest last_modified_date for cached objects
 *  - provides user configurable lifespan for cached objects
 *  - saves and fetches objects in the reason cache directory
 *
 *  Sample usage - $obj will equal false or the cache object with id $unique_id that is not more than one hour old
 *
 *  <code>
 *  	$cache = new ReasonObjectCache();
 *  	$cache->init($unique_id, 3600);
 *  	$obj =& $cache->fetch();
 *  </code>
 *
 *	@package Reason_Core
 *  @author Nathan White
 *  @todo more error checking - especially during file system transactions
 *  @todo option to save in database instead of file system
 *  @todo option for compression of saved cache objects
 */

class ReasonObjectCache
{
	/**
	 * @var string file system pathname to cache file
	 * @access private
	 */
	var $_cache_file;
	
	/**
	 * @var int lifespan in cache in seconds - defaults to 3600
	 */
	var $lifespan = 3600;
	
	/**
	 * @var int timestamp which describes last possible filemtime for cache object
	 */
	var $max_last_modified;
	
	/**
	 * @param string $id unique identifier for cache object
	 * @param int $lifespan
	 * @param int $max_last_modified unix timestamp
	 */
	function ReasonObjectCache($id = '', $lifespan = '', $max_last_modified = '') // {{{
	{
		if ($id) $this->init($id, $lifespan, $max_last_modified);
	} 
	
	/**
	 * @param string $id unique identifier for cache object
	 * @param int $lifespan
	 * @param int $max_last_modified unix timestamp
	 */
	function init($id, $lifespan = '', $max_last_modified = '')
	{
		$this->_cache_file = REASON_CACHE_DIR .'/' . md5($id).'.obj.cache';
		if ($lifespan) $this->set_lifespan($lifespan);
		if ($max_last_modified) $this->set_max_last_modified($max_last_modified);
	}
	
	/**
	 * Fetches an object from the cache directory
	 * @return mixed cached object or false
	 */
	function &fetch()
	{
		$ret = false;
		if (empty($this->_cache_file))
		{
			trigger_error('ReasonObjectCache Error - You must set the cache id in the constructor or the init function before calling fetch');
		}
		elseif (file_exists($this->_cache_file))
		{
			$last_modified = filemtime($this->_cache_file);
			$expired = (!empty($this->max_last_modified)) ? $this->max_last_modified > $last_modified : false;
			$ret = (((time() - filemtime($this->_cache_file)) < $this->lifespan) && !$expired) 
				   ? unserialize(file_get_contents($this->_cache_file)) 
				   : false;
		}
		return $ret;
	}
	
	/**
	 * Writes an object into the cache directory
	 * @param object or array to cache
	 */
	function set($object)
	{
		if (empty($this->_cache_file))
		{
			trigger_error('ReasonObjectCache Error - you must set the cache id in the constructor or the init function before calling set');
		}
		else
		{
			$fh = fopen($this->_cache_file,"w");
			flock($fh, LOCK_EX);
			fwrite($fh, serialize($object));
			flock($fh, LOCK_UN);
			fclose($fh);
		}
	}
	
	/**
	 * @param int $seconds
	 */
	function set_lifespan($seconds)
	{
		$this->lifespan = $seconds;
	}
	
	/**
	 * @param int $timestamp
	 */
	function set_max_last_modified($timestamp)
	{
		$this->max_last_modified = $timestamp;
	}
	
	/**
	 * Removes the cached object from filesystem
	 * @return boolean
	 */
	function clear()
	{
		if (empty($this->_cache_file))
		{
			trigger_error('ReasonObjectCache Error - you must set the cache id in the constructor or the init function before calling clear');
		}
		if(file_exists($this->_cache_file)) 
			return unlink( $this->_cache_file );
	}
}

?>
