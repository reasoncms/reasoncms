<?php
	reason_include_once( 'minisite_templates/modules/gallery.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'GalleryAllModule';
	
	class GalleryAllModule extends GalleryModule
	{
		
		function refine_es( $es )
		{
			$es->set_order( 'dated.datetime ASC' );
			$es->set_site( $this->parent->site_id );
			return $es;
		}
	}
?>
