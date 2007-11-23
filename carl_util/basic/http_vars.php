<?php
/**
 * @package carl_util
 * @subpackage basic
 */

/**
 * Pre-php4 solution for before include_once() existed
 */
if(!defined( '__HTTP_VARS' ))
{
	define( '__HTTP_VARS' , true);
	
	/**
	 * Get merged HTTP_GET_VARS and HTTP_POST_VARS
	 *
	 * This must be a relic of an era (php3?) before $_REQUEST was available...?
	 *
	 * @deprecated
	 * @todo Replace references to this function with $_REQUEST
	 */
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
