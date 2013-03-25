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
    if (array_key_exists('action', $_GET)) {
        $action = $_GET['action'];
    }
    if (array_key_exists('patron', $_GET)) {
        if (is_numeric($_GET['patron'])) {
            $patron = $_GET['patron'];
        }
    }
    if ($action == 'users'){
        $q = sybase_query("Select top 400 Patron_SK,First_Name, Middle_Name, Last_Name, Alternate_ID_Number as DatatelID, Plan, Last_Date_Card_Used as LastUsed , Email from Av_user_pcs_Patron where email like '%". $user ."@luther.edu%' order by Alternate_ID_Number ", $connection);
    } else if ($action == 'tender' && $patron > 0) {
        $q = sybase_query("Select Tender, Balance from Av_user_pcs_PatronAccount pa inner join Av_user_pcs_Patron p on p.Patron_SK = pa.Patron_SK_FK and p.email like '%". $user ."@luther.edu%' where Patron_SK_FK=".$patron, $connection);
    } else if ($action == 'transactions' && $patron > 0) {
        // TODO: add date sql logic in to limit on dates, 
        $q = sybase_query("Select tl.ID_Number, tl.Process_Date_Time as Transaction_Time, tl.Terminal, tl.Function as [transaction_function], tl.Previous_Balance, tl.Transaction_Amount, tl.Resulting_Balance, tl.Tender from Av_user_pcs_TransactionLog tl inner join Av_user_pcs_Patron p on p.Patron_SK = tl.Patron_SK_FK and p.email like '%". $user ."@luther.edu%' where Patron_SK_FK=". $patron  ." and Transaction_Amount <> 0 order by Tender, Process_Date_Time desc");
    }
    if ($q) {
        echo '{';
        echo '"results": [';
        while ($row = sybase_fetch_array($q)) {
            $rows[] = json_encode($row);
        }
        echo implode(', ', $rows);
        echo ']}';
    }
} else {
    echo 'Permission Denied';
}
sybase_close($connection);