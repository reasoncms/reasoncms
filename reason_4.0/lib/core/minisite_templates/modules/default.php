<?php

	// get the name of the file, without all the path information
	// and without the .php suffix and set the name of the class
	// to that index of this global array
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DefaultMinisiteModule';

	class DefaultMinisiteModule
	{
		var $cleanup_rules = array();
		// the cleaned up array of request variables
		var $request = array();
		// page id as passed from the template
		var $page_id;
		// site id as passed from the template
		var $site_id;
		// current page entity (this is the cur_page object from the pages collection from the template
		var $cur_page;
		// Reason_Session object passed if this module is editable
		var $session = null;
		// textonly variable from the template
		var $textonly = null;
		
		// allowable parameters for this module
		// IMPORTANT: this array must contain fully specified keys AND values.
		var $acceptable_params = array();
		var $base_params = array();
		// params as keys with values as values
		var $params;
		
		// see if arguments have been parsed
		var $_prepped = false;
		// copy of original arguments
		var $_args = array();
		
		///////////////////////////////////////////////////////////////
		// $parent is DEPRECATED.  Only for compatibility.
		// this used to be the way to access page_id, site_id, etc.
		// but that method sucks.  so we just pass down the right
		// information now.  which is better
		
		var $parent;
		///////////////////////////////////////////////////////////////
		
		// convenience method that the template calls. sets up the variables from the template
		function prep_args( $args = array() ) // {{{
		{
			// only run this method once.  for compatibility, it is called by init().
			// older modules may have some weird inheritance or do some of the work
			// themselves.
			if( !$this->_prepped )
			{
				// loop through the args passed and make them object variables.
				// kind of bad as it could hide some variable declarations
				if( !empty( $args ) AND is_array( $args ) )
				{
					foreach( $args AS $key => $val )
						if( $key != 'parent' )
							$this->$key = $val;
				}
				// for compatibility with older modules only.  new modules should NOT use the parent var
				$this->parent =& $args[ 'parent' ];
				// save the args
				$this->_args = $args;
				// make sure this chunk isn't run again
				$this->_prepped = true;
			}
		} // }}}
		// takes in the list of parameters set up by the page_types array.  checks to see if the module has defined each
		// key as a valid parameter to accept.  if it is, adds the value to the internal array for module usage.
		function handle_params( $params ) // {{{
		{
			// run through acceptable_params and collect the real keys.  If there is an index that
			// is an actual integer, this is an entry where the key was specified as a value.  Otherwise,
			// if there is a programmer entered key AND value, then the key is the key, as it were.
			// we stuff these defaults directly into the params array
			$this->params = array();
			$this->acceptable_params += $this->base_params; // nwhite 12/20/2006
			if( is_array( $this->acceptable_params ) )
			{
				foreach( $this->acceptable_params AS $key => $val )
				{
					if( is_int( $key ) )
						$this->params[ $val ] = '';
					else
						$this->params[ $key ] = $val;
				}
			}
			
			// now, run through the params passed in and copy them in, unless they are bad stuffs
			foreach( $params AS $name => $val )
			{
				if( array_key_exists( $name, $this->params ) )
				{
					$this->params[ $name ] = $val;
				}
				else
				{
					trigger_error( 'Module Parameter Error: module does not accept parameter named "'.$name.'"' );
				}
			}
		} // }}}
		// hook to run some code and load some objects before get_cleanup_rules() is called
		function pre_request_cleanup_init() // {{{
		{
		} // }}}
		// called by the template to do any of the DB heavy lifting.  if a module is going to get data from some source,
		// it should always do it here.  we should NOT be retrieving or munging data from other methods.
		function init( $args = array() ) // {{{
		{
		} // }}}
		// called when editing is available for any specific editable needs
		function init_editable( $session ) // {{{
		{
			$this->session = $session;
		} // }}}
		// returns a boolean value determining whether someone can edit the page or not.  this method is for performing
		// checks on authentication if they need to be made.  for instance, this is where you would check to see if the
		// logged in user is related to the page with an authentication relationship.  it could even be used to perform
		// special actions for a module that doesn't use the usual method of authentication.
		function can_edit() // {{{
		{
			return false;
		} // }}}
		// returns a boolean to determine if this module should even be run at all
		function has_content() // {{{
		{
			return true;
		} // }}}
		// basic version simply returns the variable.  more complicated actions can be done here if needed
		function get_cleanup_rules() // {{{
		{
			return $this->cleanup_rules;
		} // }}}
		// the basic run function to display this module.  this is when the template is in non-editing mode
		function run() // {{{
		{
			echo 'I am the default minisite frontend module<br />';
		} // }}}
		// displays the editable version of the page.  there is a good chance that this method will handle all states of
		// an editable form
		function run_editable() // {{{
		{
			$this->run();
		} // }}}
		// used by the template to determine the most recent modification time
		function last_modified() // {{{
		{
			return false;
		} // }}}

		/**
		 * @author Eric Naeseth
		 */
		function get_content_classes()
		{
			return null;
		}

		/**
		 * @author Eric Naeseth
		 */
		function get_content_id()
		{
			return null;
		}

	}

?>
