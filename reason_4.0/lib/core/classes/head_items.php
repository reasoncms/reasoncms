<?php
/**
 * Class for managing head items
 * @package reason
 * @subpackage classes
 */

/**
 * Inputs and outputs head items. Can combine and cache javascript, css, and less files based on filemtime.
 * 
 * Methods:
 *
 * - add head items of various types
 * - selectively remove head items whether they exist or not when method is called
 * - output html markup of head items
 *
 * Notes:
 *
 * Sample usage
 *
 * <code>
 *	$head_item = new HeadItems();
 *	$head_item->add_stylesheet('mycss.css');
 *	$head_html = $head_item->get_head_items_html();
 * </code>
 *
 * @author Nathan White and the author(s) of functions that I lifted from the default template
 */
class HeadItems
{
	/**
	 * @var array
	 * @access private
	 */
	var $_head_items = array();

	/**
	 * @var array
	 * @access private
	 */
	var $_top_head_items = array();
	
	/**
	 * @var array
	 * @access private
	 */
	var $_to_remove = array();
	
	/**
	 * What elements does the class recognize?
	 * @var array
	 */
	var $allowable_elements = array('base','link','meta','script','style','title');
	
	/**
	 * What elements are self-closing?
	 * @var array
	 */
	var $elements_that_may_have_content = array('script','style','title');
	
	/**
	 * What elements are never self-closing?
	 * @var array
	 */
	var	$elements_that_may_not_self_close = array('script','title');
	
	/**
	 * Less variables that should be added to every less stylesheet
	 *
	 * Note that values must be in the proper format for lessphp; see here:
	 * http://leafo.net/lessphp/docs/#setting_variables_from_php
	 *
	 * @var array key->value pairs
	 */
	protected $default_less_variables = array();
	
	/**
	 * Less functions that should be added to every less stylesheet
	 * @var array PHP callable functions
	 */
	protected $default_less_functions = array();
	
	/**
	 * Should old css files created by less be deleted?
	 *
	 * Change this to false if you are not able to redirect users to the most recent one where needed,
	 * since page caching can cause Reason to serve up out-of-date URLs.
	 *
	 * @var boolean
	 */
	protected $delete_old_less_css = true;
	
	function HeadItems()
	{
	}
	
	/**
	 * Adds a head item to the internal head items array
	 * @param string $element name of element to add (i.e. link or script)
	 * @param array $attributes element attributes
	 * @param string $content content to appear between element open and close tags
	 * @param boolean $add_to_top if true, places element at start of array rather than end
	 * 
	 */
	function add_head_item($element, $attributes, $content = '', $add_to_top = false, $wrapper = array('before'=>'','after'=>''))
	{
		$element = strtolower($element);
		if(in_array($element, $this->allowable_elements))
		{
			if (!empty($content) && (!in_array($element, $this->elements_that_may_have_content)))
			{
				trigger_error('The head item element ' . $element . ' had its content (' . $content . ') removed because it is not in the array of elements that may have content');
				$content = '';
			}
			$item = array('element'=>$element,'attributes'=>$attributes,'content'=>$content,'wrapper'=>$wrapper);
			if($add_to_top)
			{
				array_unshift($this->_head_items, $item);
				array_unshift($this->_top_head_items, $item); 
			}
			else
			{
				$this->_head_items[] = $item;
			}
		}
		else trigger_error('The head item element ' . $element . ' was not added because it is not in the allowable elements array');
	}
	
	/**
	 * Quick interface to add_head_item for adding stylesheets
	 * @param string $url
	 * @param string $media optional media attribute
	 * @param boolean $add_to_top
	 * @param array $wrapper 'before' and 'after' keys with values to prepend and append
	 */
	function add_stylesheet( $url, $media = '', $add_to_top = false, $wrapper = array('before'=>'','after'=>'') )
	{
		if(substr($url, 0, 1) == '/' && substr($url, -5) == '.less')
		{
			return $this->add_less_stylesheet( $url, $media, $add_to_top, $wrapper, $this->default_less_variables, $this->default_less_functions );
		}
		$attrs = array('rel'=>'stylesheet','type'=>'text/css','href'=>$url);
		if(!empty($media))
		{
			$attrs['media'] = $media;
		}
		$this->add_head_item('link', $attrs, '', $add_to_top, $wrapper);
	}
	
