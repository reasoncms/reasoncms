<?php
/**
 * Reason API Factory Class
 *
 * @package reason	
 * @subpackage classes
 */
 
/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/api/api.php');
reason_include_once('classes/page_types.php');

/**
 * Provides methods to get ReasonAPI objects, and several convenience methods.
 *
 * - Determines which region to run based upon a request for an API and (optional) module identifier.
 * - Provides a static method to get a unique identifier for a module on its params.
 *
 * @author Nathan White
 */
class ReasonAPIFactory
{
	/**
	 * This is a factory of sorts that finds the correct API on a page type and returns an array with these components:
	 *
	 * - module_name string
	 * - module_region string
	 * - api ReasonModuleAPI object
	 *
	 * @param object page_type - ReasonPageType object to query for modules that support the requested api
	 * @param string requested_api - The api identifier string from the request
	 * @param string requested_identifier - The module identification string from the request
	 * @return array containing module_name, module_region, and the ReasonModuleAPI object
	 */
	public static function get_requested_api($page_type, $requested_api, $requested_identifier = NULL)
	{
		$region = self::get_region_to_run_for_api($page_type, $requested_api, $requested_identifier);
		if ($region && ($apis = self::get_supported_apis($page_type, $region)))
		{
			$region_info = $page_type->get_region($region);
			$module_api = $apis[$requested_api];
			$module_api->set_name($requested_api);
			return array('module_name' => $region_info['module_name'],
						 'module_region' => $region,
						 'api' => $module_api);
		}
		return false;
	}
	
	/**
	 * Return a parsable, unique string for a $module_name, $module_location, $module_param combination.
	 * 
	 * @param object page_type - ReasonPageType Object
	 * @param string region - name of the page type region where the module we want identified is located.
	 * @return string identifier - unique identifier based on the module class name, location, and parameters
	 */
	public static function get_identifier_for_module($page_type, $region)
	{
		$region_info = $page_type->get_region($region);
		$key_map = self::get_identifier_key_map();
		$identifier  = $key_map['module_class'] . self::get_module_class( $page_type, $region );
		$identifier .= $key_map['module_location'] . $region;	
		$identifier .= $key_map['module_params'] . md5(serialize( $region_info['module_params'] ));
		return $identifier;
	}
	
	/**
	 * Returns ReasonModuleAPI objects that are supported a region of a page type - indexed by module_api name
	 *
	 * @param object page_type - ReasonPageType Object
	 * @param string region - name of the page type region where the module we want to check for APIs is located.
	 */
	public static function get_supported_apis($page_type, $region)
	{
		static $class_supported_apis;
		$module_class = self::get_module_class($page_type, $region);
		if (!empty($module_class))
		{
			if (!isset($class_supported_apis[$module_class]))
			{
				$class_supported_apis[$module_class] = call_user_func(array($module_class, 'get_supported_apis'), $module_class);
			}
			return $class_supported_apis[$module_class];
		}
		return false;
	}
	
	/**
	 * Gets the class name from a page type and region name
	 *
	 * Will include the module in case it has not already been included
	 *
	 * @param object page_type - ReasonPageType Object
	 * @param string region - name of the page type region where we want to find the module class name.
	 * @return string module class name or the empty string
	 * @todo this should arguably just get a method on the page type - get the class name of a region
	 */
	private static function get_module_class($page_type, $region)
	{
		$region_info = $page_type->get_region($region);
		if ($region_info['module_filename'] && reason_file_exists($region_info['module_filename'])) reason_include_once( $region_info['module_filename'] );
		return (!empty($GLOBALS[ '_module_class_names' ][ $region_info['module_name'] ])) ? $GLOBALS[ '_module_class_names' ][ $region_info['module_name'] ] : '';
	}
	
