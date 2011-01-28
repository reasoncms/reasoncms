<?php
/**
 * ReasonPageCache Class
 * @package reason
 * @subpackage classes
 */

/**
 * Include the reason libraries
 */
include_once('reason_header.php');

/**
 * Include the carl util page cache class
 */
include_once( CARL_UTIL_INC . 'cache/cache.php' );
include_once( CARL_UTIL_INC . 'basic/filesystem.php' );

/**
 * Reason Page Cache
 *
 * Extends carl_util PageCache class to allow some additional functionality for Reason Pages:
 *
 * - Store pages in site / page folders
 * - Retrieve pages from site / page folders
 * - Methods to delete cache for page / site
 * - Uses REASON_CACHE_DIR by default
 *
 * Usage examples - basically just like the original except we have to set site_id and page_id:
 *
 * Note that fetch, store, and clear only will produce expected results if the site_id and page_id are correctly set.
 *
 * Fetch
 *
 * <code>
 * $cache = new ReasonPageCache();
 * $cache->set_site_id(2342);
 * $cache->set_page_id(632342);
 * $page = $cache->fetch( get_current_url() );
 * </code>
 *
 * Store
 *
 * <code>
 * $cache = new ReasonPageCache();
 * $cache->set_site_id(2342);
 * $cache->set_page_id(632342);
 * $cache->set_page_generation_time($page_gen_time);
 * $cache->store( get_current_url(), $page );
 * </code>
 * 
 * @author Nathan White
 */
class ReasonPageCache extends PageCache
{
	private $page_id = false;
	private $site_id = false;
	
	public function __construct( $site_id = false, $page_id = false)
	{
		if ($site_id) $this->set_site_id($site_id);
		if ($page_id) $this->set_page_id($page_id);
		$this->set_cache_dir(REASON_CACHE_DIR);
	}
	
	public function set_page_id($page_id)
	{
		$this->page_id = (int) $page_id;
	}
	
	public function set_site_id($site_id)
	{
		$this->site_id = (int) $site_id;
	}
	
	public function get_page_id()
	{
		return $this->page_id;
	}
	
	public function get_site_id()
	{
		return $this->site_id;
	}

	/**
	 * Fetch requires a site_id and page_id
	 *
	 * @return string
	 */
	public function fetch($key)
	{
		if ($this->get_site_id() && $this->get_page_id())
		{
			return parent::fetch($key);
		}
		else trigger_error('ReasonPageCache needs to be given a site id and page id in order to fetch a cached page	.');
		return '';
	}
	
	/**
	 * Requires a site id and page id - create a folder for the page if needed
	 * @return void
	 */
	public function store($key, $cache_val)
	{
		if ($this->get_site_id() && $this->get_page_id())
		{
			if (!$this->page_cache_exists()) $this->_create_page_cache_directory();
			parent::store($key, $cache_val);
		}
		else trigger_error('ReasonPageCache needs to be given a site id and page id in order to cache a page.');
	}

	/**
	 * Clear a cache
	 * @return void
	 */
	public function clear($key)
	{
		if ($this->get_site_id() && $this->get_page_id())
		{
			parent::clear($key);
			if ($this->page_cache_exists() && $this->page_cache_is_empty()) $this->delete_page_cache();
		}
		else trigger_error('ReasonPageCache needs to be given a site id and page id in order to clear a cached page.');
		return false;
	}

	/**
	 * We check for existence by seeing a site cache directory exists in the filesystem.
	 */
	public function site_cache_exists()
	{
		if ($site_cache_dir = $this->get_site_cache_directory())
		{
			return (is_dir($site_cache_dir));
		}
		else trigger_error('ReasonPageCache needs to be given a site id to check if the cache exists for a site.');
		return false;
	}
	
