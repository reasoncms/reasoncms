<?php
/**
 * The reason_upgrade.php script now lives in the reason www folder and is called upgrade.php
 *
 * @package reason
 * @deprecated 
 * @todo remove by Reason 4 RC 1
 */

$path = dirname($_SERVER['REQUEST_URI']) . '/reason_4.0/www/upgrade.php';
header("Location: " . $path);
exit;
?>
