<?
/**
* @package carl_util
* @subpackage dir_service
* @author Mark Heiman
* 
* A general purpose directory service interface
*/

/**
* Luther LDAP Directory Service -- Interface for access to LDAP directories
* @subpackage dir_service
* @author Mark Heiman
*/

include_once('paths.php');
include_once(CARL_UTIL_INC . '/dir_service/services/ds_ldap.php');

class ds_ldap_luther_alumni extends ds_ldap {

	/**
	* array Connection settings for this service. 
	* @access private
	* @var array
	*/
	var $_conn_params = array(
	  	'host' => 'ldap.luther.edu',
        	'port' => 389,
		'ldap_version' => 3,
		'use_tls' => false,
		'lookup_dn' => 'cn=webauth,dc=luther,dc=edu',
        	'lookup_password' => 'daewoo$friendly$$cow',
		);
	
	/**
	* array Settings for the current search 
	* @access private
	* @var array
	*/
	var $_search_params = array(
		'base_attrs' => array('uid','mail','edupersonprimaryaffiliation','edupersonaffiliation','ds_username'),
		'subtree_search' => true,
		'base_dn' => 'ou=Alumni,dc=luther,dc=edu',
		);

	/**
	* array Dependencies for generic attributes 
	* @access private
	* @var array
	*/
	var $_gen_attr_depend = array(
		'ds_username' => array('uid'),
		'ds_email' => array('mail'),
		'ds_firstname' => array('givenname'),
		'ds_lastname' => array('sn'),
		'ds_fullname' => array('displayname','alumcn'),
		/*  'ds_phone' => array('telephonenumber'),  this returns home phone*/
		'ds_title' => array('title'),
		'ds_phone' => array('officephone'),
		'ds_affiliation' => array('edupersonaffiliation','edupersonprimaryaffiliation'),
		'ds_groupname' => array('cn'),
		'ds_member' => array('memberuid'),
		'ds_owner' => array('creatorsname'),
		);

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
							$value = $record['uid'];
							break;
						case 'ds_email':
							$value = $record['mail'];
							break;
						case 'ds_firstname':
							$value = $record['givenname'];
							break;
						case 'ds_lastname':
							$value = $record['sn'];
							break;
						case 'ds_fullname':
							/*if (!empty($record['displayname']))
								$value = $record['displayname'];
							else*/
								$value = $record['cn'];
							break;
						case 'ds_title':
							$value = $record['title'];
							break;
						/*
						case 'ds_phone':
							$value = $record['telephonenumber'];
							break;
						*/
						case 'ds_phone':
							$value = $record['officephone'];
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
						case 'ds_groupname':
							$value = $record['cn'];
							break;
						case 'ds_member':
							$value = array();
							if (!empty($record['memberuid'])) 
							{
								foreach($record['memberuid'] as $member)
								{
									$value[] = $member;
								}
							}
							break;
						case 'ds_owner':
							$value = array();
							if (!empty($record['creatorsname'])) 
							{
								foreach($record['creatorsname'] as $member)
								{
									if (preg_match('/cn=([^,]+)/i',$member, $matches))
									{
										$value[] = $matches[1];
									}
								}
							}
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
				case 'ds_member':
					$attr = '';
					if (!empty($not_flag))	
						$filter = sprintf('(%s(memberuid%s%s))', $not_flag, $compare, $value);
					else
						$filter = sprintf('(memberuid%s%s)', $compare, $value);
					break;
				case 'ds_owner':
					$attr = '';
					if (!empty($not_flag))	
						$filter = sprintf('(%s(creatorsname%suid=%s,ou=People,dc=luther,dc=edu))', $not_flag, $compare, $value);
					else
						$filter = sprintf('(creatorsname%suid=%s,ou=People,dc=luther,dc=edu))', $compare, $value);
					break;
			}
		}
		return array('filter' => $filter, 'attr' => $attr);
	}

	/**
	* Search for a particular group value
	* @access public
	* @param string $attr Name of the attribute to search
	* @param mixed $qstring string or array of strings to search for
	* @param array $return List of attributes to return
	*/
	function group_attr_search($attr, $qlist, $return = array()) {
		$this->set_search_param('subtree_search', true);
		$this->set_search_param('base_dn', 'dc=luther,dc=edu');
		$this->set_search_param('base_attrs', array('ds_groupname','ds_member','ds_owner'));
		return $this->attr_search($attr, $qlist, $return);
	}
	
	/**
	* Search groups using a provided LDAP-style filter
	* @access public
	* @param string $filter Search filter
	* @param array $return Optional list of attributes to return
	*/
	function group_filter_search($filter, $return=array()) {
		$this->set_search_param('subtree_search', true);
		$this->set_search_param('base_dn', 'dc=luther,dc=edu');
		$this->set_search_param('base_attrs', array('ds_groupname','ds_member','ds_owner'));
		return $this->filter_search($filter, $return);
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
		$bind_dn = sprintf('uid=%s, %s',$this->escape_input($username), $this->_search_params['base_dn']);
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
		
}

?>
