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
	
	/**
	 * The administrative module that handles the association of entities
	 */
	class DoAssociateModule extends DefaultModule // {{{
	{
		var $check_admin_token = true;
		
		function DoAssociateModule( &$page) // {{{
		{
			$this->admin_page =& $page;
		} // }}}		
		function init() // {{{
		{
			$this->disco_item = new doAssociateDisco;
			$this->disco_item->set_page( $this->admin_page );
			
			$this->disco_item->grab_all_page_requests( $this->admin_page );
			$this->disco_item->init();
			$this->admin_page->set_show( 'leftbar', false );
			
			$no_ass = $this->no_ass();

			$q = 'SELECT * FROM allowable_relationship WHERE id = ' . $this->admin_page->rel_id;
			$r = db_query( $q , 'Error checking allowable relationship connections' );
			$row = mysql_fetch_array( $r , MYSQL_ASSOC );
			if( $no_ass || $row[ 'connections' ] == 'many_to_many' || $row[ 'connections' ] == 'many_to_one' )
			{
				$kludge = $this->disco_item->finish();
				$this->disco_item->handle_transition( $kludge );
			}
		} // }}}
		function no_ass() // {{{
		{	
			$d = new DBSelector;
			
			$d->add_table( 'r' , 'relationship' );
			$d->add_table( 'ar' , 'allowable_relationship' );
			
			$d->add_relation( 'r.type = ar.id' );
			$d->add_relation( 'ar.id = ' . $this->admin_page->rel_id );
			$d->add_relation( 'r.entity_a = ' . $this->admin_page->id );

			$r = db_query( $d->get_query() , "Can't do query in FinishModule::check_required_relationships()" );
			if( mysql_fetch_array( $r ) )
				return false;
			else
				return true;
		} // }}}
		function run() // {{{
		{
			$this->disco_item->run();
		} // }}}
	} // }}}
?>
