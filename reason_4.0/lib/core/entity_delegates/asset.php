<?php

reason_include_once( 'entity_delegates/abstract.php' );

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
}