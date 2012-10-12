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
include_once('simplepie/autoloader.php');

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_model_class_names' ][ reason_basename( __FILE__) ] = 'ReasonSimplepieTwitterFeedModel';
	
/**
 * ReasonSimplepieTwitterFeedModel returns a SimplePie representation of a twitter feed given a screen name.
 *
 * The SimplePie object provided is sourced from the ATOM version of the user's twitter feed, and uses a custom
 * SimpliePie_Item class to provide a twitterified version of key content fields.
 *
 * Note that Twitter, as of 8/13/2012 has a rate limit of 150 unauthenticated requests / per ip / per hour.
 *
 * The default cache duration of 10 minutes basically means that anywhere you use this model you will be making
 * up to 6 requests per hour. If you are using twitter for many screen names, you might need to increase the
 * cache duration to avoid hitting the rate limit.
 *
 * User Configurables
 *
 * - screen_name
 * - cache_duration
 * - cache_directory
 *
 * @author Nathan White
 */
class ReasonSimplepieTwitterFeedModel extends ReasonMVCModel // implements ReasonFeedInterface
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
		if ($url = $this->config('screen_name'))
		{
			$simplepie = new SimplePie_Twitter;
			$simplepie->set_feed_url('http://twitter.com/statuses/user_timeline.atom?screen_name='.$this->config('screen_name'));
			$simplepie->set_cache_location($this->config('cache_directory'));
			$simplepie->set_cache_duration($this->config('cache_duration'));
			$simplepie->set_item_class('SimplePie_Twitter_Item');
			$simplepie->set_requested_screen_name($this->config('screen_name'));
			$simplepie->init();
			$simplepie->handle_content_type(); // is this needed?
			return $simplepie;
		}
		else
		{
			trigger_error('The ReasonSimplepieTwitterFeedModel must be provided with the configuration parameter screen_name.', FATAL);
		}
	}
	
	/**
	 * This is a convenience method that will return the id of the most_recent tweet.
	 */
	function get_most_recent_tweet_id()
	{
		$data = $this->get();
		$item = $data->get_item();
		if ($item)
		{
			$id = $item->get_id();
			preg_match('/[0-9]+$/', $id, $match);
			return (!empty($match)) ? $match[0] : false;
		}
		return false;
	}
}

class SimplePie_Twitter extends SimplePie
{
	private $requested_screen_name;
	
	function set_requested_screen_name($requested_screen_name)
	{
		$this->requested_screen_name = $requested_screen_name;
	}
	
	function get_screen_name()
	{
		if (!isset($this->_screen_name))
		{
			$this->_screen_name = $this->build_screen_name();
		}
		return $this->_screen_name;
	}
	
	/**
	 * Build the case correct screen name from the requested screen name.
	 */
	private function build_screen_name()
	{
		if (!empty($this->requested_screen_name))
		{
			$title = $this->get_title();
			$pos = carl_strripos($title, $this->requested_screen_name);
			if ($pos !== FALSE)
			{
				return carl_substr($title, $pos);
			}
		}
		else return '';
	}
}

class SimplePie_Twitter_Item extends SimplePie_Item
{
	function get_screen_name()
	{
		return $this->get_feed()->get_screen_name();
	}
	
	/**
	 * @return string twitterified title
	 */
	function get_title()
	{
		$title = $this->twitterify(parent::get_title());
		return $title;
	}
	
	/**
	 * @return string twitterified description
	 */
	function get_description($description_only = false)
	{
		$desc = $this->twitterify(parent::get_description($description_only));
		return $desc;
	}
	
	/**
	 * @return string twitterified content
	 */
	function get_content($content_only = false)
	{
		$content = $this->twitterify(parent::get_content($content_only));
		return $content;
	}
	
	/**
	 * Could we do a better job with this? Is there a twitter-text php library?
	 */
	function twitterify($str)
	{
		$str = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $str);
  		$str = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $str);
  		$str = preg_replace("/@(\w+)/", "<a href=\"http://www.twitter.com/\\1\" target=\"_blank\">@\\1</a>", $str);
  		$str = preg_replace("/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $str);
  		$str = preg_replace("/^".$this->get_screen_name().": /", "", $str);
  		return $str;
	}
}
?>