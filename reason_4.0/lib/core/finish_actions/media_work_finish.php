<?php
/**
 * Checks to see if the media work's categories have changed and update the kaltura entry accordingly.
 * @package reason
 * @subpackage finish_actions
 */
 
/**
 * Register the finish action with Reason & include dependencies
 */
$GLOBALS['_finish_action_classes'][ basename( __FILE__) ] = 'updateMediaWorkOnFinish';

reason_include_once('finish_actions/default.php');
reason_include_once('classes/url_manager.php');
reason_include_once('classes/kaltura_shim.php');


class updateMediaWorkOnFinish extends defaultFinishAction
{
	/**
	 * run the rewrite rules
	 */
	function run()
	{
		$user = new entity($this->vars['user_id']);
		$media_work = new entity($this->vars['id']);
		
		if ($media_work->get_value('integration_library') == 'kaltura')
		{
			$shim = new KalturaShim();
			
			$shim->update_media_entry_metadata($media_work->get_value('entry_id'), $user->get_value('name'), '', '', '', $this->_get_categories($media_work));
		}
	}
	
	function _get_categories($media_work)
	{
		$es = new entity_selector();
		$es->add_type(id_of('category_type'));
		$es->add_right_relationship($media_work->get_value('id'), relationship_id_of('av_is_about_category'));
		$abouts = $es->run_one();
		
		$es = new entity_selector();
		$es->add_type(id_of('category_type'));
		$es->add_right_relationship($media_work->get_value('id'), relationship_id_of('av_refers_to_category'));
		$refers = $es->run_one();
		
		$names = array();
		foreach ($abouts as $cat)
		{
			$names[] = $cat->get_value('name');
		}
		foreach ($refers as $cat)
		{
			$names[] = $cat->get_value('name');
		}
		
		$site = $media_work->get_owner();
		$names[] = $site->get_value('name');
		
		return $names;
	}
	
}

?>
