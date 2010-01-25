<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'LutherFlickrSlideshow';

	/**
	 * A content manager for text blurbs
	 */
	class LutherFlickrSlideshow extends ContentManager
	{
		function alter_data()
		{
		//$this->set_display_name('flickr_username', 'Flickr account username');	
		$this->set_comments('flickr_username', form_comment('Enter username used to access flickr account.'));
		$this->set_comments('flickr_photoset_id', form_comment('All images from flickr set will be used in this slideshow.'));
		$this->add_required('flickr_username');
		$this->add_required('flickr_photoset_id');


		}
	}
?>
