<?php
/**
 * A class for geocoding addresses with result caching.
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'cache/object_cache.php');

/**
 * A class for geocoding addresses (or IPs) with result caching.
 *
 * This class relies on the Google Geocoding API (http://code.google.com/apis/maps/documentation/geocoding/) 
 * but has been designed so that you can fairly easily adapt it to use another service without breaking
 * any code that relies on it.
 *
 * Example 1: Geocode an address
 *
 * <code>
 * $gc = new geocoder();
 * $gc->set_address('123 Anystreet, Anycity, Anystate');
 * $geocode = $gc->get_geocode();
 * </code>
 *
 * Example 2: Geocode an IP
 *
 * <code>
 * $gc = new geocoder();
 * $gc->set_address_from_ip('1.1.1.1');
 * $geocode = $gc->get_geocode();
 * </code>
 *
 * Example 3: Geocode a point
 *
 *
 *
 *
 * @author Mark Heiman
 * @author Nathan White
 */	
class geocoder
{
	var $location;
	var $extra_params = array();
	var $raw_query_results;
	var $query_results;
	var $last_request_time = 0;
	
	/**
	 * You may optionally provide a configured ObjectCache object to use for the cache.
	 *
	 * @param object ObjectCache
	 */
	var $cache;
	
	function geocoder($location = '')
	{
		if ($location) $this->set_location($location);
	}
	
	/**
	 * @param object ObjectCache
	 */
	function set_cache($object_cache)
	{
		$this->cache = $object_cache;
	}
	
	function set_address($address)
	{
		$this->set_location($address);	
	}

	/**
	 * Will attempt to set the address to use from an ip address - we use one of two services for this:
	 *
	 * - api.ipinfodb.com
	 * - api.hostip.info
	 *
	 * We only will try each service for a maximum of 5 seconds. We only cache the result if we get a response,
	 * though we keep track of attempted ips so we don't try an ip repeatedly if the service is down.
	 *
	 * @todo ip6?
	 * @return mixed array of values or false on failure 
	 */
	function set_address_from_ip($ip)
	{
		static $attempted_ips;
		if (!empty($ip))
		{
			// do we have a cached address for this IP already?
			$cache_key = md5('ip_to_address_cache_'.$ip);
			$cache = $this->get_cache();
			$cache->init($cache_key);
			$address = $cache->fetch();
			if ($address === FALSE)
			{
				if (!isset($attempted_ips[$ip]))
				{
					if (defined("REASON_IPINFODB_API_KEY") && constant("REASON_IPINFODB_API_KEY"))
					{
						$request = 'http://api.ipinfodb.com/v3/ip-city/?key='.REASON_IPINFODB_API_KEY.'&ip='.urlencode($ip).'&format=json';
						$response = carl_util_get_url_contents($request, false, '', '', 5); // must finish in 5 seconds or we move on
						if ($response && ($decoded = json_decode($response, true)))
						{
							$pieces = array();
							$address = array();
							if (!empty($decoded['cityName'])) $address['city'] = ucwords(strtolower($decoded['cityName']));
							if (!empty($decoded['regionName'])) $address['region'] = ucwords(strtolower($decoded['regionName']));
							if (!empty($decoded['countryName'])) $address['country'] = ucwords(strtolower($decoded['countryName']));
							if (!empty($decoded['zipCode'])) $address['postal_code'] = $decoded['zipCode'];
							if (!empty($decoded['latitude'])) $address['geocoord']['lat'] = $decoded['latitude'];
							if (!empty($decoded['longitude'])) $address['geocoord']['lon'] = $decoded['longitude'];
						}
					}
					
					if (empty($address)) // fall back to the free service api.hostip.info if the address was not meaningfully populated
					{
						$request = 'http://api.hostip.info/?position=true&ip='.urlencode($ip);
						$response = carl_util_get_url_contents($request, false, '', '', 5); // must finish in 5 seconds or we move on
						// api.hostip.info returns XML - let's parse with simplexml
						if ($response && ($xml = simplexml_load_string($response)))
						{
							$address = array();
							$citystate = $xml->children('gml', TRUE)->featureMember->children('', TRUE)->Hostip->children('gml', TRUE)->name;
							$country = $xml->children('gml', TRUE)->featureMember->children('', TRUE)->Hostip->countryName;
							if ($xml->children('gml', TRUE)->featureMember->children('', TRUE)->Hostip->ipLocation)
								$coord = $xml->children('gml', TRUE)->featureMember->children('', TRUE)->Hostip->ipLocation->children('gml', TRUE)->pointProperty->children('gml', TRUE)->Point->children('gml', TRUE)->coordinates;
							// city typically returns "City, State/Region"
							if (strtolower($citystate) != '(unknown city)')
							{
								$cityparts = preg_split('/,\s+/', $citystate, 2);
								$address['city'] = $cityparts[0];
								if (isset($cityparts[1])) $address['region'] = $cityparts[1];
							}
							if ($country) $address['country'] = ucwords(strtolower($country));
							// coord looks like -88.4588,41.7696
							if (isset($coord)) 
							{
								$coords = preg_split('/[,\s]+/', $coord, 2);
								if (count($coords) == 2)
								{
									$address['geocoord']['lat']= $coords[0];
									$address['geocoord']['lon']= $coords[1];
									
								}
							}
						}
					}
					if (empty($address))
					{
						trigger_error('The geocoder could not determine an address for IP ' . $ip);
					}
					else
					{
						$cache->set($address);
					}
					$attempted_ips[$ip] = $address;
				}
				else
				{
					$address = $attempted_ips[$ip];
				}
			}
			if (count($address)) 
			{
				$parts = array();
				if (isset($address['city'])) $parts[] = $address['city'];
				if (isset($address['region'])) $parts[] = $address['region'];
				if (isset($address['postal_code'])) $parts[] = $address['postal_code'];
				if (isset($address['country'])) $parts[] = $address['country'];
				$this->set_address(join(', ', $parts));
			}
			return ($address);
		}
		return false;
	}
	
