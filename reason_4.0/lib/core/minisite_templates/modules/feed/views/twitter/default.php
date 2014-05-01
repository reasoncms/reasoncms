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

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_view_class_names' ][ reason_basename(__FILE__) ] = 'ReasonTwitterDefaultFeedView';

/**
 * ReasonTwitterDefaultFeedView displays data from the twitter model.
 *
 * We support these params:
 *
 * - num_to_show (int default 4)
 * - randomize (boolean default false)
 * - title (string default NULL)
 * - description (string default NULL)
 *
 * Pass in an empty string for title or description to suppress it. This display is very bare bones. It is very likely that you'll want to create a local
 * version of this file that does some different styling, or just create your own default feed view that does what you want. Ultimately, the goal is for this
 * to use the current oembed HTML along with the twitter widget.js file. Sadly, twitter makes you do one API call per tweet to get the oembed markup, making
 * that approach untenable.
 *
 * @todo Make this be like the twitter widget as a default style - should use oembed markup (right now you can only get it from API a tweet at a time ... lame).
 *
 * @author Nathan White
 */
 
class ReasonTwitterDefaultFeedView extends ReasonMVCView
{
	var $config = array('num_to_show' => 4,
						'randomize' => false,
						'title' => NULL,
						'description' => "");
	
	function get()
	{
		$tweets = $this->data();
		if (!empty($tweets))
		{
			$title = (!is_null($this->config('title'))) ? $this->config('title') : $this->get_title();
			$description = (!is_null($this->config('description'))) ? $this->config('description') : $this->get_description();
			$str = (!empty($title)) ? $title : '';
			$str .= (!empty($description)) ? $description : '';
			if ($this->config('randomize')) shuffle($tweets);
			$str .= '<ul>';
			$class = 'class="twitter_action_img"';
			$imgbase = REASON_HTTP_BASE_PATH . 'css/twitter/';
			$actbase = 'https://twitter.com/intent/';
			foreach ($tweets as $tweet)
			{
				$num = (!isset($num)) ? 1 : ($num + 1);
				$reply = '<a href="'.$actbase.'tweet?in_reply_to='.$tweet['id'].'"><img src ="'.$imgbase.'reply.png" '.$class.'></a>';
				$retweet = '<a href="'.$actbase.'retweet?tweet_id='.$tweet['id'].'"><img src ="'.$imgbase.'retweet.png" '.$class.'></a>';
				$fav = '<a href="'.$actbase.'favorite?tweet_id='.$tweet['id'].'"><img src ="'.$imgbase.'favorite.png" '.$class.'></a>';
				$action = $reply.$retweet.$fav;
				$str .= '<li>'.$tweet['html'].' <div class="twitter_action_bar"><p class="twitter_date">'.carl_date('j M', strtotime($tweet['created_at']))."</p>".$action.'</div></li>';
				if ($num == $this->config('num_to_show')) break;
			}
			$str .= '</ul>';
		}
		else $str = '';
		return $str;
	}
		
	/**
	 * Use the first tweet to get the title.
	 *
	 * @todo this makes sense for a user timeline but that is it.
	 */
	function get_title()
	{
		$tweets = $this->data();
		$tweet = reset($tweets);
		$screen_name = $tweet['user']['screen_name'];
		$name = $tweet['user']['name'];
		return '<h3><a href="http://twitter.com/'. urlencode($screen_name) . '">'.htmlspecialchars($name).'</a></h3>';
	}
	
	/**
	 * Use the first tweet to get the description.
	 */
	function get_description()
	{
		$tweets = $this->data();
		$tweet = reset($tweets);
		$description = $tweet['user']['description'];
		return '<p>'.htmlspecialchars($description).'</p>';
	}
}