<?php
	include_once( 'reason_header.php' );
	include_once( DISCO_INC . 'disco_db.php' );
	reason_include_once( 'classes/entity.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	connectDB( REASON_DB );

        //set $id
        if (!empty($_REQUEST['id']))
        {
                settype($_REQUEST['id'], 'integer');
                $id = $_REQUEST['id'];
        }
        else $id = '';
	
//	if(
//		empty($_SERVER[ 'REMOTE_USER' ])
//		||
//		!user_is_a( get_user_id ( $_SERVER[ 'REMOTE_USER' ] ), id_of('admin_role') )
//	)

        // checks for both http and cookie based authentication - nwhite
        force_secure_if_available();
	$current_user = check_authentication();
        if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to delete allowable relationships.</p><p>Only Reason users who have the Administrator role may do that.</p>');
	}

	if(!empty($_REQUEST['confirmation']) && $_REQUEST['confirmation'] == 'true' && !empty($id))
	{
		$q = "DELETE FROM allowable_relationship WHERE id = $id";
		$r = mysql_query( $q );
		header( 'Location: ' . securest_available_protocol() . '://'.HTTP_HOST_NAME.REASON_HTTP_BASE_PATH.'scripts/alrel/alrel_manager.php' );
	}
	else
	{
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
		echo '<html><head><title>Allowable Relationship Manager: Delete?</title>';
		if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
		{
			echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
		}
		echo '<link rel="stylesheet" type="text/css" href="'.REASON_ADMIN_CSS_DIRECTORY.'admin.css" /></head><body><div id="allRels">'."\n";
		if(!empty($id))
		{
			
			echo '<h1>Deleting Allowable Relationship</h1><p>Are you sure you want to delete allowable relationship ID '.$_REQUEST['id'].'?</p>'."\n";
			echo '<ul><li><a href="del_alrel.php?id='.$_REQUEST['id'].'&amp;confirmation=true">Yes</a></li><li><a href="alrel_manager.php">No</a></li></ul>'."\n";
		}
		else
		{
			echo '<p>ID Needed</p>';
		}
		echo '</div></body></html>'."\n";
	}
?>
