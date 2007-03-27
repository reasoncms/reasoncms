<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Get Dept Info</title>
</head>

<body>
<h1>Get Dept Info from LDAP</h1>
<?php
	if(!empty($_REQUEST['dept']))
	{
		include_once( 'paths.php' );
		include_once( CARL_UTIL_INC.'dev/pray.php' );
		include_once( CARL_UTIL_INC.'ldap/ldap.php' );
		$dept_name = $_REQUEST['dept'];
		$info = new LDAPHelper();
		$info->connect();
		$info->search_dept(trim($dept_name));
		$dept_result = $info->get_entries();
		$dept = current($dept_result);
		$info->close();
		pray($dept);
		echo '<h2>Again?</h2>'."\n";
	}
?>
<form name="form1" id="form1" method="post" action="">
	Department Name: <input type="text" name="dept" />
    <input type="submit" name="Submit" value="Submit" />
</form>
</body>
</html>
