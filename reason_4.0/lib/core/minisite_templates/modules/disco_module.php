<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include the base module, disco dependency, and register this module with Reason
 */
reason_include_once( 'minisite_templates/modules/default.php' );
include_once( DISCO_INC.'disco.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DiscoModule';

/**
 * Generic Disco Module
 * 
 * 2004 Nov 16
 *
 * This is the first generic module to be built.  It requires two parameters to work at all: a form_name and a
 * form_include which are the class name of the Disco form to use and the include path of that form,
 * respectively.  Automatically taken care of is making sure the include file exists as well as instantiation and
 * running of the form itself.
 *
 * @author Dave Hendler
 *
 * Updated to remove get_cleanup_rules and set_request - these are internally handled by disco and the cleanup rules have not
 * been working so that the local request would always be empty anyway ... if anything depended on this functionality before
 * this update, it was broken!
 *
 * @author Nathan White
 * @version 2.0 May 18, 2010
 */
class DiscoModule extends DefaultMinisiteModule
{
	var $form;
	var $acceptable_params = array(
		'form_name',
		'form_include',
	);
	
	function pre_request_cleanup_init()
	{
		if (reason_include_once( $this->params['form_include'] ) == false)
		{
			trigger_error( 'DiscoModule tried to include a file but failed.  Check your "form_include" setting in the page_types' );
		}
		$this->form = new $this->params['form_name'];
		$this->form->add_element( 'site_id', 'hidden' );
		$this->form->add_element( 'page_id', 'hidden' );
		$this->form->set_value( 'site_id', $this->site_id );
		$this->form->set_value( 'page_id', $this->page_id );
		$this->form->init();
	}

	function run()
	{
		$this->form->run();
	}
}
?>