	/**
	 * Defines what prefixes we use in module identifier strings.
	 * @return array
	 */
	private static function get_identifier_key_map()
	{
		return array('module_class' => 'mcla-',
					 'module_location' => '-mloc-',
					 'module_params' => '-mpar-');
	}
	
	/**
	 * Make a "best guess" at which region to run from the requested api and requested identifier
	 *
	 * @param object - page_type object
	 * @param string - requested api
	 * @param string - requested identifier
	 * @return mixed page_type region to run or false if none is found.
	 */
	private static function get_region_to_run_for_api($page_type, $requested_api, $requested_identifier = NULL)
	{
		if (!$requested_api)
		{
			trigger_error('get_region_to_run_for_api should only be run when an API is requested');
			return false;
		}
		else
		{
			$first_supported_region = false;
			$regions = $page_type->get_region_names();
			$identifier = self::parse_identifier($requested_identifier);
			$search_pathways = array(
				array('module_class', 'module_location', 'module_params'),
				array('module_class', 'module_param'),
				array('module_name', 'module_location'),
				array('module_class'),
				array('module_location')
			);
			foreach ($search_pathways as $index => $match_array)
			{
				foreach( $regions AS $region )
				{
					// we have location but no name
					$region_info = $page_type->get_region($region);
					$module_name = $region_info['module_name'];
					if ($module_name)
					{
						// first we make sure we have checked for module api support
						if (!isset($supports_requested_api[$module_name]))
						{
							$supported_apis = self::get_supported_apis($page_type, $region);
							$supports_requested_api[$module_name] = (!empty($supported_apis)) ? (isset($supported_apis[$requested_api])) : false;
							if ($supports_requested_api[$module_name] && ($first_supported_region == FALSE))
							{
								$first_supported_region = $region;
							}
						}
						if ($identifier && $supports_requested_api[$module_name])
						{
							if (!isset($search_mod[$region]))
							{
								$params = ($region_info['module_params'] != null) ? $region_info['module_params'] : array();
								$search_mod[$region]['module_class'] = self::get_module_class($page_type, $region);
								$search_mod[$region]['module_location'] = $region;
								$search_mod[$region]['module_params'] = md5(serialize($params));
							}
							$test = (array_intersect_key($search_mod[$region], array_flip($match_array)) == array_intersect_key($identifier, array_flip($match_array)));
							if ($test) return $region;
						}
						elseif (!$identifier && $supports_requested_api[$module_name]) return $region;
					}
				}
			}
			// we have gone through all regions but have nothing ... lets return the first supported region if defined
			if ($first_supported_region !== FALSE) return $first_supported_region;
		}
		return false;
	}
	
	/**
	 * Parses a module identifier string - returns an array.
	 *
	 * Note that no validation is done - the array could reference non existent classes, locations, or invalid params.
	 */
	private static function parse_identifier($module_identifier)
	{
		$key_map = self::get_identifier_key_map();
		$cla_start = carl_strpos($module_identifier, $key_map['module_class']);
		$loc_start = carl_strpos($module_identifier, $key_map['module_location']);
		$par_start = carl_strpos($module_identifier, $key_map['module_params']);
		if ( ($cla_start !== false) && ($loc_start !== false) && ($par_start !== false) )
		{
			$cla_len = carl_strlen($key_map['module_class']);
			$cla = (carl_substr($module_identifier, ($cla_start + $cla_len), ($loc_start - $cla_start) - $cla_len));
			$loc_len = carl_strlen($key_map['module_location']);
			$loc = (carl_substr($module_identifier, ($loc_start + $loc_len), ($par_start - $loc_start) - $loc_len));
			$par_len = carl_strlen($key_map['module_params']);
			$par = (carl_substr($module_identifier, ($par_start + $par_len), (carl_strlen($module_identifier) - $par_start) - $par_len));
			if (!empty($cla) && !empty($loc) && !empty($par))
			return array('module_class' => $cla, 'module_location' => $loc, 'module_params' => $par);
		}
		return false;
	}
}
?>