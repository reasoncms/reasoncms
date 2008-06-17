<?php 

/**
 *
 * nutchQuery - requests search results from nutch.carleton.edu's XML interface
 * see example.php for how to use the class
 *
 * @todo generalize and/or move out of reason package
 * @todo remove dependency on Reason the application
 *
 * $search = new nutchQuery($query_term , $start_of_results , $number_of_results_to_return , $results_per_site);
 * $search->nextResult returns a result with each successive call.
 * $search->nextQueryArgs provides the fields necessary for nutchQuery to get the next batch of results for the same query. Returns FALSE if none exist.
 * $search->prevQueryArgs provides the fields necessary for nutchQuery to get the previous batch of results for the same query. Returns FALSE if none exist.
 * $search->allQueryArgs provides an array of arrays, containing every set of options for nutchQuery to get all the results from beginning to end.
 * getURL($url) grabs the nutch query result xml file (used by nutchQuery)
 *
 * @author Matt Bockol
 *
 */

require_once('paths.php');

// This should not be defined here if this file stays inside reason_package
// it makes the entire class unusable outside of Carleton -- mr
define('NUTCH_BASE_URL', 'http://nutch.carleton.edu/nutch-0.9/opensearch?');

define('MAGPIE_DIR', MAGPIERSS_INC);
require_once(MAGPIE_DIR . 'rss_parse.inc');
require_once(LIBCURLEMU_INC . 'libcurlemu.inc.php');

class nutchQuery {

	var $original_query ; 	// save what the user asked for

	var $results ; 					// the array of result items
	var $current_num = 0 ; 	// where in the nextResult array we are (used by nextResult)
	var $current_item ;			// the item from the result set that's currently selected (set in nextResult)

	var $shortListCount = 0 ; // if hitsPerSite is specified we requery nutch to see the maximum number of results that would be displayed before hitsPerSite 
														// would be set to 0 (meaning all hits per site). We do this so we can calculate allQueryArgs properly.

	var $result_count = 0 ; // how many results nutch found
	var $start = 0 ; 				// where nutch last began looking
	var $step = 10 ; 				// how many results to display
	var $success = FALSE ; 	// did the query succeed? assume the worst.

	var $hitsPerSite = 0 ; 

	// queryString = "whatver the user requested"
	// start = where in the result set to begin returning results (0 = the beginning)
	// hitsPerPage = how many items to return from this query
	// hitsPerSite = 2 - include only 'recommended' pages, 0 - include everything
	function nutchQuery($query_string="", $start=0 , $hitsPerPage=10 , $hitsPerSite=0 , $shortListTest=FALSE){

		// if no term was specified the search fails
		if(empty($query_string) || strcmp($query_string, "")==0 ){ $this->success = FALSE ; return ; }

		// save the original query
		$this->original_query = $query_string ; 

		// make sure the user's query is safe to include in the url
		$encoded_query_string = urlencode($query_string);

		// build the url
		$url = NUTCH_BASE_URL . "query=" . $encoded_query_string . "&start=" . $start . "&hitsPerPage=" . $hitsPerPage . "&hitsPerSite=" . $hitsPerSite  ;

		// query Nutch, return false if we fail to receive a result
		$xml_result = $this->getURL($url);
		if(empty($xml_result)){ $this->success = FALSE ; return ;}

		// parse the query into our rss object	
		$rss = new MagpieRSS( $xml_result, 'UTF-8' );

		// if there was an RSS parse error, the search fails
		if($rss->ERROR){ echo $rss->ERROR . "\n"; $this->success = FALSE ; return ; }

		// store the result array
		$this->results = $rss->items ;

		// store the number of results in total 
		$this->result_count = $rss->channel['opensearch']['totalresults'] ; 

		// store where in the search our results began
		$this->start = $rss->channel['opensearch']['startindex'] ;

		// store how many items have been displayed
		$this->step =  $rss->channel['opensearch']['itemsperpage'] ;

		// make sure nextResult starts at the beginning of this new query
		$this->current_num = 0 ; 

		// store how many hits per host to be displayed (0 = show all hits for all hosts. Nutch defaults to 2 in the JSP)
		$this->hitsPerSite = $hitsPerSite ; 

		// if we've specified a hitsPerSite > 0 then nutch may return fewer answers than it reports (as set in $this->result_count).
		// in the nutch JSP the user is presented with a "show all hits" link which reduces the hitsPerSite value to 0.
		// to duplicate this functionality, we check for the maximum number of items which would be displayed when the hitsPerSite > 0
		// by re-performing the query with a high (1000) hitsPerPage value. This gets us the number (shortListCount) that the user would
		// see in the nutch interface.  We can use that to determine when nextQueryArgs should switch from the non-zero hitsPerSite to 0 (show all).
		if($shortListTest == FALSE && $hitsPerSite > 0){
			$shortList = new nutchQuery($this->original_query, 0 , 1000 , $hitsPerSite , TRUE);
			$this->shortListCount = count($shortList->results) ;
			} 

		$this->success = TRUE ; 

		}

