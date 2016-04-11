<?php
/**
 * A general purpose directory service interface
 *
 * @package carl_util
 * @subpackage dir_service
 * @author Mark Heiman
 */

/**
 * Default Directory Service -- Stub interface from which other services inherit
 * @author Mark Heiman
 */
class ds_default {
	
	/**
	* string Path to settings file for this service.
	* @access private
	* @var string
	*/
	var $_conn_settings_file = '';
	
	/**
	* array Settings for the current search 
	* @access private
	* @var array
	*/
	var $_search_params = array();
		
	/**
	* array Dependencies for generic attributes 
	* @access private
	* @var array
	*/
	var $_gen_attr_depend = array();

	/**
	* integer Resource link to service connection
	* @access private
	* @var integer
	*/
	var $_conn;

	/**
	* string Last error message
	* @access private
	* @var string
	*/
	var $_error;

	/**
	* Constructor. Open connection to service here, if appropriate
	* @access private
	* @param string $attr Name of the attribute to search
	* @param string $qstring string to search for
	*/
	function ds_default() {
		$this->open_conn();
		register_shutdown_function(array(&$this, "dispose")); 
	}

	/**
	* Open connection to service
	* @access public
	*/
	function open_conn() {
	}
	
	/**
	* Check to see whether a connection is open to the service
	* @access public
	*/
	function is_conn_open() {
		return($this->_conn > 0);
	}

	/**
	* Close connection to service
	* @access public
	*/
	function close_conn() {
	}

	/**
	* Change the value of one of the connection parameters
	* @access public
	* @param string $param Name of the parameter to set
	* @param string $value New value for parameter
	*/
	function set_conn_param($param, $value) {
		$this->_conn_params[$param] = $value;
	}
	
	/**
	* Change the value of one of the search parameters
	* @access public
	* @param string $param Name of the parameter to set
	* @param string $value New value for parameter
	*/
	function set_search_param($param, $value) {
		$this->_search_params[$param] = $value;
	}

	/**
	* Search for a particular value
	* @access public
	* @param string $attr Name of the attribute to search
	* @param string $qstring string to search for
	* @param array $return Optional list of attributes to return
	*/
	function attr_search($attr, $qstring, $return=array()) {
		return false;
	}
	
	/**
	* Search using a provided LDAP-style filter
	* @access public
	* @param string $filter Search filter
	* @param array $return Optional list of attributes to return
	*/
	function filter_search($filter, $return=array()) {
		return false;
	}
	
	/**
	* Search for a particular group value
	* @access public
	* @param string $attr Name of the attribute to search
	* @param mixed $qstring string or array of strings to search for
	* @param array $return List of attributes to return
	*/
	function group_attr_search($attr, $qlist, $return = array()) {
		return false;
	}
	
	/**
	* Search groups using a provided LDAP-style filter
	* @access public
	* @param string $filter Search filter
	* @param array $return Optional list of attributes to return
	*/
	function group_filter_search($filter, $return=array()) {
		return false;
	}

	/**
	* Conduct a search using the values in search_params
	* @access public
	*/
	function search() {
		return false;
	}
	
	/**
	* Given a list of attributes, determine if any of them are generic and return a list
	* with the generic attributes replaced by the real attributes they depend on.
	* @access public
	* @param array $return List of attributes to check
	*/
	function get_dependent_attrs($attrs=array()) {
		$dependencies = array();
		foreach ($attrs as $key => $attr) {
			if (isset($this->_gen_attr_depend[$attr])) {
				// remove the generic attribute from the list
				unset ($attrs[$key]);
				// add the dependent attribute(s) to another list
				$dependencies = array_merge($dependencies,$this->_gen_attr_depend[$attr]);
			}
		}
		// merge the remaining real attributes with the list of dependent attributes
		
		return array_unique(array_merge($dependencies, $attrs));
	}

