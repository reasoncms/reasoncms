<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once('classes/admin/admin_disco.php');
	reason_include_once('classes/admin/modules/doAssociate.php');
	
	/**
	 * The administrative module that handles the action of deleting a relationship between two entities
	 */
	class DoDisassociateModule extends DefaultModule // {{{
	{
		var $check_admin_token = true;
		
		function DoDisassociateModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}		
		function init() // {{{
		{
			$this->disco_item = new doUnassociateDisco;
			$this->disco_item->set_page( $this->admin_page );
			
			$this->disco_item->grab_all_page_requests( $this->admin_page );
			$this->disco_item->init();
			$this->admin_page->set_show( 'leftbar', false );

			$q = 'SELECT * FROM allowable_relationship WHERE id = ' . $this->admin_page->rel_id;
			$r = db_query( $q , 'Error checking allowable relationship connections' );
			$row = mysql_fetch_array( $r , MYSQL_ASSOC );
			if( $row[ 'connections' ] == 'many_to_many' || $row[ 'required' ] == 'no' )
			{
				$kludge = $this->disco_item->finish();
				$this->disco_item->handle_transition( $kludge );
			}
		} // }}}
		function run() // {{{
		{
			$this->disco_item->run();
		} // }}}
	} // }}}
?>
