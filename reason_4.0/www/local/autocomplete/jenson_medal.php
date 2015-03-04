<?php
$return_arr = array();
$db_name = gethostname() == 'reason' ? 'reason_jenson_medal' : 'reasondev_jenson_medal';
$dbhost = 'database-1.luther.edu';
$dbuser = 'jenson_user';
$dbpass = '!jensonmedalisthebest!';

$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Error connecting to mysql');
mysql_select_db($dbname);

/* If connection to database, run sql statement. */
if ($conn)
{
		$cleaned_term = mysql_real_escape_string($_GET['term']);
        $remove_chars = array(",", "(", ")", "-");
        $cleaned_term = str_replace($remove_chars, " ", $cleaned_term);
        $term_parts = explode(" ", $cleaned_term, 10);
		
        $qstring = "SELECT * FROM nominees where";
		
		$num_parts = count($term_parts);
        $i = 0;
        foreach($term_parts as $part){
            $i++;
            $qstring .= " (first_name like '%" . $part . "%' or";
            $qstring .= " last_name like '%" . $part . "%' or";
            $qstring .= " username like '%" . $part . "%')";
            if($i <> $num_parts){
                 $qstring .= " and";
            }else{
                $qstring .= " LIMIT 20;";
            }
        }
		
        $fetch = mysql_query($qstring);
	
	/* Retrieve and store in array the results of the query.*/
	while ($row = mysql_fetch_array($fetch, MYSQL_ASSOC)) {
		$row_array['id'] = $row['username'];
		$row_array['value'] = $row['first_name'] . " " . $row['last_name'] . ", " . $row['username'] . "";
        array_push($return_arr,$row_array);
    }
}

/* Free connection resources. */
mysql_close($conn);

/* Toss back results as json encoded array. */
echo json_encode($return_arr);
?>