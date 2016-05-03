<?php
/**
 * Utilities for manipulating (X)HTML
 * @package carl_util
 * @subpackage basic
 */

/**
 * Performs tag replacement in an XHTML string
 * @param string $xhtml the xhtml string needing tag replacement
 * @param array $tag_array an array mapping any number of html tags (keys) to replacement tags (values)
 * @return string $output the xhtml string with replacement tags
 */
function tagTransform($xhtml, $tag_array)
{
	$output = preg_replace("/(<\/?)(\w+)([^>]*>)/e", "'\\1'._tagMap('\\2', \$tag_array ).stripslashes('\\3')", $xhtml);
	return $output;
}

/**
 * Performs a search / replace of a tag in an XHTML string
 * @param string $xhtml the xhtml string needing tag replacement
 * @param string $tag_original xhtml tag to be replaced
 * @param string $tag_replace the xhtml tag which replaces $tag_original
 * @return string $output the xhtml string with $tag_replace substituted for $tag_original
 */
function tagSearchReplace($xhtml, $tag_original, $tag_replace)
{
	$tag_array = array($tag_original => $tag_replace);
	$output = tagTransform($xhtml, $tag_array);
	return $output;
}

/**
 * Helper function for tagTransform
 * @param string $value a single xhtml tag extracted from an xhtml string
 * @param array $transform_array an array mapping tags which need to be replaced
 * @return string $value returns the replaced value
 */
 
function _tagMap($value, $transform_array)
{
	if (isset($transform_array[$value]))
	{
		return $transform_array[$value];
	}
	else return $value;
}

/**
 * Replace headings with less-important headings
 *
 * E.g. if $steps is 1, h4s become h5s
 * if $steps is 2, h4s become h6es
 * @param string $content XHTML content
 * @param integer $steps how far to demote
 */
function demote_headings($content, $steps)
{
	$steps = (integer) $steps;
	if(empty($steps))
		return $content;
	
	$tag_array = array();
	for($i = 1; $i <= 6; $i++)
	{
		$new_level = $i + $steps;
		$tag_array['h'.$i] = 'h'.( $new_level < 6 ? $new_level : 6 );
	}
	return tagTransform($content, $tag_array);
}

/**
 * Determine if an HTML snippet is essentially empty -- e.g. is there no actual content in the HTML?
 *
 * e.g. '<p><em></em><br /></p>' should return true -- there is no content here
 * '<img src="foo.gif" alt="foo" />' and '<p>foo</p>' should return false -- these contain real content
 *
 * @param string $string
 * @return boolean
 */
function carl_empty_html($string)
{
	if( empty( $string ) )
			return true;
	elseif(strlen($string) < 256)
	{
		$trimmed = trim(strip_tags($string,'<img><hr><script><embed><object><form><iframe><input><select><textarea>'));
		if(empty($trimmed))
			return true;
	}
	return false;
}

/**
 * When (X)HTML data is taken out of context (in an RSS feed for example)
 * many relative links break (as well as "absolute" links lacking a scheme and host).
 * This function will take a block of html, find links (<a href, <img src, <form action, etc)
 * and give them context.
 *
 * Note: This will not absolutify relative links in the form of "../foo/" into their canonical form. Links should still work, however (as long as the $relative_to parameter is passed)
 * 
 * @todo In the regular expression, allow spaces in the url only if the url is preceded by a quotation mark 
 * 
 * @param string $host The host to be used for urls without a host defined
 * @param string $html The html to be parsed for links needing expanded
 * @param string $scheme The scheme to be used for urls without a scheme defined
 * @param string $relative_to A fully valid URL used to resolve relative urls (i.e. if relative_to = "http://apps.carleton.edu/campus/shout/?story_id=347996" and the function comes across a url "shout_staff" it will expand to "http://apps.carleton.edu/campus/shout/shout_staff"). At this time, relative_to is only used for truely relative urls. If the url "/campus/shout" is found, the function will use the (optional) scheme and host parameters, not the scheme and host from the relative_to parameter
 * 
 * @return string $html The fully expanded html
 * 
 */
function expand_all_links_in_html($html, $scheme = 'http', $host = '', $relative_to = '')
{
	if(empty($host))
	{
		if(defined('HTTP_HOST_NAME'))
			$host = HTTP_HOST_NAME;
		elseif(isset($_SERVER['HTTP_HOST']))
			$host = $_SERVER['HTTP_HOST'];
		else
		{
			trigger_error('Missing argument 3 ($host) for expand_all_links_in_html(). Unable to expand.');
			return $html;
		}
	}
	
	$absolutifier = new absolutify_class();
	$absolutifier->host = $host;
	$absolutifier->scheme = $scheme;
	$absolutifier->relative_to = $relative_to;
	
	// Regular expression for finding urls.
	// It only looks for things coming after src=, href=, data=, or action=
	$pattern = '/(<[^\/][^>]*?(?:src|href|data|action)\s?=\s?([\'"]?))([^>\s\2]*)(\2(?:\s|[^>]*>))/';
	
	$html = preg_replace_callback($pattern,array($absolutifier, 'expand_link'),$html);

	return $html;
}

/*
 * This is only a class so that the expand_link() method could
 * be used as a callback in preg_replace_callback(), but some
 * other information could be passed along also (by means of
 * the class variables).
 */
class absolutify_class
{
	var $host;
	var $scheme;
	var $relative_to;
	var $relative_to_parts;
	var $relative_to_parts_pathinfo;
	
