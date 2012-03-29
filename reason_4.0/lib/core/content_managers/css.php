<?php
/**
 * A content manager for CSS
 * @package reason
 * @subpackage content_managers
 */
 
  /**
   * Store the class name so that the admin page can use this content manager
   */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'cssContentManager';

	/**
	 * A content manager for CSS
	 */
	class cssContentManager extends ContentManager
	{
		function alter_data()
		{
			$this->add_required('url');
			$this->add_comments('css_media',form_comment('The media string to use when including this CSS file in the page head, e.g. "print" or "all and (min-width:500px)". Leave empty to apply to all media.'));
			$this->set_order(array('name','url','css_relative_to_reason_http_base','css_media','description','keywords'));
		}
	}
?>
