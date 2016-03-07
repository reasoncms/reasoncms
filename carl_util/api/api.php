<?php
include_once('paths.php');
include_once(CARL_UTIL_INC.'basic/misc.php');

/**
 * Base class for API functionality
 *
 * Handles content_type headers and super basic error handling.
 *
 * - Currently we support json, html, and xml
 *
 * Right now outputs a 404 if content is not set AND no http_response_code has been set.
 *
 * // output json with the application/json content type
 * $api = new CarlUtilAPI('json');
 * $api->set_content( json_encode(array('text' => 'hello world')));
 * $api->run();
 *
 * @todo allow extensibility with content type definitions - remove hard coded content type map
 * 
 * Modification: http_response_code handling added for errors other than 403/4, added csv mime type, added response for csv RABBANII
 * 
 * @version .1
 * @author Nathan White
 * @package carl_util
 * @subpackage api
 */
class CarlUtilAPI
{
	/**
	 * @var array supported_content_types
	 */
	protected $supported_content_types = array();

	/**
	 * If set to a string, we will look for this in request and set the content type to its value.
	 * @var mixed string content_type_request_key or boolean FALSE to disable.
	 */
	protected $content_type_request_key = FALSE;
	
	/**
	 * Define the content types to use for all formats that an API could support.
	 */
	private $content_type_map = array(
		'json' => 'application/json',
		'html' => 'text/html',
		'xml' => 'text/xml',
		'csv' => 'text/csv'
	);
			
	private $content_type;
	private $api_name;
	private $content;
	private $http_response_code;
	
	/**
	 * Constructor allows specification of supported content types. The first listed type is considered the "default" content type.
	 *
	 * @param mixed support_types - optional param - string specifying content type or array specifying multiples content types.
	 */
	function __construct($support_types = NULL)
	{	
		if (isset($support_types))
		{
			if (is_string($support_types)) $support_types = array($support_types);
			$this->set_supported_content_types($support_types);
		}
		if ($supported_types = $this->get_supported_content_types())
		{
			$type = reset($supported_types);
			$this->set_content_type($type);
		}
		if ($this->get_content_type_request_key() && (isset($_REQUEST['format']) && check_against_regexp($_REQUEST['format'], array('safechars'))))
		{
			$this->set_content_type($_REQUEST['format']);
		}
		$this->setup_api();
	}

	/**
	 * Setup api is called at the end of __construct - it provides a way to dynamically setup parameters.
	 */
	protected function setup_api()
	{
	}
	
	/**
	 * Setup content is called first thing in the run method - it provides a way to dynamically set content.
	 */
	protected function setup_content()
	{
	}

	/** 
	 * @return mixed array setup_method_names or boolean FALSE
	 */
	final function get_content_type_request_key()
	{
		return (!empty($this->content_type_request_key)) ? $this->content_type_request_key : FALSE;
	}
	
	/**
	 * @param array array of content types - replaces anything that may already be set!
	 */	
	final function set_supported_content_types($array)
	{
		$this->supported_content_types = array();
		foreach ($array as $content_type)
		{
			$this->set_supported_content_type($content_type);
		}
	}

	/**
	 * @param string a content type name to add to the supported content types array
	 */
	final function set_supported_content_type($string)
	{
		if (!in_array($string, $this->supported_content_types))
		{
			array_push( $this->supported_content_types, $string );
		}
	}

	/**
	 * @param string content type to use
	 */
	final function set_content_type($string)
	{
		$this->content_type = $string;
	}

	/**
	 * @param string http response code
	 */
	final function set_http_response_code($string)
	{
		$this->http_response_code = $string;
	}
	
	/**
	 * @param string name for the api
	 */
	final function set_name($name)
	{

		$this->api_name = $name;
	}

	/**
	 * @param string identifier for ths API request (e.g. "mcla-RandomNumberModule-mloc-main_post-mpar-5e31a4cceaa1ecd49d73b921a67e1b4c")
	 */
	final function set_identifier($identifier) { $this->api_identifier = $identifier; }

	final function get_identifier() { return $this->api_identifier; }

	/**
	 * @param string content
	 */
	final function set_content($content)
	{
		$this->content = $content;
	}

	final function get_name()
	{
		return $this->api_name;
	}
	
