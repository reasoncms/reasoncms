<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
include_once( CARL_UTIL_INC . 'go/shorturl.php' );
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'GoModule';

/**
 * The Go Module - shorturl handling.
 *
 * @todo generalize this module and the code it depends on for easy use outside of Carleton.
 */
class GoModule extends DefaultMinisiteModule
{
	var $cleanup_rules = array(
		'action' => array('function' => 'turn_into_string')
	);
    function run()
    {
        $go = new ShortURL();
   
		if( isset( $this->request['action'] ) )
		{
			if( $this->request['action'] == 'add' )
			{	
				$go->print_results();
			}
			elseif( $this->request['action'] == 'req' )
			{
				$go->print_request_form();
			}
			elseif( $this->request['action'] == 'email' )
			{
				$go->send_email();
			}
			elseif( $this->request['action'] == 'replace' )
			{
				$go->replace_entry();
			}
		}
		else
		{
			$go->print_form();
		}
        
        //Since we connected to a different DB inside ShortURL, we need to 
        //reconnect to Reason after we are done. 
        connectDB( REASON_DB );
    }
}

?>
