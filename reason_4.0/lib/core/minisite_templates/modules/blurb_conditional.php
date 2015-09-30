<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class & register module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/blurb.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'BlurbConditionalModule';
	/**
	 * BlurbConditional Module
	 * 3/10/2010
	 *
	 * Provides a mechanism to conditionally show blurbs based on parameters defined in page_types.php - this is basically a copy of content_conditional.php
	 *
	 * The parameters used by the module include
	 *
	 * - default - optional - 'show_content' or 'hide_content' - if the comparison is true, the content visibility will be the opposite of the default - defaults to 'show_content'
	 * - parameter - required - name of the user input to look for when comparing to comparison
	 * - cleanup_function - required - function to apply to parameter ... can be a reason cleanup_function or perhaps boolean functions like is_numeric()
	 * - extra_args - optional - extra arguments to pass to cleanup_function - defaults to empty
	 * - conditional - required - comparison operator must be one of the following: <, <=, >, >=, ==, !=
	 * - comparison - comparison value - currently if set to zero acceptable parameter handling converts it to empty, which is the default
	 *
	 * NOTE: Due to the way used blurbs are counted in the blurbs module a blurb that is not shown because of this module will still be considered
	 *       a "used" blurb by other blurb modules on the page. This may or may not be desirable but that is how it behaves right now.
	 *
	 * @todo just merge with blurb??
	 * @author Nathan White
	 */
	class BlurbConditionalModule extends BlurbModule
	{
		/**
		 * Defines acceptable parameters passed by the page type definition file
		 * @var array
		 */	
		var $conditional_params = array(
			'default' => 'show_content',
			'parameter' => '',
			'cleanup_function' => '',
			'extra_args' => '',
			'conditional' => '',
			'comparison' => '',
		);
		
		/**
		 * The cleaned $this->request[$this->params['parameter'] variable - only populated if the module is passed all needed parameters
		 * @var string
		 */	
		var $param;
		
		function init( $args = array() )
		{
			parent::init();
			$valid = true;
			if (empty($this->params['default']) || check_against_array($this->params['default'], array('show_content', 'hide_content')) == false)
			{
				trigger_error('Blurb conditional module called with invalid default setting - content will be displayed as if the regular content module was used');
				$this->params['default'] = 'show_content';
				$valid = false;
			}
			if (empty($this->params['parameter'])) 
			{
				trigger_error('Blurb conditional module called without "parameter" defined - the default setting ' . $this->params['default'] . ' will be used and the parameter ignored');
				$valid = false;
			}
			if (empty($this->params['conditional']) || check_against_array($this->params['conditional'], array('<', '<=', '>', '>=', '==', '!=')) == false)
			{
				trigger_error('Blurb conditional module called with invalid conditional - the default setting ' . $this->params['default'] . ' will be used and the parameter ignored');
				$valid = false;
			}
			if (empty($this->params['cleanup_function']))
			{
				trigger_error('Blurb conditional module was not passed a cleanup_function - the default setting ' . $this->params['default'] . ' will be used and the parameter ignored');
				$valid = false;
			}
			if (($valid) && isset($this->request[$this->params['parameter']])) $this->param = $this->request[$this->params['parameter']];
		}
		
		/**
		 * Lets add our conditional_params to the blurb module acceptable_params
		 */
		function handle_params( $params )
		{
			$this->acceptable_params += $this->conditional_params;
			parent::handle_params( $params );
		}
		
        function get_cleanup_rules()
        {
			$cr = parent::get_cleanup_rules();
			if (empty($this->params['parameter']))
			{
				return $cr;
			}
			if (!empty($this->params['extra_args']) && !empty($this->params['cleanup_function']) && is_array($this->params['extra_args']))
            {
            	$cr[$this->params['parameter']] = array( 'function' => $this->params['cleanup_function'], 'extra_args' => $this->params['extra_args']);
            }
            elseif (!empty($this->params['cleanup_function']))
            {
            	$cr[$this->params['parameter']] = array( 'function' => $this->params['cleanup_function'] );
            }
            return $cr;
        }

		function has_content()
		{
			if (isset($this->param))
			{
				$condition_test = $this->check_conditional($this->params['conditional'], $this->param, $this->params['comparison']);
				if (($this->params['default'] == 'show_content') && (!empty($this->blurbs)) && !$condition_test)  return parent::has_content();
				elseif (($this->params['default'] == 'hide_content') && (!empty($this->blurbs)) && $condition_test)  return parent::has_content();
			}
			else
			{
				if ($this->params['default'] == 'show_content') return parent::has_content();
			}
			return false;
		}

		function check_conditional($conditional, $request, $comparison)
		{
			switch ($conditional) {
				case '<': return ($request < $comparison);
				case '<=': return ($request <= $comparison);
				case '>': return ($request > $comparison);
				case '>=': return ($request >= $comparison);
				case '==': return ($request == $comparison);
				case '!=': return ($request != $comparison);
				default: return false;
			}
		}
	}
?>
