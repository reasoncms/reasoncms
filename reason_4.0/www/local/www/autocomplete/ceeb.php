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
        //explode input term into base parts:  'decorah high' into 'decorah' and 'high'
        $cleaned_term = mysql_real_escape_string($_GET['term']);
        $remove_chars = array(",", "(", ")", "-");
        $cleaned_term = str_replace($remove_chars, " ", $cleaned_term);
        //$term_parts = split(" ", $cleaned_term, 10);
        $term_parts = explode(" ", $cleaned_term, 10);

        $qstring = "SELECT * FROM hs_ceeb where";
        $num_parts = count($term_parts);
        $i = 0;
        foreach($term_parts as $part){
            $i++;
            $qstring .= " (name like '%" . $part . "%' or";
            $qstring .= " city like '%" . $part . "%' or";
            $qstring .= " state like '%" . $part . "%')";
            if($i <> $num_parts){
                 $qstring .= " and";
            }else{
                $qstring .= " LIMIT 20;";
            }
        }

   //     $row_array['id'] = "testing";
   //     $row_array['value'] = $qstring;

        $fetch = mysql_query($qstring);
	//$fetch = mysql_query("SELECT * FROM ceeb where name like '%" . mysql_real_escape_string($_GET['term']) . "%'");
        //echo $fetch;
	/* Retrieve and store in array the results of the query.*/

	while ($row = mysql_fetch_array($fetch, MYSQL_ASSOC)) {
		//$row_array['current_hs_ceeb'] = $row['ceeb'];
                $row_array['id'] = $row['ceeb'];
		//$row_array['current_hs_name'] = $row['name'];
                $row_array['value'] = $row['name'] . " (" . $row['city'] . ", " . $row['state'] . ")";
		//$row_array['current_hs_address'] = $row['addr1'];
		//$row_array['addr2'] = $row['addr2'];
		//$row_array['current_hs_city'] = $row['city'];
		//$row_array['current_hs_state'] = $row['state'];
                //$row_array['current_hs_zip'] = $row['zip'];

        array_push($return_arr,$row_array);
    }
}

/* Free connection resources. */
mysql_close($conn);

/* Toss back results as json encoded array. */
echo json_encode($return_arr);
?>