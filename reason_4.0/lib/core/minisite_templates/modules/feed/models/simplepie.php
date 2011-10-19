<?php
/**
 * @package reason
 * @subpackage reason_feed_models
 */
 
/**
 * Include the base class
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );
include_once('simplepie/SimplePieAutoloader.php');

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
 * 
 * @todo support custom cache location
 *
 * @author Nathan White
 */
class ReasonSimplepieFeedModel extends ReasonMVCModel // implements ReasonFeedInterface
{
	var $config = array('cache_duration' => 6000); // defaults to 10 minutes - can be set with config.
	
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
			$simplepie->set_cache_location('/tmp/');
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