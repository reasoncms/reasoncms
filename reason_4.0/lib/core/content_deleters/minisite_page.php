<?php
/**
 * @package reason
 * @subpackage content_deleters
 */
 	/**
 	 * Register deleter with Reason and include dependencies
 	 */
	$GLOBALS[ '_reason_content_deleters' ][ basename( __FILE__) ] = 'minisite_page_deleter';
	reason_include_once( 'classes/admin/admin_disco.php' );

	/**
	 * A content deleter that handles expungement of minisite pages
	 */
	class minisite_page_deleter extends deleteDisco
	{
		function delete_entity() // {{{
		{
			if( $this->get_value( 'id' ) )
			{
				$e = new entity( $this->get_value( 'id' ) );
				if($e->get_value('state') == 'Live' || $e->get_value('state') == 'Deleted')
				{
					$q = 'UPDATE URL_history SET deleted="yes" WHERE page_id="' . $this->get_value('id') . '"';
                    $r = db_query( $q, 'Deleting Page' );
                }
			}
			parent::delete_entity();
		} // }}}
	}
?>
