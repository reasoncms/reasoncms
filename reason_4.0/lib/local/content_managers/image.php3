<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Include image library
	 */
	require_once CARL_UTIL_INC.'basic/image_funcs.php';
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
		
			$this->change_element_type( 'width','hidden' );
			$this->change_element_type( 'height','hidden' );
			$this->change_element_type( 'size','hidden' );
			$this->change_element_type( 'image_type','hidden' );
			$this->change_element_type( 'author_description','hidden' );

			$this->set_display_name( 'description', 'Short Caption' );
			$this->set_display_name( 'content', 'Long Caption' );
			$this->set_display_name( 'datetime', 'Photo Taken' );
			$this->set_display_name( 'author', 'Photographer' );

			$this->set_comments( 'name', form_comment("A name for internal reference") );
			$this->set_comments( 'content', form_comment("The long caption will appear with the full-sized image.") );
			$this->set_comments( 'description', form_comment("The short caption will go along with the thumbnail. It will also be used under the full-sized image if there is no long caption.") );
			$this->set_comments( 'keywords', form_comment('Use commas to separate terms, like this: "Fall, Campus, Sunny, Scenic, Orange, Leaves, Bicycle Ride"') );
			$ka = explode(',', $this->get_value('keywords'));
			$check_imagetop = false;
			$ba_url = "";
			$check_hide_caption = false;
			foreach($ka as $key => $value)
			{
				$ka[$key] = trim($value);
				if (preg_match("/imagetop/", $value))
				{
					unset($ka[$key]);
					$check_imagetop = true;
				}
				else if (preg_match("/bannerad\s(.*?)$/", $value, $matches))
				{
					$ba_url = $matches[1];
					unset($ka[$key]);
				}
				if (preg_match("/hide_caption/", $value))
				{
					unset($ka[$key]);
					$check_hide_caption = true;
				}
			}
