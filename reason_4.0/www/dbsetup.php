<?php
  // Database bootstrap script
  $success = true;
  $dbhost = '';
  $dbuser = '';
  $dbpass = '';
  $reason_user = 'reason_user';
  $reason_pass = '';
  
  if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Step #1: get values from form
    $dbhost = $_POST['database_host'];
    $dbuser = $_POST['database_user'];
    $dbpass = $_POST['database_password'];
    $reason_user = $_POST['reason_user'];
    $reason_pass = $_POST['reason_pass'];
    
    // Step #2: attempt to connect to the database
    $db = mysql_connect($dbhost, $dbuser, $dbpass);
    if (!$db) {
      $success = false;
      echo "<p>Failed to connect to $dbhost.</p>";
    }
    
    // Step #3: attempt to create the Reason database
    $create_database_sql = 'CREATE DATABASE reason';
    if (!mysql_query($create_database_sql, $db)) {
      $success = false;
      echo "<p>Failed to create reason table.</p>";
      break;
    }
    
    // Step #4: create reason user
    $create_reason_user_sql = 'grant all privileges on reason.* to '.$reason_user.'@'.$dbhost.' identified by \''.$reason_pass.'\'';
    if (!mysql_query($create_reason_user_sql, $db)) {
      $success = false;
      echo "<p>Failed to create reason user</p>";
      break;
    }
    
    // Step #5: flush privileges
    $flush_sql = 'flush privileges';
    if (!mysql_query($flush_sql, $db)) {
      $success = false;
      echo "<p>Failed to flush privileges</p>";
      break;
    }
    
    // Step #6: attempt to create the Thor database
    $create_database_sql = 'CREATE DATABASE thor'; 
    if (!mysql_query($create_database_sql, $db)) {   
      $success = false;
      echo "<p>Failed to create thor table.</p>";
      break;
    }
     
    // Step #7: create reason user on thor
    $create_reason_user_sql = 'grant all privileges on thor.* to '.$reason_user.'@'.$dbhost.' identified by \''.$reason_pass.'\'';
    if (!mysql_query($create_reason_user_sql, $db)) {
      $success = false;
      echo "<p>Failed to give reason user privileges on thor</p>";
      break;
    }
     
    // Step #8: flush privileges
    $flush_sql = 'flush privileges';
    if (!mysql_query($flush_sql, $db)) {
      $success = false;
      echo "<p>Failed to flush privileges</p>";
      break;
    }
    
    // Step #9: select database
    if (!mysql_select_db('reason', $db)) {
      $success = false;
      echo "<p>Failed to select database reason</p>";
      break;
    }
    
    // Step #10: import database
    $db_file = file_get_contents('/var/reason_package/reason_4.0/data/dbs/reason4.0.sql');
    $lines = explode(";\n", $db_file);
    foreach($lines as $line) {
      if (!mysql_query($line, $db)) {
        $success = false;
        echo "<p>Failed to import database</p>";
        echo mysql_error($db);
        break;
      }
    }    
  } else {
    $success = false;
  }
?>
<head>
  <style>
  <!--
    div.formcontainer span {
      display: block;
    }
    
  -->
  </style>
</head>
<body>
<?php
  if ($success) {
    $reason_user = htmlspecialchars($reason_user);
    $reason_pass = htmlspecialchars($reason_pass);
    $dbhost = htmlspecialchars($dbhost);
    $dbstring = "<?xml version=\"1.0\" encoding=\"ISO-8859-15\"?>
<!-- WARNING *** ENSURE THIS FILE IS NOT WEB ACCESSIBLE *** -->
<databases>
<database>
<connection_name>reason_connection</connection_name>
<db>reason</db>
<user>$reason_user</user>
<password>$reason_pass</password>
<host>$dbhost</host>
</database>

<database>
<connection_name>thor_connection</connection_name>
<db>thor</db>
<user>$reason_user</user>
<password>$reason_pass</password>
<host>$dbhost</host>
</database>
</databases>";
    echo "<p>Congratulations, your reason database is configured!</p>";
    echo "<p>Copy and paste the following text into your dbs.xml file</p>";
?>
<textarea rows="20" cols="40"><?=htmlspecialchars($dbstring)?></textarea>
<?php
    echo "<p>You may now return to the <a href=\"setup.php\">main setup page</a></p>";
    die;
  }
?>
<form method="post">
  <fieldset>
    <legend>MySQL authentication information</legend>
    <div class="formcontainer">
      <span>
        <label for="database_host">Host:</label>
        <input type="text" name="database_host" value="<?=$dbhost?>"/>
      </span>
      <span>
        <label for="database_user">Username:</label>
        <input type="text" name="database_user" value="<?=$dbuser?>"/>
      </span>
      <span>
        <label for="database_password">Password:</label>
        <input type="password" name="database_password" value="<?=$dbpass?>"/>
      </span> 
    </div>
  </fieldset>
  <fieldset>
    <legend>Reason user information</legend>
    <div class="formcontainer">
      <span>
        <label for="reason_user">Reason user:</label>
        <input type="text" name="reason_user" value="<?=$reason_user?>"/>
      </span>
      <span>
        <label for="reason_pass">Reason password:</label>
        <input type="password" name="reason_pass" value="<?=$reason_pass?>"/>
      </span>
    </div>
  </fieldset>
  <input type="submit" name="savedb" value="Configure" />
</form>
</body>