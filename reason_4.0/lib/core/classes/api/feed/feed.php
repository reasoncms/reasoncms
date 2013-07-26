<?php
/**
 * Reason Feed API
 *
 * @package reason
 * @subpackage classes
 */
 
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'api/api.php');

/**
 * ReasonFeedAPI provides named feeds of JSON data.
 *
 * - using the "type" parameter, it looks for an appropriate model.
 * - it configures the model.
 * - it gets data from the model.
 * - it outputs the JSON result or 403 or 404 headers as appropriate.
 *
 * It does all its own request handling and sets its own content - and should be used like this.
 *
 * <code>
 * $feedapi = new ReasonFeedAPI();
 * $feedapi->run();
 * </code>
 *
 * For now, these are the possible return values:
 *
 * - 200 JSON feed as requested.
 * - 403 requires authentication.
 * - 404 feed not found.
 *
 * @todo consider auto loading of types instead of hard coding.
 *
 * @author Nathan White
 */
class ReasonFeedAPI extends CarlUtilAPI
{
	/**
	 * We support json and only json and do not allow format specification from userland.
	 */
	var $supported_content_types = array('json');

	/**
	 * Returns a model that implements the interface ReasonFeedModel
	 */
	var $model;
	
	/**
	 * Load and configure the appropriate model.
	 *
	 * Right now we hard code this - it could be made to dynamically look in the model folder.
	 */
	final function setup_api()
	{
		if (isset($_GET['type']))
		{
			if ($_GET['type'] === 'image')
			{
				reason_include_once('classes/api/feed/models/image.php');
				$model = new ReasonImageJSON();
				$this->set_model($model);
			}
			elseif ($_GET['type'] === 'siteList')
			{
				reason_include_once('classes/api/feed/models/link.php');
				$model = new ReasonSiteListJSON();
				$this->set_model($model);
			}
			elseif ($_GET['type'] === 'pageList')
			{
				reason_include_once('classes/api/feed/models/link.php');
				$model = new ReasonPageListJSON();
				$this->set_model($model);
			}
			elseif ($_GET['type'] == 'anchorList')
			{
				reason_include_once('classes/api/feed/models/link.php');
				$model = new ReasonAnchorListJSON();
				$this->set_model($model);
			}
		}
	}
	
	final function set_model($model)
	{
		$this->model = $model;
	}
	
	final function get_model()
	{
		return $this->model;
	}
	
	/**
	 * We set the content dynamically
	 */
	final function setup_content()
	{
		if ($model = $this->get_model()) // we need all three to be valid
		{
			if ($model->authorized())
			{
				if ($json = $model->get())
				{
					$this->set_content($json);
				}	
			}
			else
			{
				$this->set_http_response_code('403');
			}
		}
		else
		{
			$this->set_http_response_code('404');
		}
	}
}

interface ReasonFeedInterface
{
	function authorized();
	function get();
}