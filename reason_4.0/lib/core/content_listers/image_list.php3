<?php
/**
 * @package reason
 * @subpackage content_listers
 */
	/**
	 * Include parent class and register viewer with Reason.
	 */
	reason_include_once( 'content_listers/default.php3' );

	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'image_viewer';
	
	/**
	 * A lister/viewer for Reason images
	 */
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
