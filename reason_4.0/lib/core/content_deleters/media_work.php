<?php
/**
 * @package reason
 * @subpackage content_deleters
 * 
 * Used alongside Kaltura Integration.
 */
	/**
	 * Register deleter with Reason & include parent class
	 */
	$GLOBALS[ '_reason_content_deleters' ][ basename( __FILE__) ] = 'media_work_deleter';
	
	reason_include_once( 'classes/admin/admin_disco.php' );
	reason_include_once('classes/kaltura_shim.php');
	
	/**
	 * A content deleter for media works.
	 *
	 * Upon expungement, we expunge the associated media files from Reason, and we also
	 * delete the entries from Kaltura if Kaltura integration is enabled.
	 */
	class media_work_deleter extends deleteDisco
	{
		function delete_entity() // {{{
		{		
			if (KalturaShim::kaltura_enabled())
			{
				$e = new entity( $this->get_value( 'id' ) );
				if ($e->get_value('integration_library') == 'kaltura')
				{
					$es = new entity_selector();
					$es->add_type(id_of('av_file'));
					$es->add_right_relationship($this->get_value('id'), relationship_id_of('av_to_av_file'));
					$media_files = $es->run_one();
					
					foreach ($media_files as $file)
					{
						reason_expunge_entity($file->id(), $this->admin_page->user_id);
					}
					if($e->get_value('entry_id'))
					{
						$shim = new KalturaShim();
						$user = new entity($this->admin_page->user_id);
						
						$shim->delete_media($e->get_value('entry_id'), $user->get_value('name'));
					}
				}
			}
			parent::delete_entity();
		}
	}
?>
