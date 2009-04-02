<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
	/**
	 * Register previewer with Reason
	 */
	$GLOBALS[ '_content_previewer_class_names' ][ basename( __FILE__) ] = 'UserPreviewer';
	
	/**
	 * A content previewer for Reason user entities
	 */
	class UserPreviewer extends default_previewer
	{
		/**
		 * Hide the user_password_hash
		 */
		function show_item_user_password_hash($field, $value) //{{{
		{
			// don't put any code here.
		} // }}}
	}
?>
