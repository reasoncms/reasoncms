<?php
/**
 * The script that bootstraps the Reason administrative interface
 *
 * This is also where hooks to AJAXy calls are placed (though this should probably be changed), basic profiling, and initial authentication (e.g. does Reason know who you are)
 *
 * @package reason
 * @subpackage admin
 * @todo Remove the ini_set that fixes max upload filesize at 10 megs (though this does not seem to have the effect one would imagine, as larger files can be uploaded...)\
 * @todo Develop a better system for supporting an XML/JSON/whatever API
 * @todo remove fallback check to DISABLE_REASON_LOGIN by the release of RC 1
 */

	//xdebug_start_trace();
	//xdebug_start_profiling();
	
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
	$auth_user_id = get_user_id( $authenticated_user_netid );

	if ($auth_user_id && isset($_GET['do']) && ($_GET['do'] === 'moveup' || $_GET['do'] === 'movedown'))
	{
		if(reason_user_has_privs($auth_user_id,'pose_as_other_user'))
		{
			if(!empty($_GET['user_id']))
			{
				$user_id = (integer) $_GET['user_id'];
				if(!empty($user_id))
				{
					$e = new entity($user_id);
					if($e->get_value('type') == id_of('user'))
						$user_netid = $e->get_value('name');
				}
			}
		}
		$user_netid = (isset($user_netid)) ? $user_netid : $authenticated_user_netid;
		reason_include_once( 'classes/admin/rel_sort.php' );
		$background = (isset($_GET['xmlhttp']) && $_GET['xmlhttp'] === 'true') ? 'yes' : 'no';
		$relationship_sort = new RelationshipSort();
		$relationship_sort->init($_GET['site_id'], $_GET['rel_id'], $_GET['id'], $_GET['eid'], $_GET['rowid'], $_GET['do'], $user_netid, $background);
		if ($relationship_sort->validate_request()) $relationship_sort->run();
	}
	
	reason_include_once( 'classes/admin/admin_page.php' );
 
 	/**
 	 * Reason 4 Beta 8 adds a setting DISABLE_REASON_ADMINISTRATIVE_INTERFACE, which is more specific than DISABLE_REASON_LOGIN,
 	 * and gets used if it is defined.
 	 */
 	if( (defined('DISABLE_REASON_ADMINISTRATIVE_INTERFACE')) ? DISABLE_REASON_ADMINISTRATIVE_INTERFACE : DISABLE_REASON_LOGIN )
    {
	    header( 'Location: /errors/maintenance.php'); //?estimate='.$maintenance_estimate );
	    die();
	}
	
	$f = new AdminPage();
	$authenticated = $f->authenticate();
	if ($authenticated)
	{
		$f->init(); // init returns false if the user cannot be authentication
		if ($f->should_run_api())
		{
			$f->run_api();
			exit();
		}
		else $f->run();
	}
	else
	{
		if (reason_unique_name_exists('unauthorized_reason_user'))
		{
			$e = new entity(id_of('unauthorized_reason_user'));
			echo $e->get_value('content');
		}
		else
		{
			echo '<p>We\'re sorry, but we do not have any record of you being an authorized Reason user.</p>';
		}
	}

	$_page_timing_end = getmicrotime();
	$page_gen_time = round(1000*($_page_timing_end - $_page_timing_start), 0);
	echo '<!-- start time: '.$_page_timing_start.'   end time: '.$_page_timing_end.'   total time: '.$page_gen_time.' ms -->';
	reason_log_page_generation_time($page_gen_time);

	//echo 'mem usage: '.xdebug_memory_usage().'<br/>';
	//xdebug_dump_function_trace();
	//xdebug_dump_function_profile(4);

?>

