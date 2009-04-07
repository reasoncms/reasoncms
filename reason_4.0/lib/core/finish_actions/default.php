<?php
/**
 * This file contains the class defaultFinishAction
 * @package reason
 * @subpackage finish_actions
 */
 
/**
 * Register the finish action with Reason
 */
$GLOBALS['_finish_action_classes'][ basename( __FILE__) ] = 'defaultFinishAction';

/**
 * Default finish action class
 * This class is meant to be extended; it merely contains the machinery for initializing the object
 * @author Matt Ryan
 * @date 2006-05-26
 */
class defaultFinishAction
{
	/**
	 * @var array $vars contains all the variables passed to the finish action by the finish module; contains the following elements: site_id, type_id, id, user_id
	 */
	var $vars = array();
	/**
	 * Initalize the finish action object
	 * @param array $vars for setting the general environment of the class(site_id, type_id, id, user_id)
	 */
	function init($vars)
	{
		$this->vars = $vars;
	}
	/**
	 * Run the finish action object
	 * This function is meant to be overloaded; it does nothing by default
	 */
	function run()
	{
	}
}

?>
