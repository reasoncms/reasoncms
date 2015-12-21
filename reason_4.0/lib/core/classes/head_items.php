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
	
	/**
	 * Should less output have an absolute URL (e.g. including the domain name)?
	 *
	 * @var boolean
	 */
	protected $use_absolute_url_for_less_output = false;
	

	/**
	 *
	 * @var array key->value pairs
	 */
	protected $default_scss_variables = array();

	/**
	 * Scss functions that should be added to every less stylesheet
	 * @var array PHP callable functions
	 */
	protected $default_scss_functions = array();

	/**
	 * Should old css files created by less be deleted?
	 *
	 * Change this to false if you are not able to redirect users to the most recent one where needed,
	 * since page caching can cause Reason to serve up out-of-date URLs.
	 *
	 * @var boolean
	 */
	protected $delete_old_scss_css = true;

	private $markup_fetched_num_times;
	
	function HeadItems()
	{
		$this->markup_fetched_num_times = 0;
	}

	public function get_num_times_markup_has_been_fetched() {
		return $this->markup_fetched_num_times;
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
		if($url[0] === '/' && substr($url, -5) === '.less')
		{
			return $this->add_less_stylesheet( $url, $media, $add_to_top, $wrapper );
		}
		if ($url[0] === '/' && substr($url, -5) === '.scss')
		{
			return $this->add_scss_stylesheet( $url, $media, $add_to_top, $wrapper );
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
	function use_absolute_url_for_less_output($use_absolute_url_for_less_output = true)
	{
		$this->use_absolute_url_for_less_output = $use_absolute_url_for_less_output;
	}

	function add_default_scss_variable($key,$value)
	{
		$this->default_scss_variables[$key] = $value;
	}
	function remove_default_scss_variable($key)
	{
		if(isset($this->default_scss_variables[$key]))
			unset($this->default_scss_variables[$key]);
	}
	function set_default_scss_variables($array)
	{
		$this->default_scss_variables = $array;
	}
	function add_default_scss_function($scss_function_name, $php_function)
	{
		$this->default_scss_functions[$scss_function_name] = $php_function;
	}
	function remove_default_scss_function($scss_function_name)
	{
		if(isset($this->default_scss_functions[$scss_function_name]))
			unset($this->default_scss_functions[$scss_function_name]);
	}
	function set_default_scss_functions($array)
	{
		$this->default_scss_functions = $array;
	}

	/**
	 * Add a less-based stylesheet
	 * @param string $url
	 * @param string $media optional media attribute
	 * @param boolean $add_to_top
	 * @param array $wrapper
	 */
	function add_less_stylesheet( $url, $media = '', $add_to_top = false, $wrapper = array('before'=>'','after'=>'') )
	{
		$parser = $this->get_less_parser($this->default_less_variables, $this->default_less_functions);
		$output_url = $this->create_parsed_stylesheet($parser, $url, $this->default_less_variables, $this->default_less_functions, $this->delete_old_less_css);
		if (!$output_url) return;
		$this->add_stylesheet($output_url, $media, $add_to_top, $wrapper);
	}

	/**
	 * Add a scss-based stylesheet
	 * @param string $url
	 * @param string $media optional media attribute
	 * @param boolean $add_to_top
	 * @param array $wrapper
	 */
	function add_scss_stylesheet( $url, $media = '', $add_to_top = false, $wrapper = array('before'=>'','after'=>'') )
	{
		$parser = $this->get_scss_parser($this->default_scss_variables, $this->default_scss_functions);
		$output_url = $this->create_parsed_stylesheet($parser, $url, $this->default_scss_variables, $this->default_scss_functions, $this->delete_old_scss_css);
		if (!$output_url) return;
		$this->add_stylesheet($output_url, $media, $add_to_top, $wrapper);
	}

	/**
	 *
	 * @param type $path
	 * @return nothing
	 */
	function add_style_import_path( $path ) {
		$this->style_import_paths[] = $path;
	}

	/**
	 *
	 * @param type $parser
	 * @param type $url
	 * @param type $variables
	 * @param type $functions
	 * @param type $delete_cached
	 * @return string|boolean
	 */
	function create_parsed_stylesheet( $parser, $url, $variables, $functions, $delete_cached = true )
	{
		if (!$parser)
		{
			return false;
		}

		if($url[0] !== '/')
		{
			trigger_error('Parsed stylesheets must be specified relative to server root (i.e. starting with "/"). Path given ('.$url.') does not conform to this specification. Stylesheet not added.');
			return false;
		}

		$input_path = WEB_PATH.substr($url, 1);
		$ok = false;
		if(file_exists($input_path))
		{
			$ok = true;
		}
		else
		{
			if(strpos($url,REASON_HTTP_BASE_PATH) === 0)
			{
				// see if it's local
				$local_url = REASON_HTTP_BASE_PATH.'local/'.substr($url, strlen(REASON_HTTP_BASE_PATH));
				$local_input_path = WEB_PATH.substr($local_url, 1);
				echo $local_input_path;
				if(file_exists($local_input_path))
				{
					$input_path = $local_input_path;
					$ok = true;
				}
				
			}
		}

		if(!$ok)
		{
			trigger_error('Stylesheet not found at "'.$input_path.'". Stylesheet not added.');
			return false;
		}

		$hash = md5($input_path.serialize($variables).serialize(array_keys($functions)));
		$first2 = substr($hash, 0, 2);

		$output_filename = $hash.'_'.filemtime($input_path).'.css';
		
		$output_url = WEB_TEMP.'less_compiled/'.$first2.'/'.$output_filename;
		
		if($this->use_absolute_url_for_less_output)
			$output_url = '//' . HTTP_HOST_NAME . $output_url;
		
		$base_output_directory = WEB_PATH.substr(WEB_TEMP, 1).'less_compiled/';
		if(!file_exists($base_output_directory))
			mkdir($base_output_directory);
		$output_directory = $base_output_directory.$first2.'/';
		if(!file_exists($output_directory))
			mkdir($output_directory);
		$output_url = WEB_TEMP.'compiled/'.$first2.'/'.$output_filename;
		$output_directory = WEB_PATH.substr(WEB_TEMP, 1).'compiled/' . $first2 .'/';
		$output_path = $output_directory.$output_filename;
		if (!file_exists($output_directory))
		{
			mkdir($output_directory, 0777, true);
		}

		// Track our modified time so we know to delete older files.
		$mtime = $delete_cached && file_exists($output_path) ? filemtime($output_path) : null;

		try
		{
			if ( get_class($parser) !== 'lessc' )
			{
				$parser->scss->addImportPath(WEB_PATH);
				$parser->scss->addImportPath( pathinfo ( $input_path, PATHINFO_DIRNAME ) );
				foreach ($this->style_import_paths as $path) {
					$parser->scss->addImportPath($path);
				}
				$parser->checkedCachedCompile($input_path, $output_path);
			} else {
				$parser->checkedCachedCompile($input_path, $output_path);
			}		
		}
		catch (Exception $ex)
		{
			trigger_error( 'Unable to compile file at "'.$input_path.'". Stylesheet not added. Message: '.$ex->getMessage() );
			return;
		}

		if ($delete_cached && $mtime !== filemtime($output_path))
		{
			foreach (glob($output_directory . $hash . '_*.css*') as $file) {
				if (strpos($file, $output_filename) === false) {
					unlink($file);
				}
			}
		}

		return $output_url;
	}

	/**
	 *
	 * @param type $variables
	 * @param type $functions
	 * @return \Leafo\ScssPhp\Server|boolean
	 */
	function get_scss_parser($variables, $functions)
	{
		if(!include_once(SCSSPHP_INC.'scss.inc.php'))
		{
			trigger_error('Unable to process .scss file -- SCSSPHP not configured correctly on your server. Check the SCSSPHP_INC setting in settings/package_settings.php.');
			return false;
		}

		$scss = new Leafo\ScssPhp\Compiler();
		$scss->setFormatter('Leafo\ScssPhp\Formatter\Compressed');
		$scss->setVariables($variables);
		foreach ($functions as $name => $func)
		{
			$scss->registerFunction($name, $func);
		}

		return new \Leafo\ScssPhp\Server('.', '.', $scss);
	}

	/**
	 *
	 * @param array $variables
	 * @param array $functions
	 * @return \lessc|boolean
	 */
	function get_less_parser($variables, $functions)
	{
		if(!include_once(LESSPHP_INC.'lessc.inc.php'))
		{
			trigger_error('Unable to process .less file -- LESSPHP not configured correctly on your server. Check the LESSPHP_INC setting in settings/package_settings.php.');
			return false;
		}

		$less = new lessc();
		$less->setFormatter('compressed');
		$less->setVariables($variables);
		foreach ($functions as $name => $func)
		{
			$less->registerFunction($name, $func);
		}

		return $less;
	}

	/**
	 * Quick interface to add_head_item for adding javascript
	 * @param string $url
	 * @param boolean $add_to_top
	 */
	function add_javascript( $url, $add_to_top = false, $wrapper = array('before'=>'','after'=>''), $inline = false )
	{
		if($inline)
		{
			if(substr($url, 0, 1) != '/')
			{
				trigger_error('Inlined javascript must be specified relative to server root (i.e. starting with "/"). Path given ('.$url.') does not conform to this specification. JS not inlined.');
			}
			else
			{
				$input_path = WEB_PATH.substr($url, 1);
				if(!file_exists($input_path))
				{
					trigger_error('Javascript file not found at "'.$input_path.'". JS not inlined.');
				}
				else
				{
					$js = file_get_contents($input_path);
					if(false === $js)
					{
						trigger_error('Javascript file readable at "'.$input_path.'". JS not inlined.');
					}
					else
					{
						$attrs = array('type' => 'text/javascript');
						$this->add_head_item('script', $attrs, $js, $add_to_top, $wrapper);
					}
				}
			}
		}
		else
		{
			$attrs = array('type' => 'text/javascript', 'src' => $url);
			$this->add_head_item('script', $attrs, '', $add_to_top, $wrapper);
		}
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
		$this->markup_fetched_num_times++;
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
