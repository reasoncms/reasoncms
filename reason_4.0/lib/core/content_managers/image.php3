<?php
	reason_include_once( 'function_libraries/images.php' );

	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'ImageManager';

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

		function alter_data() // {{{
		{
			$thumb_dimensions = get_reason_thumbnail_dimensions($this->get_value('site_id'));
			$this->thumbnail_height = $thumb_dimensions['height'];
			$this->thumbnail_width = $thumb_dimensions['width'];

			$this->add_element( 'image', 'image_upload', array('max_width'=>REASON_STANDARD_MAX_IMAGE_WIDTH,'max_height'=>REASON_STANDARD_MAX_IMAGE_HEIGHT,) );
			$this->add_element( 'thumbnail', 'image_upload' );
	
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
			$this->set_comments( 'content', form_comment("This will appear under the full-sized image. If you don't provide a long caption, the short caption will be used instead.") );
			$this->set_comments( 'description', form_comment("This will appear under the thumbnail. If you don't enter a long caption, it will also be used under the full-sized image.") );
			$this->set_comments( 'keywords', form_comment("A few words to aid in searching for the image") );
			$this->set_comments( 'datetime', form_comment('This will often automatically be determined from the image file.') );
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
				$this->add_element( 'do_not_resize', 'checkbox' );
				$this->set_comments( 'do_not_resize', form_comment('If checked, This image will be uploaded at full resolution &amp; size.  Use this feature with caution -- you can easily upload an overlarge image accidentally.'));
			}

			$this->add_required( 'description' );

		} // }}}
		function on_every_time() // {{{
		{
			
			// munge image and thumbnail elements to use image_upload correctly
			$web_image_path = WEB_PHOTOSTOCK.$this->_id.'.'.$this->get_value('image_type');
			$full_image_path = PHOTOSTOCK.$this->_id.'.'.$this->get_value('image_type');
			if( file_exists( $full_image_path ) )
				$this->change_element_type( 'image','image_upload',array('existing_file' => $full_image_path, 'existing_file_web' => $web_image_path, 'allow_upload_on_edit' => true ) );

			$web_tn_path = WEB_PHOTOSTOCK.$this->_id.'_tn.'.$this->get_value('image_type');
			$full_tn_path = PHOTOSTOCK.$this->_id.'_tn.'.$this->get_value('image_type');
			if( file_exists( $full_tn_path ) )
			{
				$this->change_element_type( 'thumbnail','image_upload',array('existing_file' => $full_tn_path, 'existing_file_web' => $web_tn_path, 'allow_upload_on_edit' => true ) );
				$this->add_element( 'replace_thumbnail', 'checkbox' );
				$this->set_comments( 'replace_thumbnail', '<span class="smallText">If checked, a thumbnail will be generated from the image.</span>' );
			}
			
			$this->set_order(
				array(
					'name',
					'author',
					'author_description',
					'description',
					'content',
					'keywords',
					'datetime',
					'original_image_format',
					'image',
					'do_not_resize',
					'replace_thumbnail',
					'thumbnail'
				)
			);
		} // }}}
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
			if( ($this->auto_create_thumbnails AND file_exists(PHOTOSTOCK.$id.'.'.$this->get_value('image_type')) AND !file_exists( PHOTOSTOCK.$id.'_tn.'.$this->get_value('image_type') )) OR $this->get_value('replace_thumbnail') )
			{
				$this->create_thumbnail($id, $image);
			}
			
			parent::process();
		} // }}}
		function handle_thumbnail($id, $thumbnail)
		{
			$info = getimagesize( $thumbnail->tmp_full_path );
			if(array_key_exists($info[2],$this->image_types) && in_array($this->image_types[ $info[2] ], $this->image_types_with_exif_data) )
			{
				turn_carl_util_error_logging_off();
				$exif_data = @read_exif_data( $thumbnail->tmp_full_path );
				turn_carl_util_error_logging_on();
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
			rename( $thumbnail->tmp_full_path, PHOTOSTOCK.$id.'_tn.'.$this->get_value('image_type') );
		}
		function handle_full_size_image($id, $image)
		{
			
				$info = getimagesize( $image->tmp_full_path );
				if(array_key_exists($info[2],$this->image_types) && in_array($this->image_types[ $info[2] ], $this->image_types_with_exif_data) )
				{
					turn_carl_util_error_logging_off();
					$exif_data = @read_exif_data( $image->tmp_full_path );
					turn_carl_util_error_logging_on();
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
				rename( $image->tmp_full_path, PHOTOSTOCK.$id.'.'.$this->get_value('image_type') );
				if( file_exists( $image->tmp_full_path.'.orig' ) )
				{
					// move the original image into the photostock directory
					rename( $image->tmp_full_path.'.orig', PHOTOSTOCK.$id.'_orig.'.$this->get_value('image_type') );
				}
		}
		function create_thumbnail($id)
		{
			list( $tmp_width, $tmp_height ) = getimagesize( PHOTOSTOCK.$id.'.'.$this->get_value('image_type') );
			if( $tmp_width > $this->thumbnail_width OR $tmp_height > $this->thumbnail_height )
			{
				copy( PHOTOSTOCK.$id.'.'.$this->get_value('image_type'), PHOTOSTOCK.$id.'_tn.'.$this->get_value('image_type') );

				$exec_output = ""; 
				$exec_result_code = "";
				exec( IMAGEMAGICK_PATH . 'mogrify -geometry '.$this->thumbnail_width.'x'.$this->thumbnail_height.' -sharpen 1 '.PHOTOSTOCK.$id.'_tn.'.$this->get_value('image_type'),$exec_output , $exec_result_code );

				if($exec_result_code != 0)
				{
					trigger_error("mogrify failed: you must have ImageMagick installed for this function to succeed.");
				}
			}
		}
	}

?>
