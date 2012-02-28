<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Include image library
	 */
	require_once CARL_UTIL_INC.'basic/image_funcs.php';
	require_once CARL_UTIL_INC . 'basic/misc.php';
	require_once DISCO_INC . 'plugins/input_limiter/input_limiter.php';
	reason_include_once('classes/plasmature/upload.php');
	reason_include_once('function_libraries/images.php');
	reason_include_once('function_libraries/image_tools.php');
	reason_include_once('content_managers/default.php3');
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'ImageManager';

	/**
	 * A content manager for images
	 */
	class ImageManager extends ContentManager
	{
		var $form_enctype = 'multipart/form-data';
		var $auto_create_thumbnails = true;
		var $thumbnail_width = REASON_STANDARD_MAX_THUMBNAIL_WIDTH;
		var $thumbnail_height = REASON_STANDARD_MAX_THUMBNAIL_HEIGHT;
		var $image_types = array(1=>'gif',2=>'jpg',3=>'png');
		var $image_types_with_exif_data = array('jpg');

		function set_thumbnail_size( $size ) // {{{
		{
			$this->thumbnail_size = $size;
		} // }}}
		
		/** @access private */
		function _get_authenticator()
		{
			return array("reason_username_has_access_to_site",
				$this->get_value("site_id"));
		}

		function alter_data() // {{{
		{
			$thumb_dimensions = get_reason_thumbnail_dimensions($this->get_value('site_id'));
			$this->thumbnail_height = $thumb_dimensions['height'];
			$this->thumbnail_width = $thumb_dimensions['width'];

			$this->add_element( 'image', 'ReasonImageUpload', array('obey_no_resize_flag' => true, 'authenticator' => $this->_get_authenticator(), 'max_width' => REASON_STANDARD_MAX_IMAGE_WIDTH, 'max_height' => REASON_STANDARD_MAX_IMAGE_HEIGHT));
			$this->add_element( 'thumbnail', 'ReasonImageUpload', array('authenticator' => $this->_get_authenticator()) );
			$image = $this->get_element('image');
			$image->get_head_items($this->head_items);
			$this->add_element('default_thumbnail', 'checkbox', 
					array('description' => 'Generate thumbnail from full-size image'));
		
			$this->change_element_type( 'width','hidden' );
			$this->change_element_type( 'height','hidden' );
			$this->change_element_type( 'size','hidden' );
			$this->change_element_type( 'image_type','hidden' );
			$this->change_element_type( 'author_description','hidden' );
			$this->change_element_type( 'thumbnail_image_type', 'hidden' );
			$this->change_element_type( 'original_image_type', 'hidden' );
			

			$this->set_display_name( 'description', 'Short Caption' );
			$this->set_display_name( 'content', 'Long Caption' );
			$this->set_display_name( 'datetime', 'Photo Taken' );
			$this->set_display_name( 'author', 'Photographer' );
			$this->set_display_name( 'default_thumbnail', '&nbsp;');


			$this->set_comments( 'name', form_comment("A name for internal reference") );
			$this->set_comments( 'content', form_comment("The long caption will appear with the full-sized image.") );
			$this->set_comments( 'description', form_comment("The short caption will go along with the thumbnail. It will also be used under the full-sized image if there is no long caption.") );
			$this->set_comments( 'keywords', form_comment('Use commas to separate terms, like this: "Fall, Campus, Sunny, Scenic, Orange, Leaves, Bicycle Ride"') );
			
			if(!$this->get_value('datetime'))
				$this->set_comments( 'datetime', form_comment('This may be automatically determined from the image file.') );
			//determine if user should be able to upload full-sized images
			if( user_is_a($this->admin_page->user_id, id_of('admin_role'))
				|| user_is_a($this->admin_page->user_id, id_of('power_user_role') ) )
			{
				$full_sizer = true;
			}
			else
			{
				$full_sizer = false;
			}
			if( $full_sizer )
			{
				$this->add_element( 'do_not_resize', 'checkbox', array('description' => 'Upload this image at full resolution &amp; size. (Use with caution &ndash; it is easy to accidentally upload an overly-large image.)'));
				$this->set_display_name( 'do_not_resize', '&nbsp;');

			}

			// Required fields
			$this->add_required( 'description' );
			$this->add_required( 'image' );
			// Make the content (long caption) WYSIWYG; description (short caption) only 3 rows
			$this->change_element_type( 'content' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			$this->set_element_properties( 'description' , array('rows' => 3));
			$this->set_element_properties( 'content' , array('rows' => 8));
			
			// Limit number of characters that can be entered for short/long caption
			$limiter = new DiscoInputLimiter($this);
		    $limiter->limit_field('description', 100);
		    $limiter->limit_field('content', 250);   
			
			
			/* 
			    Include javascript that handles hiding/showing various fields when appropriate,
			    i.e. hide the thumbnail option before a main image has been uploaded etc. 
			*/
			$this->head_items->add_javascript(JQUERY_URL, true);
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'content_managers/image_manager.js');
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH .'css/reason_admin/content_managers/image.css');
		} // }}}
		
		function on_every_time() // {{{
		{
			// munge image and thumbnail elements to use ReasonImageUpload correctly
			$image_name = reason_format_image_filename($this->_id,
				$this->get_value('image_type'));
			$web_image_path = WEB_PHOTOSTOCK.$image_name;
			$full_image_path = PHOTOSTOCK.$image_name;
			if (file_exists($full_image_path))
				$this->change_element_type( 'image','ReasonImageUpload', array('obey_no_resize_flag' => true, 'authenticator' => $this->_get_authenticator(), 'existing_file' => $full_image_path, 'existing_file_web' => $web_image_path, 'allow_upload_on_edit' => true ) );

			$tn_name = reason_format_image_filename($this->_id,
				$this->get_value('thumbnail_image_type'), "thumbnail");
			$web_tn_path = WEB_PHOTOSTOCK.$tn_name;
			$full_tn_path = PHOTOSTOCK.$tn_name;
			if (file_exists($full_tn_path))
			{
				$this->change_element_type( 'thumbnail','ReasonImageUpload',array('authenticator' => $this->_get_authenticator(), 'existing_file' => $full_tn_path, 'existing_file_web' => $web_tn_path, 'allow_upload_on_edit' => true ) );
			}
			$this->set_order(
				array(
					'name',
					'image',
					'do_not_resize',
					'thumbnail',
					'default_thumbnail',
					'author_description',
					'description',
					'content',
					'author',
					'keywords',
					'datetime',
					'original_image_format',
				)
			);
			
		} // }}}
		
		// This method is useful for debugging uploads; it maintains the same
		// upload session when you "Save & Continue Editing".
		/*
		function get_continuation_state_parameters()
		{
			$asset = $this->get_element("asset");
			$local = ($asset && $asset->upload_sid)
				? array('transfer_session' => $asset->upload_sid)
				: array();
			return array_merge(parent::get_continuation_state_parameters(),
				$local);
		}
		*/
		
		function pre_error_check_actions() // {{{
		{
			parent::pre_error_check_actions();
			$thumbnail = $this->get_element( 'thumbnail' );
			if( empty( $thumbnail->tmp_full_path ) AND empty( $thumbnail->existing_file ) )
				$this->set_comments( 'thumbnail', form_comment( 'A thumbnail will automatically be generated if one does not already exist or one is not uploaded.' ) );
		} // }}}
		function run_error_checks() // {{{
		{
			$image = $this->get_element('image');
			if( empty($image->tmp_full_path) AND empty( $image->existing_file ) )
			{
				$this->set_error( 'image', 'Please upload an image' );
			}
		} // }}}
		
		/**
		 * Handles the logic of processing newly uploaded images. Calls helper functions to handle 
		 * cases when:
		 * - new main image is uploaded
		 * - custom thumbnail is uploaded
		 * - a default thumbnail should be created from the main image
		 * Also calls parent process() method
		 */
		function process() // {{{
		{
			$id = $this->_id;
			$image = $this->get_element( 'image' );
			$thumbnail = $this->get_element( 'thumbnail' );
			
			// note that order matters here -- the original image depends on fields of full size image
			if( !empty($image->tmp_full_path) AND file_exists( $image->tmp_full_path ) )
			{
				$this->handle_standard_image($id, $image);
				$this->handle_original_image($id, $image);
			}
            
            // handle custom thumbnail if one was uploaded
			$custom_thumbnail_uploaded = file_exists( $thumbnail->tmp_full_path ) && ( !$this->get_value("default_thumbnail") );
			if($custom_thumbnail_uploaded)
			{
				$this->handle_custom_thumbnail($id, $thumbnail);
			}
			
			// if default thumbnail is checked, or no thumbnail exists in database,
			// create a thumbnail from main image
			$thumb_name = PHOTOSTOCK.reason_format_image_filename($id,
				$this->get_value("thumbnail_image_type"), "thumbnail");
			if( $this->get_value('default_thumbnail') || (!file_exists($thumb_name) && $this->auto_create_thumbnails) )
			{
			    $this->create_default_thumbnail($id);
			}
			
			parent::process();
	    } // }}}
	    
	    /**
	     * Handles saving newly uploaded main image to directory, updating database with relevant information about
	     * the image (i.e. dimensions, image type, date etc.)
	     *
	     *
	     * In addition, deletes the previous main image. For example, if the previous main image was 1234.jpg and we
	     * 1234.png is uploaded, 1234.jpg is deleted. In the case they are of the same type, the previous image
	     * is simply overwritten. 
	     * 
	     * @param $id the Reason ID of the image entity
	     * @param $image ReasonImageUploadType the image that was just uploaded 
	     */
	    
	    function handle_standard_image($id, $image)
	    {
	        $image_info = array();
	        list($image_info['width'], $image_info['height'], $image_info['image_type']) = getimagesize($image->tmp_full_path);
	        		
			// why does this if statement need to be so complicated?
			if(array_key_exists($image_info['image_type'],$this->image_types) && in_array($this->image_types[ $image_info['image_type'] ], $this->image_types_with_exif_data) && function_exists('read_exif_data') )
			{
				$exif_data = @read_exif_data( $image->tmp_full_path );
				if( !empty( $exif_data[ 'DateTime' ] ) )
				{
					$this->set_value('datetime',$exif_data['DateTime'] );
				}
			}
			$this->set_value('width', $image_info['width'] );
			$this->set_value('height', $image_info['height'] );
			
			// store old filename before possibly changing image_type
			// in case we're changing an extension -- we'll delete the old file
			$old_filename = PHOTOSTOCK . reason_get_image_filename($id);
			
			if(array_key_exists($image_info['image_type'],$this->image_types))
			{
				$this->set_value('image_type', $this->image_types[ $image_info['image_type'] ] );
			}
			$this->set_value('size', round(filesize( $image->tmp_full_path ) / 1024) );
			
			$dest_filename = PHOTOSTOCK . reason_format_image_filename($id,
				$this->get_value('image_type'));
			rename($image->tmp_full_path, $dest_filename);
			touch($dest_filename);
			if( $old_filename != $dest_filename && file_exists($old_filename) )
			{
				unlink($old_filename);
			}
	    }
	    
	    /**
	     * Begins by deleting any full-size, original image that may have been uploaded in the past.
	     * If there is a new orig image, then we want this new one to be the only orig stored in the file system.
	     * Similarly, if there isn't a new orig image, we don't want an old (and potentially different from
	     * the new image) orig image still in the file system.
	     * 
	     * Then creates a new original, full-sized image if the uploaded image is large enough. 
	     *
	     * Updates original_image_type to match the orig image, or null if there is no orig image.
	     * 
	     * @param id the entity id of the image
	     * @param image ReasonImageUploadType the image that was just uploaded
	     */
	    
	    function handle_original_image($id, $image)
	    {
	    	$old_filename = PHOTOSTOCK . reason_get_image_filename($id, 'full');
	    	if( file_exists($old_filename) )
			{
				unlink($old_filename);
			}
			if ($image->original_path && file_exists($image->original_path)) 
			{
				// Move the original image into the photostock directory, update entity's original_image_type
				list($width, $height, $type) = getimagesize($image->original_path);
				$orig_dest = PHOTOSTOCK . reason_format_image_filename($id,
					$this->image_types[ $type ], "full");
				rename($image->original_path, $orig_dest);
				touch($orig_dest);
				$this->set_value('original_image_type', $this->image_types[ $type ]);
			}
			else
			{
				$this->set_value('original_image_type', null);
			}
	    }
	    /**
	     * Handles saving uploaded thumbnail to directory, updating database with relevant information about
	     * the thumbnail (i.e. dimensions, image type, date etc.)
	     *
	     * In addition, deletes any old thumbnail file that was in use previously (i.e. if our new thumbnail
	     * is 1234_tn.jpg and the old was 1234_tn.png, the .png is deleted.
	     *
	     * @param $id the Reason ID of the image entity
	     * @param $thumbnail_image ReasonImageUploadType the custom thumbnail that was just uploaded 
	     */
	    
	    function handle_custom_thumbnail($id, $thumbnail_image)
		{
			$image_info = array();
	        list($image_info['width'], $image_info['height'], $image_info['image_type']) = getimagesize($thumbnail_image->tmp_full_path);
	        
			if(array_key_exists($image_info['image_type'],$this->image_types) && in_array($this->image_types[ $image_info['image_type'] ], $this->image_types_with_exif_data) && function_exists('read_exif_data') )
			{
				$exif_data = @read_exif_data( $thumbnail->tmp_full_path );
				if( !empty( $exif_data[ 'DateTime' ] ) )
				{
					$this->set_value('datetime',$exif_data['DateTime'] );
				}
			}
			// only set the width and height to the thumbnail size if a 
			// width and height are not already set up
			if( !$this->get_value( 'width' ) AND !$this->get_value( 'height' ) )
			{
				$this->set_value('width', $image_info['width']);
				$this->set_value('height', $image_info['height'] );
			}
			// if thumbnail was previously stored, we'll want to delete that old file
			$old_filename = PHOTOSTOCK . reason_get_image_filename($id, 'thumbnail');
			if(array_key_exists($image_info['image_type'], $this->image_types))
			{
				$this->set_value('thumbnail_image_type', $this->image_types[ $image_info['image_type'] ] );
			}
			$this->set_value('size', round(filesize( $thumbnail_image->tmp_full_path ) / 1024) );
			
			$dest_name = PHOTOSTOCK . reason_format_image_filename($id,
				$this->get_value('thumbnail_image_type'), "thumbnail");
			rename($thumbnail_image->tmp_full_path, $dest_name);
			if( $old_filename != $dest_name && file_exists($old_filename) )
			{
				unlink($old_filename);
			}
		}
		/**
		 * Creates a default thumbnail based on the main image only if main image is too large to
		 * be considered a thumbnail. Otherwise, no new file is actually created -- the main image
		 * will serve as the thumbnail, as well.
		 * 
		 * If a new thumbnail image is actually created, the image's thumbnail_image_type is updated
		 * to reflect this change. Similarly, if the main image is small enough to be considered the
		 * thumbnail as well, the thumbnail_image_type is set to null.
		 * 
		 * In addition, any previous thumbnail in the file system is deleted. 
		 *
		 * @param id of the image entity
		 */
		
		function create_default_thumbnail($id)
		{
			// if creating thumbnail based on main image, thumbnail should have same type
			$image_type = $this->get_value('image_type');
			
			$old_thumbnail = PHOTOSTOCK . reason_get_image_filename($id, 'thumbnail');
			if( file_exists($old_thumbnail) )
			{
				unlink($old_thumbnail);
			}
			
			$filename = PHOTOSTOCK . reason_format_image_filename($id,
				$image_type);
			$thumb_filename = PHOTOSTOCK . reason_format_image_filename($id,
				$image_type, "thumbnail");
			list($width, $height) = getimagesize($filename);

			if ($width > $this->thumbnail_width || $height > $this->thumbnail_height) 
			{
				copy($filename, $thumb_filename);
				resize_image($thumb_filename, $this->thumbnail_width,
					$this->thumbnail_height);
				touch($thumb_filename);
				$this->set_value('thumbnail_image_type', $image_type);
			}
			else
			{
				$this->set_value('thumbnail_image_type', null);
			}
		}
		
	}

?>
