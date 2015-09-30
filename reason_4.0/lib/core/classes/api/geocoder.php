<?php
/**
 * Reason Geocoder API
 *
 * @package reason
 * @subpackage classes
 */
 
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'api/api.php');
include_once(CARL_UTIL_INC . 'basic/url_funcs.php');
reason_include_once('classes/geocoder.php');

/**
 * ReasonGeocoderAPI defines a simple JSON api for geocoding and geolocation info.
 *
 * It does all its own request handling and sets its own content - and should be used like this.
 *
 * <code>
 * $geoApi = new ReasonGeocoderAPI();
 * $geoApi->run();
 * </code>
 *
 * For now, these are the possible return values:
 *
 * - JSON location object as defined in the Geocoder class.
 * - Empty JSON object {} if the location could not be determined.
 * - 404 if called but not setup properly.
 *
 * A few notes
 *
 * - When using ip address as the source, the location is only populated if the ip can be geocoded to the city level.
 *
 * @version .1 
 * @author Nathan White
 */
class ReasonGeocoderAPI extends CarlUtilAPI
{
	/**
	 * We support json and only json and do not allow format specification from userland.
	 */
	var $supported_content_types = array('json');
	var $geo_sources = array('ip', 'address');
	var $geo_actions = array('encode');
	var $geo_source;
	var $geo_action;
	var $geo_value;
	
	/**
	 * You may optionally provide a configured geocoder object to use.
	 *
	 * @var object geocoder
	 */
	private $geocoder;

	/**
	 * We explicitly only look at GET - the API will not respond to POST requests. We do this instead of looking
	 * generically at $_REQUEST because php will urldecode $_GET but not $_POST data. We do not want to have to figure
	 * out whether or not urldecode should be run.
	 */
	function setup_api()
	{
		if (isset($_GET['geo_source']) && in_array($_GET['geo_source'], $this->geo_sources) ) $this->geo_source = $_GET['geo_source'];
		if (isset($_GET['geo_action']) && in_array($_GET['geo_action'], $this->geo_actions) ) $this->geo_action = $_GET['geo_action'];
		if (isset($_GET['geo_value'])) $this->geo_value = turn_into_string($_GET['geo_value']); // we just make this a string since it could be almost anything 
	}
	
	/**
	 * @return mixed string geo_source or boolean FALSE
	 */
	function get_source()
	{
		return (isset($this->geo_source) && !empty($this->geo_source)) ? $this->geo_source : FALSE;
	}
	
	/**
	 * @return mixed string geo_action or boolean FALSE
	 */
	function get_action()
	{
		return (isset($this->geo_action) && !empty($this->geo_action)) ? $this->geo_action : FALSE;
	}

	/**
	 * @return mixed string geo_value or boolean FALSE
	 */	
	function get_value()
	{
		return (isset($this->geo_value) && !empty($this->geo_value)) ? $this->geo_value : FALSE;
	}
	
	/**
	 * Return our geocode object - create one if necessary.
	 * 
	 * @return object geocoder
	 */
	function get_geocoder()
	{
		if (!isset($this->geocoder))
		{
			$this->geocoder = new geocoder();
		}
		return $this->geocoder;
	}
	
	/**
	 * You can optionally provide a geocoder object.
	 *
	 * The most obvious reason to do so is if you have a one that uses a custom source or custom cache.
	 *
	 * @param object geocoder
	 * @return void
	 */
	function set_geocoder($geocoder)
	{
		$this->geocoder = $geocoder;
	}
	
	/**
	 * We set the content dynamically
	 */
	function setup_content()
	{
		if ($this->get_source() && $this->get_action()) // we need all three to be valid
		{
			$address = false;
			if (in_array($this->get_source(), $this->geo_sources))
			{
				$geo_coder = $this->get_geocoder();
				if ($this->get_source() == 'address')
				{
					$address = $this->get_value();
					$geo_coder->set_address($address);
					$results = $geo_coder->get_geocode();
				}
				elseif ($this->get_source() == 'ip')
				{
					$ip = ($this->get_value()) ? $this->get_value() : $_SERVER['REMOTE_ADDR'];
					$result = $geo_coder->set_address_from_ip($ip);
					
					if ($result && is_string($result)) // leave string handling in case we have cached results that come as string.
					{
						$results = $geo_coder->get_geocode();
					}
					elseif ($result && is_array($result)) // result is now an array - if we have a city, save the result.
					{
						if (isset($result['city'])) // we have sufficient specificity
						{
							$results = $geo_coder->get_geocode();
						}
						else $results = false;
					}
					else $results = false;
				}
				if ($results)
				{
					$this->set_content(json_encode($results));
				}
				else $this->set_content('{}');
			}
		}
	}
}
?>