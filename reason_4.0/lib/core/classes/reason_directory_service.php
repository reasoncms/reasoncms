<?php
/**
 * A directory service interface for Reason
 * @package reason
 * @subpackage classes
 */

/**
 * Include reason and directory service libraries
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
require_once(CARL_UTIL_INC.'dir_service/services/ds_default.php');

/**
* Reason Directory Service -- Interface for access to directory info in Reason
*
* This is essentially a tool that makes Reason act like an LDAP server
* It translates LDAP-style filters into Reason DB queries and returns an array of 
* data in the standard form expected by the directory service code
* @author Matt Ryan
*/
class ds_reason extends ds_default {
		
	/**
	* Generic attributes to fields in Reason to be queried
	* @access private
	* @var array
	*/
	var $_gen_attr_depend = array(
		'ds_guid' => array('entity.id'),
		'ds_username' => array('entity.name'),
		'ds_email' => array('user_email'),
		'ds_firstname' => array('user_given_name'),
		'ds_lastname' => array('user_surname'),
		'ds_fullname' => array('user_given_name','user_surname'),
		'ds_affiliation' => array('audience_integration.directory_service_value'),
		);
	/**
	* Generic attributes to returned keys
	* @access private
	* @var array
	*/
	var $_returned_vals_map = array(
		'ds_guid' => array('id'),
		'ds_username' => array('name'),
		'ds_email' => array('user_email'),
		'ds_firstname' => array('user_given_name'),
		'ds_lastname' => array('user_surname'),
		'ds_fullname' => array('user_given_name','user_surname'),
		'ds_affiliation' => array('ds_affiliation'),
		);
	/**
	 * Fields whose value shoud not be passed around
	 *
	 * Primarily for the passowrd hash, this array allows any field to be marked as "taboo"
	 * and therefore not allowed to be queried against or returned in a result set.
	 * @var array
	 */
	var $taboo_fields = array('user_password_hash');
	/**
	 * The name of the previous connection
	 *
	 * This allows this class to return to the previous connection when it is shut down
	 * @var string
	 */
	var $prev_connection_name = '';
	
	/**
	* Constructor. Open connection to service here, if appropriate
	* @access private
	*/
	function ds_reason() {
		
	}
	
	/**
	* Returns an array of field names that are OK to query against
	* @return array
	*/
	function get_ok_fieldnames()
	{
		static $ok = array();
		static $query_made = false;
		
		if(!$query_made)
		{
			$this->open_conn();
			foreach(get_entity_tables_by_type( id_of('user') ) as $table )
			{
				foreach(get_fields_by_content_table( $table ) as $field)
				{
					if(!in_array($field,$this->taboo_fields) && !in_array($field,$ok))
						$ok[] = $field;
				}
			}
			$this->close_conn();
			foreach($this->_gen_attr_depend as $fields_to_be_queried)
			{
				foreach($fields_to_be_queried as $field)
				{
					if(!in_array($field,$this->taboo_fields) && !in_array($field,$ok))
						$ok[] = $field;
				}
			}
			$query_made = true;
		}
		
		return $ok;
	}
	
	function get_audiences()
	{
		static $audiences = array();
		static $query_made = false;
		if(!$query_made)
		{
			$this->open_conn();
			$es = new entity_selector();
			$auds = $es->run_one(id_of('audience_type'));
			foreach($auds as $aud)
			{
				$audiences[$aud->get_value('directory_service_value')] = $aud->id();
			}
			$query_made = true;
			$this->close_conn();
		}
		return $audiences;
	}
	
	/**
	* Open connection to service
	* @access public
	*/
	function open_conn() {
		$this->prev_connection_name = get_current_db_connection_name();
		if(connectDB(REASON_DB))
		{
			return true;
		}
		return false;
	}
	
	/**
	* Close connection to service
	* @access public
	*/
	function close_conn() {
		// mysql_close doesn't always do what you expect (see the PHP docs) thus this solution.
		if(!empty($this->prev_connection_name) && $this->prev_connection_name != REASON_DB)
		{
			connectDB($this->prev_connection_name);
		}
	}
	
