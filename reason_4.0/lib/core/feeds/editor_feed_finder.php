<?php
/**
 * @package reason
 * @subpackage feeds
 */
include_once( 'reason_header.php' );
reason_include_once( 'feeds/default.php' );
reason_include_once( 'function_libraries/url_utils.php' );
reason_include_once( 'function_libraries/feed_utils.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorFeedFinder';

/**
 * @todo clean this up so that request['url'] is consistently provided and loki 2 hack is unnecessary
 * @todo 
 */

/**
 * Loki 2 link style was not being properly handled by the editor_feed_finder - this function converts links that start with // to http://
 * @todo normalize loki link formats, ensure
 */
function turn_into_normalized_url($string)
{
	$url = turn_into_string($string);
	if ($url) $url = (substr($url, 0, 2) === '//') ? 'http:' . $url : $url;
	return $url;
}

/**
 * @todo Make smarter and more efficient on sites with many entities
 */
class editorFeedFinder extends defaultFeed
{
	var $cleanup_rules = array('url'=>array('function'=>'turn_into_normalized_url'));
	function run($send_header = true)
	{
		$hit = false;
		if(!empty($this->request['url']))
		{
			//echo $this->request['url'];
			$url = str_replace('"','',$this->request['url']);
			$site_id = get_site_id_from_url($url);
			//echo $site_id;
			$type_feed_text = get_feed_as_text( array('site_id'=>$site_id,'type_id'=>id_of('type'),'feed'=>'editor_types' ) );
			//echo $type_feed_text;
			$type_links = get_links_from_rss_string( $type_feed_text );
			foreach($type_links as $link)
			{
				$parsed_url = parse_url($link);
				parse_str($parsed_url['query'], $parsed_query);
				$entity_feed_text = get_feed_as_text( $parsed_query );
				$entity_links = get_links_from_rss_string( $entity_feed_text );
				//pray($entity_links);
				if(in_array($this->request['url'],$entity_links) || in_array('http://'.REASON_HOST.$this->request['url'],$entity_links))
				{
					$hit = true;
					break;
				}
			}
		}
		if($send_header)
		{
			header('Content-type: text/xml');
		}
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<rss version="2.0">'."\n";
		echo '<channel>'."\n";
		echo '<title>Reason Feed Finder</title>'."\n";
		if($hit)
		{
			echo '<item>'."\n";
			echo '<title>site_feed</title>'."\n";
			echo '<link>' . securest_available_protocol() . '://'.REASON_HOST.FEED_GENERATOR_STUB_PATH.'?type_id='.id_of('type').'&amp;site_id='.$site_id.'&amp;feed=editor_types</link>'."\n";
			echo '</item>'."\n";
			echo '<item>'."\n";
			echo '<title>type_feed</title>'."\n";
			echo '<link>'.str_replace('&','&amp;',$link).'</link>'."\n";
			echo '</item>'."\n";
			echo '<item>'."\n";
			echo '<title>url_requested</title>'."\n";
			echo '<link>'.str_replace('&','&amp;',$this->request['url']).'</link>'."\n";
			echo '</item>'."\n";
		}
		echo '</channel>'."\n";
		echo '</rss>'."\n";
	}
}

?>
