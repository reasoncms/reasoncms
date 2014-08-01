<?php
/**
 * @package reason_local
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/profile/forms/default.php' );
reason_include_once( 'function_libraries/asset_functions.php' );
reason_include_once( 'classes/plasmature/upload.php' );

/**
 * The resume edit form uses the ReasonUpload plasmature type to allow addition / replacement of a résumé.
 *
 * @todo define acceptable file types?
 */
class resumeProfileEditForm extends defaultProfileEditForm
{
	// switch this is we decide we want to show the existing file.
	var $show_existing_file = true;
	var $actions = array('Upload');
	
	/**
	 * Why not just make this the disco default?
	 */
	var $form_enctype = 'multipart/form-data';
	
	/**
	 * We add our plasmature element here. We do this because we want the module head items.
	 *
	 * @todo do we even care to show the existing file?
	 */
	function custom_init()
	{
		$person = $this->get_person();
		$head_items = $this->get_head_items(); // module head items
		
		$params = array('max_file_size' => reason_get_asset_max_upload_size(),
			  	        'head_items' => $this->head_items,
			  	        'acceptable_extensions' => array('doc','docx','pdf','txt','rtf'),
			  	        );
		
		if ($this->show_existing_file && ($resume = $person->get_resume()))
		{
			/**
			 * Setting file_display_name can be taken out once all asset changes are in production (hopefully by reason 4.3)
			 */
			$params['file_display_name'] = $resume['file_name'];
			$params['existing_entity'] = $resume['id'];
			$params['allow_upload_on_edit'] = true;
		}
		
		$this->add_element($this->get_section(), 'ReasonUpload', $params);
		$this->set_display_name($this->get_section(), $this->get_section_display_name());
		$this->set_comments($this->get_section(), '<p>Preferred format: .pdf<br/>Alternate: .doc, .docx, .rtf, .txt</p>');
		$this->add_required($this->get_section());
	}
	
	/**
	 * Create / update asset
	 */
	function process()
	{
		$person = $this->get_person();
		$file = $this->get_value($this->get_section());
		$person->set_resume($file);
	}
}