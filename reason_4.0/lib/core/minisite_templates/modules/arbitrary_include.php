<?php
	/**
 	 * @package reason
 	 * @subpackage minisite_modules
	 */
	
	 /**
 	  * Include base class
  	  */
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	/**
	 * Register the class so the template can instantiate it
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ArbitraryInclude';
	
	/**
	 * Arbitrary Include Module
	 *
	 * This module includes arbitrary code from any location; use as a last resort when you don't have any other way
	 * to get (usually legacy) code running in the Reason environment. 
	 */
	class ArbitraryInclude extends DefaultMinisiteModule
	{
		var $form;
		var $acceptable_params = array(
			'code_include',
			'force_secure',
		);
		var $db_conn;
		
		function init( $args=array() ) // {{{
		{
 			if( $this->params['force_secure'] && HTTPS_AVAILABLE && !on_secure_page() )
                	{
                        	header('Location: '.get_current_url( securest_available_protocol() ) );
                        	exit(0);
                	}
                	
                	// Check for an existing database connection so we can restore it when we're done
                	$this->db_conn = get_current_db_connection_name();
		}

		function run() // {{{
		{
			if( is_file( $this->params['code_include'] ) ) 
				include_once($this->params['code_include']);
			else
				echo "Could not include ". $this->params['code_include']; 
				
			if ($this->db_conn) connectDB($this->db_conn);
		} // }}}
	}
?>
