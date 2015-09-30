<?php
/**
 * If REASON_ENABLE_ENTITY_SANITIZATION is defined, this file defines sanitization settings.
 *
 * The strings used can reference
 *
 * - a native or already included function name (ie. strip_tags or reason_sanitize_html)
 * - the empty string to disable sanitization for a field
 *
 * The keys in $GLOBALS['_reason_entity_sanitization'] are primarily the unique names of types, with two exceptions:
 *
 * - "default" is the sanitization function that will be run in nothing more explicit is defined for a field.
 * - "entity" provides defaults for the fields in the entity table - you could override then on a type by type basis.
 *
 * @todo after reason 4.5 remove "no_tidy" from admin interface and use these rules instead.
 * @todo create sanitization function which parses thor XML and sanitizes the individual fields with HTML purifier.
 * @todo create sanitization function which parses LDAP queries and sanitizes them as appropriate.
 *
 * @package reason
 * @subpackage config
 * @author Nathan White
 */

$GLOBALS['_reason_entity_sanitization'] = array(
	'default' => 'reason_sanitize_html',
	'entity' => array(
		'id' => '',
		'name' => 'reason_sanitize_html',
		'type' => '',
		'last_edited_by' => '',
		'last_modified' => '',
		'unique_name' => '',
		'state' => '',
		'creation_date' => '',
		'no_share' => '',
		'new' => '',
		'created_by' => ''
	),
	'minisite_page' => array(
		'extra_head_content' => '',
	),
	'form' => array(
		'thor_content' => '',
	),
	'audience_type' => array(
		'audience_filter' => '',
	),
	'group_type' => array(
		'arbitrary_ldap_query' => '',
		'ldap_group_filter' => '',
		'ldap_group_member_fields' => '',
	),
);

if (reason_file_exists('config/entity_sanitization/setup_local.php'))
{
	reason_include_once('config/entity_sanitization/setup_local.php');
	if(!empty($GLOBALS['_reason_entity_sanitization_local']))
	{
		$GLOBALS['_reason_entity_sanitization'] = array_merge($GLOBALS['_reason_entity_sanitization'],$GLOBALS['_reason_entity_sanitization_local']);
	}
}