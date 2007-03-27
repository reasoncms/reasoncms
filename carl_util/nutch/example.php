

<form method=POST action="example.php">
<input type=text name=query><input type=submit>
</form>

<hr>


<?php

	require_once('queryNutch.php');

	// get all the form arguments
	if(isset($_REQUEST['query'])){ $query = $_REQUEST['query']; } else { $query = '' ; }
	if(isset($_REQUEST['start'])){ $start = $_REQUEST['start']; } else { $start = 0 ; }
	if(isset($_REQUEST['step'])){ $step = $_REQUEST['step']; } else { $step = 10 ; }
	if(isset($_REQUEST['hitsPerSite'])){ $hitsPerSite = $_REQUEST['hitsPerSite']; } else { $hitsPerSite = 2; }

		echo "$query / $start / $step / $hitsPerSite <br />\n"; 

	// if we have not query, stop.
	if(empty($query)){ die(); }	

	// run the query
	$nutch = new nutchQuery($query , $start , $step , $hitsPerSite );

	// did we get anything?
	if(!$nutch->success){
  	echo "No Search Results.\n"; 
  	die();
  	} 

	// what did we get?
	echo "Found " . $nutch->result_count . " Results. \n";
	echo "Displaying " . ( $start + 1 ) . " to " . ( $start + count($nutch->results) ) . "<br />\n";

	echo "<hr>\n"; 

	// display the results
	while($nutch->nextResult()){
  	echo $nutch->current_item['title'] . "<br />\n"; 
  	echo "<a href=\"" . $nutch->current_item['link'] . "\">" . $nutch->current_item['link'] . "</a><br />\n"; 
		echo "<br />\n"; 
  	}


	echo "<br />\n"; 

	// present a link to the previous result set (if available)
	$prev = $nutch->prevQueryArgs();
	if($prev != FALSE){
		echo "<a href=\"example.php?query=" . $query .
											"&start=" . $prev['start'] .
											"&step=" . $prev['hitsPerPage'] . 
											"&hitsPerSite=" . $prev['hitsPerSite'] . 
											"\">Prev</a>";	
		}	


	// present links for every result set found
	$all_links = $nutch->allQueryArgs();

	foreach($all_links as $link){

			if(isset($link['current'])){ $name = "<span style=\"color: red; font-size: 2em ;\">" . $link['start'] . "</span>" ; }
			else { $name = $link['start'] ; }

			echo "<a href=\"example.php?query=" . $query .
							"&start=" . $link['start'] .
							"&step=" . $link['hitsPerPage'] . 
							"&hitsPerSite=" . $link['hitsPerSite'] . 
							"\">" . $name . "</a> ";	

		}


	// present links for the next result set
	$next = $nutch->nextQueryArgs();
	if($next != FALSE){
		echo "<a href=\"example.php?query=" . $query .
											"&start=" . $next['start'] .
											"&step=" . $next['hitsPerPage'] . 
											"&hitsPerSite=" . $next['hitsPerSite'] . 
											"\">Next</a>";	
		}	


?>
