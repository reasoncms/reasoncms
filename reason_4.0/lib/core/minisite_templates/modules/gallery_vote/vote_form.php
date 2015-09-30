<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include parent class
 */
	include_once( DISCO_INC.'disco.php' );

/**
 * The form that the gallery vote module uses to provide users the ability to vote on images
 */
class GalleryVoteForm extends Disco
{
	var $submitted = false;
	var $actions = array('save' => 'Vote');
	
	function GalleryVoteForm($images)
	{
			$option_array = $this->populate_images($images);
			$this->add_element('image_choice', 'radio', $option_array);
			//$this->set_comments( 'image_choice', 'Please choose the image you\'d like to select as the people\'s choice.');		
			$this->add_required( 'image_choice' );
			$this->set_display_name('image_choice', 'Choose an Image');
	}
	
	function populate_images($images)
	{
		$image_option_array = array();
		foreach ($images as $k=>$v)
		{
			$image_option_array['options'][$k] = get_show_image_html($k, false, true, false);
		}
		return $image_option_array;
	}

	function process()
	{
		$this->show_form = false; // do not display form upon successful submission
		$this->submitted = true; // module will handle process phase
	}
}	
	
?>
