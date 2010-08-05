<?php
/**
 * Set up the module sets singleton object
 *
 * This procedural file adds the core module sets to the module sets object.
 *
 * @package reason
 * @subpackage minisite_templates
 */

$ms =& reason_get_module_sets();
$ms->add(
	array(
		'events',
		'event_registration',
		'event_signup',
		'event_slot_registration',
		'events_archive',
		'events_hybrid',
		'events_schedule',
		'events_verbose',
	),
	'event_display'
);

/**
 * Create a set publication_item_display which holds module names that display publication items
 */
$ms->add('publication', 'publication_item_display');

if(reason_file_exists('config/module_sets/setup_local.php'))
	reason_include_once('config/module_sets/setup_local.php');

?>