	function expand_link($matches)
	{
		// If we have a $relative_to set, let's disect it only if it hasn't been
		// done already. This keeps us from doing the same work over and over.
		// Also, some error checking is done to make sure that we have a
		// valid relative_to_parts defined.
		if (!empty($this->relative_to) && empty($this->relative_to_parts))
		{
			$this->relative_to_parts = parse_url($this->relative_to);
			if (!isset($this->relative_to_parts['host']))
			{
				$this->relative_to_parts['host'] = $this->host;
			}
			if (!isset($this->relative_to_parts['scheme']))
			{
				$this->relative_to_parts['scheme'] = $this->scheme;
			}
			if (!isset($this->relative_to_parts['path']))
			{
				$this->relative_to_parts['path'] = '/';
			}
			$this->relative_to_parts_pathinfo = pathinfo($this->relative_to_parts['path']);				
		}
		
		$url = str_replace('&amp;','&',$matches[3]);
		
		if(strpos($url,'//') === 0)
		{
			$scheme = $this->scheme ? $this->scheme : 'http';
			$url = $scheme.':'.$url;
		}
		
		// If we can't parse it, it could be due to the face that we
		// have a url with no server and a query string containg "http://"
		// So we tack on a fake scheme and host, parse the url, then
		// strip them back out. 
		$parts = parse_url($url);
		if (!$parts)
		{
			$newurl = 'fakescheme://not.a.server';
			if (substr($url,0,1) != '/') $newurl .= '/';
			$newurl .= $url;
			$parts = parse_url($newurl);
			
			// If it still won't parse, we give up and return the original match
			if (!$parts) return $matches[0];
			if ($parts['scheme'] == 'fakescheme') unset($parts['scheme']);
			if ($parts['host'] == 'not.a.server') unset($parts['host']);
		}
		
		// If the scheme is mailto: or news: we don't want to change anything
		if (isset($parts['scheme']) && (strtolower($parts['scheme']) == 'mailto' || strtolower($parts['scheme']) == 'news'))
		{
			return $matches[0];
		}
		
		// If we have something to make everything relative to, we
		// dive in here
		if (!empty($this->relative_to_parts))
		{
			// It is relative, i.e. if the path doesn't start with "/"
			if (isset($parts['path']) && (substr($parts['path'],0,1) != '/'))
			{
				$parts['host'] = $this->relative_to_parts['host'];
				$parts['scheme'] = $this->relative_to_parts['scheme'];
				$parts['path'] = $this->relative_to_parts_pathinfo['dirname'] . '/' . $parts['path'];
			}
			
			// There is no path, just a fragment and/or query.
			if (!isset($parts['path']) && (isset($parts['fragment']) || isset($parts['query'])))
			{
				// There must just be a fragment, so we want to keep
				// any query that is in the relative_to url
				if (!isset($parts['query']) && isset($this->relative_to_parts['query']))
				{
					$parts['query'] = $this->relative_to_parts['query'];
				}
				$parts['host'] = $this->relative_to_parts['host'];
				$parts['scheme'] = $this->relative_to_parts['scheme'];
				$parts['path'] = $this->relative_to_parts['path'];
			}
		}
		
		
		if (!isset($parts['scheme'])) $parts['scheme'] = $this->scheme;
		
		if (!isset($parts['host'])) $parts['host'] = $this->host;
		
		
		// Reconstruct the URL.
		// This is basically the opposite of parse_url

		if (!is_array($parts)) return $matches[0];
	    $url = isset($parts['scheme']) ? $parts['scheme'].':'.((strtolower($parts['scheme']) == 'mailto') ? '' : '//') : '';
	    $url .= isset($parts['user']) ? $parts['user'].(isset($parts['pass']) ? ':'.$parts['pass'] : '').'@' : '';
	    $url .= isset($parts['host']) ? $parts['host'] : '';
	    $url .= isset($parts['port']) ? ':'.$parts['port'] : '';
	    if(isset($parts['path']))
	    {
	        $url .= (substr($parts['path'], 0, 1) == '/') ? $parts['path'] : ('/'.$parts['path']);
	    }
	    $url .= isset($parts['query']) ? '?'.$parts['query'] : '';
	    $url .= isset($parts['fragment']) ? '#'.$parts['fragment'] : '';
		
		
		// Put the url back in context and return it
		return $matches[1].$url.$matches[4];
	}
}

/**
 * Get the tags considered "phrasing content" in html5
 *
 * See http://stackoverflow.com/questions/9852312/list-of-html5-elements-that-can-be-nested-inside-p-element
 *
 * @return string of form '<a><abbr>';
 */
function get_phrasing_tags()
{
	return '<a><abbr><area><audio><b><bdi><bdo><br><button><canvas><cite><code><command><datalist><del><dfn><em><embed><i><iframe><img><input><ins><kbd><keygen><label><map><mark><math><meter><noscript><object><output><progress><q><ruby><s><samp><script><select><small><span><strong><sub><sup><svg><textarea><time><u><var><video><wbr>';
}
/**
 * Strip out non-phrasing tags from an html string
 *
 * HTML5 (and other flavors of HTML) only allow "phrasing content" in paragraphs. Browsers do 
 * unexpected things when, for example, a <p> or <div> is nested inside a <p>.
 *
 * Therefore, it is good practice to strip non-phrasing tags out of any content that may be placed 
 * inside a paragraph tag or other context that requires phrasing content. A common case for this is
 * when doing content substitution.
 *
 * See http://www.w3.org/TR/html5/dom.html#phrasing-content-1
 */
function strip_non_phrasing_tags($string)
{
	return strip_tags($string, get_phrasing_tags());
}