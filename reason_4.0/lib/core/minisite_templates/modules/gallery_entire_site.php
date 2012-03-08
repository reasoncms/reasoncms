<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 	/**
 	 * Include parent class & register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/gallery.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'GalleryAllModule';
	
	/**
	 * A Minisite module that shows all images on the current site
	 *
	 * This module is deprecated.
	 * Use gallery2, setting the "entire_site" parameter to true, instead.
	 * 
	 * @deprecated
	 * @todo move out of the core
	 */
	class GalleryAllModule extends GalleryModule
	{
		
		function refine_es( $es )
		{
			$es->set_order( 'dated.datetime ASC' );
			$es->set_site( $this->site_id );
			return $es;
		}
	}
?>
