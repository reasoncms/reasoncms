<?php
/**
 * Move entities from one site to another -- step 3
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
include_once( DISCO_INC .'disco.php');
reason_include_once( 'classes/entity_selector.php');
reason_include_once( 'classes/url_manager.php');
reason_include_once( 'function_libraries/user_functions.php' );
reason_include_once( 'scripts/move/move_entities_among_sites_helper.php' );
reason_include_once( 'classes/job.php' );
force_secure_if_available();
$current_user = check_authentication();
$current_user_id = get_user_id($current_user);

if (empty( $current_user_id ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to move entities among sites.</p><p>Only Reason admins may do that.</p></body></html>');
}
elseif (!reason_user_has_privs( $current_user_id, 'db_maintenance' ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to move entities among sites.</p><p>Only Reason admins who have database maintenance privs may do that.</p></body></html>');
}
if ( !empty($_REQUEST['new_site_ids']) && 
	 !empty($_REQUEST['old_site_id']) &&
	 !empty($_REQUEST['allowable_relationship_id']) && 
	 !empty($_REQUEST['type_id']) )
{
	$new_site_ids = (array) $_REQUEST['new_site_ids'];
	$old_site_id = (integer) $_REQUEST['old_site_id'];
	$type_id = (integer) $_REQUEST['type_id'];
	$allowable_relationship_id = (integer) $_REQUEST['allowable_relationship_id'];
	$borrows_relationship_id = get_borrows_relationship_id($type_id);
	$type_name = unique_name_of($type_id);
	$pre_process_method_exists = method_exists('MoveEntitiesPreProcess', $type_name);
	$post_process_method_exists = method_exists('MoveEntitiesPostProcess', $type_name);
}
else
{
	header('Location: ' . securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH  . 'scripts/move/move_entities_among_sites.php');
	die();
}

$job_stack = new ReasonJobStack();
foreach ( $new_site_ids as $entity_id => $new_site_id )
{
	$entity_id = (integer) $entity_id;
	$new_site_id = (integer) $new_site_id;
	
	if ($new_site_id != $old_site_id)
	{	
		// if a pre_process function exists for the type, run it
		if ($pre_process_method_exists)
		{
			$info = array('entity_id' => $entity_id,
						  'new_site_id' => $new_site_id,
						  'allowable_relationship_id' => $allowable_relationship_id,
						  'borrows_relationship_id' => $borrows_relationship_id
						 );
			$job = call_user_func('MoveEntitiesPreProcess::'.$type_name, array('entity_id' => $entity_id,
																			   'new_site_id' => $new_site_id,
																			   'allowable_relationship_id' => $allowable_relationship_id,
																			   'borrows_relationship_id' => $borrows_relationship_id));
			if ($job)
			{
				$job_array = (is_array($job)) ? $job : array($job);
				foreach ($job_array as $job)
				{
					$job_stack->add_job($job);
				}
			}
		}
		
		// delete borrowship rel if it exists
		$job = new DeleteBorrowshipRelIfItExistsJob();
		$job->config('new_site_id', $new_site_id);
		$job->config('entity_id', $entity_id);
		$job->config('borrows_relationship_id', $borrows_relationship_id);
		$job_stack->add_job($job);
		
		// Setup job to clear site context where the moved entity is on the entity_a side and the destination site has site context
		// This ends up being one query for each destination site.
		if (!isset($clear_context_job[$new_site_id]))
		{
			$clear_context_job[$new_site_id] = new RemoveSiteContextFromDestinationSiteJob();
			$clear_context_job[$new_site_id]->config('new_site_id', $new_site_id);
			$job_stack->add_job($clear_context_job[$new_site_id]);
		}
		$a_entities = $clear_context_job[$new_site_id]->config('a_entities');
		$a_entities[] = $entity_id;
		$clear_context_job[$new_site_id]->config('a_entities', $a_entities);

		// Setup job to change site context where the moved entity is on the entity_b side and the current rel has site context
		// This ends up being one set of queries for each destination site.
		if (!isset($change_context_job[$new_site_id]))
		{
			$change_context_job[$new_site_id] = new ChangeSiteContextAndAutoBorrowJob();
			$change_context_job[$new_site_id]->config('new_site_id', $new_site_id);
			$job_stack->add_job($change_context_job[$new_site_id]);
		}
		$b_entities = $change_context_job[$new_site_id]->config('b_entities');
		$b_entities[] = $entity_id;
		$change_context_job[$new_site_id]->config('b_entities', $b_entities);

		// Setup jobs that update the ownership relationship
		$job = new UpdateOwnershipRelJob();
		$job->config('new_site_id', $new_site_id);
		$job->config('old_site_id', $old_site_id);
		$job->config('entity_id', $entity_id);
		$job->config('allowable_relationship_id', $allowable_relationship_id);
		$job_stack->add_job($job);
		
		// if a post_process function exists for the type, run it
		if ($post_process_method_exists)
		{
			$job = call_user_func('MoveEntitiesPostProcess::'.$type_name, array('entity_id' => $entity_id,
																			    'new_site_id' => $new_site_id,
																			    'allowable_relationship_id' => $allowable_relationship_id,
																			    'borrows_relationship_id' => $borrows_relationship_id));
			if ($job)
			{
				$job_array = (is_array($job)) ? $job : array($job);
				foreach ($job_array as $job)
				{
					$job_stack->add_job($job);
				}
			}
		}
	}
}

// lets add the rewrite job
$job = new MoveEntitiesRewriteJob();
$site_ids[$old_site_id] = $old_site_id;
foreach($_REQUEST['new_site_ids'] as $new_site_id)
{
	$site_id = (integer) $new_site_id;
	$site_ids[$site_id] = $site_id;
}
$job->config('site_ids', $site_ids);
$job->config('type_unique_name', $type_name);
$job_stack->add_job($job);

// lets zap the nav cache if we are moving pages.
if ($type_name == 'minisite_page')
{
	$job = new MoveEntitiesNavCacheJob();
	$job->config('site_ids', $site_ids);
	$job_stack->add_job($job);
}

$result = $job_stack->run();

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
echo '<html><head>';
echo '<title>Reason: Move Entities Among Sites: Done</title>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
{
	echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
}
echo '<link rel="stylesheet" type="text/css" href="'.REASON_HTTP_BASE_PATH.'css/reason_admin/move_entities.css" />'."\n";
echo '</head><body>';

echo '<h1>Move Entities Among Sites</h1>';
if ($result)
{
	echo ( '<p>Successfully moved entities! Now, you may ' .
		   '<a href="' . securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH . 'scripts/move/move_entities_among_sites.php">' .
		   'move other entities among sites</a> ' .
		   'or <a href="' . securest_available_protocol() . '://' . REASON_WEB_ADMIN_PATH  . '">return to Reason admin</a>.</p>' );
		   
	echo ( '<p><strong>Please note:</strong> This script has done the particulars outlined in the report. There may be more you have to do yourself. For instance,
	        if you moved page(s), you\'ll need to attach them to the new page tree before they will show up.</p>' );
}
else
{
	echo '<p>Your move entities job was not completed successfully. Please look carefully at the report to see what you may need to change.</p>';
}
if ( isset($_SESSION['move_entities_among_sites__http_referer']) ) unset($_SESSION['move_entities_among_sites__http_referer']);

echo '<h3>Full Report</h3>';
$report = $job_stack->get_report();
echo $report;
echo '</body></html>';
?>