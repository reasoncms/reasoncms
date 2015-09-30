<?php
/**
 * @package reason
 * @subpackage content_deleters
 * 
 * Used alongside Media Integration.
 */
/**
 * Register deleter with Reason & include parent class
 */
$GLOBALS[ '_reason_content_deleters' ][ basename( __FILE__) ] = 'media_work_deleter';

reason_include_once( 'classes/admin/admin_disco.php' );
reason_include_once('classes/media/factory.php');	

/**
* A content deleter for media works.
*/
class media_work_deleter extends deleteDisco
{
	function delete_entity()
	{		
		$media_work = new entity($this->get_value('id'));
		$shim = MediaWorkFactory::shim($media_work);
		if ($shim)
		{
			$shim->delete_media_work($this);
		}
		
		parent::delete_entity();
	}
}
?>