	/** Attach an extra parameter to be passed in the request to the geocoding service **/
	function add_request_param($key, $val)
	{
		$this->extra_params[$key] = $val;		
	}
	
	/** Request geocoding for an address, either passed or already present in the object.
	 *  Will prefer cached results, but query the external service if none are available.
	 **/
	function get_geocode($location = '')
	{
		if ($location) $this->set_location($location);

		if (!$this->get_location())
		{
			trigger_error('Location (street address, ip, or geopoint) not set in geocoder->get_geocode');
			return false;
		}
		
		if ($result = $this->get_result_from_cache())
		{
			return $result;
		}
		if ($this->get_results_from_service() === true)
		{
			if ($result = $this->parse_results_from_service())
			{
				return $result;
			}
		}
		return false;
	}
	
	/**
	 *
	 */
	function set_location($location)
	{
		$this->location = $location;
	}
	
	function get_location()
	{
		return (isset($this->location)) ? $this->location : false;
	}
	
	function get_location_hash()
	{
		if ($this->get_location())
		{
			$location = ($this->location_is_lat_lon()) ? serialize($this->get_location()) : $this->get_location();
			return md5($location);
		}
		else return false;
	}
	
	function location_is_address()
	{
		return ($this->get_location() && !is_array($this->get_location()));
	}
	
	function location_is_lat_lon()
	{
		return ($this->get_location() && is_array($this->get_location()));
	}
	
	/** Query the geocoding service, given a location and any extra request parameters 
	*
	* @return array of result sets
	**/
	function get_results_from_service()
	{
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?';
		if ($this->location_is_address() || $this->location_is_lat_lon())
		{
			if ($this->location_is_address())
			{
				$url .= 'sensor=false&address='.urlencode($this->get_location());
			}
			elseif ($this->location_is_lat_lon())
			{
				$latlng_str = implode(",", $this->get_location());
				$url .= 'sensor=false&latlng='.urlencode($latlng_str);
			}
			foreach($this->extra_params as $key => $val)
			{
				$url .= '&'.urlencode($key).'='.urlencode($val);
			}
			
			// Limit requests to one per second
			if (isset($last_query_time) && ( (time() - $last_query_time) == 0) ) usleep(1000000);
			$last_query_time = time();
			
			if (!($this->raw_query_results = @file_get_contents($url))) 
			{
				trigger_error('Geocoding request failed in geocoder->get_results_from_service()');
				return false;	
			}
		}
		else {
			trigger_error('location (address, lat/lng, or ip) not set in geocoder->get_results_from_service()');
			return false;
		}

		if ($decode = json_decode($this->raw_query_results))
		{
			if (isset($decode->results))
			{
				switch ($decode->status)
				{
					case 'OK':
						$this->query_results = $decode->results;
						return true;
						break;
					case 'ZERO_RESULTS':
						return 0;
						break;
					case 'OVER_QUERY_LIMIT':
						trigger_error('Currently over query limit for Google geocoding.');
						return false;
						break;
					case 'REQUEST_DENIED':
					case 'INVALID_REQUEST':
						trigger_error('Google denied geocoding request (probable malformed query)');
						return false;
						break;
				}	
			}
			else
			{
				trigger_error('Response decoding failed in geocoder->get_results_from_service()');
				return false;
			}
		}
		else
		{
			trigger_error('Response decoding failed in geocoder->get_results_from_service()');
			return false;
		}
	}
	
