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
 * ReasonOebmedTwitterFeedModel returns the oEmbed JSON version of a single tweet from the Twitter API v1.1.
 *
 * By default, we use a 10 minute cache, and include the necessary javascript inline in the response from twitter.
 *
 * It requires twitter_api_settings.php to be populated with valid credentials.
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
			$omit_script = ($this->config('omit_script')) ? 'true' : 'false';
			$roc = new ReasonObjectCache('reason_oembed_twitter_id_'.$key.'_omit_'.$omit_script, $this->config('cache_duration'));
			$json = $roc->fetch();
			if ($json === FALSE) // nothing in the cache - lets get it
			{
				$obj = $this->get_oauth_object();
				$result = $obj->request('GET', $obj->url('1.1/statuses/oembed'), array('id' => $key, 'omit_script' => $omit_script));
				if ($result == '200')
				{
					$json = $obj->response['response'];
					$json = (!empty($json)) ? $json : '';
					$roc->set($json);
				}
				else // if we have something older in the cache lets refresh it and use it but trigger a warning.
				{
					$roc2 = new ReasonObjectCache('reason_oembed_twitter_id_'.$key.'_omit_'.$omit_script, -1);
					$json = $roc2->fetch();
					if ($json !== FALSE) // if we found something lets refresh the timer on the cache - perhaps twitter is down.
					{
						trigger_error('Using expired tweet for oembed id ' . $key . ' because the twitter API responded with code ' . $result . ' instead of giving us tweets');
						$roc->set($json);
					}
					else // we could have much more robust error messages here if we wanted.
					{
						trigger_error('No tweet available for oembed id ' . $key . '. The twitter API returned code ' . $result . ' - we will retry when we have a fresh cache interval.');
						$json = '';
						$roc->set($json);
					}
				}
			}
			return $json;
		}
		else
		{
			trigger_error('The ReasonOembedTwitterFeedModel must be provided with the configuration parameter tweet_id.', FATAL);
		}
	}
	

				
				
				
	/**
	 * Include appropriate settings files and create the thmOAuth object.
	 *
	 * @return object tmhOAuth object
	 */
	protected function get_oauth_object()
	{
		if (!isset($this->_oauth_obj))
		{
			require_once(SETTINGS_INC . 'twitter_api_settings.php');
			require_once(TMHOAUTH_INC . 'tmhOAuth.php');
			$this->_oauth_obj = new tmhOAuth(array(
				'consumer_key'    => TWITTER_API_CONSUMER_KEY,
				'consumer_secret' => TWITTER_API_CONSUMER_SECRET,
				'user_token'      => TWITTER_API_ACCESS_TOKEN,
				'user_secret'     => TWITTER_API_ACCESS_TOKEN_SECRET,
			));
		}
		return $this->_oauth_obj;
	}
}
?>