	/**
	* Search for a particular value
	*
	* @access public
	* @param string $attr Name of the attribute to search
	* @param mixed $qstring string or array of strings to search for
	* @param array $return List of attributes to return
	* @return mixed an array of data if results, false if no results
	*/
	function attr_search($attr, $qlist, $return = array()) {
		if (is_array($qlist)) {
			if(!empty($qlist))
			{
				// build a search filter for matching against multiple values.
				foreach ($qlist as $val)
					$filter_parts[] = $this->construct_filter('equality',$attr,$this->escape_input($val));
				$filter = '('.join(' OR ', $filter_parts).')';
			}
			else
			{
				// no results should be returned if empty array given as second argument
				return false;
			}
		} else {
			// build a search filter for matching against a single value.
			$filter = $this->construct_filter('equality',$attr,$this->escape_input($qlist));
		}
		
		$this->open_conn();
		$es = new entity_selector();
		$es->add_type(id_of('user'));
		$es->add_relation($filter);
		$es->add_relation($this->get_basic_limitation());
		if($attr == 'ds_affiliation' || in_array('ds_affiliation', $return))
		{
			$es->add_left_relationship_field('user_to_audience','audience_integration','directory_service_value','ds_affiliation');
			$es->enable_multivalue_results();
		}
		$results_entities = $es->run_one();
		$this->close_conn();
		
		$results = $this->reason_entities_to_array($results_entities);
		if (!empty($results))
		{
		// add in any generic attributes required
			$augmented_results = $this->add_gen_attrs_to_results($results, array_keys($this->_gen_attr_depend));
			return ($augmented_results);
		} else {
			return false;
		}
	}
	/**
	 * Builds a SQL WHERE chunk that all queries against the Reason userbase should include
	 *
	 * This will ensure that only Reason users considered authoritative will be included in results
	 * and in authentication
	 * @return string
	 */
	function get_basic_limitation()
	{
		if(REASON_USERS_DEFAULT_TO_AUTHORITATIVE)
		{
			return '(user.user_authoritative_source = "reason" OR user.user_authoritative_source = "" OR user.user_authoritative_source IS NULL)';
		}
		else
		{
			return '(user.user_authoritative_source = "reason")';
		}
	}
	
	/**
	 * Transforms an array of Reason entities into an array in the style of the directory service
	 * @return array
	 */
	function reason_entities_to_array($entities)
	{
		$ret = array();
		$this->open_conn();
		foreach($entities as $ent)
		{
			$ret[$ent->id()] = array();
			foreach($ent->get_values() as $field=>$val)
			{
				if(!in_array($field,$this->taboo_fields))
				{
					if(!is_array($val))
						$ret[$ent->id()][$field] = array($val);
					else
						$ret[$ent->id()][$field] = $val;
				}
			}
		}
		$this->close_conn();
		return $ret;
	}
	
	/**
	* Search using a provided LDAP-style filter
	* @access public
	* @param string $filter Search filter
	* @param array $return Optional list of attributes to return
	* @return mixed an array of data if results, false if no results
	*/
	function filter_search($filter, $return=array()) {
		$tree = $this->parse_filter($filter);
		$es = new entity_selector();
		$es->add_relation($this->get_basic_limitation());
		$es->add_relation($this->filter_to_sql($tree));
		$this->open_conn();
		$es->add_type(id_of('user'));
		
		if($return == 'ds_affiliation' || in_array('ds_affiliation', $return))
		{
			$es->add_left_relationship_field('user_to_audience','audience_integration','directory_service_value','ds_affiliation');
			$es->enable_multivalue_results();
		}
		
		$results_entities = $es->run_one();
		$this->close_conn();
		
		$results = $this->reason_entities_to_array($results_entities);

		if (!empty($results)) {
		// add in any generic attributes required
			$augmented_results = $this->add_gen_attrs_to_results($results, array_keys($this->_gen_attr_depend));
			return ($augmented_results);
		} else {
			return false;
		}
	}

	/**
	* Convert a parsed LDAP-style filter into a valid SQL WHERE clause.
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
	* Given a formatted set of results, blend in the generic attributes present in the 
	* provided attribute list (which may contain non-generic attributes, which are ignored)
	* @access public
	* @param array $results array of results produced by format_results
	* @param array $attr List of attributes, some of which may be generic
	* @return array
	*/
	function add_gen_attrs_to_results($results,$attrs) {	
		foreach ($results as $key=>$record) {
			foreach ($attrs as $attr) {
				if (isset($this->_returned_vals_map[$attr])) {
					$value = array();
					if(count($this->_returned_vals_map[$attr]) == 1)
					{
						if(isset($record[current($this->_returned_vals_map[$attr])]))
							$value = $record[current($this->_returned_vals_map[$attr])];
					}
					else
					{
						switch ($attr) {
							case 'ds_fullname':
								$value = array(sprintf('%s %s', $record['user_given_name'][0], $record['user_surname'][0]));
								break;
						}
					}
					$results[$key][$attr] = $value;
				}
			}
			
		}
		foreach($results as $key=>$result)
		{
			$new_results[] = $result;
		}
		return $new_results;
	}
	
