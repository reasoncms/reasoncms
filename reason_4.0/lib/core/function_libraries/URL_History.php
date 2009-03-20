<?
/*
    URL History Functions
    
    definitions: 
    
    fully resolved URL: 
    hostname + site_URL + (base_URL of parent(s))* + base_URL of current page 
    
    Here we are trying to solve the problem of broken URLs. The solution 
    involves a few steps. 
    
    1:  A table in the Reason Database will be kept to track the following
        bits of information: a fully resolved URL, page_ID, unique_ID and a 
        timestamp. 
        
    2:  When a page is finished, the above table will be queried. If there
        is no fully resolved URL and page_ID pair in the database, it is
        added. If the pair exists, the timestamp is updated.
    
    3:  When a user would normally get a 404 error, the above table is 
        queried for the requested URL. If rows are returned, the page 
        decides what action to take (which will probably be a redirect to
        the fully resolved URL of page_ID with the most recent time stamp). 
*/

include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );
reason_include_once( 'function_libraries/relationship_finder.php' );
reason_include_once( 'function_libraries/url_utils.php' );

$GLOBALS['dig_calls'] = 0;
$GLOBALS['build_calls'] = 0;

function update_URL_history( $page_id, $check_children = true )
{
	//connectDB( REASON_DB );
	$found = false;
	
	$query = 'SELECT * FROM URL_history WHERE page_id ="' . $page_id . '" ORDER BY timestamp DESC LIMIT 1';
	$results = db_query( $query );
	
	$current_time = time();    
	$URL = build_URL( $page_id );    
	
	if( mysql_num_rows( $results ) > 0 )
	{    
		while( $row = mysql_fetch_array( $results ) )
		{
			if( in_array( $URL, $row ) )
			{
				$found = true;
				$query = 'UPDATE URL_history SET timestamp = "' . $current_time . '" WHERE page_id = "' . $page_id . '"';
				db_query( $query );
			}
		}
	}

	if( !$found )
	{
		$query = 'INSERT INTO URL_history SET ' . 
						'url = "' . $URL . '", ' .
						'page_id = "' . $page_id . '", ' .
						'timestamp = "' . $current_time . '"';
		
		$results = mysql_query( $query );
		
		if( empty( $results ) )
			die( '<br />:: ' . $query . '::' . $results );
	}
	
	if( $check_children )
		update_children( $page_id );
		
	return true;
}

function update_children( $page_id )
{
	$es = new entity_selector();
	$es->description = 'Selecting children of the page';
	
	// find all the children of this page
	$es->add_type( id_of('minisite_page') );
	$es->add_left_relationship( $page_id, relationship_id_of( 'minisite_page_parent' ) );
	$es->set_order('sortable.sort_order ASC');
	$offspring = $es->run_one();
	
	foreach( $offspring as $child )
	{
		if( $child->id() != $page_id )
			update_URL_history( $child->id() );
	}
}


/*
    Looks for the URL in the URL_history table. 
    If there is no URL, send a 404 header. 
    If there are URLs, send a 301 header and a Location header.
*/
function check_URL_history( $request_uri )
{
	$url_arr = parse_URL( $request_uri );
	
	// This catches links that might not have had a trailing slash
	// pages always have a trailing slash in the db
	$URL = '/'.trim_slashes($url_arr['path']).'/';
	$URL = str_replace('//','/',$URL);
	
	if( !empty( $url_arr['query'] ) )
	{
		$query_string = '?'.$url_arr['query'];
	}
	else
	{
		$query_string = '';
	}
	
	$query =  'SELECT * FROM URL_history WHERE url ="' . addslashes ( $URL ) . '" AND deleted="no" ORDER BY timestamp DESC';
	$results = db_query( $query );
	
	$num_results = mysql_num_rows( $results );
	
	if( $num_results == 0 )
	{
		//do normal 404
		header( 'http/1.1 404 Not Found' );
	}
	else
	{
		$row = mysql_fetch_array( $results ); // grab the first result (e.g. most recent)
		$page_id = $row['page_id'];
		
		$es = new entity_selector( );
		$es->add_type( id_of( 'minisite_page') ); //'3317' id_of( 'Page' )
		$es->add_relation( 'entity.id = "' . $page_id . '"' );
		$es->add_relation( 'entity.state = "Live"' );
		$results = $es->run_one();  
		$path = build_URL( $page_id );     
		if( !empty($results) && !empty($path) )
		{
			
			if( empty( $GLOBAL['SSL_SESSION_ID'] ) )
			{
				$path = build_URL( $page_id );
				$URL = 'http://' . REASON_HOST . $path . $query_string;
			}
			else
			{
				$URL = 'https://' . REASON_HOST . $path . $query_string;
			}
			
			//header( 'http/1.1 301 Moved Permanently' );
			header( 'Location: ' . $URL, true, 301 );
			die();
		}   
	}
}
?>
