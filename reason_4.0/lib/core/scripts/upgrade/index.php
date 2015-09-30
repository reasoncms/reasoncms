<?php
/**
 * The Reason upgrade index page
 * @package reason
 * @subpackage scripts
 */

/** 
 * The old code for this produced an automatic listing, but not a pretty one - also not in the right order.
 * 
 * In any case, we now maintain an upgrade page manually ... lets just use it.
 */
 
include_once('reason_header.php');
$path = securest_available_protocol() . '://' . REASON_HOST . REASON_HTTP_BASE_PATH . 'upgrade.php';
header("Location: " . $path);
exit();
?>