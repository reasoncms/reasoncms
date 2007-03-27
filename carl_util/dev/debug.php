<?php
/*
 * A simple debugger
 * dave hendler
 */

	class SimpleDebugger
	{
		var $_debug;

		function SimpleDebugger( $debug = false )
		{
			$this->_debug = $debug;
		}
		function print_err( $msg )
		{
			if ( $this->_debug )
				echo '<div style="background: #fcc; border: 1px dashed black"><code>DEBUG: '.$msg.'</code></div>'."\n";
		}
		function set_debug( $debug )
		{
			$this->_debug = $debug;
		}
	}
	// 
	$GLOBALS['debugger'] = new SimpleDebugger;
?>
