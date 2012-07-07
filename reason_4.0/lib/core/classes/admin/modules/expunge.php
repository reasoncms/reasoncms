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
	 * Administrative module that provides an interface to expunge entities
	 *
	 * Note that expungement is final -- it is genuine removal from the db.
	 * Unless this is what you want, it is often better to delete the entity,
	 * which is easier to recover from if you make a mistake.
	 */
	class ExpungeModule extends DefaultModule // {{{
	{
		var $expungable = false;
		/**
		 * Possible values:
		 * 
		 * - no_id_provided
		 * - insufficient_privileges
		 * - not_deleted_yet
		 * - dependencies
		 */
		var $_not_expungable_reason;
		function DeleteModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}		
		function init() // {{{
		{
			$this->admin_page->set_show( 'leftbar', false );
			if(empty($this->admin_page->id))
			{
				$this->_not_expungable_reason = 'no_id_provided';
				return false;
			}
			if(!reason_user_has_privs($this->admin_page->user_id, 'expunge'))
			{
				$this->_not_expungable_reason = 'insufficient_privileges';
				return false;
			}
			$item = new entity($this->admin_page->id);
			$user = new entity($this->admin_page->user_id);
			if(!$item->user_can_edit_field('state', $user))
			{
				$this->_not_expungable_reason = 'state_field_locked';
				return false;
			}
			if($item->get_value('state') != 'Deleted')
			{
				$this->_not_expungable_reason = 'not_deleted_yet';
				return false;
			}
			$this->expungable = $this->admin_page->is_deletable();
			if($this->expungable)
			{
				$this->_set_up_form();
			}
			else
			{
				$this->_not_expungable_reason = 'dependencies';
				return false;
			}
		} // }}}
		function _set_up_form()
		{
			$deleter = 'deleteDisco';
			$type = new entity( $this->admin_page->type_id );
			if( $type->get_value( 'custom_deleter' ) )
			{
				reason_include( 'content_deleters/' . $type->get_value( 'custom_deleter' ) );
				if(!empty($GLOBALS[ '_reason_content_deleters' ][ $type->get_value( 'custom_deleter' ) ] ) )
					$deleter = $GLOBALS[ '_reason_content_deleters' ][ $type->get_value( 'custom_deleter' ) ];
				else
					trigger_error($type->get_value( 'custom_deleter' ).' needs to record its class name in $GLOBALS[ "_reason_content_deleters" ].');
			}
			$this->disco_item = new $deleter;
			$this->disco_item->actions = array();
			$this->disco_item->set_page( $this->admin_page );
			$this->disco_item->actions[ 'delete' ] = 'Yes, Expunge and Go Back to List';
			
			$this->disco_item->actions[ 'cancel' ] = 'No, Cancel';	
			$this->disco_item->grab_info( $this->admin_page->id , $graph );
			$this->disco_item->init();
		}
		function run() // {{{
		{
			if($this->expungable)
			{
				$this->disco_item->run();
			}
			else
			{
				switch($this->_not_expungable_reason)
				{
					case 'no_id_provided':
						echo '<p>Unable to expunge item. Item may already have been expunged (sometimes this happens if you click twice on the expunge button)</p>';
						return false;
					case 'state_field_locked':
						echo '<p>This item has been locked, preventing it from being expunged. Please contact an administrator if it is important to expunge this item.</p>';
						return false;
					case 'insufficient_privileges':
						echo '<p>You do not have the privileges to expunge this item.</p>';
						return false;
					case 'not_deleted_yet':
						echo '<p>This item cannot be expunged because it has not been deleted yet</p>';
						return false;
					case 'dependencies':
						$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'NoDelete' ) ) );
						header( 'Location: ' . $link );
						die();
					default:
						trigger_error('Unknown reason given for not being able to expunge item: '.$this->_not_expungable_reason);
						echo '<p>Not able to expunge item</p>';
						return false;
				}
			}
		} // }}}
	} // }}}
?>