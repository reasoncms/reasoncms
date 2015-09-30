<?php
/**
 * This script contains a class that updates the rewrite rules for the current site
 * @package reason
 * @subpackage finish_actions
 */
 
/**
 * Register the finish action with Reason & include dependencies
 */
$GLOBALS['_finish_action_classes'][ basename( __FILE__) ] = 'updateRewrites';

reason_include_once('finish_actions/default.php');
reason_include_once('classes/url_manager.php');

/**
 * Update rewrites finish action class
 * Intantiates a url amanager and runs the rewrite rules for the current site
 * @author Matt Ryan
 * @date 2006-05-26
 */
class updateRewrites extends defaultFinishAction
{
	/**
	 * run the rewrite rules
	 */
	function run()
	{
		$urlm = new url_manager($this->vars['site_id']);
		$urlm->update_rewrites();
	}
}

?>
