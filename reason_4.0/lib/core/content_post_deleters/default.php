<?php
/**
 * @package reason
 * @subpackage content_post_deleters
 */

/**
 * Register the post deleter with Reason
 */
$GLOBALS['_content_post_deleter_classes'][ basename( __FILE__) ] = 'defaultPostDeleter';

/**
 * Default content post deleter class
 *
 * Any class that uses a content post deleter should extend this class. The run method of a content post deleter
 * is executed after the state of an entity is changed from live to deleted.
 *
 * @author Nathan White
 * @date 2006-08-17
 */
class defaultPostDeleter
{
	/**
	 * @var array $vars contains all the variables passed by deleteDisco; contains the following elements: site_id, type_id, id, user_id
	 */
	var $vars = array();
	/**
	 * @var object $deleted_entity the entity with state just changed from live to deleted deleted
	 */
	var $deleted_entity;

	/**
	 * Initalize the content post deleter
	 * @param array $vars for setting the general environment of the class(site_id, type_id, id, user_id)
	 * @param object $deleted_entity for providing access to the just deleted entity
	 */
	function init($vars, $deleted_entity)
	{
		$this->vars = $vars;
		$this->deleted_entity = $deleted_entity;
	}
	/**
	 * Run the content post deleter
	 * This function is meant to be overloaded; it does nothing by default
	 */
	function run()
	{
	}
}

?>
