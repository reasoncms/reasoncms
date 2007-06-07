<?php
	//xdebug_start_trace();
	//xdebug_start_profiling();
	
	ob_start();
	function getmicrotime()
	{
		list( $usec, $sec ) = explode( " ", microtime() );
		return ((float)$usec + (float)$sec);
	}
	$_page_timing_start = getmicrotime();
	
	// admin site needs sessioning
	// $reason_session = true;
	
	include_once( 'reason_header.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	force_secure_if_available();
	$authenticated_user_netid = reason_require_authentication('admin_login');

	if (isset($_GET['do']) && ($_GET['do'] === 'moveup' || $_GET['do'] === 'movedown'))
	{
		reason_include_once( 'classes/admin/rel_sort.php' );
		$background = (isset($_GET['xmlhttp']) && $_GET['xmlhttp'] === 'true') ? 'yes' : 'no';
		$relationship_sort = new RelationshipSort();
		$relationship_sort->init($_GET['site_id'], $_GET['rel_id'], $_GET['id'], $_GET['eid'], $_GET['rowid'], $_GET['do'], $authenticated_user_netid, $background);
		if ($relationship_sort->validate_request()) $relationship_sort->run();
	}
	
	reason_include_once( 'classes/admin/admin_page.php' );
 
    if(DISABLE_REASON_LOGIN)
    {
	    header( 'Location: /errors/maintenance.php'); //?estimate='.$maintenance_estimate );
	    die();
	}
	
	ini_set( 'upload_max_filesize','10M' );
	$f = new AdminPage();
	$authenticated = $f->authenticate();
	if ($authenticated)
	{
		$f->init(); // init returns false if the user cannot be authentication
		$f->run();
	}
	else
	{
		$unauthorized = id_of('unauthorized_reason_user'); // unauthorized_reason_user can be defined as a text blurb in master admin
		$e = new entity($unauthorized);
		if ($e->get_values()) echo $e->get_value('content');
		else
		{
			echo '<p>We\'re sorry, but we do not have any record of you being an authorized Reason user.</p>';
		}
	}

	$_page_timing_end = getmicrotime();
	echo '<!-- start time: '.$_page_timing_start.'   end time: '.$_page_timing_end.'   total time: '.round(1000*($_page_timing_end - $_page_timing_start), 1).' ms -->';

	//echo 'mem usage: '.xdebug_memory_usage().'<br/>';
	//xdebug_dump_function_trace();
	//xdebug_dump_function_profile(4);

?>

