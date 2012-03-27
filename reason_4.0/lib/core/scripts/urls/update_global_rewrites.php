<?php
/**
 * A command-line script to update .htaccess files for a given site.
 *
 * In a terminal application, type:
 *
 * php -f update_global_rewrites.php -d include_path=/path/to/reason/package/ site_unique_name
 *
 * Alternately, you can enter the ID of the site instead of the unique name:
 *
 * php -f update_global_rewrites.php -d include_path=/path/to/reason/package/ 123456
 *
 * Note: This script will not run in a browser. Use update_urls.php instead.
 *
 * @package reason
 * @subpackage scripts
 */
	
	/**
	 * Include the URL manager
	 */
	include_once('reason_header.php');
	reason_include_once( 'classes/url_manager.php' );

	// determine if this is from the web or from the command line
	if( php_sapi_name() == 'cli' )
	{
		if(isset($argv[1]))
		{
			if(is_numeric($argv[1]))
				$site_id = (integer) $argv[1];	// get the site_id from the command line
			else
				$site_id = id_of( $argv[1] );
		}
		else
		{
			echo 'Please specify a site as the first argument.'."\n";
			echo 'e.g. (where "123456" is the ID of the site to update):'."\n";
			echo 'php -f update_global_rewrites.php -d include_path=/path/to/reason/package/ 123456'."\n";
			echo 'or...'."\n";
			echo 'php -f update_global_rewrites.php -d include_path=/path/to/reason/package/ site_unique_name'."\n";
			exit( 0 );
		}
	}
	else
	{
		http_response_code(401);
		echo 'This script may only be invoked from the command line.'."\n";
		exit();
	}
	if(empty($site_id))
	{
		echo 'Invalid site id or unique name provided.'."\n";
		exit( 0 );
	}

	$um = new url_manager( $site_id, true );

	$um->update_rewrites();

	echo 'Rewrites complete'."\n";
	exit( 0 );
?>