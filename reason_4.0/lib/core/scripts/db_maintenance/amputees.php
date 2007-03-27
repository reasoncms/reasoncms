<?php
	// amputees: entities without a record in its entity tables

	include_once( 'reason_header.php' );
	include_once(CARL_UTIL_INC . 'db/db.php' );
	include_once(CARL_UTIL_INC . 'dev/pray.php' );
	
	reason_include_once( 'function_libraries/user_functions.php' );
	if(!on_secure_page())
	{ 
		force_secure();
	}
	$current_user = check_authentication();
	if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to fix amputees.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
	}
	
	?>
	<html>
	<head>
	<title>Reason: Fix Amputees</title>
	</head>
	<body>
	<h1>Fix Amputees</h1>
	<?php
	if(empty($_POST['do_it']))
	{
	?>
	<form method="post">
	<p>Amputees are entities that do not have records in all of their tables. Amputees are generally invisible to Reason, since entities are grabbed all-at-once.</p>
	<p>When this script is run, it will find all of the amputees in Reason and fix them by creating records in the appropriate tables.</p>
	<p> This script must be run after a table is added to a type.  We should probably make this script a cron job and/or have this code be run when finishing a type.</p>
	<input type="submit" name="do_it" value="Run the script" />
	</form>
	<?php
	}
	else
	{
		connectDB( REASON_DB );
		reason_include_once('classes/amputee_fixer.php');
		$fixer = new AmputeeFixer();
		$fixer->fix_amputees();
		$fixer->generate_report();
	}
?>
	</body>
	</html>
