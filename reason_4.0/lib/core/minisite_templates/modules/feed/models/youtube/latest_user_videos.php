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
$GLOBALS[ '_reason_mvc_model_class_names' ][ reason_basename( __FILE__) ] = 'YouTubeLatestUserVideosFeedModel';
	
/**
 * YouTubeLatestUserVideosFeedModel returns links to the url and thumbnail for the last 25 videos YouTube user.
 *
 * By default, we use a 10 minute cache.
 *
 * User Configurables
 *
 * - user_id
 * - cache_duration
 *
 * @author Nathan White
 */
class YouTubeLatestUserVideosFeedModel extends ReasonMVCModel // implements ReasonFeedInterface
{
	/**
	 * Sets a few configuration defaults
	 *
	 * - cache_duration - 600 seconds (10 minutes)
	 */
	var $config = array('cache_duration' => 600);
		
	/**
	 * Make sure that the model is configured with a valid URL.
	 *
	 * @return string json
	 */
	function build()
	{
		if ($user_id = $this->config('user_id'))
		{
			$url = 'http://gdata.youtube.com/feeds/api/users/'.$user_id.'/uploads';
			$cache = new ReasonObjectCache($url, $this->config('cache_duration'));
			$result = $cache->fetch();
			if (!$result)
			{
				$xml = carl_util_get_url_contents($url);
				$sxml = simplexml_load_string($xml);
				if ($sxml)
				{
					foreach ($sxml->entry as $entry)
					{
						$full_id = $entry->id;
						$id = basename($full_id);
						$media = $entry->children('media', true);
						$url = (string)$media->group->player->attributes()->url;
						$thumbnail_url = (string)$media->group->thumbnail[0]->attributes()->url;
						$result[$id] = array('url' => $url, 'thumbnail' => $thumbnail_url);
					}
					$cache->set($result);
				}
				else
				{
					$cache = new ReasonObjectCache($url, -1);
					$result = $cache->fetch();
					if ($result)
					{
						trigger_error('Reusing expired version of user videos - the cache could not be refreshed using url ' . $url);
						$cache->set($result);
					}
				}
			}
			return $result;
		}
		else
		{
			trigger_error('The YouTubeLatestUserVideosFeedModel must be provided with the configuration parameter user_id.', FATAL);
		}
	}
}
?>