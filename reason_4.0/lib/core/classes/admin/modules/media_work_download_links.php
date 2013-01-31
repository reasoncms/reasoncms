<?php
/**
 * A module that offers the download links for a kaltura-integrated media work.
 * @package reason
 * @subpackage admin
 */

/**
 * Include dependencies
 */
reason_include_once('classes/admin/modules/default.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/kaltura_shim.php');
reason_include_once( 'function_libraries/url_utils.php' );
reason_include_once('classes/media_work_helper.php');


/**
* This module checks to see if the current user has access to the given media work.  It the user has access,
* All possible download links are displayed.
*
* This module is only compatible with kaltura-integrated Media Works.
*
* @author Marcus Huderle
*/
class mediaWorkDownloadLinksModule extends DefaultModule
{	
	
	var $media_work;
	var $kaltura_shim;
	var $user;
	var $media_length;
	var $media_files;
	
	function init()
	{
		parent::init();
		
		$this->admin_page->title = 'Download Links for Media Work';
		$this->media_work = new entity($this->admin_page->id);
		
		if ($this->media_work->get_value('integration_library') == 'kaltura')
		{
			$this->kaltura_shim = new KalturaShim();
			$this->user = new entity($this->admin_page->user_id);
			
			// Grab the associated media files
			$es = new entity_selector();
			$es->add_type(id_of('av_file'));
			$es->add_right_relationship($this->media_work->id(), relationship_id_of('av_to_av_file'));
			$this->media_files = $es->run_one();
		}
	}
	
	function run()
	{	
		if ($this->media_work->get_value('integration_library') == 'kaltura')
		{	
			// Kill the module if the user doesn't have access to the video
			$mwh = new media_work_helper($this->media_work);
			if ( !$mwh->user_has_access_to_media() )
			{
				die('<p>You do not have permission to view the download links for this media work.</p>');
			}
			
			echo '<p>All download links for "'.$this->media_work->get_value('name').'" are displayed below.</p>'."\n";
			
			echo '<ul>'."\n";
			echo '<li><a href="'.$this->kaltura_shim->get_original_data_url($this->media_work->get_value('entry_id')).'">Original</a></li>'."\n";
			foreach ($this->media_files as $file)
			{
				echo '<li><a href="'.$file->get_value('download_url').'">'.$file->get_value('name').'</a></li>'."\n";
			}
			echo '</ul>'."\n";
			
		}
		else
		{
			die('<p>This module only applies to kaltura-integrated Media Works.</p>');
		}
	}
}
?>
