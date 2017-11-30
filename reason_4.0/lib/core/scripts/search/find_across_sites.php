<?php
/**
 * Find entities that match a given string in *any* field on *any* site
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
header('Location: '.securest_available_protocol().'://'.REASON_WEB_ADMIN_PATH.'?cur_module=Search');
