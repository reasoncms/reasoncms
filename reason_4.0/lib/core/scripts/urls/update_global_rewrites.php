<?php
	reason_include_once( 'classes/url_manager.php' );

	// determine if this is from the web or from the command line or specified otherwise
	// the _SERVER['_'] still seems to work.  it may be a hack.
	if( !empty( $_SERVER[ '_' ] ) )
		$site_id = $argv[1];	// get the site_id from the command line
	elseif( !empty( $rewrite_site_id ) )
		$site_id = $rewrite_site_id;	// get site id from the context of the script that called this one
	else
		$site_id = !empty( $_REQUEST[ 'site_id' ] ) ? $_REQUEST[ 'site_id' ] : ''; // get site_id from _REQUEST

	if( empty( $site_id ) )
		trigger_error( 'No site ID in update rewrites script.  This is bad.',HIGH );

	$um = new url_manager( $site_id, true );

	$um->update_rewrites();

	// if we are a shell script, exit cleanly
	if( !empty( $_SERVER[ '_' ] ) )
	{
		'url shells script done'."\n";
		exit( 0 );
	}
?>