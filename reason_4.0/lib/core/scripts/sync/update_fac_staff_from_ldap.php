<?php
/**
 * Update sites' people listings from the directory service
 *
 * This is designed so it can be run as a cron job
 *
 * @todo We probably need to make sure everything works outside a Carleton-specific context
 *
 * @package reason
 * @subpackage scripts
 * @todo remove carleton-specific filter
 */
 
/**
 * include dependencies
 */
	include_once( 'reason_header.php' );
	reason_include_once( 'classes/entity_selector.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	reason_include_once( 'function_libraries/admin_actions.php' );

	$es = new entity_selector();
	$es->add_type( id_of( 'site' ) );
	$es->add_relation( 'site.department IS NOT NULL' );
	$es->add_relation( 'site.department != ""' );
	$sites = $es->run_one();
	$creator = id_of( 'ldap' );
	$report = '';
	$report_head = "Synchronizing with directory...\n\n";

	$dir = new directory_service;
	foreach( $sites AS $site )
	{
		$did_something = false;
		$report_section = '';
		$report_section .= "- ".$site->get_value('name')."\n";
		// hit directory - get all faculty and staff, add them to the faculty staff type
		$dept = $site->get_value('department');	// use the department from the site entity
		$filter = '(&(ou='.$dept.')(|(eduPersonPrimaryAffiliation=staff)(eduPersonPrimaryAffiliation=faculty)))';	// this is the filter
		if ($dir->search_by_filter($filter, array('ds_username')))
			$fac_staff = $dir->get_records();
		else
			$fac_staff = array();
		
		$netids = array();
		foreach( $fac_staff AS $f )
			$netids[] = $f['ds_username'][0];

		$nes = new entity_selector( $site->id() );
		$nes->add_type( id_of( 'faculty_staff' ) );
		$reason_fac_staff = $nes->run_one();

		$reason_netids = array();
		$ldap_created = array();
		$reason_id = array();
		foreach( $reason_fac_staff AS $f2 )
		{
			$reason_netids[] = $f2->get_value( 'name' );
			if( $f2->get_value( 'ldap_created' ) == 'yes' )
			{
				$ldap_created[] = $f2->get_value( 'name' );
				$reason_id[ $f2->get_value( 'name' ) ] = $f2->id();
			}
		}

		$diff = array_diff($netids, $reason_netids);
		foreach( $diff AS $new_person )
		{
			$report_section .= "--------Added $new_person\n";
			create_entity(  $site->id(), 
					id_of('faculty_staff'), 
					$creator, 
					$new_person , 
					array( 'faculty_staff' =>
							 array( 'ldap_created' => 'yes' ),
					       'entity' =>
							 array( 'new' => 0 ) )
			);
			$did_something = true;
		}
		
		//delete ldap created entries
		$diff = array_diff($ldap_created, $netids );
		foreach( $diff AS $old_person )
		{
			update_entity(	$reason_id[ $old_person ], 
							id_of('ldap'),
							array(
								'entity'=>
									array('state'=>'Deleted'),
								'faculty_staff' =>
							 		array( 'ldap_created' => 'no' ), // sets ldap_created to no so that if someone undeletes it it will stay undeleted
								),
							false
			);
			$report_section .= "---------Deleted $old_person\n";
			$did_something = true;
		}
		$report_section .= "\n";
		if( $did_something )
		{
			$report .= $report_section;
		}
	}

	$report_foot = "\n\n------------- done -------------\n";
	
	if(!empty($report))
	{
		$report = $report_head.$report.$report_foot;
		//mail(ADMIN_NOTIFICATIONS_EMAIL_ADDRESSES,'Reason fac/staff sync report', $report);
		echo $report;
	}
	else
	{
		echo 'No synchronization actions needed';
	}
?>
