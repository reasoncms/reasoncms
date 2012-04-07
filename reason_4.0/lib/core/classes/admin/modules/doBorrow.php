<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * The administrative module that handles the action of borrowing entities from other sites
	 */
	class DoBorrowModule extends DefaultModule // {{{
	{
		function DoBorrowModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			if(!reason_user_has_privs($this->admin_page->user_id, 'borrow'))
			{
				die('You do not have privileges to borrow or unborrow items');
			}
			$this->set_borrowship_first_level();
			if( $this->admin_page->is_second_level() )
			{
				$this->add_relationship_second_level();
			}

			$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Sharing' , 'id' => '' ) ) );
			header( 'Location: ' . $link );
			die();
		} // }}}
		function set_borrowship_first_level() // {{{
		{
			 $borrows_rel_id = get_borrows_relationship_id( $this->admin_page->type_id );
			 
			 if( !empty( $this->admin_page->request[ 'unborrow' ] ) )
			 {
				 //do query removing borrowship
				 delete_borrowed_relationship( $this->admin_page->site_id , $this->admin_page->id , $borrows_rel_id );
			 }
			 else
			 {
				 //do query creating borrowship
				create_relationship( $this->admin_page->site_id, $this->admin_page->id, $borrows_rel_id );
			 }
		} // }}}
		function add_relationship_second_level() //{{{
		{
			if(empty( $this->admin_page->request[ 'unborrow' ] ) )
			{
				create_relationship( $this->admin_page->request[ CM_VAR_PREFIX . 'id' ], $this->admin_page->id, $this->admin_page->request[ CM_VAR_PREFIX . 'rel_id' ] );
			}
		} // }}}
		function run() // {{{
		{
		} // }}}
	} // }}}
?>