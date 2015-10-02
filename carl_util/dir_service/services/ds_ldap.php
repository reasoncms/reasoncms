<?php
/**
 * A general purpose directory service interface
 * @package carl_util
 * @subpackage dir_service
 * @author Mark Heiman
 */

/**
 * Include parent class and general util functions
 */
include_once('ds_default.php');
include_once(CARL_UTIL_INC.'basic/misc.php');

/**
 * General LDAP Directory Service -- Interface for access to LDAP directories
 * @subpackage dir_service
 * @author Mark Heiman
 */
class ds_ldap extends ds_default {

	/**
	* array Connection settings for this service. 
	* @access private
	* @var array
	*/
	public $_conn_params = array(
	  	'host' => 'ldap.yourhost.edu',
        	'port' => 389,
		'ldap_version' => 3,
		'use_tls' => true,
		'opt_referrals' => 0,
		'lookup_dn' => 'cn=lookupaccount,ou=People,dc=yourhost,dc=edu',
    	'lookup_password' => 'password',
		);
	
	/**
	* array Settings for the current search 
	* @access private
	* @var array
	*/
	public $_search_params = array(
		'base_attrs' => array('ds_username'),
		'subtree_search' => false,
		'base_dn' => 'ou=People,dc=yourhost,dc=edu',
		);
		
	/**
	* array Dependencies for generic attributes 
	* @access private
	* @var array
	* Sample dependencies for Eduperson schemas
	*/
	public $_gen_attr_depend = array(
		'ds_guid' => array('uidnumber'),
		'ds_username' => array('uid'),
		'ds_email' => array('mail'),
		'ds_firstname' => array('givenname'),
		'ds_lastname' => array('sn'),
		'ds_fullname' => array('displayname','givenname','sn','edupersonnickname'),
		'ds_phone' => array('telephonenumber'),
		'ds_affiliation' => array('edupersonaffiliation','edupersonprimaryaffiliation'),
		'ds_groupid' => array('gidnumber'),
		'ds_groupname' => array('cn'),
		'ds_member' => array('memberuid'),
		);

	/**
	* Constructor. Open connection to service here, if appropriate
	* @access private
	*/
	function ds_ldap() {
		$this->init();
		register_shutdown_function(array(&$this, "dispose")); 
	}

	/**
	* Override this if you want to do something before the service starts up
	* @access private
	*/
	function init() {
	}
	
	/**
	* Open connection to service and bind for lookup
	* @access public
	*/
	function open_conn() {
		if ($this->is_conn_open()) $this->close_conn();
		if (!$this->open_connection_prebind()) return false;
		if( !ldap_bind( $this->_conn, $this->_conn_params['lookup_dn'], $this->_conn_params['lookup_password'] ) ) {
			$this->_error = sprintf( 'LDAP bind failed for %s' , $this->_conn_params['lookup_dn']);
			return false;
		}
		return true;
	}
	
	/**
	* Open initial connection without binding
	* @access public
	*/
	function open_connection_prebind() {
		if (!$this->_conn = ldap_connect( $this->_conn_params['host'], $this->_conn_params['port'] )) {
			$this->_error = sprintf('Could not connect to LDAP server %s:%d', $this->_conn_params['host'], $this->_conn_params['port'] );
			return false;
		}
		if (isset($this->_conn_params['ldap_version']))
			ldap_set_option( $this->_conn, LDAP_OPT_PROTOCOL_VERSION, $this->_conn_params['ldap_version'] );
		if (isset($this->_conn_params['opt_referrals']))
			ldap_set_option( $this->_conn, LDAP_OPT_REFERRALS, $this->_conn_params['opt_referrals'] );
		if ($this->_conn_params['use_tls'])
		{
			if (!ldap_start_tls( $this->_conn )) return false;
		}
		return true;	
	}
	
