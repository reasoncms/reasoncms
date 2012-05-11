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

	if($_SERVER[ 'REMOTE_ADDR' ] == $_SERVER[ 'SERVER_ADDR' ])
	{
		
		$es = new entity_selector();
		$es->add_type( id_of( 'site' ) );
		$es->add_relation( 'site.base_url IS NOT NULL AND site.base_url != ""' );
		$sites = $es->run_one();
		krsort( $sites );
		
		// first do the global updates
		$um = new url_manager( 0, true, true, true);
		$um->update_rewrites();

		foreach( $sites AS $id => $site )
		{
			$um = new url_manager( $id, true, false, true );
			$um->update_rewrites();
		}
	}
	else
	{
		echo "Sorry! This script can only be run by an HTTP request from the server itself.\n";
	}

	


?>
