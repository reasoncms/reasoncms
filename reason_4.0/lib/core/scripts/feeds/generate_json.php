<?php
/**
 * include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/api/feed/feed.php' );

$feedapi = new ReasonFeedAPI();
$feedapi->run();