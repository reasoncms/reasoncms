<?php
/**
 * Stub for asset access script
 * @package reason
 * @subpackage scripts
 */
 
	// Asset access is slow because of file transfer speeds, so we don't want to track it 
	if (extension_loaded('newrelic')) {
		newrelic_ignore_transaction();
	}
 
	include_once( 'reason_header.php' );
	reason_include_once( 'scripts/assets/asset_access_handler.php' );
?>
