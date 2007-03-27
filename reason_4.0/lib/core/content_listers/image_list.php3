<?php

	reason_include_once( 'content_listers/default.php3' );

	///////////////////////////////////////////////////////////////////////////////
	// MAKE SURE THIS VARIABLE IS SET IF OVERLOADING
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'image_viewer';
	///////////////////////////////////////////////////////////////////////////////
	
	class image_viewer extends generic_viewer
	{
		var $columns = array(
						'id' => true,
						'name' => true, 
						'last_modified' => 'prettify_mysql_timestamp',
						'datetime' => 'prettify_mysql_timestamp_with_time',
						);
	}
	function prettify_mysql_timestamp_with_time( $timestamp )
	{
		return prettify_mysql_timestamp( $timestamp, 'M jS, Y @ g:i a' );
	}
?>
