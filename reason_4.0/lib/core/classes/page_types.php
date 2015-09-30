<?php
/**
 * ReasonPageType provides a toolbox to get information about, modify, and export Reason page types.
 *
 * @todo implement region order setter (if needed)
 * @todo add meta understanding for exports where necessary
 * @todo add meta understanding for page type wizard
 * @author Nathan White
 * @author Andrew Bacon
 * @package reason
 * @subpackage classes
 */

class ReasonPageType
{
	/**
	* @var string The name of the page type.
	* @access private
	*/
	var $_page_type_name;
	/**
	* @var string The internal representation of the page type used by the class.
	* @access private
	*/
	var $_page_type;
	
	/**
	 * @var array Array of key-value metadata information
	 * 'note' => Administrative note about how page works and how to use it
	 * @access private -- use the meta() method
	 */
	var $_meta = array(
		'note' => null,
	);
	
	/**
	* @var array Available export formats.
	* @access private
	*/
	var $_available_export_formats = array(
		'json' => array(
			"name" => "JSON",
			"printable" => true,
			"method" => "_export_json"		
		),
		'reasonPTArray' => array(
			"name" => "page_types.php format (all regions defined)",
			"printable" => true,
			"method" => "_export_reason_pt_array"
		),
		'reasonPTArray_unmerged' => array(
			"name" => "page_types.php format (only regions different than default defined)",
			"printable" => true,
			"method" => "_export_reason_pt_array_unmerged"
		),
		'reasonPTArray_var' => array(
			"name" => "page_types.php formatted array, merged",
			"printable" => false,
			"method" => "_export_reason_pt_array_var"
		)
		
	);
	/**
	* @var string The current export format.
	* @access private
	*/
	var $_export_format = 'reasonPTArray';
	/**
	* @var string The default export format.
	* @access private
	*/
	var $_default_format = 'json';
	/**
	* @var bool whether or not the current page type is deprecated.
	* @access private
	*/
	var $_deprecated = null;
	/**
	* @var bool whether or not the page type is considered "core" or "local"
	* @access private
	*/
	var $_page_type_location = null;
	
	function ReasonPageType() {
	}


	/** 
	* Sets the name of the current page type.
	* 
	* @param string $name page type name.
	*/ 
	function set_name($name)
	{
		$this->_page_type_name = $name;
	}
	
	/** 
	* Gets the name of the current page type.
	* 
	* @return string The name of the current page type name.
	*/ 
	function get_name()
	{
		return $this->_page_type_name;
	}

	/** 
	* Sets the location of the current page type (local or core?)
	* 
	* @param string $location The location of the current page type.
	*/ 
	function set_location($location)
	{
		if (strtolower($location) != "core" && strtolower($location) != "local" && $location != "")
		{
			trigger_error("You must specify either 'core' or 'local' as the location of the page type.");
		} else
		{
			$this->_page_type_location = $location;
		}
	}
	
	/** 
	* Gets the location of the current page type (local or core)
	* 
	* @return string The location of the page type.
	*/ 
	function get_location()
	{
		return $this->_page_type_location;
	}

	/**
	 * Returns an array containing the properties of the page type (region => module, module parameters).
	 * 
	 * Obsolete?
	 * @return array
	*/
	function get_properties()
	{
		return ($this->_page_type);
	}

	/**
	 * Returns _page_type in the current export format.
	 * If $format isn't set, returns in the default format.
	 * 
	 * @param string $format the format that it should be returned in
	 */
	function export($format = NULL)
	{
		if (is_null($format)) {
			$format = $this->get_export_format();
//			echo "$format"; 
		}
		if (!array_key_exists($format, $this->_available_export_formats)) {
			trigger_error('The requested export format, ' . $format .', is not available.',WARNING);
		} else {
			$function_call = $this->_available_export_formats[$format]['method'];
//			echo $function_call;
			return $this->$function_call();
		}
	}
		
	/**
	 * Private JSON export format function for export(). Exports the current page type as JSON.
	 * 
	 * 
	 * @return string $export the JSON-encoded string containing the array.
	 */		
	function _export_json()
	{
		$ptname = $this->get_name();
		$export = array($ptname => ($this->_page_type));
		return json_encode($export);
	}
	

