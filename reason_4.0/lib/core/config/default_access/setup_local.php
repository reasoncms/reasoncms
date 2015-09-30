<?php
/**
 * Do local set up on the default access singleton object
 *
 * This procedural file sets up any local groups that should be used for sites and types
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
 *	Set up the default access group here, e.g.:
 *	
 *	$da = reason_get_default_access();
 *	$da->set('site_unique_name','type_unique_name','allowable_relationship_name','group_unique_name');
 *	$da->set('site_unique_name_2','type_unique_name_2','allowable_relationship_name_2','group_unique_name_2');
 *
 *  // Example: media works
 *	$da->set('webdev_site','av','av_restricted_to_group','media_work_access_group');
 *
 *	// Example: assets
 *	$da->set('webdev_site','asset','asset_access_permissions_to_group','asset_access_group');
 */
?>