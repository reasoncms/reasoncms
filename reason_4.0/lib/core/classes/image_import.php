<?php
	/**
	 * Image Import Disco Form
	 * @package reason
	 * @subpackage classes
	 */
	
	/**
	 * include the reason libraries
	 */
	include_once('reason_header.php');
	/**
	 * Include disco so we can extend it
	 */
	include_once( DISCO_INC .'disco.php' );
	
	require_once CARL_UTIL_INC.'basic/image_funcs.php';

	/**
	 * Form to upload and add bulk metadata to images
	 *
	 * @author Dave Hendler, Matt Ryan
	 */
	class PhotoUploadForm extends disco
	{
		var $site_id;
		var $elements = array(
    		'source_selection_note' => array(
    			'type' => 'comment',
    			'text' => '<h3>Select files to be uploaded</h3><p>Additional upload fields will be added as you progress.</p>',
    		),
    		'metadata_note' => array(
    			'type' => 'comment',
    			'text' => '<h3>Provide some information about the images</h3><p>These values will be applied to all imported images. If you would like to provide more specific information about each image, you may edit the image records in Reason once you have imported them.  Alternately, you may import images one at a time.</p>',
    		),
    		'name' => array(
    			'type' => 'text',
    			'display_name' => 'Name<br /><span class="smallText">(What is the subject of this set of images?)</span>',
    		),
    		'author' => array(
    			'type' => 'text',
    			'display_name' => 'Author<br /><span class="smallText">(Who took these photos?)</span>',
    		),
    		'description' => array(
    			'type' => 'textarea',
    			'display_name' => 'Short Caption<br /><span class="smallText">(A brief description to be used with small versions of the images)</span>',
    		),
    		'content' => array(
    			'type' => 'textarea',
    			'display_name' => 'Long Caption<br /><span class="smallText">(A more detailed description to be used with larger-sized versions of the images)</span>',
    		),
    		'keywords',
    		'datetime' => array(
    			'type' => 'textDateTime',
    			'display_name' => 'Date and Time Photo Taken',
    		),
    		'original_image_format',
    		'exif_override' => array(
    			'type' => 'checkbox',
    			'display_name' => 'Use camera-recorded date if found <span class="smallText">(Leave this checked to make sure that Reason knows when your images were taken)</span>',
    		),
    		'attach_to_page'=>array('type'=>'hidden'),
    		'assign_to_categories'=>array('type'=>'hidden'),
    		'no_share'=>array('type'=>'hidden'),
        );
		var $actions = array( 'Import Photos' );
		var $files = array();
		/**
		 * User id of who is logged in.
		 */
		var $user_id;
		var $categories;
		var $max_upload_number = 25;
		/**
		 * Converting from type to extension
		 */
		var $image_types = array(1=>'gif',2=>'jpg',3=>'png');
		
		/**
		 * If max_upload_number exceeds php's setting, lets reduce it dynamically.
		 */
		function __construct()
		{
			$max_file_uploads = ini_get('max_file_uploads');
			if (!empty($max_file_uploads) && is_numeric($max_file_uploads))
			{
				if ($max_file_uploads < $this->max_upload_number)
				{
					$this->max_upload_number = $max_file_uploads;
				}
			}
		}
		
		function get_available_categories($site_id)
		{
			$es = new entity_selector($site_id);
			$es->add_type( id_of( 'category_type' ) );
			$categories = $es->run_one();
			return $categories;
		}
		function get_available_image_galleries($site_id)
		{
			$page_types = $this->get_image_related_page_types();
			$pages = array();
			if(!empty($page_types))
			{
				$es = new entity_selector($site_id);
				$es->add_type( id_of( 'minisite_page' ) );
				$es->add_relation( 'page_node.custom_page IN (\''.implode('\', \'', $page_types).'\')' );
				$pages = $es->run_one();
			}
			return $pages;
		}
		function get_image_related_page_types()
		{
			static $page_types;
			if(!is_array($page_types))
			{
				$modules = array('gallery', 'alumni_gallery', 'gallery_horizons', 'gallery_vote','image_slideshow','gallery2');
				$page_types = array();
				foreach($modules as $module)
				{
					$page_types = array_merge($page_types,page_types_that_use_module($module));
				}
			}
			return $page_types;
		}
		
		function get_selection_page_set($site_id)
		{
			reason_include_once( 'minisite_templates/nav_classes/default.php' );
			$pages = new MinisiteNavigation();
			$site = new entity($site_id);
			$pages->site_info = $site;
			$pages->init( $site_id, id_of('minisite_page'));
			return $this->flatten_page_tree($pages->get_tree_data());
		}
		function flatten_page_tree($tree,$depth = 0)
		{
			$ret = array();
			foreach($tree as $id=>$info)
			{
				$ret[$id] = '';
				for($i = 0; $i < $depth; $i++)
				{
					$ret[$id] .= '---';
				}
				$ret[$id] .= $info['item']->get_value('name');
				if(in_array($info['item']->get_value('custom_page'),$this->get_image_related_page_types()))
				{
					$ret[$id] .= ' &#9671;';
				}
				if(!empty($info['children']))
				{
					$ret = $ret + $this->flatten_page_tree($info['children'],$depth + 1);
				}
			}
			return $ret;
		}
		
		function on_every_time()
		{
			$this->form_enctype = 'multipart/form-data';
			$this->change_element_type( 'original_image_format','select', array( 'options' => array( 'slide' => 'Slide','print' => 'Print','digital' => 'Digital' ) ) );
			
			$this->on_every_time_categories();
			$this->on_every_time_galleries();
			$this->on_every_time_sharing();
			$this->on_every_time_order();
			
			
		}
		
		function on_every_time_categories()
		{
			//find available categories
			$this->categories = $this->get_available_categories($this->site_id);
			if(!empty($this->categories))
			{
				$args = array();
				$category_args = array();
				foreach($this->categories as $category_id => $category)
				{
					$category_args[$category_id] = $category->get_value('name');
				}
				$this->change_element_type( 'assign_to_categories','select_multiple', array( 'options' => $category_args, 'display_name'=>'Assign to Categories <span class="smallText">(Control-click (PC) or command-click (Mac) to select multiple categories)</span>') );
			}
		}
		function on_every_time_galleries()
		{	
			$page_options = $this->get_selection_page_set($this->site_id);
			if(!empty($page_options))
			{
				$this->change_element_type('attach_to_page','select_no_sort', array( 'options' => $page_options, 'add_empty_value_to_top' => true ) );
				$this->add_comments('attach_to_page',form_comment('&#9671;: Photo gallery/slideshow pages'));
				$this->set_display_name('attach_to_page','Place imported images on page');
			}
		}
		
		function on_every_time_sharing()
		{
			// sharing
			if(!$this->get_value('no_share'))
			{
				$this->set_value('no_share',0);
			}
			if(!$this->get_value('exif_override'))
			{
				$this->set_value('exif_override','true');
			}
			if( site_shares_type($this->site_id, id_of('image')) )
			{
				$this->change_element_type( 'no_share', 'select', array( 'options' => array( 0=>'Shared', 1=>'Private' ) ) );
				$this->set_display_name( 'no_share', 'Sharing' );
			}
		}
		
		function on_every_time_order()
		{
			
			if (imagemagick_available())
			{
				$acceptable_types = array('image/jpeg',
						'image/pjpeg',
						//'application/pdf',
						'image/gif',
						'image/png',
						//'image/tiff',
						//'image/x-tiff',
						//'image/photoshop',
						//'image/x-photoshop',
						//'image/psd',
						);
			}
			else
			{
				$acceptable_types = array('image/jpeg',
						'image/pjpeg',
						'image/gif',
						'image/png',);
			}
		
			$order = array();
			if($this->_is_element('cancel_text'))
			{
				$order[] = 'cancel_text';
			}
			$order[] = 'source_selection_note';
			$order[] = 'destination_selection_note';
			for($i = 1; $i <= $this->max_upload_number; $i++)
			{
				$name = 'upload_'.$i;
				$this->add_element( $name, 'image_upload', array('max_width'=>REASON_STANDARD_MAX_IMAGE_WIDTH,'max_height'=>REASON_STANDARD_MAX_IMAGE_HEIGHT,'acceptable_types'=>$acceptable_types, 'resize_image'=>true) );
				$this->add_element( $name . '_filename', 'hidden', array('changeable'=>true) );
				if (! imagemagick_available())
				{
					$size = get_approx_max_image_size();
					$this->set_comments($name, 'Images with resolutions over '.$size['res'].' or '.$size['mps'].' MPs may cause errors');
				}
				
				$order[] = $name;
			}
			
			$this->set_order( $order );
		}
		
		/**
		 *
		 */
		function verify_image($img_pathname)
		{
			// return true if the image is an image, false otherwise
			$size = getimagesize($img_pathname);
			if (!$size)
			{
				trigger_error('Uploaded image at location ' . $img_pathname. ' returns false on getimagesize and will not be imported. The user has been notified.');
			}
			return ($size);
		}
		
		function run_error_checks()
		{
			//$this->set_error('upload_1', 'foo');
			/*
			parent::run_error_checks();
			for($i = 1; $i <= $this->max_upload_number; $i++)
			{
				
				$element = $this->get_element( 'upload_'.$i );
				if( !empty($element->tmp_full_path) AND file_exists( $element->tmp_full_path ) )
				{
					$filename = $this->get_value('upload_1_filename');
					//must I verify the image here?
					//$img_info = getimagesize($filename);
					echo $element->tmp_full_path;
					echo ' - ';
					echo $filename;
					$img_info = getimagesize($element->tmp_full_path);
					$this->set_error('upload_1', $img_info[3]);
				}
			}
			*/
		}
		
		// we are going to store the filename separately so that it is always accessible at process time even if there was an error
		// this handling should be build into plasmature probably so that anything using the image type does not have to worry about it...
		function pre_error_check_actions()
		{
			for($i = 1; $i <= $this->max_upload_number; $i++)
			{
				$element = $this->get_element( 'upload_'.$i );
				if( !empty($element->tmp_full_path) AND file_exists( $element->tmp_full_path ) )
				{
					if (!empty($element->file['name']))
					{
						$this->set_value('upload_'.$i.'_filename', $element->file['name']);
					}
				}
			}
		}
		
		function process()
		{
			$site_id = $this->site_id;
			
			$counts = array();
			for($i = 1; $i <= $this->max_upload_number; $i++)
			{
				$element = $this->get_element( 'upload_'.$i );
				if( !empty($element->tmp_full_path) AND file_exists( $element->tmp_full_path ) )
				{
					$filename = $this->get_value('upload_'.$i.'_filename');
					
					if ($this->verify_image($element->tmp_full_path))
					{
						if(empty($counts[$filename]))
						{
							$this->files[$filename] = $element->tmp_full_path;
							$counts[$filename] = 1;
						}
						else
						{
							$counts[$filename]++;
							$this->files[$filename.'.'.$counts[$filename]] = $element->tmp_full_path;
						}
					}
					else
					{
						$this->invalid_files[$filename] = $element->tmp_full_path;
					}
				}
			}
			
			if( count( $this->files ) )
			{
				$page_id = (integer) $this->get_value('attach_to_page');
				$max_sort_order_value = 0;
				if($page_id)
				{
					$max_sort_order_value = $this->_get_max_sort_order_value($page_id);
				}
				$sort_order_value = $max_sort_order_value;
				
				$tables = get_entity_tables_by_type( id_of( 'image' ) );
				
				$valid_file_html = '<ul>'."\n";
				foreach( $this->files AS $entry => $cur_name )
				{
					$sort_order_value++;
					$valid_file_html .= '<li><strong>'.$entry.':</strong> processing ';
					
					$date = '';
					
					// get suffix
					$type = strtolower( substr($cur_name,strrpos($cur_name,'.')+1) );
					$ok_types = array('jpg');
					
					// get exif data
					if( $this->get_value( 'exif_override' ) && in_array($type,$ok_types) && function_exists('read_exif_data'))
					{
						// read_exif_data() does not obey error supression
						$exif_data = @read_exif_data( $cur_name );
						if( $exif_data )
						{
							// some photos may have different fields filled in for dates - look through these until one is found
							$valid_dt_fields = array( 'DateTimeOriginal', 'DateTime', 'DateTimeDigitized' );
							foreach( $valid_dt_fields AS $field )
							{
								// once we've found a valid date field, store that and break out of the loop
								if( !empty( $exif_data[ $field ] ) )
								{
									$date = $exif_data[ $field ];
									break;
								}
							}
						}
					}
					else
					{
						$date = $this->get_value( 'datetime' );
					}
					
					$keywords = $entry;
					if($this->get_value( 'keywords' ))
					{
						$keywords .= ', '.$this->get_value( 'keywords' );
					}
					
					// insert entry into DB with proper info
					$values = array(
						'datetime' => $date,
						'image_type' => $type,
						'author' => $this->get_value( 'author' ),
						'state' => 'Pending',
						'keywords' => $keywords,
						'description' => $this->get_value( 'description' ),
						'name' => ($this->get_value( 'name' ) ? $this->get_value( 'name' ) : $entry),
						'content' => $this->get_value( 'content' ),
						'original_image_format' => $this->get_value( 'original_image_format' ),
						'new' => 0,		// make sure this goes in pending queue
						'no_share' => $this->get_value('no_share'),
					);
					//tidy values
					$no_tidy = array('state','new');
					foreach($values as $key=>$val)
					{
						if(!in_array($key,$no_tidy) && !empty($val))
						{
							$values[$key] = trim(get_safer_html(tidy($val)));
						}
					}
					
					$id = reason_create_entity( $site_id, id_of( 'image' ), $this->user_id, $entry, $values  );
					
					if( $id )
					{
						//assign to categories
						$categories = $this->get_value('assign_to_categories');
						if(!empty($categories))
						{
							foreach($categories as $category_id)
							{
								create_relationship($id, $category_id, relationship_id_of('image_to_category'));
							}
						}
					
						//assign to	gallery page
						if($page_id)
							create_relationship($page_id, $id, relationship_id_of('minisite_page_to_image'), array('rel_sort_order'=>$sort_order_value) );
						
						
						// resize and move photos
						$new_name = PHOTOSTOCK.$id.'.'.$type;
						$orig_name = PHOTOSTOCK.$id.'_orig.'.$type;
						$tn_name = PHOTOSTOCK.$id.'_tn.'.$type;
						
						// Support for new fields; they should be set null by default, but will be
						// changed below if a thumbnail/original image is created. This is very messy... 
						$thumbnail_image_type = null;
						$original_image_type = null;
						
						// atomic move the file if possible, copy if necessary
						if( is_writable( $cur_name ) )
						{
							rename( $cur_name, $new_name );
						}
						else
						{
							copy( $cur_name, $new_name );
						}
						
						// create a thumbnail if need be
						list($width, $height, $type, $attr) = getimagesize($new_name);

						if($width > REASON_STANDARD_MAX_IMAGE_WIDTH || $height > REASON_STANDARD_MAX_IMAGE_HEIGHT)
						{
							copy( $new_name, $orig_name );
							resize_image($new_name, REASON_STANDARD_MAX_IMAGE_WIDTH, REASON_STANDARD_MAX_IMAGE_HEIGHT);
						}
						
						$thumb_dimensions = get_reason_thumbnail_dimensions($site_id);
						
						if($width > $thumb_dimensions['width'] || $height > $thumb_dimensions['height'])
						{
							copy( $new_name, $tn_name );
							resize_image($tn_name, $thumb_dimensions['width'], $thumb_dimensions['height']);
							$thumbnail_image_type = $this->image_types[$type];
						}
						
						// real original
						$my_orig_name = $this->add_name_suffix($cur_name, '-unscaled');
						if( file_exists( $my_orig_name ) )
						{
							// move the original image into the photostock directory
							if( is_writable( $my_orig_name ) )
							{
								rename( $my_orig_name, $orig_name);
							}
							else
							{
								copy( $my_orig_name, $orig_name);
							}
							$original_image_type = $this->image_types[$type];
						}
						
						$info = getimagesize( $new_name );
						$size = round(filesize( $new_name ) / 1024);
						
						// now we have the size of the resized image.
						$values = array(
							'width' => $info[0],
							'height' => $info[1],
							'size' => $size,
							'thumbnail_image_type' => $thumbnail_image_type,
							'original_image_type' => $original_image_type
						);
						
						// update with new info - don't archive since this is really just an extension of the creation of the item
						// we needed that ID to do something
						reason_update_entity( $id, $this->user_id, $values, false );
						
						$valid_file_html .= 'completed</li>';
					}
					else
					{
						trigger_error('Unable to create image entity');
						$valid_file_html .= '<li>Unable to import '.$entry.'</li>';
					}
					sleep( 1 );
				}
				$valid_file_html .= '</ul>'."\n";
				$num_image_string = (count($this->files) == 1) ? '1 image has ' : count($this->files) . ' images have ';
				$valid_file_html .= '<p>'. $num_image_string . 'been successfully imported into Reason.</p>';
				$valid_file_html .= '<p>They are now pending.</p>';
				$next_steps[] = '<a href="?site_id='.$site_id.'&amp;type_id='.id_of( 'image' ).'&amp;user_id='.$this->user_id.'&amp;cur_module=Lister&amp;state=pending">review & approve imported images</a>';
			}
			
			if (isset($this->invalid_files))
			{
				$invalid_files = array_keys($this->invalid_files);
				
				$invalid_file_html = '<p>The following could not be validated as image files and were not successfully imported.</p>';
				$invalid_file_html .= '<ul>';
				foreach ($invalid_files as $file)
				{
					$invalid_file_html .= '<li><strong>' . reason_htmlspecialchars($file) . '</strong></li>';
				}
				$invalid_file_html .= '</ul>';
			}
			
			
			$next_steps[] = '<a href="'.get_current_url().'">Import another set of images</a>';
			
			if (!isset($this->invalid_files) && !isset($this->files)) echo '<p>You did not select any files for upload</p>';
			if (isset($valid_file_html)) echo $valid_file_html;
			if (isset($invalid_file_html)) echo $invalid_file_html;
			echo '<p>Next Steps:</p><ul><li>' . implode('</li><li>', $next_steps) . '</li></ul>';
			
			$this->show_form = false;
		}
		
		function _get_max_sort_order_value($page_id)
		{
			$es = new entity_selector();
			$es->add_type(id_of('image'));
			$es->add_right_relationship($page_id, relationship_id_of('minisite_page_to_image'));
			$es->add_field( 'relationship', 'id', 'rel_id' );
			$es->add_rel_sort_field($page_id);
			$es->set_order('relationship.rel_sort_order DESC');
			$es->set_num(1);
			$images = $es->run_one();
			if(!empty($images))
			{
				$image = current($images);
				if($image->get_value('rel_sort_order'))
					return $image->get_value('rel_sort_order');
			}
			return 0;
		}
	
		function add_name_suffix($path, $suffix)
		{
			$parts = explode('.', $path);
			$length = count($parts);
			$target = ($length > 1) ? ($length - 2) : 0;
	
			$parts[$target] .= $suffix;
			return implode('.', $parts);
		}
}
?>
