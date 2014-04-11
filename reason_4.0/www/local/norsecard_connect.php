<?php
include_once('reason_header.php');
reason_include_once('function_libraries/user_functions.php');

try {
  #connectDB('norsecard');
  # MS SQL Server and Sybase with PDO_DBLIB
  $dbh = new PDO("mysql:host=database-1.luther.edu;dbname=reason_norsecard", "norsecard_user", "KawSXRO`F[kB.bxQf|gk");

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
    $user = "$user@luther.edu";
    if ($action == 'users'){
      $query = "Select id_number as Patron_SK, first_name as First_Name, middle_name as Middle_Name, last_name as Last_Name, alt_id_number DatatelID, '' as Plan, '' as LastUsed, email as Email from norsecard where email = :user
group by id_number";
      $statement=$dbh->prepare($query);
      $statement->bindValue(':user', $user);
    }
    else if ($action == 'tender' && $patron > 0) {
      $query = "Select tender_name as Tender, sum(transaction_amount) as Balance from norsecard where email = :user and id_number=:patron and transaction_datetime >= STR_TO_DATE(:startDate, '%m/%d/%Y') and transaction_datetime <= STR_TO_DATE(:endDate, '%m/%d/%Y') group by id_number, tender_name";
      #$query = "Select Tender, Balance from Av_user_pcs_PatronAccount pa inner join Av_user_pcs_Patron p on p.Patron_SK = pa.Patron_SK_FK and p.email = '%'+:user+'@luther.edu%' where Patron_SK_FK=:patron and Tender <> 'Charge'  union Select 'Charge' as 'Tender', sum(tl.Transaction_Amount) from Av_user_pcs_TransactionLog tl inner join Av_user_pcs_Patron p on p.Patron_SK = tl.Patron_SK_FK and p.email = '%'+:user+'@luther.edu%' where Patron_SK_FK=:patron and Transaction_Amount <> 0 and tl.Process_Date_Time between :startDate and dateadd(dd, 1, :endDate) and tl.Tender='Charge' and tl.Function='Sale' group by tl.ID_Number ";
      $statement=$dbh->prepare($query);
      $statement->bindValue(':user', $user);
      $statement->bindValue(':patron', $patron);
      $statement->bindValue(':startDate', $startdate);
      $statement->bindValue(':endDate', $enddate);
    } else if ($action == 'transactions' && $patron > 0) {
      $query = "Select '' as ID_Number, transaction_datetime as Transaction_Time, case when terminal_name like '%C-store%' then 'C-store' when terminal_name like '%Marty''s%' then 'Marty''s' when terminal_name like '%Oneota%' then 'Oneota' when terminal_name like '%Bookstore%' then 'Bookstore' when terminal_name like '%Cafeteria%' then 'Cafeteria' when terminal_name like '%Print Shop%' then 'Print Shop'  when terminal_name like '%Sunnyside%' then 'Sunnyside Cafe' when terminal_name like '%Catering%' then 'Catering' when terminal_name like '%CFL Box Office%' then 'CFL Box Office' when terminal_name like '%Post Office%' then 'Post Office' else terminal_name end as Terminal, transaction_function as transaction_function, '' as Previous_Balance, transaction_amount as Transaction_Amount, '' as Resulting_Balance, tender_name as Tender from norsecard where email = :user and id_number=:patron and transaction_datetime >= STR_TO_DATE(:startDate, '%m/%d/%Y') and transaction_datetime <= STR_TO_DATE(:endDate, '%m/%d/%Y') order by transaction_datetime desc";
      #$query = "Select tl.ID_Number, convert(varchar(25), tl.Process_Date_Time,100) as Transaction_Time, case when terminal_name like '%C-store%' then 'C-store' when terminal_name like '%Marty''s%' then 'Marty''s' when terminal_name like '%Oneota%' then 'Oneota' when terminal_name like '%Bookstore%' then 'Bookstore' when terminal_name like '%Cafeteria%' then 'Cafeteria' when terminal_name like '%Print Shop%' then 'Print Shop'  when terminal_name like '%Sunnyside%' then 'Sunnyside Cafe' when terminal_name like '%Catering%' then 'Catering' when terminal_name like '%CFL Box Office%' then 'CFL Box Office' when terminal_name like '%Post Office%' then 'Post Office' else terminal_name end as [Terminal], case tl.Function when 'Delete from Balance' then 'Total Balance' else tl.Function end as [transaction_function], tl.Previous_Balance, tl.Transaction_Amount, tl.Resulting_Balance, tl.Tender from Av_user_pcs_TransactionLog tl inner join Av_user_pcs_Patron p on p.Patron_SK = tl.Patron_SK_FK and p.email = '%'+:user+'@luther.edu%' where Patron_SK_FK=:patron and Transaction_Amount <> 0 and tl.Process_Date_Time between :startDate and dateadd(dd, 1, :endDate) and terminal_name <> 'End of Day Console' order by Process_Date_Time desc";
      $statement=$dbh->prepare($query);
      $statement->bindValue(':user', $user);
      $statement->bindValue(':patron', $patron);
      $statement->bindValue(':startDate', $startdate);
      $statement->bindValue(':endDate', $enddate);
    }

  $statement->execute();
  if ($row = $statement->fetch()) {
    $data=true;
    $rows[] = json_encode($row);
    while ($row = $statement->fetch()) {
      $rows[] = json_encode($row);
    }
    echo '{';
    echo '"results": [';
    echo implode(', ', $rows);
    echo ']}';
  }
  if (!$data) {
    echo '{';
    echo '"results": []}';
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
