<?php
/**
* @package carl_util
* @subpackage dir_service
* @author Mark Heiman
* @copyright copyright 2006 Carleton College
* @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
* 
* A general purpose directory service interface
*
* <b>Overview</b>
* 
* The carl_util directory service is a PHP-based
* abstraction layer for directory information and
* authentication. It provides:
* 
* 1. a unified interface for querying and
*    authenticating against disparate directory sources
* 2. support for any PHP-accessible identity store
*    through an extensible, plugin-based framework (e.g.
*    LDAP, MySQL, Oracle, ActiveDirectory, etc.)
* 3. merged results from multiple sources
* 4. authentication fallthrough, with prioritization
* 
* <b>Integration</b>
* 
* Depending on your identity storage/management system,
* integrating the carl_util directory service into your
* environment can take from minimal to heavy PHP
* knowlege. An LDAP setup will be most straightforward,
* whereas an esoteric database or file-based system will
* likely take more work to integrate.
* 
* Currently it:
* 
* 1. fully supports the Reason content management
*    system as a directory and authentication source
* 2. provides a built-in framework for streamlined
*    integration with most LDAP-based directories
* 3. provides built-in tools to assist with
*    integration with MySQL-based directories
* 4. can be extended to support other directories,
*    limited by their accessiblity from PHP and their
*    equivalence to standard directory structures
* 
* <b>Caveat</b>
* 
* Please note that the current version of the carl_util
* directory service is still beta code and may have some
* quirks.
* 
* <b>License</b>
* 
* The carl_util directory service is free software
* licensed under the GNU General Public License.
*
* <b>API</b>
* 
* The directory service recognizes two basic datatypes: person and group.
* There are two ways to query each datatype: attribute/value and filter.
*
* Attribute searches are quite simple: you provide the name of the 
* attribute (in LDAP parlance) or field (in SQL parlance), an array of values to search for,
* and an array of attribute/field names you want returned. You do not need to worry about escaping;
* the directory service will handle that.
*
* Filter searches use the LDAP syntax (RFC 2254, http://www.ietf.org/rfc/rfc2254.txt).
* There are a variety of tutorials available online for this syntax. All searches use this syntax
* regardless of the specific system used by the various directories. If a given directory uses a 
* non-LDAP syntax, the plugin will convert the LDAP syntax into the appropriate query language for 
* that directory.
*
* <b>Examples</b>
*
* <i>Attribute search</i>
*
* <code>
* $dir = new directory_service();
* // This example will returb the usernames of all people with student or faculty affiliations
* $dir->search_by_attribute('ds_affiliation', array('student','faculty'), array('ds_username'));
* print_r($dir->get_records());
* </code>
*
* <i>Filter search</i>
*
* <code>
* $dir = new directory_service();
* // This example will return all people whose affiliation is employee and either their last name is Jensen or their first name starts with B
* $dir->search_by_filter('(&(ds_affiliation=employee)(|(ds_lastname=Jensen)(ds_firstname=B*)))');
* print_r($dir->get_records());
* </code>
*
* <i>Group attribute search</i>
*
* <code>
* $dir = new directory_service();
* // This example will return the names of all groups that have either username1 or username2 as members
* $dir->group_search_by_attribute('ds_member',array('username1','username2'),array('ds_groupname'));
* print_r($dir->get_records());
* </code>
*
* <i>Group filter search</i>
*
* <code>
* $dir = new directory_service();
* // This example will return all groups with name group_name and who have username as a member or owner.
* $dir->group_search_by_filter('(&(ds_groupname=group_name)(|(ds_member=username)(ds_owner=username))');
* print_r($dir->get_records());
* </code>
*
* <i>Authenticate</i>
*
* $dir = new directory_service();
* if($dir->authenticate($username,$password))
* 	echo $username.' is authenticated';
* else
* 	echo $username.' is not authenticated';
*
* <b>Generic attributes</b>
*
* If you are writing code for use outside your specific environment, there are several generic
* attributes which you can use and that will map to appropriate fields in directories.
*
* <i>People generic attributes:</i>
*
* - ds_username (unique username),
* - ds_email (email address),
* - ds_firstname (given name),
* - ds_lastname (surname),
* - ds_fullname (entire name, formatted appropriately),
* - ds_phone (telephone number),
* - ds_affiliation (relationship(s) between the individual and the organization)
*
* <i>Group generic attributes:</i>
*
* - ds_member (username(s) of member(s) of the group),
* - ds_groupid (unique ID of group),
* - ds_groupname (human-readable name of group),
* - ds_owner (username(s) of owner(s) of the group)
*
* If you write a new directory service plugin, you will need to make sure that the 
* plugin recognizes and translates these generic attributes to the equivalent attributes/fields
* in your directory structure.
*
* For more information on writing directory service plugins, see the ds_default documentation.
*
*/

