<?php
include_once('reason_header.php');
reason_include_once('function_libraries/user_functions.php');
 
putenv("FREETDSCONF=/etc/freetds/freetds.conf");
$connection = sybase_connect('odyssey',"pcsuser","viewonly");
 
// test connection
if (!$connection) {
    echo "Couldn't make a connection!";
    exit;
} else if ($user = reason_check_authentication()) {
    // TODO: when checking for patron > 0 below we fail right now when patron doesn't exist in url parameters
    //       we probably want to check if patron exists and is greater than 0 so it doesn't fail for no index
    //       of patron on $_GET
    if ($_GET['action'] == 'users'){
        $q = sybase_query("Select top 400 Patron_SK,First_Name, Middle_Name, Last_Name, Alternate_ID_Number as DatatelID, Plan, Last_Date_Card_Used as LastUsed , Email from Av_user_pcs_Patron where email like '". $user ."%' order by Alternate_ID_Number ", $connection);
    } else if ($_GET['action'] == 'tender' && $_GET['patron'] > 0) {
        $q = sybase_query("Select Tender, Balance from Av_user_pcs_PatronAccount where Patron_SK_FK=".$_GET['patron'], $connection);
    } else if ($_GET['action'] == 'transactions' && $_GET['patron'] > 0) {
        // TODO: add date sql logic in to limit on dates
        $q = sybase_query('Select top 2 ID_Number, Process_Date_Time as Transaction_Time, Terminal, Function, Previous_Balance, Transaction_Amount, Resulting_Balance, Tender from Av_user_pcs_TransactionLog where Patron_SK_FK='. $_GET['patron']  .' and Transaction_Amount <> 0 order by Tender, Process_Date_Time desc');
    }
    // TODO: make sure this fails gracefully if none of the above query logic happened ($q didn't get rows)
    echo '{';
    echo '"results": [';
    while ($row = sybase_fetch_array($q)) {
        $rows[] = json_encode($row);
    }
    //print_r($rows);
    echo implode(', ', $rows);
    echo ']}';
} else {
    echo 'Not Allowed';
}
sybase_close($connection);