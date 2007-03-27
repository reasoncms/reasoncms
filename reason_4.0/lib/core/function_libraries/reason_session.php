<?php
	function &get_reason_session( $session_name = '' )
	{
		static $sess;
		
		if( !isset( $sess ) )
		{
			$sess = get_session_factory( REASON_SESSION_CLASS );
			$sess->set_session_name( !empty($session_name) ? $session_name : 'REASON_SESSION' );
		}
		
		return $sess;
	}
?>