	/**
	 * We disable a site cache by deleting its site folder (and all contents) in the filesystem.
	 * 
	 * @return boolean success or failure
	 */
	public function delete_site_cache()
	{
		if ($this->site_cache_exists())
		{
			return $this->_recursive_rmdir( $this->get_site_cache_directory() );
		}
		else trigger_error('ReasonPageCache cannot delete the site cache because a site cache directory does not exist.');
		return false;
	}

	/**
	 * @return boolean
	 */
	public function page_cache_exists()
	{
		if ($page_cache_dir = $this->get_page_cache_directory())
		{
			return (is_dir($page_cache_dir));	
		}
		else trigger_error('ReasonPageCache needs to be given a site id and page id to check if the page cache directory exists for a page.');
		return false;
	}
	
	/**
	 * @return boolean
	 */
	public function page_cache_is_empty()
	{
		if ($this->page_cache_exists())
		{
			$dir = $this->get_page_cache_directory();
			return (($files = @scandir($dir)) && count($files) <= 2);
		}
		else trigger_error('ReasonPageCache error - page_cache_is_empty will return true because the page does not have a cache directory and its emptiness could not be checked.');
		return true;
	}
	
	/**
	 * Clearing a page cache clears all versions of that page that are cached.
	 * 
	 * @return boolean
	 */
	public function delete_page_cache()
	{
		if ($this->page_cache_exists())
		{
			return $this->_recursive_rmdir( $this->get_page_cache_directory() );
		}
		else trigger_error('ReasonPageCache cannot delete the page cache because the page does not have a cache directory.');
		return false;
	}
	
	/**
	 * We want to add to the base directory the site and page parameters.
	 */
	protected function get_dir()
	{
		if ($this->get_site_id() && $this->get_page_id())
		{
			return $this->get_page_cache_directory();
		}
		return false;
	}
	
	/**
	 * @return mixed string or false if it cannot be determined
	 */
	public function get_site_cache_directory()
	{
		if ($this->get_site_id())
		{
			return $this->get_cache_dir() . $this->get_site_id() . '/';
		}
		else trigger_error('ReasonPageCache cannot get the site cache directory because the site id is not set.');
		return false;
	}

	/**
	 * @return mixed string or false if it cannot be determined
	 */	
	public function get_page_cache_directory()
	{
		if ($this->get_site_id() && $this->get_page_id())
		{
			return $this->get_site_cache_directory() . $this->get_page_id() . '/';
		}
		else trigger_error('ReasonPageCache cannot get the page cache directory - the site id and page id must both be set.');
		return false;
	}

	/** 
	 * 
	 */
	private function _create_page_cache_directory()
	{
		if (!$this->page_cache_exists())
		{
			return mkdir_recursive( $this->get_page_cache_directory() );	
		}
		return false;
	}
	
	/**
	 * 
	 */
	private function _create_site_cache_directory()
	{
		if ($site_id = $this->get_site_id() && $page_id = $this->get_page_id())
		{
			if (!is_dir($this->get_site_cache_directory())) return mkdir_recursive($this->get_site_cache_directory());	
		}
		return false;
	}
	
	/**
	 * A cache specific implementation that makes sure our directory names are all numeric and files end in .cache
	 */
	private function _recursive_rmdir( $dir )
	{
		$dir = "/".trim_slashes($dir)."/";
		if (is_dir($dir))
		{
			$objects = scandir($dir);
			foreach ($objects as $object)
			{
				if ( !empty($object) && $object != "." && $object != ".." )
				{
					$full_path_obj = $dir.$object;
					if (ctype_digit($object) && (filetype($full_path_obj) == "dir")) $this->_recursive_rmdir($full_path_obj.'/');
					elseif (substr($full_path_obj, -6) == '.cache') // we only delete files that end in .cache
					{
						@unlink($full_path_obj);
					}
					else
					{
						trigger_error('ReasonPageCache cannot unlink file ('.$full_path_obj.') - for safety we will only delete files with a .cache extension');
					}
				}
			}
			reset($objects);
			return @rmdir($dir);
		}
	}
}
?>