	/**
	* Close connection to service
	* @access public
	*/
	function close_conn() {
		if ($this->is_conn_open()) 
		{
			ldap_close($this->_conn);
			$this->_conn = null;
		}
	}

		
	/**
	* Conduct a search using the values in search_params
	* @access public
	*/
	function search() {
		// ldap_list is faster for single level searches; if you need to descend a directory hierarchy, 
		// use ldap_search instead.
		if (!$this->open_conn()) return false;
		$start = time();
		if ($this->_search_params['subtree_search'])
			$result = @ldap_search($this->_conn, $this->_search_params['base_dn'], $this->_search_params['filter'], $this->_search_params['attrs']);
		else
			$result = @ldap_list($this->_conn, $this->_search_params['base_dn'], $this->_search_params['filter'], $this->_search_params['attrs']);
		$end = time();
		if (($end - $start) > 5) 
		{
			$filter = (strlen($this->_search_params['filter']) < 1000) ? $this->_search_params['filter'] : substr($this->_search_params['filter'], 0, 1000).'...';
			error_log('LDAP_SLOW: Query took '.($end-$start).' seconds: '.$filter);
		}

		if ($result) {
			$return = $this->format_results($result);
			$this->close_conn();
			return $return;
		} else {
			$this->_error = sprintf( 'LDAP search failed for filter %s' , $this->_search_params['filter']);
			$this->close_conn();
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
			if(!empty($qlist))
			{
				// build a search filter for matching against multiple values.
				foreach ($qlist as $val)
					$filter_parts[] = $this->construct_filter('equality',$attr,$this->escape_input($val));
				$this->set_search_param('filter', sprintf('(|%s)',join('',$filter_parts)));
			}
			else
			{
				// if no values are given to match, there will be no results
				return false;
			}
		} else {
			// build a search filter for matching against a single value.
			$this->set_search_param('filter', $this->construct_filter('equality',$attr,$this->escape_input($qlist)));
		}
		// assemble a list of all the attributes that should be returned --
		// anything in the filter, the base list, or explicitly requested
		$involved_attrs = array_unique(array_merge(array(strtolower($attr)),$return,$this->_search_params['base_attrs']));
		$return_attrs = $this->get_dependent_attrs($involved_attrs);
		// ldap_search requires sequential array elements; sort ensures that.
		sort($return_attrs);
		$this->set_search_param('attrs',$return_attrs);
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
		$replaced_tree = $this->replace_generic_attrs($tree);
		$replaced_filter = $this->build_filter_from_tree($replaced_tree);
		$this->set_search_param('filter', $replaced_filter);
		// assemble a list of all the attributes that should be returned --
		// anything in the filter, the base list, or explicitly requested
		$involved_attrs = array_unique(array_merge($return,$this->_search_params['base_attrs']));
		$return_attrs = $this->get_dependent_attrs($involved_attrs);
		// ldap_search requires sequential array elements; sort ensures that.
		sort($return_attrs);
		$this->set_search_param('attrs',$return_attrs);
		if ($results = $this->search()) {
		// add in any generic attributes required
			$augmented_results = $this->add_gen_attrs_to_results($results, $involved_attrs);
			return ($augmented_results);
		} else {
			return false;
		}
	}

	/**
	* Search for a particular group value
	* @access public
	* @param string $attr Name of the attribute to search
	* @param mixed $qstring string or array of strings to search for
	* @param array $return List of attributes to return
	*/
	function group_attr_search($attr, $qlist, $return = array()) {
		return $this->attr_search($attr, $qlist, $return);
	}
	