	/**
	* Private exporter for the array format that is currently used (mar 2010)
	* for the page types array file in reason. Called by export().
	* Has the default page type baked into the exported page type.
	* 
	* @return array the page types definition
	*/
	function _export_reason_pt_array_var()
	{
		$ptname = $this->get_name();
		$internalArray = $this->_page_type;
		$newArray = array($ptname => null);
		
		foreach ($internalArray as $region => $info)
		{
			$module = $info["module_name"];
		
			$params = $info["module_params"];
			if (is_null($params) || $params == "") 
			{
				$newArray[$ptname][$region] = $module;
			} else {
				$newArray[$ptname][$region] = array('module' => $module);
				foreach ($params as $param => $value)
				{
					$newArray[$ptname][$region][$param] = $value;
				}
			}
		}
		return $newArray[$ptname];
	}
	
	/**
	* Private exporter for the array format that is currently used (mar 2010)
	* for the page types array file in reason. Called by export().
	* Has the default page type baked into the exported page type.
	* 
	* @return string the page types definition
	*/
	function _export_reason_pt_array()
	{
		$ptname = $this->get_name();
		$internalArray = $this->_page_type;
		$newArray = array($ptname => null);
		
		foreach ($internalArray as $region => $info)
		{
			$module = $info["module_name"];
		
			$params = $info["module_params"];
			if (is_null($params) || $params == "") 
			{
				$newArray[$ptname][$region] = $module;
			} else {
				$newArray[$ptname][$region] = array('module' => $module);
				foreach ($params as $param => $value)
				{
					$newArray[$ptname][$region][$param] = $value;
				}
			}
		}
		$export = var_export($newArray, true);
		$lastParen = strrpos($export, ',');
		$firstParen = strpos($export, '(');
		$start = $firstParen + 1;
		$length = $lastParen - $firstParen - 1;
		$export = substr($export, $start, $length);
		return $export;
	}

	/**
	* Private exporter for the array format that is currently used (mar 2010)
	* for the page types array file in reason. Called by export().
	* Removes region definitions that are identical to the default template
	* from the exported page type.
	* 
	* @return string the page types definition
	*/
	function _export_reason_pt_array_unmerged()
	{
		$ptname = $this->get_name();
		$internalArray = $this->_page_type;
		$newArray = array($ptname => null);

		$rpt =& get_reason_page_types();
		$default_page_type = $rpt->get_page_type('default');
		foreach ($internalArray as $region => $info)
		{
			$default_info = $default_page_type->get_region($region);
			
			if ($default_info['module_name'] != $info['module_name'] || $default_info['module_params'] != $info['module_params']) 
			{
				$module = $info["module_name"];
				$params = $info["module_params"];
				if (is_null($params) || $params == "") 
				{
					$newArray[$ptname][$region] = $module;
				} else {
					$newArray[$ptname][$region] = array('module' => $module);
					foreach ($params as $param => $value)
					{
						$newArray[$ptname][$region][$param] = $value;
					}
				}
			} 
		}
		$export = var_export($newArray, true);
		$lastParen = strrpos($export, ',');
		$firstParen = strpos($export, '(');
		$start = $firstParen + 1;
		$length = $lastParen - $firstParen - 1;
		$export = substr($export, $start, $length);
		return $export;
	}


	/**
	 * Sets desired export format - throws a warning if an export format not
	 * in _available_export_formats is requested.
	 * 
	 * @param string $format desired export format
	 */
	function set_export_format($format)
	{
		if (!array_key_exists($format, $this->_available_export_formats)) {
			trigger_error('The requested export format, ' . $format .', is not available.',WARNING);
		} else {
			$this->_export_format = $format;
		}
	}
	
	
	/**
	* Gets the current export format. If the current export format hasn't been set, it sets it 
	* to the private var _default_format. 
	* 
	* @return string _format
	*/ 
	function get_export_format()
	{
		if (!isset($this->_export_format))
		{
			$this->set_export_format($this->_default_format);
		}

		return $this->_export_format;

	}
	
	/**
	* Gets an array of the supported export formats.
	* 
	* @return array _available_export_formats
	*/
	function get_export_formats()
	{
		return $this->_available_export_formats;
	}
	
