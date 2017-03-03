<?php

require_once( CARL_UTIL_INC . 'dir_service/directory.php' );
require_once( CARL_UTIL_INC . 'basic/misc.php' );

/**
 * Take mixed username/netids and email addresses and resolve into "clean" array of good-looking email addresses
 *
 * By good-looking we mean that they satisfy this regex: /^([^<]+<)?([-.]|\w)+@([-.]|\w)+\.([-.]|\w)+>?$/i
 *
 * This function works as follows: For each address, first treats
 * the address as a netid and tries to find a corresponding
 * address in the directory (if this fails, it assumes that the address
 * was intended as an address rather than a username); second, checks
 * whether the address is valid
 * 
 * If the address is invalid, the webmaster is included in the recipient list and an error is triggered
 *
 * @param mixed $addresses can be any of the following: 1) a valid email address, 2) a username in the directory, 3) a comma-delimited combination of addresses and/or usernames, or 4) an array of addresses and/or usernames.
 * @param string $address_type can be 'mixed', 'email', or 'username'
 * @param string $return_type either 'string' or 'array'
 * @return mixed Comma separated email addresses or array of email addresses (when $return_type == 'array') 
 *
 **/
function prettify_email_addresses($addresses, $address_type = 'mixed', $return_type = 'string')
{
	if($address_type != 'mixed' && $address_type != 'email' && $address_type != 'username')
	{
		trigger_error('$address_type parameter ('.$address_type.') must be "mixed","email", or "username." Defaulting to "mixed".');
		$address_type = 'mixed';
	}
	if ( !is_array($addresses) )
		$addresses = explode(',', $addresses);
	$pretty_address_array = array();
	foreach ( $addresses as $address )
	{
		$address = trim($address);
		if ( !empty($address) )
		{
			if($address_type != 'email')
			{
				$dir = new directory_service();
				$result = $dir->search_by_attribute('ds_username', $address, array('ds_email'));
				$dir_value = $dir->get_first_value('ds_email');
				if($address_type == 'username')
				{
					if(empty($dir_value))
					{
						trigger_error('Username does not exist in directory service: '.$address.'. setting address to ' . WEBMASTER_EMAIL_ADDRESS . ' instead.');
						$address = WEBMASTER_EMAIL_ADDRESS;
					}
					else
					{
						$address = $dir_value;
					}
				}
				else // mixed or other value
				{
					$address = (!empty($dir_value)) ? $dir_value : $address;
				}

			}

			$num_results = preg_match( '/^([^<]+<)?([-.]|\w)+@([-.]|\w)+\.([-.]|\w)+>?$/i', $address );
			if ($num_results <= 0)
			{
				trigger_error('The address ' . $address . ' is invalid - setting address to ' . WEBMASTER_EMAIL_ADDRESS . ' instead.');
				$pretty_address_array[] = WEBMASTER_EMAIL_ADDRESS;
			}
			else
			{
				$pretty_address_array[] = $address;
			}
		}
	}
	
	if ($return_type == 'string') {
		return implode(', ', $pretty_address_array);
	} else {
		return $pretty_address_array;
	}
}