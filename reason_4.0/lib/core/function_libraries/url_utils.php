<?php
/**
 * @package reason
 * @subpackage function_libraries
 */

/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
include_once( CARL_UTIL_INC . 'basic/url_funcs.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'classes/url/page.php' );
reason_include_once( 'function_libraries/url_utils.php' );
	
/**
 * Grab contents of a URL.
 *
 * Includes authentication to get at URLs in protected areas.
 *
 * @param string $url Absolute URL
 * @return mixed a string or false on error
 */
function get_reason_url_contents( $url )
{
	if (defined('HTTP_CREDENTIALS_FILEPATH') && file_exists(HTTP_CREDENTIALS_FILEPATH)) include( HTTP_CREDENTIALS_FILEPATH );
	else $http_authentication_username = $http_authentication_password = '';
	return carl_util_get_url_contents( $url, REASON_HOST_HAS_VALID_SSL_CERTIFICATE, $http_authentication_username, $http_authentication_password );
}

/**
 * Get the filesize of a remote document
 *
 * (mixed)remote_filesize($uri,$user='',$pw='')
 * returns the size of a remote stream in bytes or
 * the string 'unknown'. Also takes user and pw
 * in case the site requires authentication to access
 * the uri
 *
 * @param string $uri Absolute URL
 * @param string $user Username
 * @param string $pw Password
 * @return mixed integer size in bytes or null if unable to determine filesize
 */
function get_remote_filesize($uri,$user='',$pw='')
{
   require_once(LIBCURLEMU_INC . 'libcurlemu.inc.php');

   // start output buffering
   ob_start();
   // initialize curl with given uri
   $ch = curl_init($uri);
   // make sure we get the header
   curl_setopt($ch, CURLOPT_HEADER, 1);
   // make it a http HEAD request
   curl_setopt($ch, CURLOPT_NOBODY, 1);
   // if auth is needed, do it here
   if (!empty($user) && !empty($pw))
   {
       $headers = array('Authorization: Basic ' .  base64_encode($user.':'.$pw)); 
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
   }
   $okay = curl_exec($ch);
   curl_close($ch);
   // get the output buffer
   $head = ob_get_contents();
   // clean the output buffer and return to previous
   // buffer settings
   ob_end_clean();
  
   // gets you the numeric value from the Content-Length
   // field in the http header
   $regex = '/Content-Length:\s([0-9].+?)\s/';
   $count = preg_match($regex, $head, $matches);
  
   // if there was a Content-Length field, its value
   // will now be in $matches[1]
   if (isset($matches[1]))
   {
       $size = $matches[1];
   }
   else
   {
       $size = NULL;
   }
  
   return $size;
}

/**
 * Attempt to identify the most likely Reason site given a URL
 *
 * Like get_potential_sites_from_path, but for absolute URLs
 *
 * @param $url
 * @return mixed site ID integer if successful, else NULL
 */
function get_site_id_from_url($url)
{
	$parsed = parse_url($url);
	if(empty($parsed['host']) || hostname_is_associated_with_this_reason_instance($parsed['host']) )
	{
		$sites = get_potential_sites_from_path($parsed['path']);
		if(!empty($sites))
		{
			reset($sites);
			$top_site = current($sites);
			return $top_site->id();
		}
	}
}

/**
 * Determine if a given host name is now or has in the past served up this Reason instance
 *
 * This allows you to change your host/domain name, and (as long as you register the old
 * domain name in the constant REASON_PREVIOUS_HOSTS) Reason/Loki will still recognize links
 * to pages it administers.
 *
 * @param string $hostname Like www.example.com
 * @return boolean
 */
function hostname_is_associated_with_this_reason_instance($hostname)
{
	if($hostname == REASON_HOST || in_array($hostname, explode(',',REASON_PREVIOUS_HOSTS)) )
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Get an array of sites that might contain a resource at a given path
 *
 * Takes a path relative to the http root of the server, e.g. /foo/bar/baz/
 * returns an array of site entities, with the most likely (e.g. with the closest match) first
 *
 * @param string $path
 * @return array Site entities
 */
function get_potential_sites_from_path($path)
{
	if(!empty($path))
	{
		$path_parts = explode('/',trim_slashes($path));
		$values = array('/');
		$prev_parts = '/';
		foreach($path_parts as $part)
		{
			$values[] = $prev_parts = $prev_parts.$part.'/';
		}
		$where = '(site.base_url = "'.implode('" OR site.base_url = "',$values).'")';
		
		$es = new entity_selector();
		$es->add_type(id_of('site'));
		$es->add_relation($where);
		$es->set_order('site.base_url DESC');
		$potential_sites = $es->run_one();
		return $potential_sites;
	}
}

/**
 * Utility method to get the absolute url of a reason page
 * 
 * Provide an entity when possible for a slight performance boost
 *
 * @author Nathan White
 * @param mixed entity or entity_id corresponding to a page or site
 * @return mixed string absolute url if successful; else null
 */
function reason_get_page_url( $page_entity_or_id )
{
	static $builder; // lets use a singleton builder instance
	if (!isset($builder))
	{
		$builder = new reasonPageUrl();
	}
	if (is_object($page_entity_or_id))
	{
		$builder->set_id($page_entity_or_id->id());
		$builder->provide_page_entity($page_entity_or_id);
	}
	elseif (is_numeric($page_entity_or_id))
	{
		$builder->set_id($page_entity_or_id);
	}
	else
	{
		trigger_error('reason_get_page_url was passed a parameter that is not an object or integer - a url cannot be generated');
		return null;
	}
	return $builder->get_url();
}

/**
 * Get the absolute url for the site, given a reason entity or id for a site or page.
 *
 * @author Nathan White
 * @param mixed entity or entity_id corresponding to a page or site
 * @return mixed string absolute url if successful; else null
 */
function reason_get_site_url( $page_or_site_entity_or_id )
{
	$entity = is_numeric($page_or_site_entity_or_id) ? new entity($page_or_site_entity_or_id) : $page_or_site_entity_or_id;	
	$type = $entity->has_value('type') ? $entity->get_value('type') : false;
	if ( ($type == id_of('minisite_page')) || ($type == id_of('site')) )
	{
		$site = ($type == id_of('minisite_page')) ? $entity->get_owner() : $entity;
		$domain = ($site->has_value('domain') && $site->get_value('domain')) ? $site->get_value('domain') : REASON_HOST;
		$base_url = $site->get_value('base_url');
		if (on_secure_page()) // see if the domain has https available
		{
			if (isset($GLOBALS['_reason_domain_settings'][$domain]['HTTPS_AVAILABLE'])) $secure = $GLOBALS['_reason_domain_settings'][$domain]['HTTPS_AVAILABLE'];
			elseif (isset($GLOBALS['_default_domain_settings']['HTTPS_AVAILABLE'])) $secure = $GLOBALS['_default_domain_settings']['HTTPS_AVAILABLE'];
			else $secure = ($domain == REASON_HOST) ? HTTPS_AVAILABLE : false;
		}
		else $secure = false;
		return ($secure) ? 'https://'.$domain.$base_url : 'http://'.$domain.$base_url;
	}
	else
	{
		trigger_error('reason_get_site_url was not passed a valid site or page entity (or entity id) - a site url could not be built.');
		return null;
	}
}

/**
 * Get the full url (from server root) for a minisite page
 *
 * the function will cache the minisite navigation object for a site so when called repeatedly it
 * should only instantiate minisite navigation once per site
 *
 * Because this method caches whole sites, it will likely be faster than build_URL() if
 * you are getting the URLs of bunches of pages on a site. If you are getting just a single
 * URL of a single page, or you are getting URLs of a few pages on different sites, build_URL()
 * will likely be faster (and consume less memory).
 * 
 * @param int site_id
 * @param int page_id
 * @param string query_string will be appended to end of returned url
 * @param boolean secure whether to return a link prefaced with http or https
 * @param array viewer can be used to pass in existant minisite_navigation objects indexed by site id when available
 * @return string full url of the minisite page $page_id
 */
function get_minisite_page_link($site_id, $page_id, $query_string = '', $secure=true, $viewer = array())
{
	reason_include_once( 'minisite_templates/nav_classes/default.php' );
	$ret = '';
	
	if (empty($page_id) || empty($site_id))
	{
		trigger_error("get_minisite_page_link must be called with a valid page_id ($page_id) and site_id ($site_id)");
		return '';
	}
	static $nav_cache = array();
	if (isset($nav_cache[$site_id])) $pages = &$nav_cache[$site_id];
	elseif (isset($viewer[$site_id])) $pages = $viewer[$site_id];
	else
	{
		//$s = get_microtime();
		$pages = new MinisiteNavigation();
		$site = new entity($site_id);
		$pages->site_info = $site;
		$pages->init( $site_id, id_of('minisite_page'));
		$nav_cache[$site_id] = $pages;
	}
	$ret = $pages->get_full_url($page_id, true, $secure);
	//$ret = $pages->get_url_from_base($page_id);	

	if(!empty($query_string))
	{
		$ret .= '?' . $query_string;
	}
	return $ret;
} 			

/**
 *	Get the URL of a page
 *
 *	This function will provide the URL of a page of a particular type or types on a site
 *
 *	@param entity $site The site to look in
 *	@param page_tree $tree the page tree object for the site; this must be already initialized
 *	@param mixed $page_types The array of page types or string indicating single page type that is/are acceptable
 *	@param boolean $as_uri Returns a fully qualified URI if true; otherwise returns a URL relative to web root
 *	@param boolean $secure Uses https if true. This parameter only has an effect if $as_uri is true.
 */

function get_page_link( &$site, &$tree, $page_types, $as_uri = false, $secure = false ) // {{{
{
	if(empty($site) || empty($page_types))
	{
		trigger_error('site and page types must all be passed to get_page_link',EMERGENCY);
	}
	elseif(is_string($page_types))
	{
		$page_types = array($page_types);
	}
	elseif(!is_array($page_types))
	{
		trigger_error('$page_types must be an array or string',EMERGENCY);
	}
	$relations = array();
	$es = new entity_selector($site->id());
	$es->add_type( id_of( 'minisite_page' ) );
	foreach($page_types as $page_type)
	{
		$relations[] = 'page_node.custom_page = "'.$page_type.'"';
	}
	$es->add_relation( '('.implode(' or ', $relations).')' );
	$es->set_num( 1 );
	$pages = $es->run_one();
	
	if (!empty($pages))
	{
		$page = current($pages);
		if(!empty($tree))
			$ret = $tree->get_full_url($page->id(), $as_uri, $secure);
		else
			$ret = build_URL( $page->id() );
	}
	else
	{
		$ret = $site->get_value('base_url');
	}
	if($as_uri && (empty($pages)))
	{
		if($secure)
		{
			$ret = securest_available_protocol() . '://'.REASON_HOST.$ret;
		}
		else
		{
			$ret = 'http://'.REASON_HOST.$ret;
		}
	}
	
	return $ret;
}

/**
 * Get the URL for a page
 *
 * Originally developed as part of URL history as a lightweight
 * way to find a page's URL without having to query for all pages
 * in the site
 *
 * @param integer $page_id
 * return mixed string URL if found; else NULL
 */
function build_URL( $page_id )
{
	$page_id = (integer) $page_id;
	if(empty($page_id))
	{
		trigger_error('Bad $page_id');
		return;
	}
	static $cache;
	if (isset($cache[$page_id])) $url = $cache[$page_id];
	else
	{
		$es = new entity_selector();
		$es->add_type(id_of('minisite_page'));
		$es->limit_tables('page_node');
		$es->limit_fields('page_node.url_fragment');
		$es->add_relation('entity.id = '.$page_id);
		$result = $es->run_one();
		$page = (!empty($result)) ? array_shift($result) : false;
		if ($page)
		{
			$url = build_URL_from_entity($page);
			$cache[$page_id] = (!empty($url)) ? $url : false;
		}
		else $cache[$page_id] = $url = false;
	}
	return $url;
}

/**
 * Builds URL from a page entity when the related parent_id field is set and the page entities are present
 * @param object page entity object
 * @param object pages set of minisite page entity objects with parent_ids
 * @param string parent_id_field name of entity field that contains the parent_id
 * @author Nathan White
 */
function build_URL_from_entity_known_parent(&$page, &$pages, $parent_id_field = 'parent_id')
{
	$url = dig_for_URL_known_parent( $page->id(), $page->get_value($parent_id_field), $page->get_value('url_fragment').'/', $pages, $parent_id_field);
	$url = str_replace( '//', '/', $url );
	return $url;
}

/**
 * Takes an array of minisite_pages with the related parent_id present and returns an array of URLs indexed by page id
 * @param object pages array of page entities
 * @param string parent_id_field needed if not called "parent_id"
 * @author Nathan White
 */
function multi_build_URLS_known_parent($pages, $parent_id_field = 'parent_id')
{
	foreach ($pages as $k => $v)
	{
		$url = dig_for_URL_known_parent($k, $v->get_value($parent_id_field), $v->get_value('url_fragment').'/', $pages, $parent_id_field);
		$url = str_replace( '//', '/', $url );
		if ($url) $urls[$k] = $url;
	}
	return $urls;
}

/**
 * Builds URL from a page entity
 * @param object page entity object
 * @author Nathan White
 */
function build_URL_from_entity(&$page)
{
	$url = dig_for_URL( $page->id(), $page->get_value( 'url_fragment' ).'/');
	$url = str_replace( '//', '/', $url );
	return $url;
}

function dig_for_URL( $page_id, $URL)
{
	static $cache;
	if (isset($cache[$page_id])) $results = $cache[$page_id];
	else
	{
		$es = new entity_selector();
		$es->add_type( id_of( 'minisite_page') );
		$es->add_right_relationship( $page_id, relationship_id_of('minisite_page_parent') );
		$es->limit_tables('page_node');
		$es->limit_fields('page_node.url_fragment');
		$es->set_num(1);
		$results = $es->run_one();
		$cache[$page_id] = $results;
	}
	if( !empty($results) )
	{
		$page = current($results);
		if( $page->get_value( 'id' ) == $page_id )
		{
			$site_url = get_site_URL( $page_id );
			if(!empty($site_url))
				return $site_url . $URL;
			else
				return;
		}
		else
		{
			if($page->get_value( 'url_fragment' )) $URL = $page->get_value( 'url_fragment' ) . '/' . $URL;
			return dig_for_URL( $page->get_value( 'id' ), $URL );
		}
	}
}

function dig_for_URL_known_parent($page_id, $parent_id, $url, &$pages, $parent_id_field = 'parent_id', $orig_id = '')
{
	static $cache = array();
	if(empty($orig_id))
		$orig_id = $page_id;
	
	//echo 'id: '.$page_id.'; p_id: '.$parent_id.'; url: '.$url.'<br />';
	if (isset($cache[$orig_id])) return $cache[$orig_id];
	if (!isset($pages[$parent_id])) return false;
	$parent =& $pages[$parent_id];
	if (!empty($parent))
	{
		if ($parent_id == $page_id)
		{
			$site_url = get_site_URL( $page_id );
			if(!empty($site_url))
				$url = get_site_URL( $page_id ) . $url;
			else
				$url = NULL;
			$cache[$orig_id] = $url;
			return $url;
		}
		else
		{
			if ($parent->get_value('url_fragment')) $url = $parent->get_value('url_fragment').'/'.$url;
			return dig_for_URL_known_parent( $parent_id, $parent->get_value($parent_id_field), $url, $pages, $parent_id_field, $orig_id );
		}
	}
}

/**
 * Get the URL of the site that owns a given page
 *
 * This function returns a URL relative to the server root
 *
 * @param integer $page_id
 * @return mixed string URL if successful; else null
 */
function get_site_URL( $page_id )
{
	static $cache;
	if (isset($cache[$page_id])) $results = $cache[$page_id];
	else
	{
		$es = new entity_selector();
		$es->add_type( id_of( 'site') );
		$es->limit_tables('site');
		$es->limit_fields('site.base_url');
		$es->add_left_relationship( $page_id, get_owns_relationship_id(id_of('minisite_page')));
		$es->set_num(1);
		$results = $es->run_one();
		$cache[$page_id] = $results;
	}
	if( !empty( $results )  )
	{
		$site = current($results);
		return $site->get_value( 'base_url' );
	}
}

// DEPRECATED FUNCTIONS _ MOVED INTO url_funcs.php in carl_util
/**
 * Deprecated -- use carl_make_link()
 * @deprecated
 */
function make_link( $new_request_vars = array(''), $base_path = '', $type = '', $convert_entities = true, $maintain_original = true ) // {{{
{
	$call_info = array_shift( debug_backtrace() );
	$code_line = $call_info['line'];
	$file = array_pop( explode('/', $call_info['file']));
	trigger_error('deprecated function make_link called by ' . $file . ' on line ' . $code_line . ' - use carl_make_link instead.', WARNING);
	return carl_make_link($new_request_vars, $base_path, $type, $convert_entities, $maintain_original);
} // }}}

/**
 * Deprecated -- use carl_construct_link()
 * @deprecated
 */
function construct_link ( $new_request_vars = array(''), $preserve_request_vars = array(''), $base_path = '' )
{
	$call_info = array_shift( debug_backtrace() );
	$code_line = $call_info['line'];
	$file = array_pop( explode('/', $call_info['file']));
	trigger_error('deprecated function construct_link called by ' . $file . ' on line ' . $code_line . ' - use carl_construct_link instead', WARNING);
	return carl_construct_link($new_request_vars, $preserve_request_vars, $base_path);
}

/**
 * Deprecated -- use carl_construct_relative_link()
 * @deprecated
 */
function construct_relative_link ( $new_request_vars = array(''), $preserve_request_vars = array(''), $base_path = '', $convert_entities = true )
{
	$call_info = array_shift( debug_backtrace() );
	$code_line = $call_info['line'];
	$file = array_pop( explode('/', $call_info['file']));
	trigger_error('construct_relative_link called by ' . $file . ' on line ' . $code_line . ' - use carl_construct_relative_link instead', WARNING);
	return carl_construct_relative_link ( $new_request_vars, $preserve_request_vars, $base_path, $convert_entities );
}

/**
 * Deprecated -- use carl_make_redirect()
 * @deprecated
 */
function make_redirect ( $new_request_vars, $base_path)
{
	$call_info = array_shift( debug_backtrace() );
	$code_line = $call_info['line'];
	$file = array_pop( explode('/', $call_info['file']));
	trigger_error('make_redirect called by ' . $file . ' on line ' . $code_line . ' - use carl_make_redirect instead', WARNING);
	return carl_make_redirect ( $new_request_vars, $base_path);
}

/**
 * Deprecated -- use carl_construct_redirect()
 * @deprecated
 */
function construct_redirect( $new_request_vars = array(''), $preserve_request_var = array(''), $base_path = '' )
{
	$call_info = array_shift( debug_backtrace() );
	$code_line = $call_info['line'];
	$file = array_pop( explode('/', $call_info['file']));
	trigger_error('construct_redirect called by ' . $file . ' on line ' . $code_line . ' - use carl_construct_redirect instead', WARNING);
	return carl_construct_redirect( $new_request_vars, $preserve_request_var, $base_path);
}
?>
