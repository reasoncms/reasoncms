<?php
/**
 * Set up the default access singleton object
 *
 * This procedural file sets up any core groups that should be used for sites and types, then
 * calls setup_local.php, which should do the same for any local default groups.
 *
 * Note that this is not implemented at the db interaction level; it is implemented at the content
 * manager and module level. Therefore, there may be cases where support has not yet been implemented.
 *
 * @package reason
 * @subpackage minisite_templates
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');

/**
 *	Set up any CORE default access groups here, e.g.:
 *	
 *	$da = reason_get_default_access();
 *	$da->set('site_unique_name','type_unique_name','allowable_relationship_name','group_unique_name');
 *	$da->set('site_unique_name_2','type_unique_name_2','allowable_relationship_name_2','group_unique_name_2');
 *	
 *	For local access groups (probably what you want to do) do this in setup_local.php instead.
 *	
 */

?>