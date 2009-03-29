<?php
	/**
	 * @package reason
	 * @subpackage minisite_modules
	 */
	
	/**
	 * Include the base module and register this module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DiscoModule';
	
	/**
	 *    Generic Disco Module
	 * 
	 *    2004 Nov 16
	 *
	 *    This is the first generic module to be built.  It requires two parameters to work at all: a form_name and a
	 *    form_include which are the class name of the Disco form to use and the include path of that form,
	 *    respectively.  Automatically taken care of is making sure the include file exists as well as instantiation and
	 *    running of the form itself.
	 *
	 * @author Dave Hendler
	 *
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
			include_once( DISCO_INC.'disco.php' );
			
			//if( is_file( INCLUDE_PATH.$this->params['form_include'] ) )
			if (reason_include_once( $this->params['form_include'] ) == false)
			{
				trigger_error( 'DiscoModule tried to include a file but failed.  Check your "form_include" setting in the page_types' );
			}
			$this->form = new $this->params['form_name'];
			// make sure the disco form has access to the site_id and the page_id in case it needs to do something with
			// those.
			$this->form->add_element( 'site_id', 'hidden' );
			$this->form->set_value( 'site_id', $this->site_id );
			$this->form->add_element( 'page_id', 'hidden' );
			$this->form->set_value( 'page_id', $this->page_id );
			$this->form->set_request( $this->request );
			$this->form->init();
		}
		function get_cleanup_rules()
		{
			$rules = array();
			foreach( $this->form->_elements AS $key => $el )
			{
				$rules[] = array( $key => array( 'function' => 'turn_into_string' ) );
			}
			return $rules;
		}
		function run() // {{{
		{
			$this->form->run();
		} // }}}
	}
?>
