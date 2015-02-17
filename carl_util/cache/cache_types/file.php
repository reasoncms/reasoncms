<?php
include_once(CARL_UTIL_INC . 'cache/cache_types/default.php');
include_once(CARL_UTIL_INC . 'basic/filesystem.php');

/**
 *	Cache type that uses file system
 *
 *	@package carl_util
 * 	@subpackage cache
 *  @author Nathan White
 *  @todo more error checking - especially during file system transactions
 */

class FileObjectCache extends DefaultObjectCache
{	
	private $_cache_dir;
	private $_read_log = false;
	private $_write_log = true;
	
	function &fetch()
	{
		$ret = false;
		$cache_file = $this->_get_cache_file();
		$lifespan = $this->get_cache_lifespan();
		if (file_exists($cache_file))
		{
			$last_modified = filemtime($cache_file);
			$ret = (($lifespan == -1) || ((time() - $last_modified) < $lifespan) || $this->is_locked())
				   ? unserialize(file_get_contents($cache_file)) 
				   : false;
		}
		if ($ret && $this->_read_log) error_log('CACHE '.$this->get_cache_name().' read '.filesize($cache_file).' bytes ('.$_SERVER['REQUEST_URI'].")\n", 3, '/tmp/reason_cache.log');
		return $ret;
	}

	/**
	 * @return boolean true if data was written to filesystem, otherwise false.
	 */
	function set(&$object)
	{
		$cache_file = $this->_get_cache_file();
		if (!is_dir(dirname($cache_file))) mkdir_recursive(dirname($cache_file));
		$fh = fopen($cache_file,"w");
		flock($fh, LOCK_EX);
		$result = fwrite($fh, serialize($object));
		flock($fh, LOCK_UN);
		fclose($fh);
		if ($this->_write_log) error_log('CACHE '.$this->get_cache_name().' wrote '.filesize($cache_file).' bytes '.$_SERVER['REQUEST_URI'].")\n", 3, '/tmp/reason_cache.log');
		return ($result !== FALSE);
	}

	function clear()
	{
		$cache_file = $this->_get_cache_file();
		if(file_exists($cache_file)) return unlink( $cache_file );
	}	

	function lock($expire_seconds)
	{
		$lock_file = $this->_get_cache_file().'.lock';
		if (!is_dir(dirname($lock_file))) mkdir_recursive(dirname($lock_file));
		$fh = fopen($lock_file,"w");
		flock($fh, LOCK_EX);
		$result = fwrite($fh, time() + $expire_seconds);
		flock($fh, LOCK_UN);
		fclose($fh);
		return ($result !== FALSE);		
	}
	
	function unlock()
	{
		$lock_file = $this->_get_cache_file().'.lock';
		if(file_exists($lock_file)) return unlink( $lock_file );	
	}
	
	function is_locked()
	{
		$lock_file = $this->_get_cache_file().'.lock';
		if (file_exists($lock_file))
		{
			$expires = file_get_contents($lock_file);
			if ($expires > time())
			{
				return true;
			}
			else
			{
				$this->unlock();
			}
		} else {
		}
		return false;
	}
	
	function validate()
	{
		return $this->_check_directory();
	}

	/**
	 * The file object cache will accept these params:
	 *
	 * @return boolean success or failure
	 */
	function setup_params($params)
	{
		if (isset($params['cache_dir'])) $this->_cache_dir = $params['cache_dir'];
		return $this->_check_directory();
	}

	private function _check_directory()
	{
		$cache_dir = $this->_get_cache_dir();
		$dir_exists = file_exists($cache_dir);
		$is_readable = is_readable($cache_dir);
		$is_writable = is_writable($cache_dir);
		if (!$dir_exists) trigger_error('The cache directory ('.$cache_dir.') appears to not exist');
		elseif (!$is_readable && !$is_writable) trigger_error('The cache directory ('.$cache_dir.') exists, but cannot be read from or written to.');
		elseif (!$is_readable) trigger_error('The cache directory ('.$cache_dir.') exists, but cannot be read from.');
		elseif (!$is_writable) trigger_error('The cache_directory ('.$cache_dir.') exists, but cannot be written to.');
		return ($dir_exists && $is_writable && $is_readable);
	}
	
	// SUPPORT METHODS
	/**
	 * @return string cache_file
	 */	
	private function _get_cache_file()
	{
		$cache_id = $this->get_cache_id();
		$cache_dir = $this->_get_cache_dir();
		$slash_if_needed = (substr($cache_dir, -1, 1) == "/") ? "" : "/";
		$cache_sub_dir = substr($cache_id, 0, 2);
		$cache_file = ($cache_id) ? $cache_dir .$slash_if_needed.$cache_sub_dir.'/'.$cache_id.'.obj.cache' : false;
		return $cache_file;
	}
	
	private function _get_cache_dir()
	{
		if (isset($this->_cache_dir)) return $this->_cache_dir;
		elseif (defined("OBJECT_CACHE_DIR")) return OBJECT_CACHE_DIR;
		else return false;
	}
}
?>