require_once('paths.php');

/**
* Dispatcher Module -- General interface for plugging in specific directory services
* @subpackage dir_service
* @author Mark Heiman
*/

class directory_service {
	
	/**
	* Array list of services to use for queries
	* @access public
	* @var array
	*/
	var $services = array('default');
	
	/**
	* Array instances of available service objects
	* @access public
	* @var array
	*/
	var $serv_inst = array();
	
	/**
	* Array results of last search
	* @access public
	* @var array
	*/
	var $search_result = array();

	/**
	* String text of most recent error message
	* @access private
	* @var string
	*/
	var $_error = '';
	
	/**
	* Boolean Set flag to indicate whether results from multiple services should
	* be merged (TRUE) or whether only results from the first service to respond
	* should be returned.
	* @access private
	* @var boolean
	*/
	var $merge_results = TRUE;

	/**
	* Boolean debug messages on or off
	* @access private
	* @var boolean
	*/
	var $_debug = FALSE;

	/**
	* Array list of attributes for sort function
	* @access private
	* @var array
	*/
	var $sort_attrs = array();

	/**
	* Constructor; load configuration settings, instantiate services
	* @access private
	* @param array $pref_services Optional list of services to load for this instance
	*/
	function directory_service($pref_services=array()) {
		include(SETTINGS_INC.'dir_service_config.php');
		if (count($pref_services)) 
			$this->set_service($pref_services);
		else
			if (is_array($available_services)) $this->set_service($available_services);
	}
	
	/**
	* Load a named service provider
	* @access private
	* @param string $serv Name of service to load
	* @return boolean success
	*/
	function load_service($serv) //{{{ 
	{
		if (!isset($this->serv_inst[$serv])) {
			include(SETTINGS_INC.'dir_service_config.php');
			if(!empty($service_names_to_paths[$serv]))
			{
				$servname = 'ds_'.$serv;
				if (@include_once ($service_names_to_paths[$serv])) {
					$this->serv_inst[$serv] = new $servname();
					if ($this->_debug) echo '<p>Instantiated service '.$servname.' from '.$service_names_to_paths[$serv].'</p>';
				} else {
					if ($this->_debug) echo '<p>Failed to instantiate service '.$servname.' from '.$service_names_to_paths[$serv].'</p>';
					trigger_error('The service '.$serv.' was not able to be included from '.$service_names_to_paths[$serv]);
					return false;
				}
			}
			else
			{
				trigger_error('The service '.$serv.' is not registered in the directory service configs. Add it to the $service_names_to_paths array for it to be included.');
				return false;
			}
		}
		return true;
	} //}}}

	/**
	* Specify a specific (different from the default) service or services to use for 
	* subsequent queries.
	* @access public
	* @param array $servlist List of service names to use
	* @return boolean success
	*/
	function set_service($servlist = array('default')) //{{{ 
	{
		$active_services = array();
		if(!is_array($servlist))
		{
			$servlist = array($servlist);
		}
		foreach ($servlist as $serv) {
			if ($this->load_service($serv))
				$active_services[] = $serv;
		}
		if (count($active_services)) {
			$this->services = $active_services;
			return true;
		} else {
			return false;
		}
	} //}}}
	
	/**
	* Find out what directory services ae available
	* @access public
	* @abstract
	* @return array
	*/
	function get_available_services()
	{
		include(SETTINGS_INC.'dir_service_config.php');
		if (is_array($available_services))
			return $available_services;
		else
			return array();
	}
	