//			$kt = array_search(preg_match("/imagetop/", 
			$this->set_value('keywords', implode(", ", $ka));

			$this->add_element( 'hide_caption', 'checkbox', array('description' => 'Do not show caption on thumbnail or full resolution image.'));
			$this->set_value('hide_caption', $check_hide_caption);
			//$this->add_element( 'top_image', 'checkbox', array('description' => 'Should be at least as wide as the column in which it resides.  Be sure to check "Do Not Resize."<br /><small>The suggested dimensions for a top image are 530 x 215 pixels. An image outside these dimensions will be stretched or cropped.'));
			$this->add_element( 'top_image', 'checkbox', array('description' => 'Should be at least as wide as the column in which it resides.  Be sure to check "Do Not Resize."<br /><small>The suggested dimensions for a top image are 450 x 288 pixels. An image outside these dimensions will be stretched or cropped.'));
			$this->set_value('top_image', $check_imagetop);
			//$this->add_element( 'banner_ad', 'checkbox', array('description' => 'Use image as an advertisement to generate click through traffic.'));
			$this->add_element( 'banner_ad_url', 'text');
			$this->set_comments( 'banner_ad_url', form_comment('Enter a URL to use image as an advertisement.') );
			$this->set_value('banner_ad_url', $ba_url); 
			
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
			}
			

			$this->add_required( 'description' );

		} // }}}
		function on_every_time() // {{{
		{
			// munge image and thumbnail elements to use ReasonImageUpload correctly
			$image_name = reason_format_image_filename($this->_id,
				$this->get_value('image_type'));
			$web_image_path = WEB_PHOTOSTOCK.$image_name;
			$full_image_path = PHOTOSTOCK.$image_name;
			if (file_exists($full_image_path))
				$this->change_element_type( 'image','ReasonImageUpload',array('obey_no_resize_flag' => true, 'authenticator' => $this->_get_authenticator(), 'existing_file' => $full_image_path, 'existing_file_web' => $web_image_path, 'allow_upload_on_edit' => true ) );

			$tn_name = reason_format_image_filename($this->_id,
				$this->get_value('image_type'), "thumbnail");
			$web_tn_path = WEB_PHOTOSTOCK.$tn_name;
			$full_tn_path = PHOTOSTOCK.$tn_name;
			if (file_exists($full_tn_path))
			{
				$this->change_element_type( 'thumbnail','ReasonImageUpload',array('authenticator' => $this->_get_authenticator(), 'existing_file' => $full_tn_path, 'existing_file_web' => $web_tn_path, 'allow_upload_on_edit' => true ) );
				$this->add_element( 'replace_thumbnail', 'checkbox', array('description' => 'Regenerate the thumbnail from the full-size image.'));
			}
			
			$this->set_order(
				array(
					'name',
					'author',
					'author_description',
					'description',
					'content',
					'hide_caption',
					'keywords',
					'datetime',
					'original_image_format',
					'image',
					'top_image',
					'do_not_resize',
					'replace_thumbnail',
					'thumbnail',
					'banner_ad_url',
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
			$thumb = $this->get_element('thumbnail');
			if( empty($image->tmp_full_path) AND empty( $image->existing_file ) AND empty( $thumb->tmp_full_path ) AND empty( $thumb->existing_file ) )
			{
				$this->set_error( 'image', 'You must upload either a thumbnail or an image.' );
				$this->set_error( 'thumbnail' );
			}
		} // }}}
		function process() // {{{
		{
			$id = $this->_id;
			
			// handle image stuff
			$image = $this->get_element( 'image' );
			$thumbnail = $this->get_element( 'thumbnail' );

			// IMPORTANT NOTE ABOUT THE FOLLOWING CODE
			// since either an image or a thumbnail or both will be present, the code has a sneaky structure
			// i handle the thumbnail first and get all the information for the thumbnail
			// if there is a thumbnail but no image, this information falls through and gets inserted into the db
			// if there is an image, nothing changes.  thumbnail code doesn't get executed.
			// if both, the thumbnail values get overwritten by the image and the image values
			// get put in the DB.  This is how we want it.
			
			// handle thumbnail image
			if( !empty($thumbnail->tmp_full_path) AND file_exists( $thumbnail->tmp_full_path ) )
			{
				$this->handle_thumbnail($id, $thumbnail);
			}
			// handle main image
			if( !empty($image->tmp_full_path) AND file_exists( $image->tmp_full_path ) )
			{
				$this->handle_full_size_image($id, $image);
			}
			// make thumbnail if no thumbnail exists
			$full_name = PHOTOSTOCK.reason_format_image_filename($id,
				$this->get_value("image_type"));
			$thumb_name = PHOTOSTOCK.reason_format_image_filename($id,
				$this->get_value("image_type"), "thumbnail");
			if (($this->auto_create_thumbnails && file_exists($full_name) && !file_exists($thumb_name)) || $this->get_value("replace_thumbnail"))
			{
				$this->create_thumbnail($id, $image);
			}
			
			// put top image and banner ad info put into keyword
			$kw = $this->get_value("keywords");
			if ($this->get_value("top_image"))
			{
				if ($kw != "")
				{
					$kw = $kw . ', ';
				}
				$kw = $kw . 'imagetop';
			}
			else if (preg_match("/\w+/", $this->get_value("banner_ad_url")))
			{
				if ($kw != "")
				{
					$kw = $kw . ', ';
				}
				$kw = $kw . 'bannerad ' . $this->get_value("banner_ad_url");
			}
			if ($this->get_value("hide_caption"))
			{
				if ($kw != "")
				{
					$kw = $kw . ', ';
				}
				$kw = $kw . 'hide_caption';
			}
			$this->set_value('keywords', $kw); 
			parent::process();
		} // }}}
		function handle_thumbnail($id, $thumbnail)
		{
			$info = getimagesize( $thumbnail->tmp_full_path );
			if(array_key_exists($info[2],$this->image_types) && in_array($this->image_types[ $info[2] ], $this->image_types_with_exif_data) && function_exists('read_exif_data') )
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
				$this->set_value('width',$info[0]);
				$this->set_value('height', $info[1] );
			}
			if(array_key_exists($info[2],$this->image_types))
			{
				$this->set_value('image_type', $this->image_types[ $info[2] ] );
			}
			$this->set_value('size', round(filesize( $thumbnail->tmp_full_path ) / 1024) );
			
			$dest_name = reason_format_image_filename($id,
				$this->get_value('image_type'), "thumbnail");
			rename($thumbnail->tmp_full_path, PHOTOSTOCK.$dest_name);
		}
		function handle_full_size_image($id, $image)
		{
			$info = getimagesize( $image->tmp_full_path );
			if(array_key_exists($info[2],$this->image_types) && in_array($this->image_types[ $info[2] ], $this->image_types_with_exif_data) && function_exists('read_exif_data') )
			{
				$exif_data = @read_exif_data( $image->tmp_full_path );
				if( !empty( $exif_data[ 'DateTime' ] ) )
				{
					$this->set_value('datetime',$exif_data['DateTime'] );
				}
			}
			$this->set_value('width', $info[0] );
			$this->set_value('height', $info[1] );
			if(array_key_exists($info[2],$this->image_types))
			{
				$this->set_value('image_type', $this->image_types[ $info[2] ] );
			}
			$this->set_value('size', round(filesize( $image->tmp_full_path ) / 1024) );
			
			$dest_filename = reason_format_image_filename($id,
				$this->get_value('image_type'));
			rename($image->tmp_full_path, PHOTOSTOCK.$dest_filename);
			touch(PHOTOSTOCK.$dest_filename);
			
			$orig_dest = PHOTOSTOCK.reason_format_image_filename($id,
				$this->get_value("image_type"), "full");
			if ($image->original_path && file_exists($image->original_path)) {
				// Move the original image into the photostock directory.
				rename($image->original_path, $orig_dest);
				touch($orig_dest);
			} else if (file_exists($orig_dest)) {
				// Clear out an old high-res file.
				@unlink($orig_dest);
			}
		}
		function create_thumbnail($id)
		{
			$filename = PHOTOSTOCK.reason_format_image_filename($id,
				$this->get_value('image_type'));
			$thumb_filename = PHOTOSTOCK.reason_format_image_filename($id,
				$this->get_value('image_type'), "thumbnail");

			list($width, $height) = getimagesize($filename);

			if ($width > $this->thumbnail_width || $height > $this->thumbnail_height) {
				copy($filename, $thumb_filename);
				resize_image($thumb_filename, $this->thumbnail_width,
					$this->thumbnail_height);
				touch($thumb_filename);
			}
		}
	}

?>