	/**
	* Search groups using a provided LDAP-style filter
	* @access public
	* @param string $filter Search filter
	* @param array $return Optional list of attributes to return
	*/
	function group_filter_search($filter, $return=array()) {
		return $this->filter_search($filter, $return);
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
		$results = ldap_get_entries( $this->_conn, $result );
		$nice_entries = array();
	
		if (is_array($results))
		{
			// get rid of leading count index
			array_shift( $results );
		
			// loop through all real entries
			foreach( $results AS $entry )
			{
				$nice_entry = array();
				// loop through all attributes of entry
				foreach( $entry AS $attr_key => $attributes )
				{
					// all attributes we want to look at are arrays
					if( is_array( $attributes ) )
					{
						// again, nix the count index
						array_shift( $attributes );
		
						foreach( $attributes AS $value )
							$nice_entry[strtolower($attr_key) ][] = $value;
					}
				}
				$nice_entries[] = $nice_entry;
			}
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
				$value = null;
				if (isset($this->_gen_attr_depend[$attr])) {
					// Provide mappings between all generic attributes and your local attributes.  This
					// will need to be modified for your own particular situation.  These values would work
					// under an EduPerson LDAP schema
					switch ($attr) {
						case 'ds_guid':
							$value = $record['uidnumber'];
							break;
						case 'ds_username':
							$value = $record['uid'];
							break;
						case 'ds_email':
							$value = (isset($record['mail'])) ? $record['mail'] : array();
							break;
						case 'ds_firstname':
							$value = $record['givenname'];
							break;
						case 'ds_lastname':
							$value = $record['sn'];
							break;
						case 'ds_fullname':
							if (!empty($record['edupersonnickname'])) {
								$value = array(0 => $record['edupersonnickname'][0] . ' ' . $record['sn'][0]);
							} else {
								$value = $record['displayname'];
							}
							break;
						case 'ds_phone':
							$value = (isset($record['telephonenumber'])) ? $record['telephonenumber'] : array();
							break;
						case 'ds_affiliation':
							// Create a list of affiliations; the primary affiliation is the first entry
							$value = array();
							if (!empty($record['edupersonprimaryaffiliation'])) 
								$value[0] = $record['edupersonprimaryaffiliation'][0];
							if (!empty($record['edupersonaffiliation'])) 
							{
								foreach($record['edupersonaffiliation'] as $affill)
								{
									if ($affill <> $value[0])
										$value[] = $affill;
								}
							}
							break;
						case 'ds_groupid':
							$value = (isset($record['gidnumber'])) ? $record['gidnumber'] : array();
							break;
						case 'ds_groupname':
							$value = $record['cn'];
							break;
						case 'ds_member':
							$value = array();
							if (!empty($record['memberuid'])) 
							{
								$value = $record['memberuid'];
							}
							break;
					}
					if ($value) $record[$attr] = $value;
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
			case 'equality':
				$not_flag = '';
				$compare = '=';
				break;
			case 'inequality':
				$not_flag = '!';
				$compare = '=';
				break;
			case 'like':
				$not_flag = '';
				$compare = '~=';
				break;
			break;
		}
		
		$mappings = $this->map_generic_attr($attr, $compare, $not_flag, $value);
		$attr = $mappings['attr'];
		
		if (empty($mappings['filter'])) 
			$filter = (empty($not_flag)) ? sprintf('(%s%s%s)', $attr, $compare, $value) : sprintf('(%s(%s%s%s))', $not_flag, $attr, $compare, $value);
		else
			$filter = $mappings['filter'];
		return $filter;
	}

        /**
        * Walk parsed filter tree and apply generic attribute replacement rules as needed 
        * @access public
        * @param array $tree Parsed filter tree
        * @param string $key Optional operator array key (used in recursive calls)
        */
        function replace_generic_attrs($tree)
        {
			foreach ($tree as $key => $val)
			{
				if (is_array(current($val)))
				// if this is not a leaf node
				{
						$tree[$key] = $this->replace_generic_attrs($val);
				}
				else if (isset($val[0]))
				{
				// if this is a leaf node
					$map = $this->map_generic_attr($val[0],$key,'',$val[1]);
					// If the map passes back an attribute name, do a simple substitution
					if (!empty($map['attr']))
						$tree[$key] = array($map['attr'],$val[1]); 
					// If the map passes back a filter,
					if (!empty($map['filter']))
					{
						// parse the filter into a tree
						$parse_result = $this->parse_filter($map['filter']);
						// then tack the new subtree onto the full filter in place of the old leaf node.
						foreach ($parse_result as $repkey=>$subtree)
						{
							$tree[$repkey] = $subtree;
							// The new subtree may have a different operator key than the old one
							if ($repkey != $key) unset ($tree[$key]);
						}
					}
				}
				else
				{
					trigger_error('Invalid node format for '.$key.'=>'.json_encode($val).' in replace_generic_attrs()');
				}

			}
		return $tree;
        }
	
	
	/**
	* Map generic attribute to real attributes 
	* @access public
	* @param string $attr Name of attribute
	*/
	function map_generic_attr($attr, $compare, $not_flag, $value) {
		// Provide special actions for generic attributes.  This
		// will need to be modified for your own particular situation.
		// The values might work under an EduPerson LDAP schema
		// If you just need to replace one attribute with another, redefine
		// $attr; otherwise, define $filter to be the full filter to return.
		$filter = '';
		if (isset($this->_gen_attr_depend[$attr])) {
			switch ($attr) {
				case 'ds_guid':
					$attr = 'uidnumber';
					break;
				case 'ds_username':
					$attr = 'uid';
					break;
				case 'ds_email':
					$attr = 'mail';
					break;
				case 'ds_firstname':
					$attr = 'givenname';
					break;
				case 'ds_lastname':
					$attr = 'sn';
					break;
				case 'ds_fullname':
					$attr = 'displayname';
					break;
				case 'ds_affiliation':
					$attr = 'edupersonaffiliation';
					break;
				case 'ds_groupname':
					$attr = 'cn';
					break;
				case 'ds_groupid':
					$attr = 'gidnumber';
					break;
				case 'ds_member':
					$attr = 'memberuid';
					break;
			}
		}
		return array('filter' => $filter, 'attr' => $attr);
	}
	
	/**
	* Apply escaping to special characters in search values. 
	* @access private
	* @param string $value User provided value
	*/
	function escape_input($value) {
		return ldap_escape($value);
	}
	
	/**
	* Validate username and password 
	* @access public
	* @param string $username Userid
	* @param string $password Password
	*/
	function authenticate($username, $password) {
		if (!$this->open_connection_prebind()) return false;
		// You'd need to construct an appropriate bind dn for your server here
		$bind_dn = sprintf('bind_id=%s, %s',$this->escape_input($username), $this->_search_params['base_dn']);
		turn_carl_util_error_logging_off();
		$bind_result = @ldap_bind($this->_conn, $bind_dn, $password);
		turn_carl_util_error_logging_on();
		if (!$bind_result) $this->_error = ldap_error( $this->_conn );
		// Rebind for future searches
		if( !ldap_bind( $this->_conn, $this->_conn_params['lookup_dn'], $this->_conn_params['lookup_password'] ) ) {
			$this->_error = sprintf( 'LDAP bind failed for %s, %s' , $this->_conn_params['lookup_dn'], ldap_error( $this->_conn ));
		}
		$this->close_conn();
		return $bind_result;
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
