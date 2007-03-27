<?php
	reason_include_once( 'minisite_templates/modules/av.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AvWithFiltersModule';

	class AvWithFiltersModule extends AvModule
	{
		var $use_filters = true;
		var $search_fields = array('entity.name','meta.description','meta.keywords','chunk.content','chunk.author',);
	}
?>
