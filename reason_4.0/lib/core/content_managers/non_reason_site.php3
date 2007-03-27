<?php
	reason_include_once( 'content_managers/default.php3' );
	
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'NonReasonSiteManager';

	class NonReasonSiteManager extends ContentManager
	{
		function alter_data() // {{{
		{
			parent::alter_data();
			
			$this->set_value( 'site_state' , 'Live' );
			
			//$this->add_required( 'site_type' );
			$this->add_required( 'url' );
			
			$this->change_element_type( 'script_url', 'hidden');
			$this->change_element_type( 'primary_maintainer', 'hidden');
			$this->change_element_type( 'base_breadcrumbs', 'hidden');
			$this->change_element_type( 'base_url', 'hidden');
			//$this->change_element_type( 'department', 'hidden');
			$this->change_element_type( 'asset_directory', 'hidden');
			$this->change_element_type( 'loki_default', 'hidden');
			$this->change_element_type( 'short_department_name', 'hidden');
			$this->change_element_type( 'site_state', 'hidden');
			$this->change_element_type( 'is_incarnate', 'hidden');
			$this->change_element_type( 'custom_url_handler', 'hidden');
			$this->change_element_type( 'use_page_caching', 'hidden');
			$this->change_element_type( 'allow_site_to_change_theme', 'hidden');
			$this->change_element_type( 'other_base_urls', 'hidden');
			
		} // }}}
		
	}
?>