	/**
	 * Returns the contents of a page type region as an associative array with four keys:
	 * region_name, module_name, module_filename (relative to /reason_package/reason_4.0/), and module_params.
	 * for_each_region('page_type_name', 'func_name');
	 *
	 * <code>
	 * $rpts =& get_reason_page_types();
	 * $pt = $rpts->get_page_type('default');
	 * $regions = $pt->get_region_names();
	 * foreach ($regions as $region)
	 * {
	 * 	$region_info = $pt->get_region($region);
	 * 	if (!empty($region_info['module_name']))
	 * 	{
	 * 		echo "The region $region_info['region_name'] uses module $region_info['module_name'],";
	 * 		echo "Located at $region_info['module_filename'],";
	 * 		echo "with parameters: ";
	 * 		var_dump($region_info['module_params']);
	 * 	}
	 * }
	 * </code>
	 * 
	 * @param string $region_name the name of the region to be fetched.
	 * @return mixed false if the region doesn't exist, otherwise array with keys region_name, module_name, module_filename, module_params
	 */
	function get_region($region_name)
	{
		if (isset($this->_page_type[$region_name])) {
			$thisRegion['region_name'] = $region_name;
			$thisRegion += $this->_page_type[$region_name];
			return $thisRegion;
		} else {
			return false;
		};
	}

	/**
	 * Gets whether or not a page type is deprecated.
	 * @return bool $value the truth value of deprecated
	 */
	
	function get_deprecated() {
		return $this->_deprecated;
	}
	
	/**
	 * Sets whether or not a page type is deprecated.
	 * @param bool $value the truth value of deprecated
	 */
	
	function set_deprecated($value) {
		$this->_deprecated = $value;
	}
	
	/**
	 * Modifies an existing region or create a new region according to the parameters
	 * 
	 * @param string $region_name name of the region
	 * @param string $module_name name of the module
	 * @param string $module_filename relative to core or local
	 * @param string $module_params module parameters 
	 *
	 * @todo remove restriction on _meta region name once we move off the php array fprmat for page type definitions
	 */
	function set_region($region_name, $module_name, $module_filename, $module_params)
	{
		if('_meta' == $region_name)
		{
			trigger_error('The php export format will not accept the region name "_meta"; this region name is currently not reliable');
			return false;
		}
		$this->_page_type[$region_name] = array(
			'module_name' => $module_name,
			'module_filename' => $module_filename,
			'module_params' => $module_params,
		);

	}
	
	/**
	 * Set the parameters for a given region
	 *
	 * This function clears any existing parameters for the region. To leave existing parameters
	 * in place, use set_region_parameter().
	 *
	 * @access public
	 * @param string $region_name
	 * @param array $module_params An array of parameter key-value pairs
	 * @return boolean true if region exists, false if not
	 */
	function set_region_parameters($region_name, $module_params)
	{
		if(isset($this->_page_type[$region_name]['module_name']))
		{
			$this->_page_type[$region_name]['module_params'] = $module_params;
			return true;
		}
		return false;
	}
	
	/**
	 * Set a single parameter for a given region
	 *
	 * This function changes a single parameter for a given region. If you want to clear all parameters
	 * and start from scratch, use set_region_parameters().
	 *
	 * @access public
	 * @param string $region_name
	 * @param array $key The parameter name to set
	 * @param $calue The parameter value to set
	 * @return boolean true if region exists, false if not
	 */
	function set_region_parameter($region_name, $key, $value)
	{
		if(isset($this->_page_type[$region_name]['module_name']))
		{
			if(empty($this->_page_type[$region_name]['module_params']))
				$this->_page_type[$region_name]['module_params'] = array();
			$this->_page_type[$region_name]['module_params'][$key] = $value;
			return true;
		}
		return false;
	}

	/**
	 * Removes a region from a page type object.
	 * 
	 * <code>
	 * $rpts =& get_reason_page_types();
	 * $pt = $rpts->get_page_type('default');
	 * $pt->remove_region('main');
	 * </code>
	 * 
	 * @param array $region_name the region to be removed
	 * @return mixed contents of the removed region or false if nothing was removed
	 */
	function remove_region($region_name)
	{
		if(isset($this->_page_type[$region_name])) {
			$removedStuff = $this->_page_type[$region_name];
		} else {
			$removedStuff = false;
		}
		$this->_page_type[$region_name] = NULL;
	}
	