	/**
	* Parse an LDAP-style filter into a parse tree of operators and values. May be used to
	* transform filters into equivalent queries for other systems. Invalid filters may return
	* unexpected results.
	* @access public
	* @param string $filter Search filter
	*/
	function parse_filter($filter) {
		// replace any newlines and tabs; allows us to use pretty syntax
		$filter = str_replace(array("\r\n","\n","\r","\t"), '', $filter);
		// strip any outside parens if present
		if (preg_match('/^\s*\(\s*(.+?)\s*\)\s*$/x', $filter, $matches)) 
			$filter = $matches[1];
		// if this is the inner most layer of nesting (x=y) return the elements
		if (preg_match('/^\s*([^!\|&]+?)\s*([~<>]?=)\s*(.+?)\s*$/x', $filter, $matches))
			return array($matches[2] => array($matches[1], $matches[3]));
		// if this is a not/or/and construct, strip and save the operator
		if (preg_match('/^\s*([!\|&])\s*(.+?)\s*$/x', $filter, $matches)) {
			$operator = $matches[1];
			$filter = $matches[2];
		}
	    // Split the filter on open and close parenthesis characters. 
	    $parts = preg_split('/[\(\)]/', $filter);
	    // generate a list of all parenthesis positions in the filter
	    preg_match_all('/[\(\)]/', $filter, $paren);
	    
	    // use a variable 'stack' to keep track of what level of nested parens we're at. 
	    $stack = 0;
	    $start_index = 0;
	    $level = array();
	    // If there are parens, search for the outmost set.
	    if (sizeof($paren)) {
		for ($i = 0; $i < sizeof($paren[0]); $i++) {
		    // Everytime we find an open parenthesis we need to increment the stack variable.
		    // If the stack is at its lowest level, save the index of the open parenthesis so we know where to
		    // combine again when we find the matching close parenthesis.
		    if ($paren[0][$i] == '(') {
			if ($stack == 0) { $start_index = $i; }
			$stack++;
		    }
		    // If we hit a close parenthesis character, we need to decrement the stack. If the stack value reaches
		    // 0 again, this means we've hit a top level parenthesis and we need to recursively call the function
		    // and tell it to evaluate the inner expression.
		    elseif ($paren[0][$i] == ')') {
			$stack--;
			if ($stack == 0) {
			    $sub = '';
			    // Here we recombine the inside equation. Since this can also include nested parens, we need
			    // to take care of that.
			    for ($j = $start_index+1; $j <= $i; $j++) {
				$sub .= $parts[$j];
				if ($j != $i) {
				    $sub .= $paren[0][$j];
				}
			    }
			    // Call the function recursively on the subfilter, and add the result to our parse tree.
			    $level[$operator][] = $this->parse_filter( $sub );
			}
		    }
		}
	    }
	    return $level;
	}

	/**
	* Assemble parsed filter tree back into LDAP-style filter
	* @access public
	* @param array $tree Parsed filter tree
	*/
	function build_filter_from_tree($tree)
	{
		$filter = '';
		foreach ($tree as $key => $val)
		{
			if (is_array(current($val)))
			// if this is not a leaf node
			{
				// if it has a unary operator, add parens
				if (!is_numeric($key)) $filter .= '('.$key;
				$filter .= $this->build_filter_from_tree($val);
				if (!is_numeric($key)) $filter .=  ')';
			}
			else if (isset($val[0]))
			{
				// if this is a leaf node
				$filter .= "($val[0]$key$val[1])";
			}
			else
			{
				trigger_error('Invalid node format for '.$key.'=>'.json_encode($val).' in build_filter_from_tree()');
			}
		}
		return $filter;
	}

	/**
	* Put query results into common format for return.
	* @access private
	* @param mixed $results Raw results from service
	* 
	* Return structure is:
	* Array[]
	*   Record_n[]
	*     Attribute_n[]
	*       Value_n
	* There may be multiple records, multiple attributes per record, and multiple values per attribute.
	* You must return an array even if your provider only stores single values.
	*/
	function format_results($results) {
		return array();
	}
	
	/**
	* Given a formatted set of results, blend in the generic attributes present in the 
	* provided attribute list (which may contain non-generic attributes, which are ignored)
	* @access public
	* @param array $results array of results produced by format_results
	* @param array $attr List of attributes, some of which may be generic
	*/
	function add_gen_attrs_to_results($results,$attrs) {
		return $results;
	}

	/**
	* Validate username and password 
	* @access public
	* @param string $username Userid
	* @param string $password Password
	*/
	function authenticate($username, $password) {
		return false;
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
