<?php
/*
 * merge HTTP_GET_VARS and HTTP_POST_VARS
 */
if(!defined( '__HTTP_VARS' ))
{
	define( '__HTTP_VARS' , true);
	
	function get_http_vars()
	{
		/*
		$HTTP_VARS = array();
		global $HTTP_POST_VARS, $HTTP_GET_VARS;
		if ( !empty( $HTTP_POST_VARS ) )
		{
			reset( $HTTP_POST_VARS );
			while( list( $key, $val ) = each ( $HTTP_POST_VARS ) )
				$HTTP_VARS[ $key ] = $val;
		}
		if ( !empty( $HTTP_GET_VARS ) )
		{
			reset( $HTTP_GET_VARS );
			while( list( $key, $val ) = each ( $HTTP_GET_VARS ) )
				if ( !isset( $HTTP_VARS[ $key ] ) )
					$HTTP_VARS[ $key ] = $val;
		}
		
		return $HTTP_VARS;
		*/
		return $_REQUEST;
	}
}
?>
