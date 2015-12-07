<?php
/**
 * Provides a web service for the Thor WYSIWYG editor to get the current value of the temporary XML file in the DB
 * @package thor
 */

include_once ( 'paths.php' );
include_once ( SETTINGS_INC.'thor_settings.php' );

if ( !empty($_REQUEST['tmp_id']) )
{
	include_once( CARL_UTIL_INC . 'db/db.php' );
	include_once( CARL_UTIL_INC . 'db/db_selector.php' );
	connectDB( THOR_FORM_DB_CONN );
	$dbs = new DBSelector();
	$dbs->add_table('thor');
	$dbs->add_field('thor', 'content');
	$dbs->add_relation('thor.id = ' . carl_util_sql_string_escape($_REQUEST['tmp_id']));
	$results = $dbs->run();

	if ( count($results) > 0 )
	{
		header('Content-type: text/xml; charset=utf-8');
		echo $results[0]['content'];
	}
	else
		$results[0]['content'] = '';

// 	if ( empty($results[0]['content']) )
// 		die('<' . '?xml version="1.0" ?' . '><form submit="Submit" reset="Clear" />');
}
else
{
	die('Please provide a tmp_id.');
}

?>
