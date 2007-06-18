

<?php

	// old url
	// http://search.carleton.edu/search/cgi-bin/htsearch.cgi 

	require_once('queryNutch.php');

	// get all the form arguments
	$query = '' ; 
	if(isset($_REQUEST['query'])){ $query = $_REQUEST['query']; } else { $query = '' ; }
	if(isset($_REQUEST['start'])){ $start = $_REQUEST['start']; } else { $start = 0 ; }
	if(isset($_REQUEST['step'])){ $step = $_REQUEST['step']; } else { $step = 20 ; }
	if(isset($_REQUEST['hitsPerSite'])){ $hitsPerSite = $_REQUEST['hitsPerSite']; } else { $hitsPerSite = 0; }
	if(isset($_REQUEST['mode'])){ $mode = $_REQUEST['mode']; } else { $mode = ""; }

	// included for htdig form compatibility 
	if(isset($_REQUEST['words'])){ $query .= " " . $_REQUEST['words']; }

	// limit to a particular URL
	if(isset($_REQUEST['restrict'])){ 
		$url = $_REQUEST['restrict'] ; 
		$url = preg_replace('/http:\/\//i', '', $url);
		$url = preg_replace('/https:\/\//i', '', $url);
		$query .= " url:" . $url ; 
		}

	// reformat the query string for required words specified on the advanced search page
	if(isset($_REQUEST['required_words']) && !empty($_REQUEST['required_words'])){

		$required_words = explode(" ", $_REQUEST['required_words']);

		foreach($required_words as $word){
			$query .= " +$word";
			}

 		}


	// reformat the query string for the exact phrase specified on the advanced search page
	if(isset($_REQUEST['exact_phrase']) && !empty($_REQUEST['exact_phrase'])){ 

		$phrase = $_REQUEST['exact_phrase'] ; 
		$phrase = preg_replace('/"/' , "" , $phrase);
		$phrase = preg_replace("/'/" , "" , $phrase);

		$query .= " +'" . $phrase . "'"; 

		}


	// reformat the query string for the excluded words specified on the advanced search page 
	if(isset($_REQUEST['exclude_words']) && !empty($_REQUEST['exclude_words'])){ 

		$exclude_words = explode(" ", $_REQUEST['exclude_words']);

		foreach($exclude_words as $word){
			$query .= " -$word";
			}

		}



	// the top half of the page (blue bar, et al.)
	sendHeader();


	// send the seach form (simple or advanced)
	if($mode == "advanced"){
		sendAdvancedForm();
		}
	else {
		sendSimpleForm($query);
		}



	// echo "$query / $start / $step / $hitsPerSite <br />\n"; 

	// if we have no query, stop.
	if(empty($query)){ sendFooter(); die(); }	

	// run the query
	$nutch = new nutchQuery($query , $start , $step , $hitsPerSite );

	// did we get anything?
	if(!$nutch->success || $nutch->result_count == 0){
	//if(!$nutch->success){
  	echo "No Search Results.\n"; 
		sendfooter();
  	die();
  	} 

	// make quoted search terms viable for subsequent navigation links
	$query = urlencode($query);

	// what did we get?
	echo "Found " . $nutch->result_count . " Results. \n";
	echo "Displaying " . ( $start + 1 ) . " to " . ( $start + count($nutch->results) ) . "<br />\n";

	echo "<hr>\n"; 


	// display the results
	while($nutch->nextResult()){

		// determine the path portion of the url so we can guess at filetypes
		$parsed_url = parse_url($nutch->current_item['link']);
		$path = $parsed_url['path'];	

		// display the title as a link
	  	echo "<strong>" . "<a href=\"" . $nutch->current_item['link'] . "\">" . htmlentities(strip_tags($nutch->current_item['title'])) . "</a></strong><br />\n"; 
		
		// crude indenting
		echo "<table border=0><tr><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>\n";


		$show_file_icon = 0 ; 

		$link = "<a href=\"" . $nutch->current_item['link'] . "\">";

		if(preg_match('/.doc$/i', $path)){ echo "$link<img align=absmiddle src='doc.png' border=0></a>Word Document<br />\n"; $show_file_icon = 1; } 
		if(preg_match('/.ppt$/i', $path)){ echo "$link<img align=absmiddle src='ppt.png' border=0></a>PowerPoint Document<br />\n"; $show_file_icon = 1; } 
		if(preg_match('/.xls$/i', $path)){ echo "$link<img align=absmiddle src='xls.png' border=0></a>Excel Document<br />\n"; $show_file_icon = 1; } 
		if(preg_match('/.pdf$/i', $path)){ echo "$link<img align=absmiddle src='pdf.png' border=0></a>PDF Document<br />\n"; $show_file_icon = 1; } 
		if(preg_match('/ogg$/i', $path)){ echo "A Ogg Vorbis Document</a><br />\n"; $show_file_icon = 1; } 
		if(preg_match('/mp3$/i', $path)){ echo "An MP3 Document</a><br />\n"; $show_file_icon = 1; } 

		// display result details and link
		if($show_file_icon == 0){
			if(isset($nutch->current_item['description'])){ echo htmlentities(strip_tags($nutch->current_item['description'])) . "<br />\n"; }
	  		echo "<a href=\"" . $nutch->current_item['link'] . "\">" . $nutch->current_item['link'] . "</a><br />\n"; 
			}


		echo "</td></tr></table>\n"; 
		echo "<br />\n"; 
  	}


	echo "<br />\n"; 
	echo "<center>";


	echo "<div id='search_content'><div class='pagination'>\n";




	// present a link to the previous result set (if available)
	$prev = $nutch->prevQueryArgs();
	if($prev != FALSE){
		echo "<a href=\"search.php?query=" . $query .
											"&start=" . $prev['start'] .
											"&step=" . $prev['hitsPerPage'] . 
											"&hitsPerSite=" . $prev['hitsPerSite'] . 
											"\">Prev</a>&nbsp;";	
		}	


	// present links for every result set found
	$all_links = $nutch->allQueryArgs();

	if(count($all_links) > 1){

		// array of pages which should be includes in the navigation list where key = page number and value = 1 if it should be displayed
		$pages_to_display = array();

		$page_count = 1 ; 

		// find out how many pages of results we've received
		$total_pages = count($all_links);

		// find out the position of the the result page being show
		$current_position = 1 ; 
		foreach($all_links as $link){
			if(isset($link['current'])){ break; }
			$current_position++ ;
			}

		// the start of result page links to be shown should be no more than 5 pages before the result page being shown
		$per_page_start = $current_position - 2 ; 
		if($per_page_start < 1){ $per_page_start = 1 ; }


		// the end of result page links to be shown should be no more than 5 pages after the result page being shown.
		$per_page_end = $per_page_start + 5 ;
		
		// if the per_page_end exceeds the total number of pages, set the range = to the (last page - 10) to the (last page).
		if($per_page_end > $total_pages){ 
			$per_page_end = $total_pages ;
			$per_page_start = $total_pages - 5 ; 
			}
		
		for($n = $per_page_start ; $n <= $per_page_end ; $n++ ){
			$pages_to_display{$n} = 1 ; 
			}
			


		// trailing "big jump" result links

		// find out how many pages there are after the last result page link above
		$next_page_count_total = $total_pages - $per_page_end ; 

		// divide that number by 5 to determine the distance between the "big jump" result page links to be shown.
		$next_page_skip_rate = 1 ; 
		if($next_page_count_total > 0){
			$next_page_skip_rate = intval($next_page_count_total / 3) ; 
			if($next_page_skip_rate < 1){ $next_page_skip_rate = 1 ; }
			}

		

		// mark pages to display based on the skip rate
		for($n = $per_page_end + $next_page_skip_rate ; $n <= $total_pages ; $n = $n + $next_page_skip_rate){
			$pages_to_display{$n} = 1 ; 
			}


		// leading "big jump" result links

		// find out how many pages there are before the start of result page links above
		$prev_page_count_total = $per_page_start - 1 ;

		// divide that number by 5 to determine the distance between the "big jump" result page links to be show.
		$prev_page_skip_rate = 1 ; 
		if($prev_page_count_total > 0){
			$prev_page_skip_rate = intval($prev_page_count_total / 3);
			if($prev_page_skip_rate < 1){ $prev_page_skip_rate = 1 ; }
			}

		// mark pages to display based on the skip rate
		for($n = 1 ; $n < $per_page_start ; $n = $n + $prev_page_skip_rate ){
			$pages_to_display{$n} = 1 ; 
			}






		foreach($all_links as $link){

				// displays result number rather than page count	
				// if(isset($link['current'])){ $name = "<span style=\"color: red; font-size: 2em ;\">" . $link['start'] . "</span>" ; }
				// else { $name = $link['start'] ; }

				if(isset($pages_to_display[$page_count])){

					$name = $page_count ; 

					if(isset($link['current'])){ echo "<strong>"; }

					echo "<a href=\"search.php?query=" . $query .
									"&start=" . $link['start'] .
									"&step=" . $link['hitsPerPage'] . 
									"&hitsPerSite=" . $link['hitsPerSite'] . 
									"\">" . $name . "</a> ";	

					if(isset($link['current'])){ echo "</strong>"; }

					}
				
				$page_count++ ; 
	
			}
		}



	// present links for the next result set
	$next = $nutch->nextQueryArgs();
	if($next != FALSE){
		echo "<a href=\"search.php?query=" . $query .
											"&start=" . $next['start'] .
											"&step=" . $next['hitsPerPage'] . 
											"&hitsPerSite=" . $next['hitsPerSite'] . 
											"\">Next</a>";	
		}	


	echo "</div></div>\n"; 
	echo "</center>";


