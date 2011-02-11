<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include the parent class & dependencies, and register the module with Reason
 */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/api/api.php' );
$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__, '.php' ) ] = 'RandomNumberModule';
	
/**
 * The random number module displays a random number - and sets up an API that can be used to grab a random number for a page 
 * that uses this module. It is intended to demonstrate best practices for setting up basic ajax functionality for a reason module
 * 
 * There are two ways a page running this module could be asked for a random number:
 *
 * First way - specify the module_api name (random_number) as declared in setup_supported_apis. In this case, the template will 
 * look at the page type, and call run_api on the first instance of the random_number module.
 *
 * eg. http://reason.site.url/path/to/page/?module_api=random_number
 *
 * If we want to target a particular instance of the module, as we would want to do for most use cases that provided some kind 
 * of ajax functionality, we can target the module more specifically by including a module_identifier.
 *
 * eg. http://reason.site.url/path/to/page/?module_api=random_number&module_identifier=module_identifier-mcla-RandomNumberModule-mloc-main_post-mpar-dcca48101505dd86b703689a604fe3c4
 *
 * In the random_number module run method, note that we call the default minisite module method get_api_class_string. That method
 * gives us a string that includes the module_identifier and well as the names of all the module_apis supported by a module.
 *
 * The actual string varies depending upon the page location and parameters of a given instance of the module, but looks something like this:
 *
 * module_identifier-mcla-RandomNumberModule-mloc-main_post-mpar-dcca48101505dd86b703689a604fe3c4 module_api-random_number module_api-standalone
 *
 * @author Nathan White
 */
class RandomNumberModule extends DefaultMinisiteModule
{
	/**
	 * Lets support a "last_number" setting - if set, we'll make sure our random number is not the last_number.
	 */
	var $cleanup_rules = array('last_number' => 'turn_into_int');
	
	static function setup_supported_apis()
	{
		$random_number_api = new ReasonAPI(array('json', 'html'));
		self::add_api('random_number', $random_number_api);
	}
	
	/**
	 * We add random_number.js - a javascript file that adds a "refresh number" feature to our page.
	 *
	 * We place the javascript at a location that sensibly mirrors our module location. While the javascript could be anywhere, this is a best practice.
	 * 
	 * Module location within reason_package/reason_4.0/lib/core/minisite_templates/
	 *
	 * - modules/random_number/random_number.php
	 *
	 * Module javascript location within reason_package/reason_4.0/www/js/
	 *
	 * - modules/random_number/random_number.js
	 */
	function init( $args = array() )
	{	
		$head_items =& $this->get_head_items();
		$head_items->add_javascript(JQUERY_URL, true); // load jquery - specify load first
		$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'jquery.reasonAjax.js'); // our random_number.js file requires jquery.reasonAjax.js
		$head_items->add_javascript(WEB_JAVASCRIPT_PATH . 'modules/random_number/random_number.js');
	}
	
	/**
	 * We run the api we setup in setup_supported_apis()
	 *
	 * Note that we ask for the content type and set the content differently for the json and html content types.
	 *
	 * If the content type is not 'json' or 'html', note that we run the api anyway, as it supports standard error cases.
	 */
	function run_api()
	{
		$api = $this->get_api();
		if ($api->get_name() == 'random_number')
		{
			if ($api->get_content_type() == 'json') $api->set_content(json_encode(array('random_number' => $this->get_random_number())));
			if ($api->get_content_type() == 'html') $api->set_content('<p>'.$this->get_random_number().'</p>');
			$api->run();
		}
		else parent::run_api(); // support other apis defined by parents
	}
	
	/**
	 * We include our api class string and a span around the number - these inclusions are used by our javascript file.
	 */
	function run()
	{
		echo '<div class="random_number '.$this->get_api_class_string().'">';
		echo '<p>Our random number is <span class="random_number">'.$this->get_random_number().'</span>.</p>';
		echo '</div>';
	}
	
	/**
	 * Lets get our random number - recurse if last_number is set and our pick is not a fresh number.
	 */
	function get_random_number()
	{
		$number = rand(0,10);
		return (isset($this->request['last_number']) && ($number == $this->request['last_number'])) ? $this->get_random_number() : $number;
	}
}
?>