<?php
	/*
	 *    Arbitrary Include Module
	 *
	 * This module includes arbitrary code from any location; use as a last resort when you don't have any other way
	 * to get (usually legacy) code running in the Reason environment. 
	*/
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ArbitraryInclude';
	
	class ArbitraryInclude extends DefaultMinisiteModule
	{
		var $form;
		var $acceptable_params = array(
			'code_include',
			'force_secure',
		);
		
		function init() // {{{
		{
 			if( $this->params['force_secure'] && !on_secure_page() )
                	{
                        	header('Location: '.get_current_url( 'https' ) );
                        	exit(0);
                	}
		}

		function run() // {{{
		{
			if( is_file( $this->params['code_include'] ) ) 
				include_once($this->params['code_include']);
			else
				echo "Could not include ". $this->params['code_include']; 
		} // }}}
	}
?>
