<?php
$return_arr = array();

$dbhost = 'reason.luther.edu';
$dbuser = 'ceeb_user';
$dbpass = '!ceebdatabasesarethebest!';
$dbname = 'ceeb';

$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Error connecting to mysql');
mysql_select_db($dbname);

/* If connection to database, run sql statement. */
if ($conn)
{
	$fetch = mysql_query("SELECT * FROM ceeb where name like '%" . mysql_real_escape_string($_GET['term']) . "%'");
        //echo $fetch;
	/* Retrieve and store in array the results of the query.*/

	while ($row = mysql_fetch_array($fetch, MYSQL_ASSOC)) {
		$row_array['current_hs_ceeb'] = $row['ceeb'];
		$row_array['current_hs_name'] = $row['name'];
		$row_array['current_hs_address'] = $row['addr1'];
		$row_array['addr2'] = $row['addr2'];
		$row_array['current_hs_city'] = $row['city'];
		$row_array['current_hs_state'] = $row['state'];
                $row_array['current_hs_zip'] = $row['zip'];

        array_push($return_arr,$row_array);
    }
}

/* Free connection resources. */
mysql_close($conn);

//$availableTags = array(
//    'ActionScript' => 'ActionScript' ,
//    'AppleScript' => 'AppleScript',
//    'Asp' => 'Asp',
//    'BASIC' => 'BASIC',
//    'C' => 'C',
//    'C++' => 'C++',
//    'Clojure' => 'Clojure',
//    'COBOL' => 'COBOL',
//    'ColdFusion' => 'ColdFusion',
//    'Erlang' => 'Erlang',
//    'Fortran' => 'Fortran',
//    'Groovy' => 'Groovy',
//    'Haskell' => 'Haskell',
//    'Java' => 'Java',
//    'JavaScript' => 'JavaScript',
//    'Lisp' => 'Lisp',
//    'Perl' => 'Perl',
//    'PHP' => 'PHP',
//    'Python' => 'Python',
//    'Ruby' => 'Ruby',
//    'Scala' => 'Scala',
//    'Scheme' => 'Scheme'
//    "ActionScript",
//    "AppleScript",
//    "Asp",
//    "BASIC",
//    "C",
//    "C++",
//    "Clojure",
//    "COBOL",
//    "ColdFusion",
//    "Erlang",
//    "Fortran",
//    "Groovy",
//    "Haskell",
//    "Java",
//    "JavaScript",
//    "Lisp",
//    "Perl",
//    "PHP",
//    "Python",
//    "Ruby",
//    "Scala",
//    "Scheme"
//);
array_push($return_arr, $availableTags);

/* Toss back results as json encoded array. */
echo json_encode($return_arr);
?>