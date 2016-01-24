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
reason_include_once('classes/plasmature/upload.php');
reason_include_once('content_managers/image.php3');    

/**
 * Profile image edit form.
 *
 * - Allows you to modify your profile image
 */
class imageProfileEditForm extends defaultProfileEditForm
{
	protected $min_width = 100;
	protected $min_height = 100;
	public $form_enctype = 'multipart/form-data';
	public $actions = array('Upload');

	/**
	 * We add our plasmature element here. We do this because we want the module head items.
	 *
	 */
	function custom_init()
	{
		$params = array('head_items' => $this->head_items,
				'crop_ratio' => 1,
				'require_crop' => true,
			  	);
		$this->add_element($this->get_section(), 'reasonImageUploadCroppable', $params);
		$this->set_display_name($this->get_section(), $this->get_section_display_name());
		$this->add_required($this->get_section());
	}
		
	function pre_show_form()
	{
		echo '<h3>Profile Image</h3>';
		echo '<p>Choose an image from your computer. Once it displays below, you may drag the selection square
		to crop your image, making sure your head takes up most of the frame.</p>';
	}
	
	/**
	 * @todo this should not depend on imagemagick.
	 */
	function run_error_checks() 
	{
		if( !$this->_has_errors() && ($upload = $this->get_element('image')))
		{
			if ($info = get_dimensions_image_magick($upload->tmp_full_path))
			{
				if ($info['width'] < $this->min_width || $info['height'] < $this->min_height)
					$this->set_error($this->get_section(),'Your image is not large enough; it needs to be at least 
						'.$this->min_width.'x'.$this->min_height.' pixels in size.');
			}
		}
	}

	/**
	 * Create the profile entity if it does not yet exist.
	 */
	function process()
	{
		$this->person->set_image($this->get_element('image'));
	}
	
}