<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'classes/admin/admin_disco.php' );
	
	/**
	 * An administrative module that handles setting deleted entities' state back to "Live".
	 */
	class UndeleteModule extends DefaultModule // {{{
	{
		/**
		 * Possible values:
		 * 
		 * - no_id_provided
		 * - insufficient_privileges
		 * - not_deleted_yet
		 * - dependencies
		 */
		var $_not_undeletable_reason;
		function UndeleteModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}		
		function init() // {{{
		{
			$this->admin_page->set_show( 'leftbar', false );
			if(empty($this->admin_page->id))
			{
				$this->_not_undeletable_reason = 'no_id_provided';
				return false;
			}
			if(!reason_user_has_privs($this->admin_page->user_id, 'publish'))
			{
				$this->_not_undeletable_reason = 'insufficient_privileges';
				return false;
			}
			$item = new entity($this->admin_page->id);
			if($item->get_value('state') != 'Deleted')
			{
				$this->_not_undeletable_reason = 'not_deleted_yet';
				return false;
			}
			
			$q = 'UPDATE entity SET state = "Live", last_edited_by = "'.$this->admin_page->user_id.'" where id = ' . $this->admin_page->id;
			db_query( $q , 'Error setting state as live in DeleteModule::init()' );
			
			//Updates the rewrites to prevent infinite redirection loop.
			reason_include_once('classes/url_manager.php');
			$urlm = new url_manager($this->admin_page->site_id);
			$urlm->update_rewrites();
			
			if( get_class( $graph->nodes[ $graph->start ] ) == 'admin_lister_node' 
				AND isset( $_SESSION[ 'listers' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ] ) 
			  )
				$link = unhtmlentities( $_SESSION[ 'listers' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ] ).
					'&unique_id=' . $this->admin_page->unique_id;
			else 	
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Lister' , 'id' => '' , 'state' => 'deleted' ) ) );
			header( 'Location: ' . $link );
			die();
		} // }}}
		function run() // {{{
		{
			switch($this->_not_undeletable_reason)
			{
				case 'no_id_provided':
					echo '<p>Unable to undelete item; it does not appear to exist.</p>';
					return false;
				case 'insufficient_privileges':
					echo '<p>You do not have the privileges to undelete (e.g. publish) this item.</p>';
					return false;
				case 'not_deleted_yet':
					echo '<p>This item cannot be undeleted because it has not been deleted yet</p>';
					return false;
				default:
					trigger_error('Unknown reason given for not being able to undelete item: '.$this->_not_undeletable_reason);
					echo '<p>Not able to expunge item</p>';
					return false;
			}
		} // }}}
	} // }}}
?>