	/** 
	 * Process the results from the geocoding service into a standard format. 
	 * 
	 * Code using this method is expecting an array like this:
	 * 
	 * array(
	 *   'address' => "The address the user entered",
	 *	 'latitude' => double,
	 *	 'longitude' => double
	 *	 'raw_response' => 
	 * )
	 *
 	 */
	function parse_results_from_service()
	{
		if (empty($this->query_results))
		{
			trigger_error('geocoder->parse_results_from_service() called with no query results');
			return false;
		}
		
		if (count($this->query_results) > 1)
		{
			$result = $this->find_best_result($this->query_results);
		}
		else
		{
			$result = reset($this->query_results);
		}
		
		$address_parts = array();
		if (is_array($result->address_components))
		{
			foreach ($result->address_components as $comp)
			{
				if (in_array('locality', $comp->types))
				{
					if (isset($comp->long_name)) $address_parts['city'] = $comp->long_name;
				}
				if (in_array('administrative_area_level_1', $comp->types))
				{
					if (isset($comp->long_name)) $address_parts['state'] = $comp->long_name;
					if (isset($comp->short_name)) $address_parts['state_code'] = $comp->short_name;
				}
				if (in_array('country', $comp->types))
				{
					if (isset($comp->long_name)) $address_parts['country'] = $comp->long_name;
					if (isset($comp->short_name)) $address_parts['country_code'] = $comp->short_name;
				}
				if (in_array('postal_code', $comp->types))
				{
					if (isset($comp->long_name)) $address_parts['postal_code'] = $comp->long_name;
				}
			}
		}
		$location = ($this->location_is_lat_lon()) ? serialize($this->get_location()) : $this->get_location();
		$address = ($this->location_is_address()) ? $this->get_location() : $result->formatted_address;
		$values = array(
			'address' => $address,
			'address_parts' => $address_parts,
			'latitude' => $result->geometry->location->lat,
			'longitude' => $result->geometry->location->lng,
			'address_hash' => md5($address), // i would like this to go away if possible
			'location_hash' => md5($location),
			'raw_response' => json_decode($this->raw_query_results));
		
		$this->save_result_to_cache($values);
		
		return $values;
	}
	
	/** if the geocoding service returns more than one result, this method will determine the most accurate
	 *  (as reported by the geocoder).
	 **/
	function find_best_result($results)
	{
		$score = array('ROOFTOP' => 1, 'RANGE_INTERPOLATED' => 2, 'GEOMETRIC_CENTER' => 3, 'APPROXIMATE' => 4);
		$best = array();
		foreach ($results as $result)
		{
			if (isset($best->geometry->location_type))
			{
				if ($score[$result->geometry->location_type] < $score[$best->geometry->location_type])
					$best = $result;
			}
			else
			{
				$best = $result;	
			}
		}
		return $best;
	}
	
	/** Retrieve geocodes from the cache by comparing address hashes. 
	 *  Code using this method is expecting an array that minimally contains this:
	 *  array(
	 *     'address' => "The address the user entered",
	 *	   'latitude' => double,
	 *	   'longitude' => double
	 *  )
 	 **/
	function get_result_from_cache()
	{
		$location = ($this->location_is_lat_lon()) ? serialize($this->get_location()) : $this->get_location();
		$cache = $this->get_cache();
		$cache->init(md5($location));
		$result = $cache->fetch();
		if ($result !== FALSE)
		{
			$ret = reset($result);
			return $ret;
		}
		return false;
	}
	
	/**
	 * Lets save the cache.
	 */
	function save_result_to_cache($values)
	{
		$location = ($this->location_is_lat_lon()) ? serialize($this->get_location()) : $this->get_location();
		$cache = $this->get_cache();
		$cache->init($values['location_hash'], -1); // grab anything that exists, specify -1 so we get anything.
		$newvalues[] = $values;
		$cache->set($newvalues);
	}
	
	/**
	 * Return the default ObjectCache if an ObjectCache object has not been provided.
	 */
	function get_cache()
	{
		if (!isset($this->cache))
		{
			$this->cache = new ObjectCache();
		}
		return $this->cache;
	}
}
 
?>
