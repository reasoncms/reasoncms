<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 	/**
 	 * Include parent class & register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/gallery.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'GallerySinglePageModule';
	
	/**
	 * A minisite module that shows all the page's images without paginating
	 *
	 * This module is deprecated.
	 * Use the gallery2 module, with the "number_per_page" parameter set to a very high number.
	 *
	 * @deprecated
	 * @todo Move out of the core
	 */
	class GallerySinglePageModule extends GalleryModule
	{
		var $rows = 2000;
	}
?>
