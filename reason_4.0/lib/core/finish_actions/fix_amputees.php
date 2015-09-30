<?php
/**
 * This script contains a class that fixes amputees for the current type.
 * @package reason
 * @subpackage finish_actions
 */
 
/**
 * Register the finish action with Reason & include dependencies
 */
$GLOBALS['_finish_action_classes'][ basename( __FILE__) ] = 'fixAmputees';
reason_include_once('finish_actions/default.php');
reason_include_once('classes/url_manager.php');
reason_include_once('classes/amputee_fixer.php');

/**
 * Fix Amputees finish action class
 * Instantiates an amputee fixer and fixes amputees for the current type.
 * @author Nathan White
 * @date August 20, 2008
 */
class fixAmputees extends defaultFinishAction
{
	/**
	 * fix the amputees
	 */
	function run()
	{
		$fixer = new AmputeeFixer();
		$fixer->fix_amputees($this->vars['id']);
	}
}
?>
