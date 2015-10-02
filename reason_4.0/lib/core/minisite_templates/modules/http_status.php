<?php
	/**
	 * Http Status Module
	 *
	 * Provides a message and emits an appropriate http status when http_status=XXX is in the query string
	 *
	 * @package reason
	 * @subpackage minisite_modules
	 */
	 
	/**
	 * Include the base module & register with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'httpStatusModule';
	include_once(CARL_UTIL_INC.'basic/misc.php');
	/**
	 * Http Status Module
	 *
	 * Note that by default this module only supports 404. If you want to support other status codes,
	 * you'll need to provide messages for them in the module parameters.
	 */
	class httpStatusModule extends DefaultMinisiteModule
	{
		public $cleanup_rules = array(
			'http_status' => 'turn_into_int',
		);
		public $acceptable_params = array(
			'statuses' => array(
				404 => 'Sorry, the page you are looking for wasn\'t found.',
			),
		);
		protected $message;
		/**
		* Initialize the module
		* @param $args array
		*/
		function init( $args = array() )
		{
			parent::init($args);
			if(!empty( $this->request['http_status'] ) && isset($this->params['statuses'][$this->request['http_status']]) )
			{
				http_response_code($this->request['http_status']);
				$this->message = $this->params['statuses'][$this->request['http_status']];
			}
		}
		/**
		* Tells template whether module has content or not
		* @return boolean $has_content
		*/
		function has_content()
		{
			return ( !empty( $this->message ) );
		}
		/**
		* Generates the html for the site announcements
		*/
		function run()
		{
			echo '<div id="httpStatus">'."\n";
			echo $this->message;
			echo '</div>'."\n";
		}
	}