<?php
/**
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include the base class
 */
include_once( 'reason_header.php' );
include_once( CARL_UTIL_INC . 'basic/misc.php' );
reason_include_once( 'classes/mvc.php' );
reason_include_once( 'classes/object_cache.php' );

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_model_class_names' ][ reason_basename( __FILE__) ] = 'ReasonOembedTwitterFeedModel';
	
/**
 * ReasonOebmedTwitterFeedModel returns an oEmbed version of a single tweet.
 *
 * By default, we use a 10 minute cache, and include the necessary javascript inline in the response from twitter.
 *
 * User Configurables
 *
 * - tweet_id
 * - cache_duration
 * - omit_script
 *
 * @author Nathan White
 */
class ReasonOembedTwitterFeedModel extends ReasonMVCModel // implements ReasonFeedInterface
{
	/**
	 * Sets a few configuration defaults
	 *
	 * - cache_duration - 600 seconds (10 minutes)
	 * - omit_script = false
	 */
	var $config = array('cache_duration' => 600, 'omit_script' => false);
		
	/**
	 * Make sure that the model is configured with a valid URL.
	 *
	 * @return string json
	 */
	function build()
	{
		if ($id = $this->config('tweet_id'))
		{
			$key = (int) $id;
			$omit_script = ($this->config('omit_script')) ? '&omit_script=true' : '';
			$url = 'https://api.twitter.com/1/statuses/oembed.json?id='.$key.$omit_script;
			$cache = new ReasonObjectCache($url, $this->config('cache_duration'));
			$json = $cache->fetch();
			if (!$json)
			{
				$json = carl_util_get_url_contents($url);
				$cache->set($json);
			}
			return $json;
		}
		else
		{
			trigger_error('The ReasonOembedTwitterFeedModel must be provided with the configuration parameter tweet_id.', FATAL);
		}
	}
}
?>