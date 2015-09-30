<?php
/**
 * @package reason
 * @subpackage classes
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('minisite_templates/page_types.php');
reason_include_once('classes/page_types.php');

/**
 * When given a site_id and page_id, this class will check all modules on the page to see if they define a method clear_cache.
 *
 * Each module's clear_cache method will be invoked, and given the site_id and page_id as parameters.
 *
 * By default, the module will not rebuild the cache, but if rebuild_cache is enabled the page will be hit via CURL after the cache is cleared.
 *
 * The report will specify the modules for which clear_cache was run.
 *
 * @todo better reporting on success/failure (probably involves updates to ReasonObjectCache class)
 */
class PageCacheZap
{
	var $site_id;
	var $page_id;
	var $modules;
	var $rebuild_cache = false;
	var $report = array();
	
	function init($site_id = NULL, $page_id = NULL)
	{
		if ($site_id) $this->set_site_id($site_id);
		if ($page_id) $this->set_page_id($page_id);
		
		if ($this->get_site_id() && $this->get_page_id())
		{
			$this->set_modules_to_process();
		}
		else trigger_error('The Page Cache Zap class needs a site_id and page_id in order to init.');
	}
	
	function run()
	{
		if ($this->modules)
		{
			$keys = array_keys($this->modules);
			foreach ($keys as $module_key)
			{
				$this->clear_cache($module_key);
			}
			if ($this->rebuild_cache) $this->refresh_cache();
		}
	}
	
	function refresh_cache()
	{
		$url = reason_get_page_url($this->get_page());
		$result = get_reason_url_contents($url);
		if ($result) $this->report('Rebuilt the cache by hitting ' . $url);
	}
	
	function set_site_id($site_id)
	{
		$this->site_id = $site_id;
	}
	
	function set_page_id($page_id)
	{
		$this->page_id = $page_id;
	}

	function get_site_id()
	{
		return $this->site_id;
	}
	
	function get_page_id()
	{
		return $this->page_id;
	}
	
	function &get_page()
	{
		static $page;
		if (!isset($page))
		{
			$page = new entity($this->get_page_id());
		}
		return $page;
	}
	
	function set_modules_to_process()
	{
		$modules = false;
		$rpts =& get_reason_page_types();
		$page =& $this->get_page();
		$page_type_name = $page->get_value('custom_page');
		$page_type = $rpts->get_page_type($page_type_name);
		$regions = $page_type->get_region_names();
		
		foreach ($regions as $region)
		{
			$region_info = $page_type->get_region($region);
			if (!empty($region_info['module_name']) && !empty($region_info['module_filename']) && reason_file_exists($region_info['module_filename']))
			{
				reason_include_once($region_info['module_filename']);
				$module_class = $GLOBALS['_module_class_names'][$region_info['module_name']];
				$module_obj = new $module_class;
				if (method_exists($module_obj, 'clear_cache'))
				{
					$modules[$region_info['module_name']] = $module_obj; 
				}
			}
		}
		$this->modules = ($modules) ? $modules : false;
	}
	
	function clear_cache($module_key)
	{
		$this->modules[$module_key]->clear_cache($this->get_site_id(), $this->get_page_id());
		$this->report('Ran "clear_cache" for module ' . $module_key . ' (page_id = ' . $this->get_page_id() . ' and site_id = ' . $this->get_site_id() .')');
	}
	
	function report($string)
	{
		$this->report[] = $string;
	}
	
	function get_report()
	{
		return $this->report;
	}
}
?>
