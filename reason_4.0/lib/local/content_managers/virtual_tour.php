<?php
/**
 * @package reason
 * @subpackage content_managers
 */

/**
 * Include dependencies
 */
	reason_include_once('classes/url_manager.php');
	reason_include_once('classes/plasmature/upload.php');
	reason_include_once('content_managers/default.php3');
	reason_include_once('function_libraries/asset_functions.php');
	reason_include_once('classes/default_access.php');
	
	require_once CARL_UTIL_INC.'basic/mime_types.php';
	require_once CARL_UTIL_INC.'basic/misc.php';

/**
 * Register content manager with Reason
 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'LutherVirtualTour';

/**
 * A content manager for PTgui virtual tours (http://www.ptgui.com)
 */
	class LutherVirtualTour extends ContentManager
	{
		function alter_data()
		{
			$authenticator = array("reason_username_has_access_to_site",
					$this->get_value("site_id"));
			
			$existing_asset_type = $this->get_value("file_type");
			$full_asset_path = ($this->get_value('file_type')) ? ASSET_PATH.$this->_id.'.'.$this->get_value('file_type') : false;
			$params = array('authenticator' => $authenticator,
					'max_file_size' => $this->get_actual_max_upload_size(),
					'head_items' => &$this->head_items,
					'file_display_name' => $this->get_value('file_name'));
			if (!empty($existing_asset_type)) {
				$params = array_merge($params, array(
						'existing_entity' => $this->_id,
						'allow_upload_on_edit' => true));
			}
				
			$this->add_element('asset', 'ReasonUpload', $params);
			$asset = $this->get_element('asset');
				
			$this->set_comments( 'description', form_comment('A description of the virtual tour.') );
			$this->set_comments ( 'asset', form_comment('Your zipfile name may be modified if it is has already been taken, or includes spaces or unusual characters.'));
				
			$this->add_required( 'description' );
			
			$this->set_display_name( 'asset', 'File' );
			
			$this->change_element_type( 'file_size', 'hidden' );
			$this->change_element_type( 'file_name', 'text' );
			$this->change_element_type( 'file_type', 'hidden' );
			$this->change_element_type( 'mime_type', 'hidden' );
			
			//$this->add_restriction_selector();				
		}
		
		function process()
		{


			parent::process();
		}
		
		function get_actual_max_upload_size()
		{
			return reason_get_asset_max_upload_size();
		}
	}
?>
