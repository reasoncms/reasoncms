<?php
/**
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include the base class
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );
include_once('simplepie/autoloader.php');

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_model_class_names' ][ reason_basename(__FILE__) ] = 'ReasonSimplepieFeedModel';
	
/**
 * ReasonSimplepieModel uses our configurables and returns a SimplePie object.
 *
 * User Configurables
 *
 * - url
 * - cache_duration
 * - cache_directory
 *
 * @author Nathan White
 */
class ReasonSimplepieFeedModel extends ReasonMVCModel // implements ReasonFeedInterface
{
	/**
	 * Sets a few configuration defaults
	 *
	 * - cache_duration - 600 seconds (10 minutes)
	 * - cache_directory - defaults to REASON_CACHE_DIR defined in reason_settings.php
	 */
	var $config = array('cache_duration' => 600, 'cache_directory' => REASON_CACHE_DIR);
	
	/**
	 * Make sure that the model is configured with a valid URL.
	 *
	 * @return mixed model data
	 */
	function build()
	{
		if ($url = $this->config('url'))
		{
			$simplepie = new SimplePie;
			$simplepie->set_feed_url($this->config('url'));
			$simplepie->set_cache_location($this->config('cache_directory'));
			$simplepie->set_cache_duration($this->config('cache_duration'));
			$simplepie->init();
			$simplepie->handle_content_type(); // is this needed?
			return $simplepie;
		}
		else
		{
			trigger_error('The ReasonSimplepieFeedModel must be configured with a feed url.', FATAL);
		}
	}
}
?>