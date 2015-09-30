<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Include the parent class
	 */
	reason_include_once( 'content_managers/default.php3' );
	
	/**
	 * Register the content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'NonReasonSiteManager';

	/**
	 * A content manager for non-reason sites (e.g. metadata about sites not managed by
	 * reason, but which you want to integrate into a-z guide, etc.)
	 *
	 * @todo remove check for is_incarnate before Reason 4 RC 1
	 */
	class NonReasonSiteManager extends ContentManager
	{
		function alter_data() // {{{
		{
			parent::alter_data();
			
			$this->set_value( 'site_state' , 'Live' );
			
			//$this->add_required( 'site_type' );
			$this->add_required( 'url' );
			
			if ($this->_is_element('script_url')) $this->change_element_type( 'script_url', 'hidden');
			$this->change_element_type( 'primary_maintainer', 'hidden');
			$this->change_element_type( 'base_breadcrumbs', 'hidden');
			$this->change_element_type( 'base_url', 'hidden');
			//$this->change_element_type( 'department', 'hidden');
			$this->change_element_type( 'asset_directory', 'hidden');
			$this->change_element_type( 'loki_default', 'hidden');
			$this->change_element_type( 'short_department_name', 'hidden');
			$this->change_element_type( 'site_state', 'hidden');
			if ($this->_is_element('is_incarnate')) $this->remove_element( 'is_incarnate' );
			$this->change_element_type( 'custom_url_handler', 'hidden');
			$this->change_element_type( 'use_page_caching', 'hidden');
			$this->change_element_type( 'allow_site_to_change_theme', 'hidden');
			$this->change_element_type( 'other_base_urls', 'hidden');
                        $this->change_element_type( 'use_custom_footer', 'hidden');
                        $this->change_element_type( 'custom_footer', 'hidden');
			
		} // }}}
		
	}
?>
