<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/profile/forms/default.php' );
reason_include_once( 'minisite_templates/modules/profile/lib/profile_functions.php' );
reason_include_once( 'classes/plasmature/upload.php' );
reason_include_once( 'content_managers/image.php3' ); 
reason_include_once( 'minisite_templates/modules/profile/lib/models/gallery.php' );  
reason_include_once( 'minisite_templates/modules/profile/lib/views/gallery.php' ); 

/**
 * Image gallery edit form - this functions as a controller.
 *
 * - Allow upload and choice of album.
 *
 * Phase 1 - No Fanciness, No albums
 *
 * We show a list of images.
 * Under the list is an "add image" form with a description field.
 * If you click a gallery image we show an edit image form instead of add image.
 * 
 * Adding / editing options include:
 *
 * - Set description
 * - Upload new image
 * - Move up / down?
 *
 * @todo album creation / deletion / checkbox selection?
 * @todo AJAX hide add an image until you click an "Add an image" link.
 * @todo AJAX hide description until upload completes.
 *
 * Phase 2 - Progressive Enhancement
 *
 * @todo implement order control - drag and drop?
 *
 */
class galleryProfileEditForm extends defaultProfileEditForm
{
	protected $min_width = 0;
	protected $min_height = 0;
	public $form_enctype = 'multipart/form-data';
	public $actions = array('Upload');
	var $box_class = 'StackedBox';

	/**
	 * We add our plasmature element here. We do this because we want the module head items.
	 *
	 */
	function custom_init()
	{
		$head_items = $this->get_head_items(); // module head items
		$params = array( 'head_items' => $this->head_items );
		$this->add_element( $this->get_section(), 'reasonImageUpload', $params );
		$this->set_display_name($this->get_section(), 'Add an Image');
		$this->add_element('description', 'text');
		$this->add_element('image_id', 'hidden');
		
		// ALBUMS PHASE 2
		//$this->add_element('Albums', 'checkboxgroup', array('options'=> array('A','B')));
		//$this->add_required($this->get_section());
	}
		
	function pre_show_form()
	{
		// if we are editing an exising image we don't show them all
		$image_id =  $this->get_value('image_id');
		var_dump($image_id);
		if ($image_id)
		{
		
		}
		else
		{
			$profile_gallery = $this->get_profile_gallery();
			$view = new DefaultProfileGalleryView();
			$view->config('edit', true);
			$view->data($profile_gallery);
			echo $view->get();
		}
		
		//$images->get_all_images();
		
		//return '<p>Hi i rock</p>';
		//echo '<ul>';
		//echo '<li><a href="'.profile_construct_link(array('mode'=>'manage','edit_section' => NULL,'username' => NULL)).'">Manage</li>';
		//echo '<li><a href="'.profile_construct_link(array('mode'=>'upload','edit_section' => NULL,'username' => NULL)).'">Upload</li>';
		//echo '</ul>';
	}
	
	/**
	 * @todo this should not depend on imagemagick.
	 */
	function run_error_checks()
	{
		if( !$this->_has_errors() && ($upload = $this->get_element('gallery')))
		{
			if ($info = get_dimensions_image_magick($upload->tmp_full_path))
			{
				if ($info['width'] < $this->min_width && $info['height'] < $this->min_height)
					$this->set_error('profile_image','Your image is not large enough; it needs to be at least 
						'.$this->min_width.'x'.$this->min_height.' pixels in size.');
			}
		}
	}

	/**
	 * Create the profile entity if it does not yet exist.
	 */
	function process()
	{
		$profile_gallery = $this->get_profile_gallery();
		$image = $this->get_element('gallery');
		
		// Save the image to the gallery
		$profile_gallery->save_image($image, $this->get_value('description'));
		
		// Update description if needed
		// $profile_gallery->set_description($image, $this->get_value('description'));
		
		// PHASE 2 Place image in appropriate albums if they are selected
		//$this->person->set_image($this->get_element('image'));
	}
	
	function get_albums()
	{
	
	}
	
	function get_album_photos()
	{
	
	}
	
	function get_profile_gallery()
	{
		if (!isset($this->_profile_gallery))
		{
			$person = $this->get_person();
			$model = new DefaultProfileGalleryModel();
			$model->config('username', $person->get_username());
			$this->_profile_gallery = $model->get();
		}
		return $this->_profile_gallery;
	}	
}