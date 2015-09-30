<?php
/**
 * An interface to update .htaccess files & check validity of site directories
 *
 * @package reason
 * @subpackage scripts
 */
	/**
	 * Include Reason libraries
	 */
	include_once( 'reason_header.php' );
	reason_include_once( 'classes/url_manager.php' );

	reason_include_once( 'function_libraries/user_functions.php' );
	force_secure_if_available();
	$current_user = check_authentication();
	
	echo '<!DOCTYPE html>'."\n";
	echo '<html>'."\n";
	echo '<head>'."\n";
	echo '<title>Update Reason URLs</title>'."\n";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
	echo '<meta name="robots" content="none" />'."\n";
	echo '</head>'."\n";
	echo '<body>'."\n";
	
	
	if (!reason_user_has_privs( get_user_id ( $current_user ), 'update_urls' ) )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to update urls.</p></body></html>');
	}

	$es = new entity_selector();
	$es->add_type( id_of( 'site' ) );
	$es->add_relation( 'site.base_url IS NOT NULL AND site.base_url != ""' );
	// order results by name so the site picker is easy to use
	$es->set_order( 'entity.name' );
	$sites = $es->run_one();

	$mode = !empty( $_REQUEST[ 'mode' ] ) ? $_REQUEST[ 'mode' ] : '';
	$id = !empty( $_REQUEST[ 'id' ] ) ? $_REQUEST[ 'id' ] : '';
	$id = turn_into_int($id);

	echo '<a href="?mode=update">Update All Rewrites</a> | <a href="?mode=check">Check Site Dir Configuration</a> | ';
	echo 'Update One Site <form style="display: inline"><input type="hidden" name="mode" value="update"/>';
	echo '<select name="id">';
	foreach( $sites AS $site )
	{
		echo '<option value="'.$site->id().'">'.$site->get_value('name').'</option>';
	}
	echo '</select>';
	echo '<input type="submit" value="Update"/>';
	echo '</form> <br/><br/>';
	
	echo 'This script will either update all URLs in this domain or test every site to see if it is properly configured. ' .
		 'If a site is not properly configured, this script will dump a list of commands that will bring the site to the ' .
		 'proper state.  Someone with shell access needs to run these commands and watch to see if they all work.<br /><br />'.
		 "You can also update all URLS from the server's command line by typing into the shell:<br />".
		 'curl -k -f https://'.htmlspecialchars($_SERVER['SERVER_NAME']).REASON_HTTP_BASE_PATH.'scripts/urls/update_urls_cli.php';
	echo '<strong>';
	if( $mode == 'check' )
	{
		echo 'Checking Site Configuration - look at bottom of page to see what needs be done.';
	}
	elseif( $mode == 'update' )
	{
		if( empty($id) )
		{
			echo 'Updating All URL files';
		}
		else
		{
			$site = new entity( $id );
			echo 'Updating URLs for "'.$site->get_value( 'name' ).'"';
		}
	}
	echo '</strong><br /><br />';

	if( $mode )
	{
		if( !empty( $id ) AND $mode == 'update' )
		{
			$um = new url_manager( $id, true );
			$um->update_rewrites();
		}
		else
		{
			// sort the sites by ID, ascending.  this matches the old way.  this lumps new stuff at the bottom of the
			// page.
			krsort( $sites );
			$to_run = array();
			
			// first do the global updates
			$um = new url_manager( 0, true,true );
			if($mode== 'update')
			{
				$um->update_rewrites();
			}
			else
			{
				$to_run = array_merge( $to_run, $um->make_site_valid() );
			}
			echo '<br />';
			foreach( $sites AS $id => $site )
			{
				$um = new url_manager( $id, true );
				if( $mode == 'update' )
				{
					$um->update_rewrites();
				}
				else
				{
					if(!$um->make_site_valid(false))
						$to_run = array_merge( $to_run, $um->make_site_valid() );
					echo '<a href="?mode=update&amp;id='.$id.'">update URLs for this site</a><br/>';
				}
				echo '<br />';
			}

			if( $mode == 'check' )
			{
				if( !empty( $to_run ) )
					echo '<p><strong>Commands to run:</strong></p><p>'.join( array_unique( $to_run ), '; ' ).'</p>';
				else
					echo '<strong>Everything is up to date.  Nothing to do.</strong><br />';
			}
		}
		echo '<br />done';
	}
	echo '</body>'."\n";
	echo '</html>'."\n";
?>