	/**
	 * Does the page type make use of $module_name? 
	 * Takes either a single module as a string, or 1-dimensional array of (string) module names. 
	 * 
	 * @param mixed string or array of strings $module_name
	 * @return bool true if the page types uses the module, otherwise false.
	 */
	function has_module($module_name)
	{
		foreach ($this->get_region_names() as $region)
		{
			$region_info = $this->get_region($region);
			$has_module = (is_array($module_name)) ? (in_array($region_info['module_name'], $module_name)) :  ($region_info['module_name'] == $module_name);
			if ($has_module) return true;
		}
		return false;
	}
	
	/**
	 * What region(s) (if any) use a given module or modules?
	 * Takes either a single module as a string, or 1-dimensional array of (string) module names. 
	 * 
	 * @param mixed string or array of strings $module_name
	 * @return array region names
	 */
	function module_regions($module_name)
	{
		$region_names = array();
		foreach ($this->get_region_names() as $region)
		{
			$region_info = $this->get_region($region);
			$has_module = (is_array($module_name)) ? (in_array($region_info['module_name'], $module_name)) :  ($region_info['module_name'] == $module_name);
			if ($has_module)
				$region_names[] = $region;
		}
		return $region_names;
	}
	
	/**
	 * Orders the named regions of a page type from top to bottom.
	 *
	 * If only a subset of all region names is included in ordered_region_names, those regions will be ordered from top to bottom
	 * and the region names that aren't mentioned will remain at the bottom of the stack.
	 * 
	 * @param array $ordered_region_names
	 * @todo implement
	 */
	function set_region_order($ordered_region_names)
	{
	}

	/**
	 * Unsurprisingly, gets a list of all the region names. 
	 * This function gets a lot of use! Loop through the regions of a 
	 * page type like this:
	 * 
	 * <code>
	 * $rpts =& get_reason_page_types();
	 * $pt = $rpts->get_page_type('default');
	 * $regions = $pt->get_region_names();
	 * foreach ($regions as $region)
	 * {
	 * 	$region_info = $pt->get_region($region);
	 * 	if (!empty($region_info['module_name']))
	 * 	{
	 * 		echo "The region $region uses module $region_info['module_name'],";
	 *	}
	 * }
	 * </code>
	 * 
	 * @see ReasonPageType::get_region()
	 * 
	 * @return array an array of the region names
	 */
	function get_region_names()
	{
		foreach ($this->_page_type as $region => $definition)
		{
			$region_array[] = $region;
		}
		return $region_array;
	}


	/**
	 * Outputs the page type as something pretty and readable.
	 * Used by the page_type_wizard developer tool. 
	 * 
	 * @param mixed $data The data to parse. If null, $this->_page_type.
	 * @param mixed $escape whether or not to escape special characters
	 * @return void
	 * @todo should this instead return a string?
	 * @todo add meta information
	 */	
	function get_as_html($data=NULL, $escape=NULL) {
		if (!isset($data))
			$data = $this->_page_type;
		
		if (is_array($data)) 
		{
			if (count ($data))
			{
				echo '<ul>'."\n";
				while (list ($key,$value) = each ($data))
				{
					echo '<li>';
					$type=gettype($value);
					if ($type=="array")
					{
						if($type == 'object')
						{
							$type = $type.' - '.get_class($value);
						}
						if( $escape )
						{
							$type = htmlspecialchars( $type );
							$type = get_class( $value );
							$key = htmlspecialchars( $key );
						}
						printf ("<strong>%s</strong>:\n", $key);
						$this->get_as_html ($value, $escape);
					}
					else 
					{
						if (!$value)
						{
							// show actual non-value
							switch( gettype( $value ) )
							{
								case 'integer': case 'double':
									$value = '0';
									break;
								case 'boolean':
									$value = 'false';
									break;
								case 'NULL':
									$value = 'NULL';
									break;
								default:
									$value="(none)";
									break;
							}
						}
						// check for strict equivalance to true
						if( $value === true )
							$value = 'true';
						if( $escape )
						{
							$type = htmlspecialchars( $type );
							$key = htmlspecialchars( $key );
							$value = nl2br(htmlspecialchars( $value ));
						}
						printf ("<strong>%s</strong> = %s", $key, $value);
					}
					echo '</li>'."\n";
				}
				echo "</ul>\n";
			}
			else
			{
				echo '(empty)'."\n";
			}
		}
	}
	
