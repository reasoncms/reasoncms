<?php
	/**
	 *	Interface for sessions
	 *
	 *	@package reason
	 *	@subpackage classes
	 *	@author Dave Hendler
	 */

	/**
	 * A class that defines the Reason session interface
	 */
	class Session
	{
		var $sess_name = 'REASON_SESS';
		var $sess_values;
		var $expires = 600;
		var $started;

		function Session() {}
		/**
		 * @access public
		 */
		function start() {}
		/**
		 * @access public
		 */
		function destroy() {}
		/**
		 * @access public
		 */
		function exists() {}
		// public methods that are always the same.  regardless of the implementation, these make sure
		// that error checking happens.  the _store is the implementation specific method
		/**
		 * @access public
		 */
		function set( $var, $value )
		{
			$this->_store( $var, $value );
		}
		/**
		 * @access public
		 */
		function get( $var )
		{
			return $this->_retrieve( $var );
		}
		/**
		 * @access public
		 */
		function is_idle() {}
		/**
		 * @access public
		 */
		function set_session_name( $name )
		{
			$this->sess_name = $name;
		}
		/**
		 * @access public
		 */
		function define_vars( $var_array )
		{
			if( is_array( $var_array ) )
			{
				$this->sess_vars = $var_array;
				foreach( $var_array AS $key )
					$this->sess_values[ $key ] = '';
			}
		}
		/**
		 * This is the implementation-specific method
		 * @access private
		 */
		function _store( $var, $val )
		{
			$this->sess_values[ $var ] = $val;
		}
		/**
		 * standard retrieve just pulls from the sess_values
		 * @access private
		 */
		function _retrieve( $var )
		{
			return $this->sess_values[ $var ];
		}
	}
?>