	function nextResult(){

		// if there are items remaining in the search result
		if(!empty($this->results[$this->current_num])){
			$this->current_item = $this->results[$this->current_num] ; 
			$this->current_num++ ; 
			return TRUE ; 
		}
		else {
			// we've run out of items
			$this->current_num = 0 ; 
			return FALSE ; 
			}

		}


	// give the current search, return an array with the nutchQuery arguments necessary to find the next set of results
	// returns FALSE if there aren't any (we're already at the end of the results for instance)
	function nextQueryArgs(){

		// if we're at the end of our search results, return FALSE ; 
		if( ($this->start + $this->step >= $this->result_count) &&
				($this->hitsPerSite == 0) 
			 ){ return FALSE ; }
		
		// where to begin the next search 	
		$new_start = $this->start + $this->step ; 

		$hitsPerSite = $this->hitsPerSite ;


		// if the last query did not provide a complete step's worth of results and the query has been specified with 
		// a hitsPerSite restriction then there are more results yet to see.  We set hitsPerSite = 0 for the next query 
		// so that nutch will provide a complete set instead of the one limited per site.
		if($this->hitsPerSite > 0 && ( $this->start + $this->step ) > $this->shortListCount){
			$new_start = 0 ; 
			$hitsPerSite = 0 ; 
			}

		return array(
			'query' => $this->original_query ,
			'start' => $new_start , 
			'hitsPerPage' => $this->step ,
			'hitsPerSite' => $hitsPerSite , 
			);

		}


	// given the current search, return an array with the nutchQuery arguments necessary to find the previous set of results
	// returns FALSE if there aren't any (we're already at the start of the results for instance)
	function prevQueryArgs(){

		// if we're already at the start, return 0
		if($this->start < 1){ return FALSE ; }

		// set start to be one full step back from its current position
		$new_start = $this->start - $this->step ; 

		// if the new start is less than zero, make it zero (negative search results, hmm, ah, the opposite of what you were looking for)
		if($new_start < 1){ $new_start = 0 ; }

		return array(
			'query' => $this->original_query , 
			'start' => $new_start , 
			'hitsPerPage' => $this->step ,
			'hitsPerSite' => $this->hitsPerSite ,
			);

		}

	// given the current search, return an array of arrays, containing the full set of possible search arguments (query, start, step, hitsPerSite) for the query
	// this would allow you to generate a list of links to jump to any point in the search results.
	function allQueryArgs(){

		// the array of nextQueryArgs arrays
		$querySet = array();

		// save current query values to restore at the end of the function
		$current_start = $this->start ; 
		$current_hitsPerSite = $this->hitsPerSite ;

		// start at the beginning
		$this->start = 0 ; 

		// populate the first queryArgs

		// if we're at the very first result, make sure to mark it as the current view
		if($current_start == 0){
			$firstArgs = array('query' => $this->original_query , 'start' => $this->start , 'hitsPerPage' => $this->step , 'hitsPerSite' => $this->hitsPerSite , 'current' => 1 );
			}
		else {
			// otherwise we're looking some pages away from the start and the current search mark below will catch it.
			$firstArgs = array('query' => $this->original_query , 'start' => $this->start , 'hitsPerPage' => $this->step , 'hitsPerSite' => $this->hitsPerSite);
			}
		array_push($querySet , $firstArgs);

		// loop through all the nextQueryArgs until we exhaust them, adding each to the querySet array
		while($queryArgs = $this->nextQueryArgs()){

			// mark the array from the current search
			if($queryArgs['start'] == $current_start){
				$queryArgs['current'] = 1 ; 
				}

			array_push($querySet , $queryArgs);

			// adjust these values so nextQueryArgs has them to work with
			$this->start = $queryArgs['start'];
			$this->hitsPerSite = $queryArgs['hitsPerSite'];

			} 

		// restore the start and hitsPerSite values to the original query values so that individual calls to nextQueryArgs and prevQueryArgs will still work.
		$this->start = $current_start ; 
		$this->hitsPerSite = $current_hitsPerSite ; 

		return $querySet ; 

		}


	// uses curl to request a url and returns the result as a string
	// in this case we're hitting the nutch server and getting the xml search response
	function getURL($url)
	{
		$xml = '';
		
		// create the curl object
		$ch=curl_init();

		// set the URL we're querying
		curl_setopt($ch, CURLOPT_URL, $url);

		// makes curl_exec return the response rather than echoing to STDOUT
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		
		// get the response
		if( $xml = curl_exec ($ch) )
		{
			$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
			if($http_code >= 300)
			{
				$xml = '';
				trigger_error('Nutch web service returned http status '.$http_code.'; clearing result');
			}
		}

		// clean up
		curl_close ($ch);

		return $xml;
		}

	}

?>