	/**
	 * Get and set page type meta information
	 *
	 * $pt->meta() returns all meta information as an array
	 *
	 * $pt->meta('key') returns the meta information for a given key=
	 * (or null if a key has not been set)
	 *
	 * $pt->meta('key','value') sets a given key to a given value
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	function meta($key = null, $value = null)
	{
		if(null === $key && null === $value)
		{
			return $this->_meta;
		}
		elseif( null === $value )
		{
			if(isset($this->_meta[$key]))
				return $this->_meta[$key];
			else
				return null;
		}
		else
		{
			$this->_meta[$key] = $value;
			return true;
		}
	}
}


/**
 * Returns a singleton ReasonPageTypes object.
 * If you're using the ReasonPageTypes class, you should use this function! 
 *
 * <code>
 * $rpts =& get_reason_page_types();
 * $pt = $rpts->get_page_type('default');
 * </code>
 * 
 * @return object ReasonPageTypes
 */
function &get_reason_page_types()
{
	static $reason_page_types;
	if (!isset($reason_page_types))
	{
		$reason_page_types = new ReasonPageTypes;
	}
	return $reason_page_types;
}

/**
 * A utility class which should be used for almost all page type-related tasks.
 * You should get a ReasonPageTypes object using the singleton function {@link get_reason_page_types() get_reason_page_types()}.
 * 
 * You should use ReasonPageTypes to:
 * <ul>
 * <li>Get a list of page types ({@link ReasonPageTypes::get_page_type_names()})</li>
 * <li>Get an array of all page type objects ({@link ReasonPageTypes::get_page_types()})</li>
 * <li>Get an array of names of page types that use a module ({@link ReasonPageTypes::get_page_type_names_that_use_module()})</li>
 * <li>Get a named page type object ({@link ReasonPageTypes::get_page_type()})</li>
 * </ul>
 * @author Nathan White
 * @author Andrew Bacon
 * @package reason
 * @subpackage classes
 */
class ReasonPageTypes
{
	/**
	 * Resolves the module filename relative to minisite_templates/modules given the name. 
	 * Used in {@link ReasonPageTypes::get_page_type() get_page_type()}.
	 * 
	 * @param string $module_name The name of the module whose filename will be determined.
	 * @return mixed the filename of $module_name or false if it cannot be found.
	 */
	static function resolve_filename($module_name)
	{
		static $cachedModules;
		if (!empty($module_name))
		{
			if (!isset($cachedModules[$module_name])) {
				if (reason_file_exists( 'minisite_templates/modules/'.$module_name.'.php' ))
				{
					$cachedModules[$module_name] = 'minisite_templates/modules/'.$module_name.'.php';
				}
				elseif (reason_file_exists( 'minisite_templates/modules/'.$module_name.'/module.php' ))
				{
					$cachedModules[$module_name] = 'minisite_templates/modules/'.$module_name.'/module.php';
				}
				else
				{
					trigger_error('The minisite module class file for "'.$module_name.'" cannot be found',WARNING);
					$cachedModules[$module_name] = false;
				}
			}
			return $cachedModules[$module_name];
		}
		else
		{
			return false;
		}
	}
	
	/** 
	 * Returns a ReasonPageType object of the given $page_type_name.
	 *
	 * If no $page_type_name is given, returns the 'default' page type.
	 *
	 * If given no argument, returns the default page type - uses caching.
	 * <code>
	 * $rpts =& get_reason_page_types();
	 * $pt = $rpts->get_page_type();
	 * </code>
	 *
	 * If only given a name, looks it up as usual in page_types.php array - uses caching.
	 * <code>
	 * $rpts =& get_reason_page_types();
	 * $pt = $rpts->get_page_type('blurb');
	 * </code>
	 * 
	 * If given a name and an array, make a new page type with name and use the array as definition. Caching is not available.  
	 * <code>
	 * $pageTypeArray = array(
	 * 	'main_post' => 'publication',
	 * 	'main_head' => array( 
	 * 		'module'=>'basic_tabs', 
	 * 		'mode'=>'parent'
	 * 	)
	 * );
	 * $rpts =& get_reason_page_types();
	 * $pt = $rpts->get_page_type('monkey_page_type', $pageTypeArray);
	 * </code>
	 *
	 * If asked for a named page type that does not exist, will return false
	 *
	 * @param string $page_type_name The name of the page type you want to get from the global.
	 * @param array $dynamic_page_type_array An array containing a region to section mapping. 
	 * @return mixed ReasonPageType object or false if the page type requested does not exist
	 * @todo add a caching scheme
	 */

