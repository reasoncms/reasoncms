<?php
/**
 * Clear Cache Administrative Module
 *
 * Deletes all page caches for a site
 *
 * @package reason_package
 * @subpackage admin
 * @author Nathan White
 */
reason_include_once('classes/admin/modules/default.php');
reason_include_once('classes/page_cache.php');

/**
 * This admin module allows users to clear all the page caches for a site.
 *
 * @todo implement a web service mode that returns status as json
 */
class ReasonClearCacheModule extends DefaultModule // {{{
{	
	function has_access()
	{
		if (!isset($this->_has_access))
		{
			$this->_has_access = (!empty($this->admin_page->site_id)) && reason_user_has_privs($this->admin_page->user_id, 'edit');
		}
		return $this->_has_access;
	}
	
	function get_page_cache_object()
	{
		if (!isset($this->_page_cache_obj))
		{
			$this->_page_cache_obj = new ReasonPageCache($this->admin_page->site_id);
		}
		return $this->_page_cache_obj;
	}
	
	function run()
	{
		if ($this->has_access())
		{
			echo '<h4>Clearing all page caches on the site</h4>';
			$cache_obj = $this->get_page_cache_object();
			if ($cache_obj->site_cache_exists())
			{
				$deleted = $cache_obj->delete_site_cache();
				if ($deleted) echo '<p>All caches successfully cleared.</p>';
				else echo '<p>The caches could not be cleared due to an error.</p>';
			}
			else
			{
				echo '<p>No caches exist for the site - nothing to do.</p>';
			}
		}
		else
		{
			echo '<h4>Access Denied</h4>';
			if (!empty($this->admin_page->site_id)) echo '<p>You don\t have the privileges to manage caches on this site.</p>';
			else echo '<p>This module must be run within the context of a site.</p>';
		}
	}
}
?>
