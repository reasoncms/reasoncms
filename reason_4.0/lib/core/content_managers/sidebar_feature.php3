<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'sidebar_feature_manager';

	/**
	 * A content manager for sidebar features
	 * @deprecated
	 * @todo Remove from Reason core
	 */
	class sidebar_feature_manager extends ContentManager 
	{
		function alter_data() {
			trigger_error('sidebar_feature_manager is deprecated.');
			$this -> add_required ("description");
			$this -> add_required ("show_hide");
			$this -> set_comments ("name", form_comment("For your reference only"));
			$this -> set_display_name ("description", "Sidebar Feature Text");
			$this -> set_display_name ("show_hide", "Show/Hide");
			$this -> set_comments ("show_hide", form_comment('This allows you to toggle a feature on &amp; off'));
		}	
	}
?>