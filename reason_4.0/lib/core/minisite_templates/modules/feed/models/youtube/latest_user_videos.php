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
require_once( SETTINGS_INC . 'google_api_settings.php' );
require_once( GOOGLE_API_INC . 'Google_Client.php' );
require_once( GOOGLE_API_INC . 'contrib/Google_YoutubeService.php' );

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_model_class_names' ][ reason_basename( __FILE__) ] = 'YouTubeLatestUserVideosFeedModel';
	
/**
 * YouTubeLatestUserVideosFeedModel returns links to the url and thumbnail for the last 25 videos YouTube user.
 *
 * By default, we use a 10 minute cache.
 *
 * User Configurable settings
 *
 * - user_id
 * - cache_duration
 *
 * @author Nathan White
 */
class YouTubeLatestUserVideosFeedModel extends ReasonMVCModel
{
	/**
	 * Sets a few configuration defaults
	 *
	 * - cache_duration - 600 seconds (10 minutes)
	 */
	var $config = array('cache_duration' => 600);

	/**
	 * Make sure that the model is configured with a valid youtube user ID / channel ID.
     * You will also likely need an API key (set as YOUTUBE_DEVELOPER_KEY) from the Google Cloud Console.
	 *
	 */
	function build()
	{
		if ($user_id = $this->config('user_id'))
		{
            $client = new Google_Client();

            /*
             * Set YOUTUBE_DEVELOPER_KEY to the "API key" value from the "Access" tab of the
             * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
             * Please ensure that you have enabled the YouTube Data API for your project.
             */

            if (!empty(YOUTUBE_DEVELOPER_KEY)) {
                $client->setDeveloperKey(YOUTUBE_DEVELOPER_KEY);
            }

            // Define an object that will be used to make all API requests.
            $youtube = new Google_YoutubeService($client);

            $cacheKey = 'youtube_'.$user_id;
			$cache = new ReasonObjectCache($cacheKey, $this->config('cache_duration'));
			$result = $cache->fetch();

			if (!$result)
			{
                try {
                    // first try to get a channel_id from the supplied username
                    // if a channel_id is supplied this is wasted work but there is no good way to
                    // know if the $user_id is a channel_id or something else...
                    $channelIdResponse = $youtube->channels->listChannels('id', array(
                        'forUsername' => $user_id
                    ));

                    $retrieved_user_id = empty($channelIdResponse['items']) ? '' : $channelIdResponse['items'][0]['id'];
                    $user_id = empty($retrieved_user_id) ? $user_id : $retrieved_user_id;

                    // Now call the channels.list method to retrieve information about the channel_id.
                    $channelsResponse = $youtube->channels->listChannels('contentDetails', array(
                        'id' => $user_id,
                    ));
                    foreach ($channelsResponse['items'] as $channel) {
                        // Extract the unique playlist ID that identifies the list of videos
                        // uploaded to the channel, and then call the playlistItems.list method
                        // to retrieve that list.
                        $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];
                        $playlistItemsResponse = $youtube->playlistitems->listPlaylistItems('snippet', array(
                            'playlistId' => $uploadsListId,
                            'maxResults' => 50
                        ));

                        foreach ($playlistItemsResponse['items'] as $playlistItem)
                        {
                            $videoId = $playlistItem['snippet']['resourceId']['videoId'];
                            $title = $playlistItem['snippet']['title'];
                            $url = 'https://www.youtube.com/watch?v='.$videoId;
                            $thumbnail_url = $playlistItem['snippet']['thumbnails']['medium']['url'];
                            $result[$videoId] = array('url' => $url, 'thumbnail' => $thumbnail_url, 'title' => $title);
                        }

                        $cache->set($result);

                    }
                } catch (Google_Service_Exception $e) {
                    $result['error'] = sprintf('<p>A service error occurred: <code>%s</code></p>',
                        htmlspecialchars($e->getMessage()));
                } catch (Google_Exception $e) {
                    $result['error'] = sprintf('<p>A client error occurred: <code>%s</code></p>',
                        htmlspecialchars($e->getMessage()));
                }
			}
            if (empty($result))
            {
                $result['error'] = 'No videos were found for user_id: '.$user_id.', please be sure to supply a channel ID or username that has videos';
            }

            return $result;
		}
		else
		{
			trigger_error('The YouTubeLatestUserVideosFeedModel must be provided with the configuration parameter user_id.
			user_id can be a Youtube username or a channel ID', FATAL);
		}
	}
}
?>