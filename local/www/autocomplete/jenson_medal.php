<?php
$return_arr = array();

$dbhost = 'database.luther.edu';
$dbuser = 'jenson_user';
$dbpass = '!jensonmedalisthebest!';
$dbname = 'jenson_medal';

$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Error connecting to mysql');
mysql_select_db($dbname);

/* If connection to database, run sql statement. */
if ($conn)
{
        $qstring = "SELECT * FROM nominees where first_name like '%' or last_name like '%' or username like '%' LIMIT 20";
        $fetch = mysql_query($qstring);
	
	/* Retrieve and store in array the results of the query.*/
	while ($row = mysql_fetch_array($fetch, MYSQL_ASSOC)) {
		$row_array['id'] = $row['PERSON'];
		$row_array['value'] = $row['first_name'] . " " . $row['last_name'] . ", " . $row['username'] . "";
        array_push($return_arr,$row_array);
    }
}

/* Free connection resources. */
mysql_close($conn);

/* Toss back results as json encoded array. */
echo json_encode($return_arr);
?>