	/**
	* Modify a search parameter on a directory service
	* @access public
	* @param string $service Name of the affected service
	* @param array $params Service parameters (see service definitions for details)
	*/
	function set_search_params($service, $params) //{{{ 
	{
		if (isset($this->serv_inst[$service])) {
			foreach ($params as $key => $val)
			{
				$this->serv_inst[$service]->set_search_param($key, $val);
			}
		} else {
			trigger_error('Attempted to set parameters on non-existant service '.$serv);
		}
	}
	/**
	* Determine if a search filter is valid
	* @access public
	* @param string $filter Search filter to validate
	*/
	function validate_search_filter($filter) //{{{ 
	{
		if (!isset($this->serv_inst['default'])) {
			$this->serv_inst['default'] = new ds_default();
		}
		$tree = $this->serv_inst['default']->parse_filter($filter);
		$collapsed_input = preg_replace("/\s*/",'',$filter);
		$collapsed_output = preg_replace("/\s*/",'',$this->serv_inst['default']->build_filter_from_tree($tree));
		return ($collapsed_input == $collapsed_output);
	}

	/**
	* Search for a particular value or list of values
	* @access public
	* @param string $attr Name of the attribute to search
	* @param mixed $qlist string or array of values to search for
	* @param array $return_attrs Optional array of attributes to return

	*/
	function search_by_attribute($attr, $qlist, $return_attr=array()) //{{{ 
	{
		$this->search_result = array();
		$results = array();
		foreach ($this->services as $service) 
		{
			if(!empty($this->serv_inst[$service]))
			{
				$serv_results = $this->serv_inst[$service]->attr_search($attr, $qlist, $return_attr);
				if(!empty($serv_results))
				{
					if($this->merge_results)
					{
						$results[] = $serv_results;
					}
					else
					{
						$this->search_result = $serv_results;
						break;
					}
				}
			}
		}
		if(empty($this->search_result))
		{
			$this->search_result = $this->result_merge($results);
		}
		if (count($this->search_result))
			return true;
		else
			return false;
	} //}}}

	/**
	* Search using a provided filter written in LDAP search syntax
	* @access public
	* @param string $filter Search filter
	* @param array $return_attrs Optional array of attributes to return

	*/
	function search_by_filter($filter, $return_attr=array()) //{{{ 
	{
		$this->search_result = array();
		$results = array();
		foreach ($this->services as $service) 
		{
			if(!empty($this->serv_inst[$service])) 
				$serv_results = $this->serv_inst[$service]->filter_search($filter, $return_attr);
			if(!empty($serv_results))
			{
				if($this->merge_results)
				{
					$results[] = $serv_results;
				}
				else
				{
					$this->search_result = $serv_results;
					break;
				}
			}
		}
		if(empty($this->search_result))
		{
			$this->search_result = $this->result_merge($results);
		}
		if (count($this->search_result))
			return true;
		else
			return false;
	} //}}}

	/**
	* Search for groups by a particular value or list of values
	* @access public
	* @param string $attr Name of the attribute to search
	* @param mixed $qlist string or array of values to search for
	* @param array $return_attrs Optional array of attributes to return

	*/
	function group_search_by_attribute($attr, $qlist, $return_attr=array()) //{{{ 
	{
		foreach ($this->services as $service) {
			if ($this->search_result = $this->serv_inst[$service]->group_attr_search($attr, $qlist, $return_attr))
			{
				return true;
			}
		}
		return false;
	} //}}}

	/**
	* Search for groups using a provided filter written in LDAP search syntax
	* @access public
	* @param string $filter Search filter
	* @param array $return_attrs Optional array of attributes to return

	*/
	function group_search_by_filter($filter, $return_attr=array()) //{{{ 
	{
		foreach ($this->services as $service)
		{
			if(!empty($this->serv_inst[$service]))
			{
				if ($this->search_result = $this->serv_inst[$service]->group_filter_search($filter, $return_attr))
				return true;
			}
		}
		return false;
	} //}}}
	
