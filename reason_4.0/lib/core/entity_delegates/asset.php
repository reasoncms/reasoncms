<?php

reason_include_once( 'entity_delegates/abstract.php' );
 reason_include_once('function_libraries/asset_functions.php');

$GLOBALS['entity_delegates']['entity_delegates/asset.php'] = 'assetDelegate';

/**
 * @todo implement methods that help with ingestion of images
 */
class assetDelegate extends entityDelegate
{
	/**
	 * Determines the MIME type of the asset based on its file extension.
	 * Requires the {@link APACHE_MIME_TYPES} constant to be properly defined.
	 * @param string $fileext
	 * @return string the asset's MIME type, or an empty string if it could
	 *         not be determined
	 * @uses mime_type_from_extension
	 */
	function get_mime_type()
	{
		if ($this->entity->has_value('mime_type'))
		{
		 	return $this->entity->get_value('mime_type');
		}
	    
		if($this->entity->has_value('file_type'))
			return mime_type_from_extension($this->entity->get_value('file_type'), '');
		
		
	}
	function get_url($type = '')
	{
		return reason_get_asset_url($this->entity);
	}
	function get_asset_path()
	{
		return reason_get_asset_filesystem_location($this->entity);
	}
	function get_export_generated_data()
	{
		$ret = array();
		$ret['url'] = $this->absolutify($this->entity->get_url());
		$ret['filesystem_location'] = $this->entity->get_asset_path();
		return $ret;
	}
	protected function absolutify($path)
	{
		return securest_available_protocol() . '://' . REASON_HOST . $path;
	}
}