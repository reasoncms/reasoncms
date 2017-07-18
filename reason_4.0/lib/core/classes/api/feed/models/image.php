<?php
/**
 * Provides a feed of image data for a site.
 *
 * @package reason
 * @subpackage classes
 */

/**
 * Include the reason libraries & setup
 */
include_once('reason_header.php');
reason_include_once('classes/api/feed/models/reason_json.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/image_tools.php');

/**
 * Image JSON requires authentication and uses caching.
 *
 * It refreshes its cache any time the most recent last modified date of an image on the site changes.
 *
 * @author Nathan White
 * @author Tom Brice
 */
class ReasonImageJSON extends ReasonJSON implements ReasonFeedInterface
{
	function configure()
	{
		parent::configure();

		if (!$this->config('type')) {
			if (isset($_GET['type'])) $this->config('type', id_of($_GET['type']));
		}

		if (!$this->config('lastmod'))
		{
			$last_mod_value = false;
			if (isset($_GET['lastmod'])) {
				$last_mod_value = $_GET['lastmod'];
			}
			$this->config('lastmod', $last_mod_value);
		}
		if (!$this->config('num'))
		{
			$num_value = '500';
			if (isset($_GET['num'])) {
				$num_value = intval($_GET['num']);
			}
			$this->config('num', $num_value);
		}
		if (!$this->config('offset'))
		{
			$offset_value = '0';
			if (isset($_GET['offset'])) {
				$offset_value = intval($_GET['offset']);
			}
			$this->config('offset', $offset_value);
		}
		if (!$this->config('caching'))
		{
			$caching_value = true;
			if (isset($_GET['caching'])) {
				$caching_value = boolval($_GET['caching']);
			}
			$this->config('caching', $caching_value);
		}
	}

	function configured()
	{
		if ($site_id = $this->config('site_id'))
		{
			$site = new entity($site_id);
			if (reason_is_entity($site, 'site')) return true;
		}
		return false;
	}

	function authorized()
	{
		return (reason_check_authentication());
	}

	protected function get_json()
	{
		// if caching is off or the items are not yet in the cache.
		$items = ($this->caching()) ? $this->cache() : FALSE;
		if (!$items)
		{
			$items = $this->get_items('items');
			if ($this->caching()) $this->cache($items);
		}
		$data = $this->make_chunk($items, 'items');

		return $this->encoded_json_from($data);
	}

	protected function get_items_selector()
	{
		$es = new entity_selector($this->config('site_id'));
		$es->add_type($this->config('type'));
		$es->set_order('last_modified DESC');
		return $es;
	}

	/**
	 * This function should be overloaded in each new ReasonJSON type. It is the
	 * mapping of values from the Reason entities to the JSON object.
	 */
	protected function transform_item($v)
	{
		$newArray = array();
		$newArray['id'] = $v->get_value('id');
		$newArray['name'] = $v->get_value('name');
		$newArray['description'] = $v->get_value('description');
		$newArray['pubDate'] = $v->get_value('creation_date');
		$newArray['lastMod'] = $v->get_value('last_modified');
		$newArray['link'] = '//' . REASON_HOST.WEB_PHOTOSTOCK . reason_format_image_filename($v->id(), $v->get_value('image_type'), 'standard');
		$newArray['thumbnail'] = ($v->get_value('thumbnail_image_type')) ? '//' . REASON_HOST.WEB_PHOTOSTOCK . reason_format_image_filename($v->id(), $v->get_value('thumbnail_image_type'), 'thumbnail') : $newArray['link'];
		$newArray['content'] = $v->get_value('content');
		$newArray['keywords'] = $v->get_value('keywords');
		return $newArray;
	}
}