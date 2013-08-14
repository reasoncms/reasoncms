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
 * @todo rework this to be based on ReasonMVCModel so it is configurable via config() and not just the current URL.
 *
 * @author Nathan White
 */
class ReasonImageJSON extends ReasonJSON implements ReasonFeedInterface
{
	/** 
	 * Set myself up.
	 */
	function __construct()
	{
		$type = turn_into_string($_GET['type']);
		$site_id = turn_into_int($_GET['site_id']);
		$this->type(id_of('image'));
		$this->site_id($site_id);
		$last_mod = (isset($_GET['lastmod'])) ? $_GET['lastmod'] : false;
		$num = !empty($_REQUEST['num']) ? turn_into_int($_REQUEST['num']) : '500';
		$offset = !empty($_REQUEST['offset']) ? turn_into_int($_REQUEST['offset']) : '0';
		$this->num($num);
		$this->offset($offset);
		$this->last_mod($last_mod);
		$this->caching((isset($_GET['caching']))? turn_into_boolean($_GET['caching']) : true);	
	}
	
	function authorized()
	{
		return (reason_check_authentication());
	}
	
	function get()
	{
		return $this->run();
	}
	/**
	 * This function should be overloaded in each new ReasonJSON type. It is the
	 * mapping of values from the Reason entities to the JSON object.
	 */
	function transform_item($v)
	{
		$newArray = array();
		$newArray['id'] = $v->get_value('id');
		$newArray['name'] = $v->get_value('name');
		$newArray['description'] = $v->get_value('description');
		$newArray['pubDate'] = $v->get_value('creation_date');
		$newArray['lastMod'] = $v->get_value('last_modified');
		$newArray['link'] = '//' . REASON_HOST.WEB_PHOTOSTOCK . reason_format_image_filename($v->id(), $v->get_value('image_type'), 'standard');
		$newArray['thumbnail'] = '//' . REASON_HOST.WEB_PHOTOSTOCK . reason_format_image_filename($v->id(), $v->get_value('thumbnail_image_type'), 'thumbnail');
		$newArray['content'] = $v->get_value('content');
		$newArray['keywords'] = $v->get_value('keywords');
		return $newArray;
	}
}