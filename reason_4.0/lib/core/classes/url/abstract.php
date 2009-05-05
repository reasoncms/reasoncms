<?php
/**
 * Reason Abstract URL class
 *
 * @package reason
 * @subpackage classes
 * @author Nathan White
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'basic/url_funcs.php');

/**
 * Reason URL
 * 
 * An abstract class to simplify getting correct URLs.
 * 
 * Notables:
 *
 * - supports multidomain installs
 * - static caching to improve speed
 *
 * @todo add persistent caching
 * @author Nathan White
 */
class reasonURL
{
	var $_id;
	var $_static_cache = true;
	
	function reasonURL($id = NULL)
	{
		if ($id) $this->set_id($id);
	}
	
	/**
	 * Returns an absolute url with our best guess at the appropriate protocol
	 * 
	 * @return string absolute url
	 */
	function get_url()
	{
		return false;
	}
	
	/**
	 * Returns an absolute url with the https protocol
	 *
	 * @return string absolute url
	 */
	function get_url_https()
	{
		return false;
	}
	
	/**
	 * Returns an absolute url with the http protocol
	 *
	 * @return string absolute url
	 */
	function get_url_http()
	{
		return false;
	}
	
	/**
	 * Returns an absolute url with the https (if available) or http protocol
	 *
	 * @return string absolute url
	 */
	function get_url_most_secure()
	{
		return false;
	}
	
	/**
	 * Returns the relative URL from the document root
	 * 
	 * @return string relative url
	 */
	function get_relative_url()
	{
		return false;
	}
	
	/**
	 * @return boolean whether or not to use static caching
	 */
	function use_static_cache()
	{
		return $this->_static_cache;
	}
	
	/**
	 * Disable static caching, which means any lookups will be "live", ignoring the values in the static cache
	 *
	 * Note that even if disabled, lookups will continue to build the static cache for other instances of the class
	 * that might be using it.
	 *
	 * @return null 
	 */
	function disable_static_cache()
	{
		$this->static_cache = false;
	}

	/**
	 * Enable static caching, which means any lookups will use values in the static cache where possible
	 *
	 * @return null 
	 */
	function enable_static_cache()
	{
		$this->static_cache = true;
	}

	function get_id()
	{
		return $this->_id;
	}
	
	function set_id($id)
	{
		$this->_id = $id;
	}
}