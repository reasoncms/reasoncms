<?php
session_start();

if (isset($_SESSION['test']))
{
	echo $_SESSION['test'];
}
else
{
	echo "session variable was not set";
}

echo '<pre>';
print_r($_SESSION);
echo '</pre>';
?>