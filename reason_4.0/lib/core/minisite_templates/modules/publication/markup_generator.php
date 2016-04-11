<?php
/**
 * The abstract class for the markup generators for the Publication minisite module. 
 *
 * @package reason
 * @subpackage minisite_modules
 * @author Meg Gibbs
 */

/**
 *  The abstract class for the markup generators for the Publication minisite module. 
 */
class PublicationMarkupGenerator 
{

/////////
//  VARIABLES
////////

	/**
	* The HTML that will be returned to the module.  
	* @var string
	* @access private
	*/
	var $markup_string = '';
	
	/**
	*  The names of the variables that the markup generator needs from the module.  
	*  In general, this array should be accessed and modified through the get_variables_needed() function.  
	*  The names of these variables need to match whatever mechanism is used in the module to pass the variables;
	*  in the current structure, that means that they need to either match the names of class variables in the
	*  module, or match keys in the $variables_to_pass array that map to a function that generates the
	*  appropriate value.
	*  @var array
	*  @access private
	*/			
	var $variables_needed = array();	

	/**
	*  The variables that have been passed from the module.  
	*  The format is expected to be $var_name => $var_value, but this array will be set to whatever array is 
	*  passed using the {@link set_passed_variables()} method.
	*  @var array
	*  @access private
	*/	
	var $passed_vars = array();  		


	/** 
	 * does this markup generator have custom [[IMG]] style embed support in content? By default, no - this gets us
	 * decent behavior on legacy sites that borrow in the new embeds created for Carleton's 2015 news site redesign.
	 */
	protected $has_custom_embed_handling = false;

////////
///  METHODS
////////

	/**
	* Returns an array of the names of the variables we need from the publication module.
	* May be extended or overloaded by children to modify variable names.
	* @access public
	* @return array
	*/
	function get_variables_needed()
	{
		return $this->variables_needed;
	}

	/**
	* Sets the {@link passed_vars} array to whatever array is passed to it.  
	* Expects to receive an array in the format $var_name => $var_value.
	* @access public
	* @param array $variable_array
	*/
	function set_passed_variables($variable_array)
	{
		$this->passed_vars = $this->passed_vars + $variable_array;
	}

	/**
	* Checks that all necessary variables have been passed and performs any other necessary init actions.
	* Calls on {@link run_variable_checks()}, {@link additional_init_actions()}.
	* @access private
	*/
	function init()
	{
		$this->run_variable_checks();
		$this->additional_init_actions();
	}

	/**
	* Hook for any init actions that may be necessary in child classes.
	* @access protected
	*/
	function additional_init_actions()
	{
	}

	/**
	*  Checks to make sure that each variable requested in the $variables_needed array has been set in 
	*  the {@link passed_vars} array.  
	*  Triggers a warning if a variable has not been set.
	*  @access protected
	*/
	function run_variable_checks()
	{
		if(empty($this->passed_vars) && !empty($variables_needed))
		{
			trigger_error('No variables have been passed from the publication module', WARNING);
		}
		else
		{
			$variables_needed = $this->get_variables_needed();
			foreach($variables_needed as $var_name)
			{
				if(!isset($this->passed_vars[$var_name]))
					trigger_error('Variable '.$var_name.' has not been passed from the publication module', WARNING);
			}
		}
	}
	/**
	 * If a markup generator needs to add head items it can interact with the head items object here.
	 * @param object $head_items
	 * @return void
	 */
	function add_head_items($head_items)
	{
	}

	/**
	*  Hook for the code that actually generates the markup.  
	*  Whatever code you use to accomplish that, the {@link markup_string} should be exactly as you would like it to 
	*  be passed to the module at the end of this function. 
	*  @access protected
	*/
	function run()
	{
		trigger_error('This method must be overloaded', WARNING);
	}

	/**
	*  The function that returns the HTML markup to the module.  
	*  After initializing and passing the appropriate variables to the markup generator, this should be the only 
	*  function that the module needs to call.  Not intended to be overloaded or extended; changes should be made 
	*  in {@link additional_init_actions} or in {@link run()}.
	*  @access public
	*  @return string $markup_string The complete HTML markup
	*/
	function get_markup()
	{
		$this->init();		
		$this->run();
		return $this->markup_string;
	}

	final function has_custom_embed_handling() {
		return $this->has_custom_embed_handling;
	}

}
?>
