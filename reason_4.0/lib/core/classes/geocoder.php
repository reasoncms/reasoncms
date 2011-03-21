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
 * @author Mark Heiman
 * @author Nathan White
 */	
class geocoder
{
	var $address;
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
	
	function geocoder($address = '')
	{
		if ($address) $this->set_address($address);
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
		$this->address = $address;	
	}

	/**
	 * Will attempt to set the address to use from an ip address - we use one of two services for this:
	 *
	 * - api.ipinfodb.com
	 * - api.hostip.info
	 *
	 * We will only set the address if we get a level of specificity beyond the country from our ip geocode attempt.
	 * 
	 * @todo ip6?
	 * @todo somehow utilize a curl timeout?
	 * @return boolean true / false
	 */
	function set_address_from_ip($ip)
	{
		if (!empty($ip))
		{
			// do we have a cached address for this IP already?
			$cache_key = md5('ip_to_address_cache_'.$ip);
			$cache = $this->get_cache();
			$cache->init($cache_key);
			$address = $cache->fetch();
			if ($address === FALSE)
			{
				$address = '';
				if (!empty($ip) && defined("REASON_IPINFODB_API_KEY") && constant("REASON_IPINFODB_API_KEY"))
				{
					$ip = urlencode($ip);
					$response = carl_util_get_url_contents('http://api.ipinfodb.com/v3/ip-city/?key='.REASON_IPINFODB_API_KEY.'&ip='.$ip.'&format=json');
					$decoded = json_decode($response, true);
					$pieces = array();
					if (!empty($decoded['cityName'])) array_push($pieces, $decoded['cityName']);
					if (!empty($decoded['regionName'])) array_push($pieces, $decoded['regionName']);
					if (!empty($decoded['countryName'])) array_push($pieces, $decoded['countryName']);
					
					// only return result if we have more than just country.
					if (!empty($decoded['cityName']) || !empty($decoded['regionName']))
					{
						$address = implode(", ", $pieces);
					}
				}
				elseif (!empty($ip)) // we'll use the free service api.hostip.info - only return a result if we determine a city and country
				{
					$ip = urlencode($ip);
					$response = carl_util_get_url_contents('http://api.hostip.info/?ip='.$ip);
					// api.hostip.info returns XML - lets parse with simplexml
					if ($response && $xml = simplexml_load_string($response))
					{
						$city = $xml->children('gml', TRUE)->featureMember->children('', TRUE)->Hostip->children('gml', TRUE)->name;
						$country = $xml->children('gml', TRUE)->featureMember->children('', TRUE)->Hostip->countryName;
						if (strtolower($city) == '(unknown city)') $city = false;
						if ( !empty($city) && !empty($country))
						{
							$address = $city . ', ' . $country;
						}
					}
				}
				 
				// save the result to the cache - even if it is the empty string.
				$cache->set($address);
			}
			if (!empty($address)) $this->set_address($address);
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
	function get_geocode($address = '')
	{
		if ($address) $this->set_address($address);

		if (!isset($this->address))
		{
			trigger_error('Address not set in geocoder->get_geocode');
			return false;
		}
		
		if ($result = $this->get_result_from_cache())
		{
			return $result;
		}
		if ($this->get_results_from_service())
		{
			if ($result = $this->parse_results_from_service())
			{
				return $result;
			}
		}
		return false;
	}
	
	/** Query the geocoding service, given the address and any extra request parameters 
	*
	* @return array of result sets
	**/
	function get_results_from_service()
	{
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?';
		if (isset($this->address))
		{
			$url .= 'sensor=false&address='.urlencode($this->address);
			foreach($this->extra_params as $key => $val)
			{
				$url .= '&'.urlencode($key).'='.urlencode($val);
			}
			
			// Limit requests to one per second
			if (isset($this->last_query_time) && ( (time() - $this->last_query_time()) == 0) ) usleep(1000000);
			$this->last_query_time = time();
			
			if (!($this->raw_query_results = @file_get_contents($url))) 
			{
				trigger_error('Geocoding request failed in geocoder->get_results_from_service()');
				return false;	
			}
		
		} else {
			trigger_error('Address not set in geocoder->get_results_from_service()');
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
						return false;
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
		
		$values = array(
			'address' => $this->address,
			'latitude' => $result->geometry->location->lat,
			'longitude' => $result->geometry->location->lng,
			'address_hash' => md5($this->address),
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
		$cache = $this->get_cache();
		$cache->init(md5($this->address));
		$result = $cache->fetch();
		if ($result !== FALSE)
		{
			foreach ($result as $current)
			{
				if ($current['address'] == $this->address) return $current;
			}
		}
		return false;
	}
	
	/**
	 * Lets save the cache - we allow for the remote possibility of two values hashing to the same thing.
	 */
	function save_result_to_cache($values)
	{
		$cache = $this->get_cache();
		$cache->init(md5($values['address']), -1); // grab anything that exists, specify -1 so we get anything.
		if ($existing = $cache->fetch()) // if something exists in the cache, lets update it.
		{
			foreach ($existing as $current)
			{
				if ($current['address'] != $values['address'])
				$newvalues[] = $current;
			}
		}
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
