<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'site_user_handler';
	
	/**
	 * A content manager for site users
	 */
	class site_user_handler extends ContentManager
	{
		function alter_data() {
			$this->set_display_name('name','Username');
			$this->add_comments('name',form_comment('This is the NetID of the user, e.g. hendlerd or mryan'));
		}
	}
?>
