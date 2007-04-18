<?php
    // allow ten minutes for execution ... can be commented out once its a cron job
    set_time_limit(900);
    
	include_once('reason_header.php');
	reason_include_once( 'classes/entity_selector.php' );
    reason_include_once( 'scripts/import/assets/asset_import_class.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	//reason_include_once('classes/ReportGenerator.php');
	$db = connectDB(REASON_DB);
	
	force_secure_if_available();
	$current_user = check_authentication();
	
	if ( !empty($current_user) )
	{
		$user_id = get_user_id ( $current_user );
	}
	else
	{
		die('To use this script, you must log in and be a recognized Reason user.');
	}
    
    // xdebug 1 has a default maximum function nesting level of 64 before interrupting scripts
    // this is insufficient for many XML documents but may be modified at runtime
    
    $maxnestinglevel = 1024; // 1024 will be safe for most XML documents
    ini_set('xdebug.max_nesting_level', $maxnestinglevel);
    ini_set('mysql.connect_timeout', 600);
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', 900);
    
    $errorlevel = 'detailed'; // what error level to output to the page (detailed, normal, critical)
 
 	$import_dir = REASON_INC.'data/tmp/asset_import/digital_commons/'; // replace this with an upload form
 	$file = $import_dir.'test_import.xml';
	echo $import_dir.'<br />';
	echo $file;
	
	$site_id = false;
	if(!empty($_REQUEST['site_id']))
	{
		$site_id = intval($_REQUEST['site_id']);
	}
	
	$es = new entity_selector();
	$es->add_type(id_of('site'));
	$es->add_left_relationship($user_id, relationship_id_of('site_to_user'));
	$es->set_order('entity.name ASC');
	if(!empty($site_id))
	{
		$es->add_relation('entity.id = '.$site_id);
		$es->set_num(1);
	}
	$sites = $es->run_one();
	
	if(empty($site_id))
	{
		echo '<form method="post">';
		echo '<div>';
		echo '<select size="1" name="site_id">';
		foreach($sites as $site)
		{
			echo '<option value="'.$site->id().'">'.$site->get_value('name').'</option>';
		}
		echo '</select>';
		echo '</div><div>';
		echo '<input type="checkbox" name="testing_mode" value="true" checked="checked" id="testing_mode_select"/> <label for="testing_mode_select">Testing Mode</label>';
		echo '</div>';
		echo '<input type="submit" value="Run import" />';
		echo '</form>';
	}
	elseif(array_key_exists($site_id,$sites))
	{
		//$report = new ReportGenerator();
		if(!empty($_REQUEST['testing_mode']))
		{
			print '<h1>Test Import</h1>';
		}
		else
		{
			print '<h1>Importing</h1>';
		}
		
		ob_end_flush();
		$importer = new asset_import_dc();
		$importer->set_file( $file );
		$importer->set_directory($import_dir);
		//$importer->set_report( $report );
		$importer->set_site( $site_id );
		$importer->set_user( $user_id );
		if(!empty($_REQUEST['testing_mode']))
		{
			$importer->enter_testing_mode();
		}
	 
		$importer->run_import();
		//$report->print_report( $errorlevel );
		//$report->email_report( 'nwhite@acs.carleton.edu', 'critical' , 'Building Sync' );
		//$report->email_report( 'nwhite@acs.carleton.edu', 'normal' , 'Building Sync' );
		//$report->email_report( 'nwhite@acs.carleton.edu', 'detailed' , 'Building Sync' );
		//$report->email_report( 'mryan@acs.carleton.edu', 'normal', 'Building Sync' );
	}
	else
	{
		echo 'you don\'t have assess to the site in question';
	}
	
	
    

?>    
