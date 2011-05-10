<?php
$return_arr = array();

$dbhost = 'reason.luther.edu';
$dbuser = 'root';
$dbpass = '!ceebdatabasesarethebest!';
$dbname = 'ceeb';

$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Error connecting to mysql');
mysql_select_db($dbname);

/* If connection to database, run sql statement. */
if ($conn)
{
	$fetch = mysql_query("SELECT * FROM ceeb where name like '%" . mysql_real_escape_string($_GET['term']) . "%'");

	/* Retrieve and store in array the results of the query.*/

	while ($row = mysql_fetch_array($fetch, MYSQL_ASSOC)) {
		$row_array['ceeb'] = $row['ceeb'];
		$row_array['name'] = $row['name'];
		$row_array['addr1'] = $row['addr1'];
		$row_array['addr2'] = $row['addr2'];
		$row_array['city'] = $row['city'];
		$row_array['state'] = $row['state'];
                $row_array['zip'] = $row['zip'];

        array_push($return_arr,$row_array);
    }

}

/* Free connection resources. */
mysql_close($conn);

/* Toss back results as json encoded array. */
echo json_encode($return_arr);
?>