	/**
	 * Returns the content type header.
	 *
	 * @return mixed string content type header or boolean FALSE
	 */
	final function get_content_type_header()
	{
		$content_type = $this->get_content_type();
		return ($content_type && isset($this->content_type_map[$content_type])) ? $this->content_type_map[$content_type] : FALSE;
	}
	
	/** 
	 * @return mixed array supported_content_types or boolean FALSE
	 */
	final function get_supported_content_types()
	{
		return (!empty($this->supported_content_types) && is_array($this->supported_content_types)) ? $this->supported_content_types : FALSE;
	}
	
	/**
	 * Returns the content type.
	 *
	 * @return mixed string content_type or boolean FALSE
	 */
	final function get_content_type()
	{
		return (isset($this->content_type)) ? $this->content_type : FALSE;
	}

	/**
	 * Returns the http_response_code if it has been set.
	 *
	 * @return mixed string http_response_code or boolean FALSE
	 */
	final function get_http_response_code()
	{
		return (isset($this->http_response_code)) ? $this->http_response_code : FALSE;
	}
	
	/** 
	 * @return mixed string content or boolean FALSE
	 */
	final function get_content()
	{
		return (isset($this->content)) ? $this->content : FALSE;
	}
	
	/**
	 * @return boolean
	 */
	final function have_supported_content_type()
	{
		if ( ($content_type = $this->get_content_type()) && ($content_types = $this->get_supported_content_types()) )
		{
			if (in_array($content_type, $content_types)) return true;
		}
		return false;
	}
	
	/**
	 * We provide a default 404 if content is empty but everything else is in place.
	 *
	 * We provide a default 400 for invalid content_type requests.
	 *
	 * @todo add customizable messages, additional status code support
	 */
	final function run()
	{
		$this->setup_content();
		$content = $this->get_content();
		$content_type = $this->get_content_type();
		$content_type_header = $this->get_content_type_header();
		$content_type_supported = $this->have_supported_content_type();
		
		// we set some defaults for the response code if it was never set
		if ($this->get_http_response_code() === FALSE)
		{
			if ($content !== FALSE) $this->set_http_response_code(200);
			else $this->set_http_response_code(404);
		}
		$http_response_code = $this->get_http_response_code();
		
		if ( ($content !== FALSE) && $http_response_code && $content_type && $content_type_header && $content_type_supported)
		{
			http_response_code( $http_response_code );
			header('Content-type: ' . $content_type_header);
			echo $content;
		}
		elseif ($http_response_code && $content_type && $content_type_header && $content_type_supported) // defaults for certain codes if content was not set.
		{
			if ($http_response_code == '404')
			{
				http_response_code(404);
				header('Content-type: ' . $content_type_header);
				switch ($this->get_content_type())
				{
					case "json":
						echo json_encode(array('status' => '404', 'error' => 'Resource Not Found'));
						break;
					case "xml":
						$xml = '<?xml version=\'1.0\' standalone=\'yes\'?>';
						$xml .= '<root><status>404</status><error>Resource Not Found</error></root>';
						break;
					case "html":
					default:
						echo '<html><body><h1>404</h1><p>Resource Not Found</p></body></html>';
						break;
				}
			}
			elseif ($http_response_code == '403')
			{
				http_response_code(403);
				header('Content-type: ' . $content_type_header);
				switch ($this->get_content_type())
				{
					case "json":
						echo json_encode(array('status' => '403', 'error' => 'Unauthorized'));
						break;
					case "xml":
						$xml = '<?xml version=\'1.0\' standalone=\'yes\'?>';
						$xml .= '<root><status>403</status><error>Unauthorized</error></root>';
						break;
					case "html":
					default:
						echo '<html><body><h1>403</h1><p>Unauthorized</p></body></html>';
						break;
				}
			}
			else {
				$message = get_message_for_http_status($http_response_code);
				echo '<html><body><h1>'.$http_response_code.'</h1><p>'.$message.'</p></body></html>';
			}
		}
		elseif ($content_type && !$content_type_supported) // this request is invalid - no content type set
		{
			http_response_code(400);
			header('Content-type: text/html');
			echo '<html><body><h1>400</h1><p>The Content Type Requested is Not Supported</p></body></html>';
		}
		elseif (!$content_type) // this request is invalid - no content type set
		{
			http_response_code(400);
			header('Content-type: text/html');
			echo '<html><body><h1>400</h1><p>No Content Type</p></body></html>';
		}
	}
}
?>