sendfooter();

function sendHeader(){

?>

<html>
<head>
<title>Carleton College: Search</title>

<!-- Do not edit these -->
<LINK REL="stylesheet" TYPE="text/css" HREF="http://www.carleton.edu/global_stock/css/LocationBar.css">
<LINK REL="stylesheet" TYPE="text/css" HREF="http://www.carleton.edu/global_stock/css/JavascriptIncludeChildMenus.css">
<script language="JavaScript" src="http://www.carleton.edu/global_stock/js/PersistentNavigation.js"></script>
<!-- Do not edit these -->


<style>
div#search_content div.pagination {
  font-size:120%;
}
div#search_content div.pagination a {
  padding:.25em .33em;
  border:1px solid #8AA3D0;
}
div#search_content div.pagination a:hover {
  background-color:#8AA3D0;
}
div#search_content div.pagination a.prev, div#search_content div.pagination a.next {
  border:none;
}
div#search_content div.pagination strong a, div#search_content div.pagination strong a:hover {
  background-color:#8AA3D0;
  text-decoration:none;
  color:#000;
  cursor:default;
}
</style>

</head>
<!-- Do not edit these -->
<script language="JavaScript" src="http://www.carleton.edu/global_stock/js/JavascriptIncludeNotice.js"></script>
<script language="JavaScript" src="http://www.carleton.edu/global_stock/js/JavascriptIncludeBodyTag.js"></script>

