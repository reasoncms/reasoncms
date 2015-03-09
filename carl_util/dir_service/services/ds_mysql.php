<?php
/**
 * A general purpose directory service interface
 * @package carl_util
 * @subpackage dir_service
 * @author Mark Heiman
 */

/**
 * Include database connection library and parent class
 */
include_once( CARL_UTIL_INC . 'db/db.php' );
include_once('ds_default.php');

/**
 * MySQL Directory Service -- Interface for access to directory info in MySQL tables
 * @author Mark Heiman
 */
class ds_mysql extends ds_default {

	/**
	* array Connection settings for this service. 
	* @access private
	* @var array
	*/
	var $_conn_params = array(
	  	'connectDBname' => 'dirtest',
		);
	
	/**
	* array Settings for the current search 
	* @access private
	* @var array
	*/
	var $_search_params = array(
        	'table' => 'users',
		'base_attrs' => array('carlnetid','mail','edupersonprimaryaffiliation','edupersonaffiliation','ds_username'),
		);
		
	/**
	* array Dependencies for generic attributes 
	* @access private
	* @var array
	*/
	var $_gen_attr_depend = array(
		'ds_username' => array('username'),
		'ds_email' => array('email'),
		'ds_firstname' => array('firstname'),
		'ds_lastname' => array('lastname'),
		'ds_fullname' => array('firstname','lastname'),
		);

	/**
	* string Name of any database we were previously connected to  
	* @access private
	* @var string
	*/	
	var $prev_connection_name;
	
	/**
	* Constructor. Open connection to service here, if appropriate
	* @access private
	*/
	function ds_mysql() {
		register_shutdown_function(array(&$this, "dispose")); 
	}
	
	/**
	* Open connection to service
	* @access public
	*/
	function open_conn() {
		if (get_current_db_connection_name() != $this->_conn_params['connectDBname'])
		{
			$this->prev_connection_name = get_current_db_connection_name();
			if($this->_conn = connectDB($this->_conn_params['connectDBname']))
			{
				return true;
			}
			return false;
		}
		return true;
	}
	
	/**
	* Close connection to service
	* @access public
	*/
	function close_conn() {
		$this->reconnect_prev_db();
	}

	/**
	* Reconnect to previously open database
	* @access public
	*/
	function reconnect_prev_db() {
		if(!empty($this->prev_connection_name) && $this->prev_connection_name != $this->_conn_params['connectDBname'])
		{
			connectDB($this->prev_connection_name);
		}
	}
	
	/**
	* Conduct a search using the values in search_params
	* @access public
	*/
	function search() {
		$this->open_conn();
		$result = mysql_query($this->_search_params['filter'], $this->_conn);
		// If we were connected to another DB, reconnect
		$this->close_conn();

		if ($result) {
			return ($this->format_results($result));
		} else {
			$this->_error = sprintf('Error executing statement: %s -- %d %s', $this->_search_params['filter'], mysql_errno($this->_conn), mysql_error($this->_conn));
			return false;
		}
	} 

	
	/**
	* Search for a particular value
	* @access public
	* @param string $attr Name of the attribute to search
	* @param mixed $qstring string or array of strings to search for
	* @param array $return List of attributes to return
	*/
	function attr_search($attr, $qlist, $return = array()) {
		if (is_array($qlist)) {
			// build a search filter for matching against multiple values.
			foreach ($qlist as $val)
				$filter_parts[] = $this->construct_filter('equality',$attr,$this->escape_input($val));
			$filter = join(' AND ', $filter_parts);
		} else {
			// build a search filter for matching against a single value.
			$filter = $this->construct_filter('equality',$attr,$this->escape_input($qlist));
		}
		// assemble a list of all the attributes that should be returned --
		// anything in the filter, the base list, or explicitly requested
		$involved_attrs = array_unique(array_merge(array(strtolower($attr)),$return,$this->_search_params['base_attrs']));
		$return_attrs = $this->get_dependent_attrs($involved_attrs);
		// ldap_search requires sequential array elements; sort ensures that.
		sort($return_attrs);
		$this->set_search_param('attrs',$return_attrs);
		$this->set_search_param('filter', sprintf('SELECT %s FROM %s WHERE %s', join(', ', $return_attrs), $this->_search_params['table'], $filter));
		if ($results = $this->search()) {
		// add in any generic attributes required
			$augmented_results = $this->add_gen_attrs_to_results($results, $involved_attrs);
			return ($augmented_results);
		} else {
			return false;
		}
	}
	
	/**
	* Search using a provided LDAP-style filter
	* @access public
	* @param string $filter Search filter
	* @param array $return Optional list of attributes to return
	*/
	function filter_search($filter, $return=array()) {
		$tree = $this->parse_filter($filter);
		// assemble a list of all the attributes that should be returned --
		// anything in the filter, the base list, or explicitly requested
		$involved_attrs = array_unique(array_merge($return,$this->_search_params['base_attrs']));
		$return_attrs = $this->get_dependent_attrs($involved_attrs);
		// ldap_search requires sequential array elements; sort ensures that.
		sort($return_attrs);
		$this->set_search_param('attrs',$return_attrs);
		$this->set_search_param('filter', sprintf('SELECT %s FROM %s WHERE %s', join(', ', $return_attrs), $this->_search_params['table'], $this->filter_to_sql($tree)));
		if ($results = $this->search()) {
		// add in any generic attributes required
			$augmented_results = $this->add_gen_attrs_to_results($results, $involved_attrs);
			return ($augmented_results);
		} else {
			return false;
		}
	}

