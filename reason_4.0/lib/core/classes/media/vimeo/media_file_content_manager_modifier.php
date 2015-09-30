<?php
require_once(SETTINGS_INC.'media_integration/vimeo_settings.php');
reason_include_once('classes/media/interfaces/media_file_content_manager_modifier_interface.php');
/**
 * Media file content manager modifier.  Causes the content mangaer to give a warning that the use 
 * of this manager is not supported for Vimeo-integrated Reason.
 */ 
class VimeoMediaFileContentManagerModifier implements MediaFileContentManagerModifierInterface
{
	protected $manager;
	
	public function set_content_manager($manager)
	{
		$this->manager = $manager;
	}
	
	/**
	 * No head items to set because this content manager is not used for integrated media works.
	 */
	public function set_head_items()
	{}
	
	/**
	 * Output message to user saying the use of the media file content manager is supported for
	 * vimeo-integrated works.
	 */
	public function show_form()
	{
		echo '<p>Use of the Media File Content Manager is deprecated.  Use Media Work instead.</p>'."\n";
	}
	
	/**
	 * Nothing happens since this content manager is not used for integrated media works.
	 */
	public function alter_data()
	{}
	
	/**
	 *  Don't add a callback because this content manager is not used for integrated media works.
	 */
	public function process()
	{}
	
	/**
	 * Don't add a callback because this content manager is not used for integrated media works.
	 */
	public function run_error_checks()
	{}
}
?>