<?php
/**
 * A class for managing groups of modules
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
 
 /**
  * A class for managing groups of modules
  * 
  * This class allows Reason to build and understand collections of modules.
  * 
  * This class is intended to be used as a singleton; call reason_get_module_sets() to get the
  * single module_sets object.
  *
  * Example usage:
  *
  * $ms =& reason_get_module_sets();
  * $ms->add('foo_module','foo_set'); // Adds a module named foo_module to the foo_set
  * $ms->add(array('bar_module_1','bar_module_2'),'bar_set'); // Adds two modules to the bar_set
  * $ms->add('baz_module',array('foo_set','bar_set')); // Adds the baz_module to the foo_set and the bar_set
  * $ms->add(array('foo_module','bar_module'),array('foo_set','baz_set')); // Adds two modules to two sets
  * print_r($ms->get()); // Array of all sets and their member modules
  * print_r($ms->get('bar_set')); // Array of the members of the bar set
  */
class module_sets
{
	/**
	 * Internal storage for sets
	 * @var private
	 */
	var $_sets = array();
	
	/**
	 * Constructor
	 *
	 * Should only be called once, by the factory function reason_get_module_sets().
	 *
	 * @access public
	 */
	function module_sets($setup)
	{
		if($setup != 'This setup string should only be in the factory function')
		{
			trigger_error('module_sets is a singleton class; please use the reason_get_module_sets() function to get a reference');
		}
		
	}
	/**
	 * Add named modules to named sets
	 *
	 * All given modules will be assigned to all given sets.
	 *
	 * @access public
	 * @param mixed $modules A string module identifier or an array of string module identifiers
	 * @param mixed $sets A string set name or an array of string set names
	 * @return boolean success
	 */
	function add($modules, $sets)
	{
		if(empty($sets))
		{
			trigger_error('Unable to add modules (no set given)');
			return false;
		}
		if(empty($modules))
		{
			trigger_error('Unable to add modules (no module(s) given)');
			return false;
		}
		if(!is_array($modules))
		{
			$modules = array($modules);
		}
		if(!is_array($sets))
		{
			$sets = array($sets);
		}
		foreach($sets as $set)
		{
			if(!isset($this->_sets[$set]))
				$this->_sets[$set] = array();
			$this->_sets[$set] = array_unique(array_merge($this->_sets[$set],$modules));
		}
		return true;
	}
	/**
	 * Get module set information
	 *
	 * A request for an undefined set does not trigger an error; instead it
	 * simply returns an empty array.
	 *
	 * @access public
	 * @param mixed $set A string for the set to return; null will return all sets
	 * @return array
	 */
	function get($set = NULL)
	{
		if($set)
		{
			if(isset($this->_sets[$set]))
				return $this->_sets[$set];
			else
				return array();
		}
		return $this->_sets;
	}
}
/**
 * Get the singleton module sets object
 *
 * The first time it is run, this function will get the object 
 * and set it up using minisite_templates/module_sets.php
 *
 * Subsequent times it is run it will just return references to 
 * that single instance.
 *
 * @return object
 */
function &reason_get_module_sets()
{
	static $ms;
	if(empty($ms))
	{
		$ms = new module_sets('This setup string should only be in the factory function');
		if(reason_file_exists('config/module_sets/setup.php'))
			reason_include_once('config/module_sets/setup.php');
	}
	return $ms;
}

?>