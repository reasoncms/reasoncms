<?php
	reason_include_once( 'minisite_templates/modules/gallery.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'GallerySinglePageModule';
	
	class GallerySinglePageModule extends GalleryModule
	{
		var $rows = 2000;
	}
?>
