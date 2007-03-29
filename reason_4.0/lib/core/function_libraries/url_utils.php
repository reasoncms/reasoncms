<?php

// grab contents of a URL.  includes authentication to get at URLs in protected areas.
// returns a string or false on error
function get_reason_url_contents( $url )
{
	// Includes the variables $http_authentication_username and $http_authentication_password
	include( HTTP_CREDENTIALS_FILEPATH );
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
	curl_setopt( $ch, CURLOPT_USERPWD, $http_authentication_username.':'.$http_authentication_password);
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
	$page = curl_exec( $ch );
	// check for errors
	if( empty( $page ) )
	{
		trigger_error( 'CURL: '.curl_error( $ch ) );
	}
	curl_close( $ch );
	return $page;
}

/*
* (mixed)remote_filesize($uri,$user='',$pw='')
* returns the size of a remote stream in bytes or
* the string 'unknown'. Also takes user and pw
* incase the site requires authentication to access
* the uri
*/
function get_remote_filesize($uri,$user='',$pw='')
{
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

// Takes any url and attempts to identify the most likely site 
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

// Takes a path relative to the http root of the server, e.g. /foo/bar/baz/
// returns an array of site entities, with the most likely (e.g. with the closest match) first
function get_potential_sites_from_path($path)
{
	if(!empty($path))
	{
		$path_parts = explode('/',trim_slashes($path));
		$values = array();
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

//replaces a protocol with another protocol
//like switching http to https or http to rtsp
function alter_protocol($url,$current_protocol,$new_protocol)
{
	return preg_replace("/^".$current_protocol.":\/\//" , $new_protocol."://" , $url, 1);
}

/**
 * returns a full url for a minisite_page
 * the function will cache the minisite navigation object for a site so when called repeatedly it
 * should only instantiate minisite navigation once per site
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
		echo 'get_minisite_page_link drawing nav for site id ' . $site_id . '<br />';
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
function make_link( $new_request_vars = array(''), $base_path = '', $type = '', $convert_entities = true, $maintain_original = true ) // {{{
{
    $url = get_current_url();
    $parts = parse_url($url);
    if ($maintain_original && !empty($parts['query'])) parse_str($parts['query'], $cur_request_vars);
    else $cur_request_vars = array();
    if (empty($base_path)) $base_path = $parts['path'];
    $baseurl = $parts['scheme'] . '://' . $parts['host'] . $base_path;
    if ($type == 'relative') $baseurl = $base_path;

    $params = array_merge( $cur_request_vars, $new_request_vars );
    $link = '';
    foreach( $params AS $key => $val )
    {
        if(!empty( $val ) )
        {
            $link .= '&'.$key.'='.$val;
        }
    }
    $link = substr( $link, strlen( '&' ) );
    if ($convert_entities) $link = htmlentities($link);
    if (!empty($link))
        return trim($baseurl.'?'.$link);
    else return trim($baseurl);
} // }}}

function construct_link ( $new_request_vars = array(''), $preserve_request_vars = array(''), $base_path = '' )
{
	if (empty($preserve_request_vars))
	{
		return make_link( $new_request_vars, $base_path, '', true, false );
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
		$params = (isset($preserve_array)) ? array_merge( $preserve_array, $new_request_vars ) : $new_request_vars;
		return make_link( $params, $base_path, '', true, false );
	}
}

function construct_relative_link ( $new_request_vars = array(''), $preserve_request_vars = array(''), $base_path = '', $convert_entities = true )
{
	if (empty($preserve_request_vars))
	{
		return make_link( $new_request_vars, $base_path, 'relative', true, false );
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
		$params = (isset($preserve_array)) ? array_merge( $preserve_array, $new_request_vars ) : $new_request_vars;
		return make_link( $params, $base_path, 'relative', true, false );
	}
}

function make_redirect ( $new_request_vars, $base_path = '' )
{
	return make_link ($new_request_vars, $base_path, '', false, true);
}

function construct_redirect( $new_request_vars = array(''), $preserve_request_var = array(''), $base_path = '' )
{
	if (empty($preserve_request_vars))
	{
		return make_link( $new_request_vars, $base_path, '', false, false );
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
		$params = (isset($preserve_array)) ? array_merge( $preserve_array, $new_request_vars ) : $new_request_vars;
		return make_link( $params, $base_path, '', false, false );
	}
}

/**
 *	Get the URL of a page
 *
 *	This function will provide the URL of a page of a particular type or types on a site
 *
 *	@param entity $site The site to look in
 *	@param page_tree $tree the page tree object for the site; this must be already initialized
 *	@param array $page_types The page types that are acceptable
 *	@param boolean $as_uri Returns a fully qualified URI if true; otherwise returns a URL relative to web root
 *	@param boolean $secure Uses https if true. This parameter only has an effect if $as_uri is true.
 */

function get_page_link( &$site, &$tree, $page_types, $as_uri = false, $secure = false ) // {{{
{
	if(empty($site) || empty($page_types))
	{
		trigger_error('site and page types must all be passed to get_page_link',EMERGENCY);
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
			$ret = 'https://'.REASON_HOST.$ret;
		}
		else
		{
			$ret = 'http://'.REASON_HOST.$ret;
		}
	}
	
	return $ret;
}


// moved from URL history
function build_URL( $page_id )
{
	$URL = '';
	$es = new entity_selector( );
	$es->add_type( id_of( 'minisite_page') ); //'3317' id_of( 'Page' )
	$es->add_relation( 'entity.id = "' . $page_id . '"' );
	$es->set_num(1);
	$results = $es->run_one();
	if( !empty( $results ) )
	{
		$page = current($results);
		$URL = $page->get_value( 'url_fragment' ).'/';

		$URL = dig_for_URL( $page_id, $URL );
		
		$URL = str_replace( '//', '/', $URL );

		return $URL;
	}
	else
	{
		return false;
	}
}

function dig_for_URL( $page_id, $URL )
{
	$es = new entity_selector();
	$es->add_type( id_of( 'minisite_page') );
	$es->add_right_relationship( $page_id, relationship_id_of('minisite_page_parent') );
	$es->set_num(1);
	$results = $es->run_one();
	if( !empty($results) )
	{
		$page = current($results);
		if( $page->get_value( 'id' ) == $page_id )
		{
			//echo $page->get_value( 'id' ) . '::' . $page_id . '~<br />';
			return get_site_URL( $page_id ) . $URL;
		}
		else
		{
			//echo $page->get_value( 'id' ) . '::' . $page_id . '!<br />';
			if($page->get_value( 'url_fragment' ))
				$URL = $page->get_value( 'url_fragment' ) . '/' . $URL;
			return dig_for_URL( $page->get_value( 'id' ), $URL );
		}
	}
}    

function get_site_URL( $page_id )
{
	reason_include_once( 'function_libraries/relationship_finder.php' );
	$es = new entity_selector();
	$es->add_type( id_of( 'site') );
	$es->add_left_relationship( $page_id, relationship_finder( 'site', 'minisite_page', 'owns' ) ); //relationship_id_of('owns') 
	$es->set_num(1);
	$results = $es->run_one();
	
	if( !empty( $results )  )
	{
		$site = current($results);
		return $site->get_value( 'base_url' );
	}
}
?>
