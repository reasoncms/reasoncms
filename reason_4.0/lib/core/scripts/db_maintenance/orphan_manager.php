<?php
/**
 * Finds Reason entities that do not belong to a site.
 *
 * This has been replaced with an administrative module. We just redirect to that if this is hit.
 *
 * @deprecated
 * @package reason
 * @subpackage scripts
 */

include_once( 'reason_header.php' );
include_once( CARL_UTIL_INC . 'basic/url_funcs.php' );
$qs = carl_make_query_string(array('cur_module' => 'OrphanManager' ));
$redir = (HTTPS_AVAILABLE) ? 'https://' . REASON_WEB_ADMIN_PATH . $qs : 'http://' . REASON_WEB_ADMIN_PATH . $qs;
header('Location: ' . $redir);
exit;
?>

