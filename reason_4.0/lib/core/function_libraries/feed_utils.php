<?php
/**
 * Functions for working with feeds
 * @package reason
 * @subpackage function_libraries
 */

/**
 * Make a standardized link to a feed
 * @param string $url the url of the feed
 * @param string $title the anchor title
 * @param string $text the visible link text
 */
function make_feed_link( $url, $title = 'Link to feed', $text = 'xml' )
{
	$ret = '<div class="feedLink"><div class="feedInfo"><a href="'.$url.'" title="'.$title.'">'.$text.'</a></div>';
	if(defined('REASON_URL_FOR_GENERAL_FEED_HELP'))
	{
		$ret .= '<div class="feedHelp"><a href="'.REASON_URL_FOR_GENERAL_FEED_HELP.'" title="More information about feeds">What is this?</a></div>';
	}
	$ret .= '</div>';
	return $ret;
}

function get_feed_as_text( $params )
{
	if(!empty($params['type_id']))
	{
		$type = new entity( $params['type_id'] );
		
		if(!empty($params['feed'])) //use requested feed script if given
		{
			$feed_file = $params['feed'];
		}
		elseif($type->get_value('custom_feed')) // otherwise use the type's custom feed script
		{
			$feed_file = str_replace('.php', '', $type->get_value('custom_feed') );
		}
		else
		{
			$feed_file = 'default'; // otherwise use default feed script
		}
			
		reason_include_once( 'feeds/'.$feed_file.'.php' );
		
		$feed_class = $GLOBALS[ '_feed_class_names' ][ $feed_file ];
		
		if(!empty($params['site_id']))
		{
			$site = new entity($params['site_id']);
			$feed = new $feed_class( $type, $site );
		}
		else
		{
			$feed = new $feed_class( $type );
		}
		
		$feed->set_request_vars($params);
		ob_start();
		$feed->run(false);
		$feed_text = ob_get_contents();
		ob_end_clean();
		if(!empty($feed_text))
		{
			return $feed_text;
		}
	}
}

function get_links_from_rss_string( $rss )
{
	require_once( INCLUDE_PATH . 'xml/xmlparser.php' );
	$xml_parse = new XMLParser($rss);
	$xml_parse->Parse();
	$links = array();
	if (isset($xml_parse->document->channel[0]->item))
	{
		foreach ($xml_parse->document->channel[0]->item as $k=>$item)
		{
			if (isset($item->link[0]->tagData)) 
 			{
 				$links[] = trim(str_replace('&amp;','&',$item->link[0]->tagData));
 			}
		}
	}
 	return $links;
}
?>
