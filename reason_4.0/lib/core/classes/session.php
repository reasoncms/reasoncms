<?php
	/*
	 *	session.php
	 *	Dave Hendler
	 *	7/28/04
	 *
	 *	Interface for sessions
	 */

	class Session  /* interface */
	{
		var $sess_name = 'REASON_SESS';
		var $sess_values;
		var $expires = 600;
		var $started;

		function Session() {}
		function start() {}
		function destroy() {}
		function exists() {}
		// public methods that are always the same.  regardless of the implementation, these make sure
		// that error checking happens.  the _store is the implementation specific method
		function set( $var, $value )
		{
			$this->_store( $var, $value );
		}
		function get( $var )
		{
			return $this->_retrieve( $var );
		}
		function is_idle() {}
		function set_session_name( $name )
		{
			$this->sess_name = $name;
		}
		function define_vars( $var_array )
		{
			if( is_array( $var_array ) )
			{
				$this->sess_vars = $var_array;
				foreach( $var_array AS $key )
					$this->sess_values[ $key ] = '';
			}
		}
		function _store( $var, $val )
		{
			$this->sess_values[ $var ] = $val;
		}
		// standard retrieve just pulls from the sess_values
		function _retrieve( $var )
		{
			return $this->sess_values[ $var ];
		}
	}
?>