	/**
	* Create a valid search filter, taking into account generic attribute requirements 
	* @access public
	* @param string $type Type of comparison (equality, inequality, like)
	* @param string $attr Name of attribute
	* @param string $value Value to compare against
	* @return string
	*/
	function construct_filter($type,$attr,$value) {	
		$negative = false;
		switch ($type) {
			case 'equality':
			case '=':
				$type = 'equality';
				$not_flag = '';
				$compare = '=';
				break;
			case 'inequality':
			case '!=':
				$type = 'inequality';
				$not_flag = '';
				$compare = '!=';
				$negative = true;
				break;
			case 'like':
			case 'LIKE':
			case '~=':
				$type = 'like';
				$not_flag = '';
				$compare = 'LIKE';
				$value = '%'.$value.'%';
				break;
			default:
				$not_flag = '';
				$compare = $type;
				break;
			break;
		}
		// If the filter contains wildcards, force a LIKE with appropriate syntax.
		if (strpos($value, '*') !== false)
		{
			$value = str_replace('*','%',$value);
			if($type == 'inequality')
				$compare = 'NOT LIKE';
			else
			{
				$compare = 'LIKE';
				$negative = true;
			}
			$type = 'like';
		}
		
		// Provide special actions for generic attributes.  This
		// will need to be modified for your own particular situation.
		// If you just need to replace one attribute with another, redefine
		// $attr; otherwise, define $filter to be the full filter to return.
		
		if (isset($this->_gen_attr_depend[$attr]))
		{
			switch ($attr)
			{
				case 'ds_fullname':
					$filter = sprintf('%s (CONCAT(user_given_name," ",user_surname) %s "%s")', $not_flag, $compare, $this->escape_input($value));
					break;
				case 'ds_affiliation':
					
					$es = new entity_selector();
					//echo current($this->_gen_attr_depend[$attr]).' '.$compare.' '.$value;
					$es->add_relation(current($this->_gen_attr_depend[$attr]).' '.$compare.' "'.$this->escape_input($value).'"');
					$this->open_conn();
					$es->add_left_relationship_field('user_to_audience','audience_integration','directory_service_value','ds_affiliation');
					$users = $es->run_one(id_of('user'));
					$this->close_conn();
					if(!empty($users))
					{
						$filter = 'entity.id '.$not_flag.' IN ('.implode(',',array_keys($users)).')';
					}
					else
					{
						$filter = '1 = 2';
					}
					break;
				default:
					if(count($this->_gen_attr_depend[$attr]) == 1)
					{
						$attr = reset($this->_gen_attr_depend[$attr]);
					}
			}
		}
		if (!isset($filter))
		{
			$ok = $this->get_ok_fieldnames();
			// since SQL behaves differently from LDAP when unknown attributes/columns are asked for,
			// we need to replace unknown selectors with an elegant failure equivalent, e.g. 1=2.
			if(!in_array($attr,$ok))
			{
				//trigger_error($attr.' is not a recognized attribute');
				return '( 1 = 2 )';
			}
			$filter = (empty($not_flag)) ? sprintf('%s %s "%s"', $attr, $compare, $this->escape_input($value)) : sprintf('%s (%s %s "%s")', $not_flag, $attr, $compare, $this->escape_input($value));
		}
		return $filter;
	}
	
	/**
	* Apply escaping to special characters in search values. 
	* @access private
	* @param string $value User provided value
	* @return string
	*/
	function escape_input($value) {
		return reason_sql_string_escape(ldap_unescape($value));
	}
	
	/**
	* Validate username and password 
	* @access public
	* @param string $username Userid
	* @param string $password Password
	* @return boolean
	*/
	function authenticate($username, $password)
	{
		settype($username, 'string');
		settype($password, 'string');
		if(!empty($username) && !empty($password))
		{
			$es = new entity_selector();
			$es->add_relation('entity.name = "'.reason_sql_string_escape($username).'"');
			$es->add_relation('user.user_password_hash = "'.sha1($password).'"');
			$es->add_relation($this->get_basic_limitation());
			$es->set_num(1);
			$this->open_conn();
			$users = $es->run_one(id_of('user'));
			$this->close_conn();
			if(!empty($users))
			{
				return true;
			}
		}
		return false;
	}
}

?>
