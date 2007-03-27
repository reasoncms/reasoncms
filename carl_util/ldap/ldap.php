<?php
	/**
	 *  Given an OU, find the long OU.  This is to address a change in LDAP that is storing both the long and short form
	 *  of OUs.
	 */
	function ldap_get_long_ou( $ous )
	{
		$ret = $ous;
		if( is_array( $ous ) AND count( $ous ) > 1 )
		{
			$found = false;
			foreach( $ous AS $ou )
			{
				if( is_mixed_case( $ou ) )
				{
					$ret = $ou;
					$found = true;
					break;
				}
			}
			// fail safe is there no mixed case department OU
			if( !$found )
			{
				reset( $ous );
				$ret = current( $ous );
			}
		}
		return $ret;
	}
	
	
	class LDAPHelper
	{
		var $ldap_connection_settings_file = '/usr/local/etc/php3/global_php_settings.php';
		var $filter = '';
		var $base_dn = 'ou=people,dc=carleton,dc=edu';

		var $_all_fields = array('carlnetid','ou','cn','sn','givenName','eduPersonNickname','mail','title',
				'eduPersonPrimaryAffiliation','carlOfficeLocation','carlCampusPostalAddress','telephoneNumber','carlSpouse',
				'homePostalAddress', 'carlStudentPermanentAddress', 'homePhone', 'carlMajor', 'carlConcentration', 'eduPersonPrimaryAffiliation',
				'eduPersonAffiliation','carlStudentStatus','carlGraduationYear','carlCohortYear','carlHomeEmail');
		var $_dept_fields = array('ou','description','telephonenumber','facsimiletelephonenumber');
		var $_dept_base_dn = 'dc=carleton,dc=edu';
		var $prospie_base_dn = 'ou=prospects,dc=carleton,dc=edu';
		
		function connect() // {{{
		{
			// Includes $ldap_lookup_host, $ldap_lookup_port, $ldap_lookup_username, $ldap_lookup_password
			include($this->ldap_connection_settings_file);
			$this->_ldap_conn = ldap_connect( $ldap_lookup_host, $ldap_lookup_port ) OR die( 'Could not connect to LDAP server' );
			ldap_set_option( $this->_ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3 );
			ldap_start_tls( $this->_ldap_conn );
			if( !ldap_bind( $this->_ldap_conn, 'cn='.$ldap_lookup_username.', ou=People, dc=carleton, dc=edu', $ldap_lookup_password ) )
				die( 'LDAP bind failed.' );
		} // }}}
		function search() // {{{
		{
			if( empty( $this->fields ) )
				$this->fields =& $this->_all_fields;

			$this->_result = ldap_search($this->_ldap_conn, $this->base_dn, $this->filter, $this->fields );
		} // }}}
		function get_entries() // {{{
		{
			$this->_entries = ldap_get_entries( $this->_ldap_conn, $this->_result );
			$nice_entries = array();
		
			// get rid of leading count index
			array_shift( $this->_entries );

			// loop through all real entries
			foreach( $this->_entries AS $entry )
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

						// see how many results there are
						if( count( $attributes ) <= 1 )
							$nice_entry[ $attr_key ] = $attributes[0];
						else
							foreach( $attributes AS $value )
								$nice_entry[ $attr_key ][] = $value;
					}
				}
				$nice_entries[] = $nice_entry;
			}

			return $nice_entries;
		} // }}}
		function close() // {{{
		{
			ldap_close( $this->_ldap_conn );
		} // }}}
		function search_dept($name, $code = '') // {{{
		{
			if( empty( $this->fields ) )
				$this->fields =& $this->_dept_fields;
			if (!empty($name))
				$filter = '(&(objectClass=organizationalUnit)(businessCategory=*)(ou='.$name.'))';
			elseif (!empty($code))
				$filter = '(&(objectClass=organizationalUnit)(businessCategory=*)(description='.$code.'))';
			else
				$filter = '(&(objectClass=organizationalUnit)(businessCategory=*))';
				
			$this->_result = ldap_search($this->_ldap_conn, $this->_dept_base_dn, $filter, $this->fields );
		} // }}}
		
		function search_prospies()
		{
			$this->_result = ldap_search($this->_ldap_conn, $this->prospie_base_dn, $this->filter , $this->_all_fields );
		}
		function change_base_dn( $dn )
		{
			$this->base_dn = $dn;
		}

	}
?>
