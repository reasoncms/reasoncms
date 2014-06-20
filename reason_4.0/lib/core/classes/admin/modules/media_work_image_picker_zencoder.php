<?php
/**
 * Include dependencies
 */
reason_include_once('classes/admin/modules/default.php');
reason_include_once('classes/entity_selector.php');
include_once( DISCO_INC . 'disco.php');
reason_include_once('content_managers/image.php3');
reason_include_once('classes/media_work_helper.php');
reason_include_once('classes/media/factory.php');

/**
 * This module allows for easy selection of still frames from a video to use as its placard image.  The
 * disco form presents a slider to the user that corresponds to a time position in the video.  As the
 * user drags the slider around, a thumbnail is displayed from image entities in Reason that are 
 * the thumbnails from video.
 *
 * This module is only compatible with Zencoder-integrated Media Works.
 *
 * @author Marcus Huderle
 */
class zencoderMediaWorkImagePickerModule extends DefaultModule
{	
	
	var $media_work;
	var $cur_image;
	var $user;
	var $media_length;
	var $images;
	var $shim;
	
	function init()
	{
		parent::init();
		
		$this->admin_page->title = 'Select Thumbnail for Media Work';
		$this->media_work = new entity($this->admin_page->id);
		
		$this->user = new entity($this->admin_page->user_id);
			
		// Grab the initial associated image, if it exists
		$es = new entity_selector();
		$es->add_type(id_of('image'));
		$es->add_right_relationship($this->media_work->id(), relationship_id_of('av_to_primary_image'));
		$this->cur_image = current($es->run_one());
	
		$this->admin_page->head_items->add_javascript(JQUERY_URL, true);
		$this->admin_page->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'media_image_picker_zencoder.js');
		
