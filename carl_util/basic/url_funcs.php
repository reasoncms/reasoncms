<?
/**
 * A collection of functions to work with urls
 *
 * @package carl_util
 * @subpackage basic
 */

/**
 * make_link will preserve the url query string, while adding or removing items specified in the new_request_vars array.
 * note that because of the use of array_merge, this function will handle only keys that are strings - if the keys are
 * integers, the key in new_request_vars will be incremented and added to the query string instead of replacing the numeric
 * key.
 *
 * @author nwhite
 *
 * @param array new_request_vars an array of key/value pairs which specify new items, replacement items, or items to remove from the query string
 * @param string base_path a base path for the returned URL, relative to the web server root - should begin with "/"
 * @param type string default '' - if 'relative' then the url returned will be relative to the web server root versus absolute
 * @param convert_entities  boolean default true - run html entities on link before returning it 
 */
function carl_make_link( $new_request_vars = array(''), $base_path = '', $type = '', $convert_entities = true, $maintain_original = true ) // {{{
{
	$url = get_current_url();
	$parts = parse_url($url);
	if ($maintain_original && !empty($parts['query'])) parse_str($parts['query'], $cur_request_vars);
	else $cur_request_vars = array();
	if (empty($base_path)) $base_path = $parts['path'];
	
	if ($type == 'relative')
	{
		$baseurl = $base_path;
	}
	else
	{
		$port = (isset($parts['port']) && !empty($parts['port'])) ? ':'.$parts['port'] : '';
		$baseurl = $parts['scheme'] . '://' . $parts['host'] . $port . $base_path;
	}
	$params = array_merge( (array)$cur_request_vars, (array)$new_request_vars );
	$link_pieces = array();
	$params = urlencode_array_keys_and_values($params);
	foreach( $params AS $key => $val )
	{
		if(is_array($val))
		{
			$link_pieces = array_merge( $link_pieces, flatten_array_for_url($key, $val) );
		}
		elseif(strlen($val) > 0)
		{
			$link_pieces[] = $key.'='.$val;
		}
	}
	$link = implode('&',$link_pieces);
	if ($convert_entities) $link = htmlspecialchars($link);
	if (!empty($link))
		return trim($baseurl.'?'.$link);
	else return trim($baseurl);
} // }}}
	
function carl_construct_link ( $new_request_vars = array(''), $preserve_request_vars = array(''), $base_path = '' )
{
	if (empty($preserve_request_vars))
	{
		return carl_make_link( $new_request_vars, $base_path, '', true, false );
	}
	else
	{
		$url = get_current_url();
		$preserve_array = '';
		$parts = parse_url($url);
		if (!empty($parts['query'])) parse_str($parts['query'], $cur_request_vars);
		foreach ($preserve_request_vars as $key)
		{
			if (isset($cur_request_vars[$key]))
			{
				$preserve_array[$key] = $cur_request_vars[$key];
			}
		}
		$params = (isset($preserve_array)) ? array_merge( (array)$preserve_array, (array)$new_request_vars ) : $new_request_vars;
		return carl_make_link( $params, $base_path, '', true, false );
	}
}

function carl_construct_relative_link ( $new_request_vars = array(''), $preserve_request_vars = array(''), $base_path = '', $convert_entities = true )
{
	if (empty($preserve_request_vars))
	{
		return carl_make_link( $new_request_vars, $base_path, 'relative', true, false );
	}
	else
	{
		$url = get_current_url();
		$preserve_array = '';
		$parts = parse_url($url);
		if (!empty($parts['query'])) parse_str($parts['query'], $cur_request_vars);
		foreach ($preserve_request_vars as $key)
		{
			if (isset($cur_request_vars[$key]))
			{
				$preserve_array[$key] = $cur_request_vars[$key];
			}
		}
		$params = (isset($preserve_array)) ? array_merge( (array)$preserve_array, (array)$new_request_vars ) : $new_request_vars;
		return carl_make_link( $params, $base_path, 'relative', true, false );
	}
}

function carl_make_redirect ( $new_request_vars, $base_path = '' )
{
	return carl_make_link ($new_request_vars, $base_path, '', false, true);
}

function carl_construct_redirect( $new_request_vars = array(''), $preserve_request_var = array(''), $base_path = '' )
{
	if (empty($preserve_request_vars))
	{
		return carl_make_link( $new_request_vars, $base_path, '', false, false );
	}
	else
	{
		$url = get_current_url();
		$preserve_array = '';
		$parts = parse_url($url);
		if (!empty($parts['query'])) parse_str($parts['query'], $cur_request_vars);
		foreach ($preserve_request_vars as $key)
		{
			if (isset($cur_request_vars[$key]))
			{
				$preserve_array[$key] = $cur_request_vars[$key];
			}
		}
		$params = (isset($preserve_array)) ? array_merge( (array)$preserve_array, (array)$new_request_vars ) : $new_request_vars;
		return carl_make_link( $params, $base_path, '', false, false );
	}
}

function get_current_url( $scheme = '' )
{
	// without $scheme, we figure out whether we're in SSL or not.  Providing a scheme will return the current URI		// with the new scheme
	$url = '';
	if( empty($scheme) )
	{
		if( !empty( $_SERVER['HTTPS'] ) AND strtolower($_SERVER['HTTPS']) == 'on' )
		{
			$scheme = 'https';
		}
		else
		{
			$scheme = 'http';
		}
	}
	$host = $_SERVER['HTTP_HOST'];
	$path = $_SERVER['REQUEST_URI'];
	$url = $scheme.'://'.$host.$path;
	return $url;
}

function on_secure_page()
{
	return (!empty( $_SERVER['HTTPS'] ) AND strtolower( $_SERVER['HTTPS'] ) == 'on' );
}

function urlencode_array_keys_and_values($array)
{
	$ret = array();
	foreach($array as $key=>$val)
	{
		if(is_array($val))
		{
			$ret[urlencode($key)] = urlencode_array_keys_and_values($val);
		}
		else
		{
			$ret[urlencode($key)] = urlencode($val);
		}
	}
	return $ret;
}
function flatten_array_for_url($key, $array)
{
	$ret = '';
	$flat = array_flatten_url($array);
	foreach($flat as $subkey=>$v)
	{
		$ret[] = $key.$subkey.'='.$v;
	}
	return $ret;
}

function array_flatten_url(&$a, $pref = '')
{
	$ret=array();
	foreach ($a as $i => $j)
	{
		if (is_array($j)) {
			$ret=array_merge($ret, array_flatten_url($j, $pref . '[' . $i . ']' ) );
		}
		else
		{
			$ret[ $pref . '[' .$i . ']' ] = $j;
		}
	}
	return $ret;
}

//replaces a protocol with another protocol
//like switching http to https or http to rtsp
function alter_protocol($url,$current_protocol,$new_protocol)
{
	return preg_replace("/^".$current_protocol.":\/\//" , $new_protocol."://" , $url, 1);
}
?>