<script language="JavaScript" src="http://www.carleton.edu/global_stock/js/JavascriptIncludePersistentNavigation_Info.js"></script>
<!-- Do not edit these -->

<!-- Location Bar -->
<!-- Remember to add ** class="locationBarLinks" ** to your anchor tags -->
<table border="0" cellpadding="4" cellspacing="0" width="100%"><tr><td align="left" valign="middle" width="100%" class="locationBarText">&nbsp;You are here:&nbsp;<a href="/" class="locationBarLinks">Search</a>&nbsp;>&nbsp;Search Results</td></tr></table>

<!-- Content Starts Here -->

<BLOCKQUOTE>

<?php
	}



function sendSimpleForm($query=""){

	// display the current search term
	if($query != ""){ 
		$clean_query = htmlspecialchars($query);
		echo "<H1><FONT COLOR='#6699CC'>Search results for $clean_query</FONT></H1>\n"; 
		}
	else { echo "<H1><FONT COLOR='#6699CC'>Search</FONT></H1>\n"; }

?>


<form method=GET action="search.php">
<table border=0>
<tr><td><input type=text name=query></td><td><input type=submit value=Search></td></tr>
<tr><td></td><td><a href="search.php?mode=advanced">Advanced Search</a></td></tr>
</table>
</form>

<hr>





<?php 

	}


function sendAdvancedForm(){

?>

<H1><FONT COLOR='#6699CC'>Advanced Search</FONT></H1>

<form method=POST action="search.php">
<table border="0"><tr><td>

<table border="0">
<tr><td align=right>with all of the words:</td><td><input type=text name=required_words></td></tr>
<tr><td>with the exact phrase</td><td><input type=text name=exact_phrase></td></tr>
<tr><td>without the words:</td><td><input type=text name=exclude_words></td></tr>
<tr><td colspan=2 align=right><input type=submit value=Search></td></tr>
</table>


</td><td valign=top>



<table border="0">
<tr><td>
<select name=step>
<option value="10">10</option>
<option value="20" selected>20</option>
<option value="30">30</option>
<option value="40">40</option>
<option value="50">50</option>
<option value="60">60</option>
<option value="70">70</option>
<option value="80">80</option>
<option value="90">90</option>
<option value="100">100</option>
</select> results per page.
<br />
<a href="search.php">Basic Search</a>
</td></tr>
</table>



</td></tr>

</table>
</form>

<?php


	}



function sendfooter(){

	print "<table border=0 width='100%'><tr><td align=right><i>Maintained By: <a href='mailto:mbockol@carleton.edu'>Matt Bockol</a></i></td></tr></table>";

	}



?>