	function get_page_type($page_type_name = null, $dynamic_page_type_array = null, $use_cache = null)
	{
		//lets do some validation and setup defaults
		static $page_type_definitions;
		if (($page_type_name !== null) && (!is_string($page_type_name) || (is_string($page_type_name) && empty($page_type_name))))
		{
			trigger_error('The page_type_name parameter cannot be used. When provided, it must be a non empty string - ');
			$page_type_name = null;
		}
		if (($dynamic_page_type_array !== null) && (!is_array($dynamic_page_type_array) || (is_array($dynamic_page_type_array) && (array_values($dynamic_page_type_array) === $dynamic_page_type_array))))
		{
			trigger_error('The dynamic_page_type_array parameter cannot be used. When provided, it must be a non empty associative array.');
			$dynamic_page_type_array = null;
		}
		if (($use_cache !== null) && (!is_bool($use_cache)))
		{
			trigger_error('The use_cache parameter cannot be used. When provided, must be a boolean.');
			$use_cache = null;
		}
		elseif ( ($use_cache === true) && is_array($dynamic_page_type_array) )
		{
			trigger_error('The use_cache parameter is set to true, but will be ignored - virtual page types cannot be cached.');
		}
		
		$page_type_name = ($page_type_name !== null) ? $page_type_name : 'default';
		$dynamic_page_type_array = ($dynamic_page_type_array !== null) ? $dynamic_page_type_array : false;
		
		// if a cache default provided, use it as long as we haven't also specified a virtual page type.
		$use_cache = (($use_cache !== null) && !$dynamic_page_type_array ) ? $use_cache : !is_array($dynamic_page_type_array);
	
		// we always save to cache unless this is dynamic.
		$save_to_cache = (!$dynamic_page_type_array) ? true : false;
		
		if ($use_cache && isset($page_type_definitions[$page_type_name]))
		{
			$pt = $page_type_definitions[$page_type_name];
		}
		else // we create a new ReasonPageType
		{
			$pt = new ReasonPageType;
			$pt->set_name($page_type_name);
			$page_type_array = (!$dynamic_page_type_array) ? $this->_get_page_type_array($page_type_name) : $dynamic_page_type_array;
			if ($page_type_array)
			{
				foreach ($page_type_array as $region_name => $module)
				{
					if('_meta' == $region_name)
					{
						foreach($module as $k=>$v)
							$pt->meta($k, $v);
					}
					elseif (!is_array($module))
					{
						if (empty($module))
						{
							$module_name = NULL;
							$module_filename = '';
						}
						else
						{
							$module_name = $module;
							$module_filename = $this->resolve_filename($module);
						}
						$pt->set_region($region_name, $module_name, $module_filename, null);
					}
					elseif (is_array($module))
					{
						$module_name = $module['module'];
						$module_filename = $this->resolve_filename($module['module']);
						unset($module['module']);
						$module_params = $module;
						$pt->set_region($region_name, $module_name, $module_filename, $module_params);
						}
					if ($module_name && !$dynamic_page_type_array && $this->_is_module_deprecated($module_name)) $pt->set_deprecated(true);
				}
				if (!$dynamic_page_type_array && ($location = $this->_get_page_type_location($page_type_name))) $pt->set_location($location);
			}
			else $pt = false; // if there is no array dynamic or lookup up we set $pt to false;
			if ($save_to_cache) $page_type_definitions[$page_type_name] = $pt;
		}
		return $pt;
	}

	private function _get_page_type_array($page_type_name)
	{
		reason_include_once('minisite_templates/page_types.php');
		if (isset($GLOBALS['_reason_page_types'][$page_type_name]))
		{
			$page_type = $GLOBALS['_reason_page_types']['default'];
			if ($page_type_name != 'default')
			{
				$specific_page_type = $GLOBALS['_reason_page_types'][$page_type_name];
				foreach ($specific_page_type as $region => $info)
				{
					if (isset($page_type[$region])) unset($page_type[$region]);
					$page_type[$region] = $info;
				}
			} 
		}
		else trigger_warning('Page type specified ('.htmlspecialchars($page_type_name,ENT_QUOTES,'UTF-8').') does not exist. You should either reinstate or change the page type.', 1);
		return (isset($page_type)) ? $page_type : false;
	}
			
