<?php
	/**
 	 * @package reason
 	 * @subpackage minisite_modules
	 */
	
	 /**
 	  * Include base class
  	  */
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	/**
	 * Register the class so the template can instantiate it
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'HeadItemsIncludeModule';
	
	/**
	 * Head Items Include Module
	 * 
	 * Module that adds head items to the top of the page based on parameters in the page type.
	 *
	 * Noe that this module does not output anything into the body of the page, so place it in a
	 * page location where you don't want any content.
	 *
	 * example as used in in page type:
	 *
	 * <code>
	 * 'page_location' => array(
	 *		'module' => 'head_items_include',
	 *		'head_items' => array(
	 *			array('meta',array('name'=>'foo','value'=>'bar',),),
	 *			array('meta',array('name'=>'baz','value'=>'woo',),),
	 *		),
	 *		'css' => array(
	 *			array('/path/to/css.css'),
	 *			array('/path/to/css2.css'),
	 *		),
	 *		'js' => array(
	 *			array('/path/to/js.js'),
	 *			array('/path/to/js2.js'),
	 *		),
	 *	),
	 * </code>
	 */
	class HeadItemsIncludeModule extends DefaultMinisiteModule
	{
		/**
		 * Acceptable parameters
		 *
		 * 'head_items' takes an array of head items, each of which is itself an array in this form:
		 * array( (str) $element,(array) $attributes,(str) $content,(bool) $add_to_top)
		 *
		 * 'css' takes an array of css stylesheets, each of which is itself an array in this form:
		 * array((str) $url, (str) $media, (bool) $add_to_top)
		 *
		 * 'js' takes an array of javascript items, each of which is itself an array in this form:
		 * array((str) $url, (bool) $add_to_top)
		 *
		 * In each of these parameters, you can leave out any of the array elements except the 
		 * first one. Note that the array items are passed to the appropriate function via call_user_func_array().
		 */
		var $acceptable_params = array(
			'head_items' => array(),
			'css' => array(),
			'js' => array(),
		);
		
		function init( $args=array() ) // {{{
		{
			if($head_items =& $this->get_head_items())
			{
				foreach($this->params['head_items'] as $item)
 				{
 					$callback = array(&$head_items,'add_head_item');
 					call_user_func_array($callback, $item );
 				}
 				
 				foreach($this->params['css'] as $item)
 				{
 					$callback = array(&$head_items,'add_stylesheet');
 					call_user_func_array($callback, $item );
 				}
 				
 				foreach($this->params['js'] as $item)
 				{
 					$callback = array(&$head_items,'add_javascript');
 					call_user_func_array($callback, $item );
 				}
 			}
		}
		
		/*
		 This is an aborted attempt to support simpler paramater syntaxes, but it just got really complex.
		 
		 1. an array of arrays (the most verbose and powerful option); 
		    each sub-array is a set of parameters for the given method
		 2. an array of item URLs (only available for css & js)
		 3. an array of parameters for a single head item
		 4. a single string (only available for css & js)
		 
		function add_head_items_autodetect($method,$param)
		{
			if(empty($param))
				return;
			
			if($head_items =& $this->get_head_items())
			{
				reset($param);
				
				if(is_array($param))
				{
					if(count($param) == 1)
					{
						call_user_func_array(array($head_items,$method), current($param));
					}
					if(is_array(current($param))) // Possibility #1
					{
						foreach($param as $item)
						{
							$this->add_head_items_autodetect($method,$item);
							//call_user_func_array(array($head_items,$method), $item );
						}
					}
					elseif(
					 ( isset($param[1]) && $method == 'add_head_item' ) )
					 ||
					 ( isset($param[1]) && $method == 'add_stylesheet' && $this->_is_a_css_media_type($param[1])) )
					 ||
					 ( isset($param[1]) && $method == 'add_javascript' && is_boolean($param[1]))
					  // Possibility #2
					{
						call_user_func_array(array($head_items,$method), $param);
					}
				}
				else // Possibility #4
				{
					$head_items->$method($param);
				}
			}
		}
		
		function _is_a_css_media_type($str)
		{
			//return (boolean) preg_match('/^screen$/i',$str);
			
			return (boolean) preg_match('/^(?'.'>all|braille|embossed|handheld|print|projection|screen|tty|tv)$/i',$str);
		}
		*/

		function has_content() // {{{
		{
			 return false;
		} // }}}
		function run() // {{{
		{
			 
		} // }}}
	}
?>