	/**
	* Convert a parsed LDAP-style filter into valid SQL.
	* @access private
	* @param array $parse_tree Results from parse_filter
	*/
	function filter_to_sql ($parse_tree) {
		foreach ($parse_tree as $operator => $elements) {
			if (in_array($operator, array('=','~=','>=','<='))) {
				return $this->construct_filter($operator,$elements[0],$elements[1]);	
			}
			if ($operator == '!') {
				return sprintf ('NOT (%s)', $this->filter_to_sql($elements[0]));
			}
			if (in_array($operator, array('|','&'))) {
				$join = array();
				foreach ($elements as $branch) {
					$join[] = $this->filter_to_sql($branch);
				}
				$op = ($operator == '|') ? ' OR ' : ' AND ';
				return sprintf ('(%s)', (join($op, $join)));
			}
		}
	}
	

	/**
	* Put query results into common format for return.
	* @access private
	* @param mixed $results Raw results from service
	
	Return structure is:
	
	Array[]
		Record_n[]
			Attribute_n[]
				Value_n
	There may be multiple records, multiple attributes per record, and multiple values per attribute.
	You must return an array even if your provider only stores single values.
	
	Attribute names are canonicalized to lowercase.
	
	*/
	function format_results($result) {
		$nice_entries = array();
		
		// loop through all entries
		while ( $entry = mysql_fetch_array($result) )
		{
			$nice_entry = array();
			// loop through all attributes of entry
			foreach( $entry AS $attr_key => $value )
			{
				$nice_entry[strtolower($attr_key) ][] = $value;
			}
			$nice_entries[] = $nice_entry;
		}	
		return $nice_entries;
	}

	/**
	* Given a formatted set of results, blend in the generic attributes present in the 
	* provided attribute list (which may contain non-generic attributes, which are ignored)
	* @access public
	* @param array $results array of results produced by format_results
	* @param array $attr List of attributes, some of which may be generic
	*/

	function add_gen_attrs_to_results($results,$attrs) {
		foreach ($results as $record) {
			foreach ($attrs as $attr) {
				if (isset($this->_gen_attr_depend[$attr])) {
					// Provide mappings between all generic attributes and your local attributes.  This
					// will need to be modified for your own particular situation.
					switch ($attr) {
						case 'ds_username':
							$value = $record['username'];
							break;
						case 'ds_email':
							$value = $record['email'];
							break;
						case 'ds_firstname':
							$value = $record['firstname'];
							break;
						case 'ds_lastname':
							$value = $record['lastname'];
							break;
						case 'ds_fullname':
							$value = sprintf('%s %s', $record['firstname'], $record['lastname']);
							break;
					}
					$record[$attr] = $value;
				}	
			}
			$updated_results[] = $record;
		}
		return $updated_results;
	}
	
	/**
	* Create a valid search filter, taking into account generic attribute requirements 
	* @access public
	* @param string $type Type of comparison (equality, inequality, like)
	* @param string $attr Name of attribute
	* @param string $value Value to compare against
	*/
	function construct_filter($type,$attr,$value) {	
		switch ($type) {
			case '~=':
				$compare = 'LIKE';
				break;
			case 'equality':
				$compare = '=';
				break;
			default:
				$compare = $type;
			break;
		}
		// If the filter contains wildcards, force a LIKE with appropriate syntax.
		if (strpos($value, '*') !== false) {
			$value = str_replace('*','%',$value);
			$compare = 'LIKE';
		}
		// Provide special actions for generic attributes.  This
		// will need to be modified for your own particular situation.
		// If you just need to replace one attribute with another, redefine
		// $attr; otherwise, define $filter to be the full filter to return.
		if (isset($this->_gen_attr_depend[$attr])) {
			switch ($attr) {
				case 'ds_username':
					$attr = 'username';
					break;
				case 'ds_email':
					$attr = 'email';
					break;
				case 'ds_firstname':
					$attr = 'firstname';
					break;
				case 'ds_lastname':
					$attr = 'lastname';
					break;
				case 'ds_fullname':
					$filter = sprintf('%s (lastname %s "%s" AND firstname %s "%s")', $not_flag, $compare, $value, $compare, $value);
					break;
			}
		}
		if (!isset($filter)) 
			$filter = (empty($not_flag)) ? sprintf('%s %s "%s"', $attr, $compare, $value) : sprintf('%s (%s %s "%s")', $not_flag, $attr, $compare, $value);
		return $filter;
	}
	
	/**
	* Apply escaping to special characters in search values. 
	* @access private
	* @param string $value User provided value
	*/
	function escape_input($value) {
		return carl_util_sql_string_escape($value);
	}
	
	/**
	* Validate username and password 
	* @access public
	* @param string $username Userid
	* @param string $password Password
	*/
	function authenticate($username, $password) {
		$this->set_search_param('filter', sprintf('SELECT username FROM %s WHERE username="%s" AND passord="%s"', $this->_search_params['table'], $username, md5($password)));
		$results = $this->search();
		return count($results);
	}
	
	/**
	* Destructor. Close connection to service here, if appropriate
	* @access private
	*/
	function dispose() {
		$this->close_conn();
	}

	
}

?>