	private function _get_page_type_location($page_type_name)
	{
		reason_include_once('minisite_templates/page_types.php');
		$reason_page_types = $GLOBALS['_reason_page_types'];
		if (isset($GLOBALS['_reason_page_types_local'][$page_type_name]))
		{
			return 'local';
		}
		elseif (isset($GLOBALS['_reason_page_types'][$page_type_name]))
		{
			return 'core';
		}
		return false;
	}
	
	private function _is_module_deprecated($module_name)
	{
		static $is_deprecated;
		if (!isset($is_deprecated[$module_name]))
		{
			reason_include_once('minisite_templates/page_types.php');
			$is_deprecated[$module_name] = (in_array($module_name, $GLOBALS['_reason_deprecated_modules']));
		}
		return $is_deprecated[$module_name];
	}
	
	/**
	 * Returns the <i>names</i> of page types that use $module_name, <i>not</i> ReasonPageType objects.
	 * Does not create pt objects in order to get the names (faster).
	 * 
	 * Replaces page_types_that_use_module in util.php.
	 * @param mixed $module_name the name (or array of names) for the mod to be matched
	 * @return array an array of names of page types that use the module.
	 */
	function get_page_type_names_that_use_module($module_name)
	{
		static $modules_to_page_types = array();
		if(empty($modules_to_page_types))
		{
			reason_include_once('minisite_templates/page_types.php');
			foreach($GLOBALS['_reason_page_types'] as $page_type => $type )
			{
				if( $page_type != 'default' )
				{
					$type = array_merge( $GLOBALS['_reason_page_types'][ 'default' ], $type  );
				}
				foreach( $type AS $section => $module_info )
				{
					if('_meta' != $section)
					{
						$module = is_array( $module_info ) ? $module_info[ 'module' ] : $module_info;
						if ($module) $modules_to_page_types[$module][] = $page_type;
					}
				}
			}
		}
		if (is_array($module_name))
		{
			$result_array = array();
			foreach ($module_name as $single_module_name)
			{
				if (!is_string($single_module_name))
				{
					trigger_error("Malformed parameter passed to get_page_type_names_that_use_module. Takes either a string or an array of strings.", WARNING);
				}
				if (array_key_exists($single_module_name, $modules_to_page_types))
				{
					$result_array = array_merge($result_array, $modules_to_page_types[$single_module_name]); 
				}
			}
			return array_unique($result_array);
		} elseif (is_string($module_name))
		{		
			if(array_key_exists($module_name,$modules_to_page_types))
			{
				return $modules_to_page_types[$module_name];
			}
		}
		return array();
	}
	
	
	/**
	 * Provides an array of all the page type names. Faster than get_page_types(). 
	 * 
	 * @return array an array of the names of all page types in the $GLOBALS var
	 */
	function get_page_type_names()
	{
		reason_include_once('minisite_templates/page_types.php');
		return array_keys($GLOBALS['_reason_page_types']);
	}
	
	/**
	 * Gets an array of ReasonPageType objects, one object for each page type in the $GLOBALS var.
	 *
	 * If you don't need all the page type objects you should use get_page_type($name) to just grab a single page type object.
	 *
	 * @return array an array of ReasonPageType objects
	 */
	function get_page_types()
	{
		if (!isset($this->_reason_page_types))
		{
			reason_include_once('minisite_templates/page_types.php');
			$this->_reason_page_types = array();
			foreach ($GLOBALS['_reason_page_types'] as $page_type_name => $info)
			{
				$pt = @$this->get_page_type($page_type_name);
				$this->_reason_page_types[$page_type_name] = $pt;
			}
		}
		return $this->_reason_page_types;
	}
	
	function get_params_of_page_types_that_use_module($module_name)
	{
		$ret=array();
		$rpts =& get_reason_page_types();
		$page_types=$rpts->get_page_type_names_that_use_module($module_name);
		foreach($page_types as $page_type)
		{
			$pts=$rpts->get_page_type($page_type);
			$tmps=$pts->get_properties();
			$d=array();
			foreach($tmps as $key=>$tmp)
			{
				if($tmp['module_name']==$module_name)
				{
					if(is_array($tmp['module_params']))
					{
						$d['params']=$tmp['module_params'];
					}
					$d['page_type']=$page_type;
					$d['location']=$key;
					$ret[]=$d;
				}
			}
		}
		return $ret;
	}
}
?>