	function add_default_less_variable($key,$value)
	{
		$this->default_less_variables[$key] = $value;
	}
	function remove_default_less_variable($key)
	{
		if(isset($this->default_less_variables[$key]))
			unset($this->default_less_variables[$key]);
	}
	function set_default_less_variables($array)
	{
		$this->default_less_variables = $array;
	}
	function add_default_less_function($less_function_name, $php_function)
	{
		$this->default_less_functions[$less_function_name] = $php_function;
	}
	function remove_default_less_function($less_function_name)
	{
		if(isset($this->default_less_functions[$less_function_name]))
			unset($this->default_less_functions[$less_function_name]);
	}
	function set_default_less_functionss($array)
	{
		$this->default_less_functions = $array;
	}
	
	/**
	 * Add a less-based stylesheet
	 * @param string $url
	 * @param string $media optional media attribute
	 * @param boolean $add_to_top
	 * @param array $wrapper
	 * @param array $less_variables
	 * @param array $less_functions
	 * @return boolean success
	 */
	function add_less_stylesheet( $url, $media = '', $add_to_top = false, $wrapper = array('before'=>'','after'=>''), $less_variables = array(), $less_functions = array() )
	{
		if(substr($url, 0, 1) != '/')
		{
			trigger_error('Less stylesheets must be specified relative to server root (i.e. starting with "/"). Path given ('.$url.') does not conform to this specification. Stylesheet not added.');
			return false;
		}
		
		if(!include_once(LESSPHP_INC.'lessc.inc.php'))
		{
			trigger_error('Unable to process .less file -- LESSPHP not configured correctly on your server. Check the LESSPHP_INC setting in settings/package_settings.php.');
			return false;
		}
		
		$input_path = WEB_PATH.substr($url, 1);
		if(!file_exists($input_path))
		{
			trigger_error('Less stylesheet not found at "'.$input_path.'". Stylesheet not added.');
			return false;
		}
		$hash = md5($input_path.serialize($less_variables).serialize(array_keys($less_functions)));
		$first2 = substr($hash, 0, 2);
		$output_filename = $hash.'_'.filemtime($input_path).'.css';
		
		$output_url = WEB_TEMP.'less_compiled/'.$first2.'/'.$output_filename;
		
		$base_output_directory = WEB_PATH.substr(WEB_TEMP, 1).'less_compiled/';
		if(!file_exists($base_output_directory))
			mkdir($base_output_directory);
		$output_directory = $base_output_directory.$first2.'/';
		if(!file_exists($output_directory))
			mkdir($output_directory);
		$output_path = $output_directory.$output_filename;
		$cache_path = $output_directory.$hash.'.cache';
		
		$less = new lessc;
		$less->setFormatter('compressed');
		$less->setVariables($less_variables);
		foreach($less_functions as $less_function_name => $php_function)
		{
			$less->registerFunction($less_function_name, $php_function);
		}
		try
		{
			if($this->less_cached_compile($input_path, $output_path, $less, $cache_path))
			{
				$compiled = true; // something has changed
				if ($this->delete_old_less_css && ( $handle = opendir($output_directory) ) )
				{
					while (false !== ($entry = readdir($handle)))
					{
        				if($entry != $output_filename && strpos($entry, $hash.'_') === 0 && substr($entry, -4) == '.css')
        				{
        					unlink($output_directory.$entry);
        				}
    				}
				}
				else
				{
					trigger_error('Unable to delete old less css files');
				}
			}
			else
			{
				$compiled = false; // nothing has changed
			}
		}
		catch (Exception $ex)
		{
			trigger_error( 'lessphp unable to compile less at "'.$input_path.'". Stylesheet not added. Message: '.$ex->getMessage() );
			return;
		}
		
		return $this->add_stylesheet( $output_url, $media, $add_to_top, $wrapper );
		
		return true;
	}
	/**
	 * Compile less using a cache for included files
	 * @return boolean true if newly compiled, false if cached version unchanged
	 */
	function less_cached_compile($input_path, $output_path, $less, $cache_path)
	{
		if (file_exists($cache_path))
			$cache = unserialize(file_get_contents($cache_path));
		else
			$cache = $input_path;

		$new_cache = $less->cachedCompile($cache);

		if (!is_array($cache) || $new_cache["updated"] > $cache["updated"])
		{
			file_put_contents($cache_path, serialize($new_cache));
			file_put_contents($output_path, $new_cache['compiled']);
			return true;
		}
		return false;
	}

	/**
	 * Quick interface to add_head_item for adding javascript
	 * @param string $url
	 * @param boolean $add_to_top
	 */	
	function add_javascript( $url, $add_to_top = false, $wrapper = array('before'=>'','after'=>'') )
	{
		$attrs = array('type' => 'text/javascript', 'src' => $url);
		$this->add_head_item('script', $attrs, '', $add_to_top, $wrapper);
	}
	
