<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'SiteTypeContentManager';

	/**
	 * A content manager for site types
	 */
	class SiteTypeContentManager extends ContentManager
	{
		function alter_data()
		{
			$this->set_display_name('show_hide','Visibility in Sitemap');
			$this->add_comments('show_hide',form_comment('This controls whether this site type 
(and the 
sites associated with it)  are displayed in the site map module'));
		}
	}
?>
