<?php
/**
 *  URL History Functions
 *  
 *  Definitions: 
 *  
 *  fully resolved URL: 
 *  hostname + site_URL + (base_URL of parent(s))* + base_URL of current page 
 *  
 *  Here we are trying to solve the problem of broken URLs. The solution 
 *  involves a few steps. 
 *  
 *  1:  A table in the Reason Database will be kept to track the following
 *      bits of information: a fully resolved URL, page_ID, unique_ID and a
 *      timestamp. 
 *      
 *  2:  When a page is finished, the above table will be queried. If there
 *      is no fully resolved URL and page_ID pair in the database, it is
 *      added. If the pair exists, the timestamp is updated.
 *  
 *  3:  When a user would normally get a 404 error, the above table is 
 *      queried for the requested URL. If rows are returned, the page 
 *      decides what action to take (which will probably be a redirect to
 *      the fully resolved URL of page_ID with the most recent time stamp). 
 *
 *  @package reason
 *  @subpackage function_libraries
 *  @todo fully document functions
 */

/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
include_once( CARL_UTIL_INC . 'db/sqler.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'function_libraries/url_utils.php' );

/**
 * Get the most recent entry for the page_id - if its URL does not match the URL of the page now, create a new entry and update
 * the timestamp of the last location (if it exists).
 *
 * @todo consider whether or not there is any utility in checking children if we are not creating an entry for the current
 *       location the page
 *
 * @param integer $page_id
 * @param boolean $check_children
 * @return boolean
 */
function update_URL_history( $page_id, $check_children = true )
{
	$page = new entity($page_id);
	if (reason_is_entity($page, 'minisite_page') && !$page->get_value('url'))
	{
		$builder = new reasonPageURL();
		$builder->disable_static_cache(); // force a refresh for this instance of the builder in case the URL just changed
		$builder->set_id($page->id());
		$url = $builder->get_relative_url();
		
		if (!empty($url)) // we only bother if we can get a url
		{
			// lets grab the most recent entry for this page_id
		
			$d = new DBselector();
			$d->add_table( 'history', 'URL_history' );
			$d->add_field( 'history', 'id', 'id' );
			$d->add_field( 'history', 'url', 'url' );
			$d->add_field( 'history', 'page_id', 'page_id' );
			$d->add_field( 'history', 'timestamp', 'timestamp' );
			$d->add_relation( 'history.page_id = ' . $page_id );
			$d->set_num(1);
			$d->set_order( 'history.timestamp DESC, history.id DESC' ); // get highest id of highest timestamp
			$result = db_query( $d->get_query() , 'Error getting most recent URL_history entry for page '.$page_id );
			if( $row = mysql_fetch_assoc($result))
			{
				$create_new = ($row['url'] == $url) ? false : true;
				$update_old = $create_new; // if we create new in this case we want to update the old as well
			}
			else
			{
				$create_new = true;
				$update_old = false;
			}
			if ($create_new) // lets use the SQLer to do this
			{
				$sqler = new SQLER();
				$cur_time = time();
				$values = array('url' => $url, 'page_id' => $page_id, 'timestamp' => $cur_time);
				$sqler->insert('URL_history', $values);
				if ($update_old) // the old row is the one we already grabbed - lets update its timestamp to be just before what we just created
				{
					$sqler->update_one('URL_history', array('timestamp' => ($cur_time - 1)), $row['id']);
				}
			}
			if( $check_children ) update_children( $page_id );
			return true;
		}
	}
	trigger_error('update_URL_history called on entity id ' . $page_id . ' which does not appear to be a valid minisite_page entity with a url (is it an external URL?)');
	return false;
}

/**
 * @param integer $page_id
 * @return NULL
 */
function update_children( $page_id )
{
	$es = new entity_selector();
	$es->description = 'Selecting children of the page';
	
	// find all the children of this page
	$es->add_type( id_of('minisite_page') );
	$es->add_left_relationship( $page_id, relationship_id_of( 'minisite_page_parent' ) );
	$es->add_relation ('((url.url = "") OR (url.url IS NULL))');
	$es->set_order('sortable.sort_order ASC');
	$offspring = $es->run_one();
	
	foreach( $offspring as $child )
	{
		if( $child->id() != $page_id ) update_URL_history( $child->id() );
	}
}


/**
 * Header the browser to the current location of the most recent page
 * that occupied a given URL
 *
 * How it works:
 *
 * 1. Looks for the URL in the URL_history table. 
 *
 * 2. If there is no URL, send a 404 header. 
 *    If there are URLs, send a 301 header and a Location header to the
 *    location of the live page that most recent inhabited that URL.
 *
 * Important: Because it may attempt to header the client to a different URL, 
 * this method must be called before any output is started, or in the context
 * of output buffering. 
 *
 * @param string $request_uri a URL relative to the host root (e.g. /foo/bar/)
 * @return NULL
 *
 * @todo modify to make multidomain safe
 */
function check_URL_history( $request_uri )
{
	$url_arr = parse_URL( $request_uri );
	// This catches links that might not have had a trailing slash
	// pages always have a trailing slash in the db
	$URL = '/'.trim_slashes($url_arr['path']).'/';
	$URL = str_replace('//','/',$URL);
	$query_string = (!empty($url_arr['query'])) ? '?'.$url_arr['query'] : '';
	$query =  'SELECT * FROM URL_history WHERE url ="' . addslashes ( $URL ) . '" ORDER BY timestamp DESC';
	$results = db_query( $query );
	$num_results = mysql_num_rows( $results );
	
	if (mysql_num_rows($results) > 0)
	{
		while ($row = mysql_fetch_array( $results )) // grab the first result (e.g. most recent)
		{
			$page_id = $row['page_id'];
			$page = new entity($page_id);
			if (reason_is_entity($page, 'minisite_page') && ($page->get_value('state') == 'Live') && ($redir = reason_get_page_url($page)))
			{
				if ($redir == $request_uri)
				{
					//Could potentially update rewrites here, solving most times this happens, perhaps.
					trigger_error("A page should exist here, but apparently does not at the moment. A web administrator may need to run URL updating on this site.");
				} 
				else 
				{
					header( 'Location: ' . $redir . $query_string, true, 301 );
					exit();
				}
				
			}
		}
	}
	
	// if we have gotten this far and not found a URL lets send a 404
	http_response_code(404);
}
?>
