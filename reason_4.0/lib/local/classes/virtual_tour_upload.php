<?php

/**
 * File upload for Luther virtual tours.
 * 
 * @package reason
 * @subpackage classes
 * @author Brian Jones <jonebr01@luther.edu>
 */

reason_include_once('classes/plasmature/upload.php');

class VirtualTourUploadType extends ReasonUploadType
{
	var $type = "VirtualTourUpload";
	var $max_file_size = 52428800; // 50 MB
	
	/** @access private */
	var $type_valid_args = array(
			'existing_file',
			'existing_file_web',
			'original_path',
			'file_display_name',
			'file_display_size',
			'acceptable_types',
			'acceptable_extensions',
			'allow_upload_on_edit',
			'max_file_size'
	);
	

	function additional_init_actions($args=array())
	{
		$auth = @$args['authenticator'];
		$this->upload_sid = _get_disco_async_upload_session($auth);
	
		$constraints = array(
				'mime_type' => $this->acceptable_types,
				'extension' => $this->acceptable_extensions,
				'max_size' => $this->max_file_size
		);
		reason_add_async_upload_constraints($this->upload_sid, $this->name,
		$constraints);
	
		if (isset($args["head_items"]))
			$this->get_head_items($args["head_items"]);
	
		_reason_upload_handle_entity($this, "virtual_tour_type",
		"reason_get_asset_filesystem_location", "reason_get_asset_url");
	
		//return parent::additional_init_actions($args);
		// want additional_init_actions() from grandparent
		// copied from file: reason_package_luther/disco/plasmature/type/upload.php
		if (!empty($this->existing_file)) {
			$this->_state = $this->state = 'existing';
			$this->value = $this->existing_file;
		}
		return;
		
	}

	function _get_current_file_display($current)
	{
		if (!$current)
			return '';

		if ($current->path) {
			$filename = $this->_get_display_filename($current);
			$size = format_bytes_as_human_readable($this->file_display_size);
			$style = '';
		} else {
			$filename = $size = '';
			$style = ' style="display: none;"';
		}
		
		return '<div class="uploaded_file"'.$style.'>'.
			'<span class="filename">'.htmlspecialchars($filename).'</span> '.
			'<span class="size"><span class="filesize">'.$size.
			'</span></span></div>';
	}
	
}