	/**
	* Take the results returned by multiple services and merge them if required
	* @access public
	* @param array $results Array of result sets
	*/
	function result_merge($results) //{{{ 
	{
		// No results
		if (count($results) == 0) return array();
				
		// Merge results
		$merged  = array();
		// merge from last to first, so first returned entries override later entries
		for ($set = count($results) -1; $set >= 0; $set--)
		{
			if(!empty($results[$set]))
			{
				foreach($results[$set] as $key => $object)
				{
					// key on either username or groupid or groupname
					if (isset($object['ds_username'][0])) $key = $object['ds_username'][0];
					elseif (isset($object['ds_groupid'][0])) $key = $object['ds_groupid'][0];
					elseif (isset($object['ds_groupname'][0])) $key = $object['ds_groupname'][0];
					$merged[$key] = $object;
				}
			}
		}
		return $merged;
		
	} //}}}

	/**
	* Retrieve first value from a search result for a particular attribute
	* @access public
	* @param string $attr Name of the attribute value to return
	* @param string $key Optional record key, defaults to first record
	*/
	function get_first_value($attr,$key='') //{{{ 
	{
		if (isset($this->search_result) && is_array($this->search_result) && !empty($this->search_result))
		{
				if (!empty($key) && isset($this->search_result[$key]))
			{
				$record = $this->search_result[$key];
			} else {
				reset($this->search_result);
				$record = current($this->search_result);
			}
			if (isset($record))
			{
				$attr = strtolower($attr);
				if (isset($record[$attr]) && isset($record[$attr][0]))
					return $record[$attr][0];
			}
		}
		return '';
	} //}}}

	/**
	* Retrieve all values from a search result for a particular attribute
	* @access public
	* @param string $attr Name of the attribute value to return
	* @param string $key Optional record key, defaults to first record
	*/
	function get_values($attr,$key='') //{{{ 
	{
		if (isset($this->search_result) && is_array($this->search_result) && !empty($this->search_result))
		{
			if (!empty($key) && isset($this->search_result[$key]))
			{
				$record = $this->search_result[$key];
			} else {
				reset($this->search_result);
				$record = current($this->search_result);
			}
			if (isset($record))
			{
				if (isset($record[strtolower($attr)]))
					return $record[strtolower($attr)];
			}
		}
		return array();
	} //}}}
	
		/**
	* Retrieve first full record from a search result
	* @access public
	*/
	function get_first_record() //{{{ 
	{
		if (isset($this->search_result) && is_array($this->search_result) && !empty($this->search_result))
		{
			reset($this->search_result);
			return current($this->search_result);
		}
		else
			return array();
	} //}}}

	/**
	* Retrieve all records from a search result
	* @access public
	*/
	function get_records() //{{{ 
	{
		if (isset($this->search_result))
			return $this->search_result;
		else
			return array();
	} //}}}

	/**
	* Validate username and password 
	* @access public
	* @param string $username Userid
	* @param string $password Password
	*/
	function authenticate($username, $password) //{{{ 
	{
		foreach ($this->services as $service) {
			if ($this->serv_inst[$service]->authenticate($username, $password))
				return true;
		}
		error_log('DIR_SERV: Authentication failed for '.$username.', '.join(' / ', $this->services). ', '. $this->_error);
		return false;
	} //}}}

	/**
	 * Turn results merging on
	 * 
	 * This allows for more complete result sets, made up of all directory services that returned values
	 * @access public
	 */
	function merge_results_on()
	{
		$this->merge_results = true;
	}
	/**
	 * Turn results merging off
	 * 
	 * This allows for better performance when you are simply wanting to determine if a filter contains any results. You should leave result merging on if you are using the returned data in any significant way.
	 * @access public
	 */
	function merge_results_off()
	{
		$this->merge_results = false;
	}

	/**
	* Sort a result set according to a list of attributes.
	* @param $attrs Attribute list (in order of importance)
	*/
	function sort_records( $attrs )
	{
		if (isset($this->search_result))
		{
			$this->sort_attrs = $attrs;
			uasort( $this->search_result, array($this, '_sort_function') );
		}
	}
	
	/**
	* Internal sorting function for sort_records()
	*/
	function _sort_function( $a, $b )
	{
		foreach( $this->sort_attrs as $f )
		{
			$strc = strcasecmp( $a[$f][0], $b[$f][0] );
			if ( $strc != 0 ) return $strc;
		}
		return 0;
	}	
	
}

?>
