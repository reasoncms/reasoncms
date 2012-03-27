<?php
/**
 * Create a feed
 * 
 * This script will run the requested RSS feed
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
$reason_session = false;
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );

$start_time = get_microtime();

// clean up type id
if(!empty($_GET['type_id']))
{
	$type_id = turn_into_int($_GET['type_id']);
}
// clean up site id
if(!empty($_GET['site_id']))
{
	$site_id = turn_into_int($_GET['site_id']);
}
// clean up feed file name
if(!empty($_GET['feed'])) //use requested feed script if given
{
	// remove everything that might allow an arbitrary file to be requested
	// The file must be in reason's feeds directory and have not periods or spaces in its name
	// .php should not be included in the request
	$requested_file = str_replace(array('/','\\','.',' '),'',$_GET['feed']);
}

if(!empty($type_id))
{
	$type = new entity( $type_id );
	
	if(!empty($requested_file)) //use requested feed script if given
	{
		$feed_file = $requested_file;
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
	
	if(!empty($site_id))
	{
		$site = new entity($site_id);
		$feed = new $feed_class( $type, $site );
	}
	else
	{
		$feed = new $feed_class( $type );
	}
	// Since we are using mod_rewrite to handle URLs for feeds,
	// we have to do a little fancy footwork to get any variables passed
	// on the GET string.  Basically, the original REQUEST_URI has the
	// query string we are interested in, so we parse that URL and then
	// parse the query string.  Then, we merge the two query strings back
	// into the superglobal one.
	$url_arr = parse_url( get_current_url() );
	$apparent_get = array();
	if( !empty( $url_arr[ 'query' ] ) )
		parse_str( $url_arr[ 'query' ], $apparent_get );
	// all additional request items must be integers.
	// This is a simple way to prevent SQL injection
	// if we need to do more at a later point we can 
	// use a cleanup rules-style method of
	// managing request stuff.
	$cleanup_rules = $feed->get_cleanup_rules();
	// original request clobbers new request
	$full_get = array_merge( $apparent_get, $_GET );
	foreach($full_get as $key=>$val)
	{
		if(empty($cleanup_rules[$key]))
		{
			$cleanup_rules[$key] = array('function'=>'turn_into_int');
		}
	}
	$full_get = carl_clean_vars( $full_get, $cleanup_rules );
	$feed->set_request_vars($full_get);
	$feed->run();
}
else
{
	http_response_code(400);
	echo '<html><head><title>Feed did not work</title><meta name="robots" content="none" /></head><body><h1>Feed did not work</h1><p>Use the form "?type_id=xx [ &site_id=yy ]"</p></body></html>';
}

reason_log_page_generation_time( round( 1000 * (get_microtime() - $start_time) ) );


?>