		// fd-slider is a polyfill for the range plasmature type
		$this->admin_page->head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'fd-slider/js/fd-slider.js');
		$this->admin_page->head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'fd-slider/css/fd-slider.css');
		
		$this->shim = MediaWorkFactory::shim($this->media_work);
	}
	
	function run()
	{
		// Kill the module if the media work is somehow not a media work video
		if ($this->media_work->get_value('integration_library') != 'zencoder' || $this->media_work->get_value('av_type') != 'Video' || $this->admin_page->type_id != id_of('av'))
		{
			die('<p>This module only works with Zencoder-integrated Media Works whose av_type is \'Video\'.</p>');
		}
		// Kill the module if the user doesn't have access to the video
		$mwh = new media_work_helper($this->media_work);
		if ( !$mwh->user_has_access_to_media() )
		{
			die('<p>You do not have permissions to change the still frame for this video.</p>');
		}
		
		if (count($this->get_images()) == 0)
		{
			die('<p>There are no thumbnails for this media work. Attach an image to this media work if you want to assign it a thumbnail.</p>'."\n");
		}
		
		if ($this->cur_image)
		{
			echo '<p>This Media Work\'s current thumbnail is displayed below:</p>'."\n";
			echo '<img src="'.reason_get_image_url($this->cur_image).'">'."\n";
			echo '<hr />'."\n";
			
			$choose_img_link = carl_make_redirect(array('cur_module' => 'Associator', 'rel_id' => relationship_id_of('av_to_primary_image')));
			echo '<h4><a href="'.$choose_img_link.'">Choose your own image.</a></h4>'."\n";
			echo '<h3>OR</h3>'."\n";			
			echo '<h4>Use the slider below to choose a different still frame from the video to use as its thumbnail.</h4>'."\n";
		}
		else
		{
			echo '<h4>Choose your own image to use as its placard image.</h4>'."\n";
			echo '<h2>OR</h2>'."\n";
			echo '<h4>Use the slider below to choose a still frame from the video to use as its thumbnail.</h4>'."\n";
		}
		if (count($this->get_images()) > 1)
		{
			echo '<p>There are '.count($this->get_images()).' images to choose from.</p>'."\n";
		}
		elseif (count($this->get_images()) == 1)
		{
			echo '<p>There is only one image to choose from.</p>'."\n";
		}
		else
		{
			echo '<p>There are no images to choose from.</p>'."\n";
		}
		// provide data for javascript magic		
		echo $this->get_js_data();			
					
		$this->run_form();
	}
		
	function run_form()
	{	
		$form = new Disco();
		$form->actions = array('save' => 'Use This Image');
		
		$range_opts = $this->generate_range_options();
		$form->add_element('thumbnails', 'range_slider', $range_opts);
		$form->set_display_name('thumbnails', 'Thumbnails');
		
		// This element is merely a placeholder for the javascript to see
		$form->add_element('entry_id', 'hidden');
		$form->set_value('entry_id', $this->media_work->get_value('entry_id'));
	
		$form->add_callback(array(&$this, 'process_form'),'process');
		$form->add_callback(array(&$this, 'where_to'), 'where_to');
		$form->run();	
	}	
	
	function where_to(&$disco)
	{
		// Simply redirect to the Editor module
		return carl_make_redirect(array('cur_module' => 'Editor'));
	}
	
	// Creates a new image entity and associates it with the media work.  
	function process_form(&$disco)
	{
		$images = $this->get_images();
		$thumbnail_image = $images[$disco->get_value('thumbnails')];
		
		$tmp_path = WEB_PATH . trim_slashes(WEB_TEMP).'/temp_media_image_'.uniqid().'.jpg';
		
		$contents = get_reason_url_contents($thumbnail_image);
		if (!$contents)
		{
			trigger_error('Unable to retrieve file contents for '.$thumbnail_image.'. The url is invalid or S3 is being slow.');
			return;
		}
		
		$f = fopen($tmp_path, 'w');
		fwrite($f, $contents);
		fclose($f);
		
		if( !empty($tmp_path) AND file_exists( $tmp_path) )
		{
			// Create a new entity for the image
			if ($id = $this->create_image_entity())
			{
				$im = new ImageManager();
				//$im->convert_non_web_to = $this->convert_non_web_to;
				$im->thumbnail_width = REASON_STANDARD_MAX_THUMBNAIL_WIDTH;
				$im->thumbnail_height = REASON_STANDARD_MAX_THUMBNAIL_HEIGHT;
				$im->max_width = REASON_STANDARD_MAX_IMAGE_WIDTH;
				$im->max_height = REASON_STANDARD_MAX_IMAGE_HEIGHT;
				$im->load_by_type( id_of('image'), $id, $this->user->id() );
				
				$im->handle_standard_image($id, $tmp_path);
				//$im->handle_original_image($id, $image);		
				
				$im->create_default_thumbnail($id);
						
				$image_path = PHOTOSTOCK . reason_format_image_filename($id, 'jpg');
				$original_path = add_name_suffix($image_path, '_orig');
				@copy($image_path, $original_path);
				resize_image($image_path, $im->max_width, $im->max_height);
				
				// Pull the values generated in the content manager
				// and save them to the entity
				$values = array();
				foreach($im->get_element_names() as $element_name)
				{
					$values[ $element_name ] = $im->get_value($element_name);
				}
				reason_update_entity( $id, $this->user->id(), $values, false );
				
				// Remove any existing association with an image and replace it with this new one
				delete_relationships(array('entity_a' => $this->media_work->id(), 'type' => relationship_id_of('av_to_primary_image')));
				create_relationship($this->media_work->id(), $id, relationship_id_of('av_to_primary_image'));
			} 
			else 
			{
				trigger_error('Failed to create image entity.');		
			}
		} 
		else 
		{
			trigger_error('No path to image: '.$tmp_path);
		}
	}
	
	function create_image_entity()
	{
		$name = $this->media_work->get_value('name').' (Generated Thumbnail)';
		$values = array();
		$values['new'] = '0';
		$values['author'] = $this->user->get_value('name');
		$values['description'] = 'A placard image for media work '.$this->media_work->get_value('name');
		$values['no_share'] = '0';
		
		return reason_create_entity( $this->admin_page->site_id, id_of('image'), $this->user->id(), $name, $values);
	}
	
	function generate_range_options()
	{
		$opts = array();
		$opts['min'] = 0;
		$opts['max'] = count($this->get_images())-1;
		$opts['step'] = 1;
		$opts['value'] = intval($opts['max'] / 2);
		
		return $opts;
	}
	
	function get_images()
	{
		if (!isset($this->images))
		{
			$this->images = $this->_get_images();
		}
		return $this->images;
	}
	
	/** 
	 * Gets an array of thumbnails for the media work
	 * right now, an image is condidered a thumbnail for a media work if it's name follows the
	 * pattern of: [Media Work Name] (Generated Thumbnail)
	 */
	function _get_images()
	{
		$images = array();
		for ($i = 0; $i < $this->shim->get_num_stills(); $i += 1)
		{
			$images[] = $this->shim->get_storage_class()->get_stills_base_url($this->media_work).$i.'.jpg';
		}
		return $images;
	}
	
	function get_js_data()
	{
		$markup = '<div id="thumbnail_data">';
		$i = 0;
		foreach ($this->get_images() as $key => $url)
		{
			$markup .= '<span class="'.$i.'" data-src="'.$url.'">';
			$markup .= '</span>'."\n";
			$i += 1;
		}
		$markup .= '</div>'."\n";
		return $markup;
	}
}
?>
