<?php
header('Content-type: text/html; charset=utf-8');

include_once('object.php');

$loki = new Loki('the_field_id',get_data(),'all',3619,true);
$loki2 = new Loki('the_field_id_2',get_data(),'all',3619,true);

?>

<html>
<head>
<title>Hel-Loki Test</title>
</head>

<body>
<form action="exampleResults.php3">

	<?php $loki->print_form_children() ?><br />
	<?php $loki2->print_form_children() ?><br />
	<input type="submit" value="Submit" />

</form>
</body>

</html>

<?php

function connectDB($dbName) {
 
	include("/usr/local/etc/php3/dbstuff.php3");

	// connect to server
	$db= @mysql_connect($dbhost, $dbuser, $dbpasswd) or die("Not able to connect to the server at this time. Please try again later.");
	
	// select database
	@mysql_select_db($dbName, $db) or die("Not able to connect to the database at this time. Please try again later.");
	
	return $db;

}

function get_data() {
	$db = connectDB('test');

	$result = mysql_query("SELECT pagecontent FROM chaplains_office WHERE id='1'",$db);

	while($row = mysql_fetch_row($result))
		$story = $row[0];

	return $story;
}

?>
