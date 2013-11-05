<?php
/**
 * @package reason
 * @subpackage classes
 */
/**
 * A tool for wrapping up a set of functions into a single package
 *
 * Use:
 *
 * $bundle = new functionBundle();
 *
 * $blundle->set_function('do_something','phpinfo');
 * $bundle->set_function('method', array($object, 'method_name'));
 * $bundle->set_function('static_method', array('class_name', 'static_method_name'));
 * 
 * $bundle->do_something(); // calls phpinfo();
 * $bundle->method('argument'); // calls $object->method_name('argument');
 * $bundle->static_method('argument1', 'argument2'); // calls classname::static_method_name('argument1', 'argument2');
 *
 * The bundle will raise an error if a method is called that does not exist. If you want
 * your code to handle the possibility that a function may not be set on the bundle,
 * use this pattern:
 *
 * if($bundle->has_function('functionname'))
 * 		$bundle->functionname();
 * 
 */
class functionBundle
{
	/**
	 * An array of functions that have been set
	 *
	 * Keys: function names
	 *
	 * Values: php callbacks
	 *
	 * @var array
	 */
	protected $functions = array();
	
	/**
	 * Add a function to the object
	 *
	 * @param string $name The function name
	 * @param callback $function The function to be called
	 * @return success
	 */
	function set_function($name, $function)
	{
		$this->functions[$name] = $function;
		return true;
	}
	
	/**
	 * Magic method to call set functions
	 *
	 * @param string $name The function name
	 * @param array $arguments
	 * @return mixed function return value or NULL if function not found
	 */
	function __call( $name, $arguments )
	{
		if($function = $this->get_function($name))
			return call_user_func_array($function, $arguments);
		
		$trace = debug_backtrace(false);
		$error = 'Function '.$name.' not set on function bundle.';
		if(isset($trace[1]))
			$error .= ' Called in '.$trace[1]['file'].' at line '.$trace[1]['line'];
		trigger_error($error);
		return null;
	}
	
	/**
	 * Get the callback function with a given name
	 *
	 * @param string $name The function name
	 * @return mixed the callback or NULL if function not found
	 */
	function get_function($name)
	{
		if(isset($this->functions[$name]))
			return $this->functions[$name];
		return null;
	}
	
	/**
	 * Does this bundle have a function with a given name?
	 *
	 * @param string $name The function name
	 * @return boolean
	 */
	function has_function($name)
	{
		return isset($this->functions[$name]);
	}
	
	/**
	 * Get the full array of functions set on the class
	 *
	 * @return array
	 */
	function get_functions()
	{
		return $this->functions;
	}
}