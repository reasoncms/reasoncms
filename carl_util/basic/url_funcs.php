<?php
/**
 * A collection of functions to work with urls
 *
 * @package carl_util
 * @subpackage basic
 * @todo document these functions
 */

/**
 * Include dependencies
 */
include_once('cleanup_funcs.php');

/**
 * carl_make_link() will preserve the url query string, while adding or removing items specified in the new_request_vars array.
 * note that because of the use of array_merge, this function will handle only keys that are strings - if the keys are
 * integers, the key in new_request_vars will be incremented and added to the query string instead of replacing the numeric
 * key.
 *
 * @author nwhite
 *
 * @param array new_request_vars an array of key/value pairs which specify new items, replacement items, or items to remove from the query string
 * @param string base_path a base path for the returned URL, relative to the web server root - should begin with "/"
 * @param type string default '' - if 'relative' then the url returned will be relative to the web server root, if 'qs_only' only the query string part will be returned
 * @param convert_entities  boolean default true - run html entities on link before returning it 
 */
function carl_make_link( $new_request_vars = array(''), $base_path = '', $type = '', $convert_entities = true, $maintain_original = true ) // {{{
{
	$url = get_current_url();
	$parts = parse_url($url);
	if ($maintain_original && !empty($parts['query'])) parse_str($parts['query'], $cur_request_vars);
	else $cur_request_vars = array();
	$cur_request_vars = (!empty($cur_request_vars)) ? conditional_stripslashes($cur_request_vars) : $cur_request_vars;
	if (empty($base_path)) $base_path = $parts['path'];
	
	if ($type == 'qs_only')
	{
		$baseurl = '';
	}
	elseif ($type == 'relative')
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
	elseif ($type == 'qs_only') return '?'; 
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
		if (isset($cur_request_vars)) $cur_request_vars = conditional_stripslashes($cur_request_vars);
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
		if (isset($cur_request_vars)) $cur_request_vars = conditional_stripslashes($cur_request_vars);
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

function carl_construct_redirect( $new_request_vars = array(''), $preserve_request_vars = array(''), $base_path = '' )
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
		if (isset($cur_request_vars)) $cur_request_vars = conditional_stripslashes($cur_request_vars);
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

function carl_construct_query_string ( $new_request_vars, $preserve_request_vars = array('') )
{
	if (empty($preserve_request_vars))
	{
		return carl_make_link( $new_request_vars, '', 'qs_only', true, false );
	}
	else
	{
		$url = get_current_url();
		$preserve_array = '';
		$parts = parse_url($url);
		if (!empty($parts['query'])) parse_str($parts['query'], $cur_request_vars);
		if (isset($cur_request_vars)) $cur_request_vars = conditional_stripslashes($cur_request_vars);
		foreach ($preserve_request_vars as $key)
		{
			if (isset($cur_request_vars[$key]))
			{
				$preserve_array[$key] = $cur_request_vars[$key];
			}
		}
		$params = (isset($preserve_array)) ? array_merge( (array)$preserve_array, (array)$new_request_vars ) : $new_request_vars;
		return carl_make_link( $params, '', 'qs_only', true, false );
	}
}

function carl_make_query_string ( $new_request_vars )
{
	return carl_make_link( $new_request_vars, '', 'qs_only', true, true );
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

/**
 * Determine if request used HTTPS
 * 
 * Looks for HTTPS or HTTP_X_FORWARDED_PROTO in $_SERVER
 * 
 * @return boolean TRUE if request was via https, FALSE if via http
 */
function on_secure_page()
{
	return array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] == "on" ||
		array_key_exists("HTTP_X_FORWARDED_PROTO", $_SERVER) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https';
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
	$ret = array();
	$flat = array_flatten_url($array);
	foreach($flat as $subkey=>$v)
	{
		if (strlen($v) > 0) $ret[] = $key.$subkey.'='.$v;
	}
	return (!empty($ret)) ? $ret : array();
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

/**
 * Grab contents of a URL. We allow up to 30 seconds total per operation and 10 seconds to connect by default.
 *
 * @param string url fully qualified url to grab
 * @param boolean verify_ssl whether or not to require a valid certificate for an https connection default false
 * @param string http_auth_username Absolute URL
 * @param string http_auth_password
 * @param int timeout - timout time for entire request
 * @param int connect_timeout - timeout time to establish connection to server
 * @param boolean return_null_on_error - If there is an error, return NULL even if there was a response from the server
 * @param boolean error_on_failure - Whether to trigger an error on failure (true) or to fail silently (false)
 * @return mixed a string or false on error
 */
function carl_util_get_url_contents($url, $verify_ssl = false, $http_auth_username = '', $http_auth_password = '', $timeout = 30, $connect_timeout = 10, $return_null_on_error = false, $error_on_failure = true)
{
	$ch = curl_init( $url );
	$useragent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : false; // grab the current browser user agent is possible
	if ($useragent) curl_setopt( $ch, CURLOPT_USERAGENT, $useragent); // we spoof the browsers user agent if possible - some servers reject the default
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
	if (!empty($http_authentication_username) || !empty($http_authentication_password))
	{
		curl_setopt( $ch, CURLOPT_USERPWD, $http_authentication_username.':'.$http_authentication_password);
	}
	if (!$verify_ssl)
	{
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
	}
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout ); // number of seconds to try to connect
	curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout ); // number of seconds allowed overall for execution
	curl_setopt( $ch, CURLOPT_FAILONERROR, true);
	$page = curl_exec( $ch );
	
	// check for errors
	if( $err = curl_error( $ch ) )
	{
		if ($error_on_failure)
			trigger_error( 'carl_util_get_url_contents() failed. Msg: '.$err.'; url: '.$url );
		
		if( $return_null_on_error )
		{
			curl_close( $ch );
			return NULL;
		}
	}
	curl_close( $ch );
	return $page;
}
?>
