<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the base class & register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/av.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AvWithFiltersModule';

	/**
	 * Minisite module that displays media works & media files with a search box and category filter
	 */
	class AvWithFiltersModule extends AvModule
	{
		var $use_filters = true;
		var $search_fields = array('entity.name','meta.description','meta.keywords','chunk.content','chunk.author',);
	}
?>
