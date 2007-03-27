<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Reason Upgrade Scripts</title>
</head>

<body>
<?php
include ('reason_header.php');
reason_include_once('function_libraries/user_functions.php');
force_secure();
$user_netID = check_authentication();

$directory = dirname($_SERVER['SCRIPT_FILENAME']);
$dirs = array();
if(is_dir( $directory ) )
{
	$handle = opendir( $directory );
	while( $entry = readdir( $handle ) )
	{
		if( is_dir( $directory.'/'.$entry ) && substr($entry, 0, 1) != '.' )
		{
			$dirs[] = $entry;
		}
	}
}
if(!empty($dirs))
{
	echo '<h1>Reason upgrade scripts</h1>'."\n";
	echo '<ul>'."\n";
	foreach($dirs as $dir)
	{
		echo '<li><a href="'.$dir.'/">'.prettify_string($dir).'</a></li>'."\n";
	}
	echo '</ul>'."\n";
}
else
{
	echo '<h1>No upgrade scripts found</h1>'."\n";;
}

?>
</body>
</html>
