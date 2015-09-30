<?php
/**
 * @package disco
 * @subpackage controller
 */

/**
 * include base disco class
 */
include_once( 'disco.php' );

/**
 * Simple extension of Disco for use in the FormController
 * @author Dave Hendler
 * @since 2005/02/01
 * @package disco
 * @subpackage controller
 */
class FormStep extends Disco
{
	/**
	 * Keep track of whether the form is done or not.
	 * @access public
	 * @var bool
	 */
	var $processed = false;
	/**
	 * A reference to the controller that is running this FormStep
	 * @access private
	 * @var FormController
	 */
	var $controller;
	/**
	 * Array of variable names that should NOT be stored in the session.  Use for private data (credit card numbers,
	 * etc)
	 * @access public
	 * @var array
	 */
	var $no_session;
	 
	/**
	 * Pretty name for this form.   Available for building navigation, etc.
	 * @access public
	 * @var string
	 */
	var $display_name;
	
	/**
	 * Overloaded method - do nothing by default.  The only time where_to() is ever called by the FormController is on
	 * the final form once everything is done.  If returning something, return a valid URL to bounce to.
	 * @access private
	 * @return string
	 */
	function where_to()
	{
		return false;
	}
	/**
	 * Overloaded to both make sure that the form does not handle transitions as well as well as providing a little
	 * extra information specific to the controller and Disco's behavior in a Controller
	 * @access private
	 * @return void
	 */
	function handle_transition( $kludge )
	{
		if(!empty($kludge))
			trigger_error('$kludge is deprecated; ignoring value');
		$this->processed = true;
		$this->show_form = false;
	}
	/**
	 * Set up the controller for this step
	 * @access public
	 * @return void
	 * @param Controller $c The Controller this step is a part of
	 */
	function set_controller( &$c )
	{
		$this->controller =& $c;
	}
}

?>
