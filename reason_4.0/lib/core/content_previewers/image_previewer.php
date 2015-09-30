<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	reason_include_once('function_libraries/image_tools.php');
	/**
	 * Register previewer with Reason
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'image_previewer';

	/**
	 * A content previewer for images
	 */
	class image_previewer extends default_previewer
	{
		// Revised Jan./2012 by Nick Jones to use standard Reason image_tools library for naming 
		// convention of files
		function display_entity() // {{{
		{
			$this->start_table();
			$id = $this->_entity->id();
			
			// Full Size Image
			$full_name = reason_get_image_filename( $id );
			$local_full_image_path = PHOTOSTOCK . $full_name;
			if( file_exists( $local_full_image_path ) )
			{
				$this->show_item_default( 'Full Image' , '<img src="'.WEB_PHOTOSTOCK.$full_name.'" alt="Full Size Image" />' );
			}
			
			// Thumbnail Image
			$tn_name = reason_get_image_filename($id, 'thumbnail');
			$local_tn_image_path = PHOTOSTOCK . $tn_name;
			if( file_exists( $local_tn_image_path ) )
			{
				$this->show_item_default( 'Thumbnail Image' , '<img src="'.WEB_PHOTOSTOCK.$tn_name.'" alt="Thumbnail Image" />' );
			}
			
			// Original Image if we are in the owner site
			$owner = $this->_entity->get_owner();
			if( !empty($owner) && $owner->id() == $this->admin_page->site_id )
			{
				$original_name = reason_get_image_filename($id, 'original');
				$local_original_image_path = PHOTOSTOCK.$original_name;
				if( file_exists( $local_original_image_path ) )
				{
					$this->show_item_default( 'Hi-Res Image' , '<a href="'.WEB_PHOTOSTOCK.$original_name.'">View original image </a>' );
				}
			}
			
			$this->show_item_default( 'Custom sizes' , '<a href="'.carl_make_link(array('cur_module'=>'ImageSizer')).'">Get this image at a custom size</a>' );
			
			// Everything Else
			$this->show_all_values( $this->_entity->get_values() );
			
			$this->end_table();
		} // }}}
		function show_item_name( $field , $value ) // {{{
		{
			$this->show_item_default( $field , $value );
		} // }}}
	}
?>