	/**
	 * Selectively removes head items by element type and attribute(s)
	 * @param string $element type of head item to remove
	 * @param array $attribute_limiter optional array of key / value pairs which must correspond to the attributes of an item to be deleted
	 * @return void
	 * @access private
	 * @author Nathan White
	 */
	function _remove_head_item($element, $attribute_limiter = false)
	{
		$head_items =& $this->_head_items;
		foreach ($head_items as $k=>$item)
		{
			if (strtolower($element) === $item['element'])
			{
				$diff_array = is_array($attribute_limiter) ? array_diff_assoc($attribute_limiter, $item['attributes']) : array();
				{
					if (empty($diff_array))
					{
						unset ($head_items[$k]);
						if (isset($this->_top_head_items[$k])) unset ($this->_top_head_items[$k]);
					}
				}
			}
		}
	}
	
	/**
	 * Remove head items by element type and attribute(s) just before head is displayed
	 * @param string $element type of head item to remove
	 * @param array $attribute_limiter optional array of key / value pairs which must correspond to the attributes of an item to be deleted
	 * @return void
	 * @access public
	 * @author Nathan White
	 */	
	function remove_head_item($element, $attribute_limiter = false)
	{
		$this->_to_remove[] = array('e' => $element, 'a_l' => $attribute_limiter);
	}
	
	/**
	 * @access private
	 */
	function _remove_head_items_at_end()
	{
		if (!empty($this->_to_remove))
		{
			foreach ($this->_to_remove as $v)
			{
				$this->_remove_head_item($v['e'], $v['a_l']);
			}
		}
	}
	
	/**
	 * Returns head items array
	 * @return array head items
	 */
	function &get_head_item_array()
	{
		return $this->_head_items;
	}
	
	/**
	 * Returns html for head items
	 * @return string html of head items
	 */
	function get_head_item_markup()
	{
		if (empty($this->_head_items)) return '';
		$this->_remove_head_items_at_end();
		$allowable_elements =& $this->allowable_elements;
		$elements_that_may_have_content =& $this->elements_that_may_have_content;
		$elements_that_may_not_self_close =& $this->elements_that_may_not_self_close;
		$html_items = array();
		foreach($this->_head_items as $item)
		{
			$html_item = '';
			if(!empty($item['wrapper']['before']))
				$html_item .= $item['wrapper']['before'];
			$html_item .= '<'.$item['element'];
			foreach($item['attributes'] as $attr_key=>$attr_val)
			{
				$html_item .= ' '.$this->_htmlspecialchars_unknown_source($attr_key).'="'.$this->_htmlspecialchars_unknown_source($attr_val).'"';
			}
			if(!empty($item['content']) )
			{
				$html_item .= '>'.$item['content'].'</'.$item['element'].'>';
			}
			elseif(in_array($item['element'],$elements_that_may_not_self_close))
			{
				$html_item .= '></'.$item['element'].'>';
			}
			else
			{
				$html_item .= ' />';
			}
			if(!empty($item['wrapper']['after']))
				$html_item .= $item['wrapper']['after'];
			$html_items[] = $html_item;
		}
		$this->handle_duplicates($html_items);
		return implode("\n",$html_items)."\n";
	}
	
	/**
	 * A clone of reason_htmlspecialchars for use outside Reason
	 * @param string $string
	 * @return string
	 */
	function _htmlspecialchars_unknown_source($string)
	{
		$string = str_replace(array('&amp;','&gt;','&lt;','&quot;','&#039;'),array('&','>','<','"',"'"),$string);
		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8' );
	}
	
	/**
	 * Modifies the html items array to remove exact duplicates - "add to top" items remain at the top when duplicates are found,
	 * while the last instance of regular items is preserved. This is important because while javascript files such as jquery
	 * may need to be at the top of the head items, when CSS duplicates are found the last instance will override rules in previous files.
	 * @param array html_items
	 * @return void
	 * @author Nathan White
	 */
	function handle_duplicates(&$html_items)
	{
		$top_head_items_count = count($this->_top_head_items);
		if ($top_head_items_count > 0)
		{
			$top_html_items = array_unique(array_slice($html_items, 0, $top_head_items_count));
			$non_top_html_items = array_diff($html_items, $top_html_items);
			$html_items = array_merge($top_html_items, $non_top_html_items);
		}
		$html_items = array_reverse(array_unique(array_reverse($html_items))); // removes duplicates - leaving only last instance of a string
	}
}
