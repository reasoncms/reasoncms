<?php
include_once('reason_header.php');
reason_include_once('function_libraries/user_functions.php');

try {
  # MS SQL Server and Sybase with PDO_DBLIB
  $dbh = new PDO("dblib:host=odyssey.luther.edu:2638;dbname=odyssey", "pcsuser", "viewonly");


  if ($user = reason_check_authentication()) {
    if (array_key_exists('action', $_GET)) {
      $action = $_GET['action'];
    }
    if (array_key_exists('patron', $_GET)) {
      if (is_numeric($_GET['patron'])) {
        $patron = $_GET['patron'];
      }
    }
    // TODO: make sure this stuff really is a date?
    if (array_key_exists('startdate', $_GET)){
      $startdate = $_GET['startdate'];
    }
    if (array_key_exists('enddate', $_GET)){
      $enddate = $_GET['enddate'];
    }
    if ($action == 'users'){
      $query = "Select top 400 Patron_SK,First_Name, Middle_Name, Last_Name, Alternate_ID_Number as DatatelID, Plan, Last_Date_Card_Used as LastUsed , Email from Av_user_pcs_Patron where email like '%'+:user+'@luther.edu%' order by Alternate_ID_Number";
      $statement=$dbh->prepare($query);
      $statement->bindValue(':user', $user);
    }
    else if ($action == 'tender' && $patron > 0) {
      $query = "Select Tender, Balance from Av_user_pcs_PatronAccount pa inner join Av_user_pcs_Patron p on p.Patron_SK = pa.Patron_SK_FK and p.email like '%'+:user+'@luther.edu%' where Patron_SK_FK=:patron and Tender <> 'Charge'  union Select 'Charge' as 'Tender', sum(tl.Transaction_Amount) from Av_user_pcs_TransactionLog tl inner join Av_user_pcs_Patron p on p.Patron_SK = tl.Patron_SK_FK and p.email like '%'+:user+'@luther.edu%' where Patron_SK_FK=:patron and Transaction_Amount <> 0 and tl.Process_Date_Time between :startDate and dateadd(dd, 1, :endDate) and tl.Tender='Charge' and tl.Function='Sale' group by tl.ID_Number ";
      $statement=$dbh->prepare($query);
      $statement->bindValue(':user', $user);
      $statement->bindValue(':patron', $patron);
      $statement->bindValue(':startDate', $startdate);
      $statement->bindValue(':endDate', $enddate);
    } else if ($action == 'transactions' && $patron > 0) {
      $query = "Select tl.ID_Number, convert(varchar(25), tl.Process_Date_Time,100) as Transaction_Time, case when tl.Terminal = 'End of Day Console' then 'End of Week' when tl.Terminal like '%C-store%' then 'C-store' when tl.Terminal like '%Marty''s%' then 'Marty''s' when tl.Terminal like '%Oneota%' then 'Oneota' when tl.Terminal like '%Bookstore%' then 'Bookstore' when tl.Terminal like '%Cafeteria%' then 'Cafeteria' when tl.Terminal like '%Print Shop%' then 'Print Shop'  when tl.Terminal like '%Sunnyside%' then 'Sunnyside Cafe' when tl.Terminal like '%Catering%' then 'Catering' when tl.Terminal like '%CFL Box Office%' then 'CFL Box Office' when tl.Terminal like '%Post Office%' then 'Post Office' else tl.Terminal end as [Terminal], case tl.Function when 'Delete from Balance' then 'Total Balance' else tl.Function end as [transaction_function], tl.Previous_Balance, tl.Transaction_Amount, tl.Resulting_Balance, tl.Tender from Av_user_pcs_TransactionLog tl inner join Av_user_pcs_Patron p on p.Patron_SK = tl.Patron_SK_FK and p.email like '%'+:user+'@luther.edu%' where Patron_SK_FK=:patron and Transaction_Amount <> 0 and tl.Process_Date_Time between :startDate and dateadd(dd, 1, :endDate) order by Process_Date_Time desc";
      $statement=$dbh->prepare($query);
      $statement->bindValue(':user', $user);
      $statement->bindValue(':patron', $patron);
      $statement->bindValue(':startDate', $startdate);
      $statement->bindValue(':endDate', $enddate);
    }

  $statement->execute();
  if ($row = $statement->fetch()) {
    $rows[] = json_encode($row);
    while ($row = $statement->fetch()) {
      $rows[] = json_encode($row);
    }
    echo '{';
    echo '"results": [';
    echo implode(', ', $rows);
    echo ']}';
  }
  unset($dbh);
  unset($statement);
  }
}
catch(PDOException $e) {
    echo $e->getMessage();
}
# close the connection
$dbh = null;