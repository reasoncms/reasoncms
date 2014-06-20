<?php
/**
 * Checks to see if the media work's categories have changed, and do any updating if necessary.
 * @package reason
 * @subpackage finish_actions
 */
 
/**
 * Register the finish action with Reason & include dependencies
 */
$GLOBALS['_finish_action_classes'][ basename( __FILE__) ] = 'updateMediaWorkOnFinish';

reason_include_once('finish_actions/default.php');
reason_include_once('classes/url_manager.php');
reason_include_once('classes/media/factory.php');	

class updateMediaWorkOnFinish extends defaultFinishAction
{
	/**
	 * run the rewrite rules
	 */
	function run()
	{
		$user = new entity($this->vars['user_id']);
		$media_work = new entity($this->vars['id']);
		
		$shim = MediaWorkFactory::shim($media_work);
		if ($shim)
		{
			$shim->finish_actions($media_work, $user);
		}
	}
}

?>
