<?php
/**
 * Deleter for images
 *
 * @package reason
 * @subpackage content_deleters
 * 
 */
	/**
	 * Register deleter with Reason & include parent class
	 */
	$GLOBALS[ '_reason_content_deleters' ][ basename( __FILE__) ] = 'image_deleter';
	
	reason_include_once( 'classes/admin/admin_disco.php' );
	reason_include_once( 'function_libraries/image_tools.php' );
	
	/**
	 * A content deleter for images
	 *
	 * Upon expungement, we delete the image files.
	 *
	 * If REASON_IMAGE_GRAVEYARD is defined, original (or largest) images
	 * will be transferred there.
	 */
	class image_deleter extends deleteDisco
	{
		function delete_entity() // {{{
		{
			if(!$this->get_value( 'id' ))
				return;
			
			$image = new entity( $this->get_value( 'id' ) );
			$paths = array();
			$paths['thumbnail'] = reason_get_image_path($image,'thumbnail');
			$paths['normal'] = reason_get_image_path($image);
			$paths['original'] = reason_get_image_path($image,'original');
			foreach($paths as $key => $path)
			{
				if(file_exists($path))
					$largest = $key;
			}
			if(!empty($largest))
			{
				// move to image graveyard
				if(defined('REASON_IMAGE_GRAVEYARD') && REASON_IMAGE_GRAVEYARD)
				{
					if(!file_exists(REASON_IMAGE_GRAVEYARD))
						mkdir(REASON_IMAGE_GRAVEYARD);
					rename($paths[$largest],REASON_IMAGE_GRAVEYARD.basename($paths[$largest]));
					unset($paths[$largest]);
				}
				foreach($paths as $key => $path)
				{
					if(file_exists($path))
						unlink($path);
				}
			}
			$sized_image_directory = REASON_SIZED_IMAGE_DIR.$image->id().'/';
			if(file_exists($sized_image_directory))
				$this->delete_directory($sized_image_directory);
				
			$custom_sized_image_directory = REASON_INC.'www/sized_images_custom/'.$image->id().'/';
			if(file_exists($custom_sized_image_directory))
				$this->delete_directory($custom_sized_image_directory);
			
			parent::delete_entity();
		}
		function delete_directory($dir) {
			$files = array_diff(scandir($dir), array('.','..'));
			foreach ($files as $file) {
      			(is_dir("$dir/$file")) ? $this->delete_directory("$dir/$file") : unlink("$dir/$file");
    		}
    		return rmdir($dir);
  		}
	}
?>
