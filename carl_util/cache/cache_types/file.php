<?php
include_once(CARL_UTIL_INC . 'cache/cache_types/default.php');

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
	function &fetch()
	{
		$ret = false;
		$cache_file = $this->_get_cache_file();
		$lifespan = $this->get_cache_lifespan();
		if (file_exists($cache_file))
		{
			$last_modified = filemtime($cache_file);
			$ret = (($lifespan == -1) || ((time() - $last_modified) < $lifespan))
				   ? unserialize(file_get_contents($cache_file)) 
				   : false;
		}
		return $ret;
	}

	function set(&$object)
	{
		$cache_file = $this->_get_cache_file();
		$fh = fopen($cache_file,"w");
		flock($fh, LOCK_EX);
		fwrite($fh, serialize($object));
		flock($fh, LOCK_UN);
		fclose($fh);
		return true;
	}

	function clear()
	{
		$cache_file = $this->_get_cache_file();
		if(file_exists($cache_file)) return unlink( $cache_file );
	}	
	
	// SUPPORT METHODS
	/**
	 * @return string cache_file
	 */	
	function _get_cache_file()
	{
		$cache_id = $this->get_cache_id();
		$slash_if_needed = (substr(OBJECT_CACHE_DIR, -1, 1) == "/") ? "" : "/";
		$cache_file = ($cache_id) ? OBJECT_CACHE_DIR .$slash_if_needed.$cache_id.'.obj.cache' : false;
		return $cache_file;
	}
	
	function validate()
	{
		$dir_exists = file_exists(OBJECT_CACHE_DIR);
		$is_readable = is_readable(OBJECT_CACHE_DIR);
		$is_writable = is_writable(OBJECT_CACHE_DIR);
		if (!$dir_exists) trigger_error('The OBJECT_CACHE_DIR constant ('.OBJECT_CACHE_DIR.') in object_cache_settings.php references a directory that appears to not exist');
		elseif (!$is_readable && !$is_writable) trigger_error('The OBJECT_CACHE_DIR constant ('.OBJECT_CACHE_DIR.') in object_cache_settings.php exists, but cannot be read from or written to.');
		elseif (!$is_readable) trigger_error('The OBJECT_CACHE_DIR constant ('.OBJECT_CACHE_DIR.') in object_cache_settings.php references a directory that exists, but cannot be read from.');
		elseif (!$is_writable) trigger_error('The OBJECT_CACHE_DIR constant ('.OBJECT_CACHE_DIR.') in object_cache_settings.php references a directory that exists, but cannot be written to.');
		return ($dir_exists && $is_writable && $is_readable);
	}
}
?>
