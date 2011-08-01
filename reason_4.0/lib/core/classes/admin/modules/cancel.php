<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  * @todo look into whether the image library is actually needed
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'function_libraries/images.php' );
	
	/**
	 * An administrative module that handles cancellation
	 *
	 * In the context of the administrative interface, "cancellation"
	 * means backing out of entity creation with irrevocable expungement of
	 * new, empty entity.
	 */
	class CancelModule extends DefaultModule // {{{
	{
		function CancelModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}		
		function init() // {{{
		{
			if( !empty( $this->admin_page->id ) )
			{
				$temp = new entity( $this->admin_page->id,false );
				if( $temp->get_value( 'new' ) && $temp->get_value( 'state' ) == 'Pending' && !$temp->get_value( 'name' ) && reason_user_has_privs($this->admin_page->user_id,'delete_pending')  )
				{
					reason_expunge_entity( $this->admin_page->id, $this->admin_page->user_id );
				}
			}
			if( !empty( $this->admin_page->request[ CM_VAR_PREFIX.'type_id' ] ) )
			{
				$old_vars = array();	
				foreach( $this->admin_page->request AS $key => $val )
					if( substr( $key, 0, strlen( CM_VAR_PREFIX ) ) == CM_VAR_PREFIX )
					{
						$old_vars[ substr( $key, strlen( CM_VAR_PREFIX ) ) ] = $val;
						$old_vars[ $key ] = '';
					}
				foreach( $this->admin_page->default_args AS $arg )
					if( !isset( $old_vars[ $arg ] ) )
						$old_vars[ $arg ] = '';
				$link = $this->admin_page->make_link( $old_vars );
			}
			else
			{
				$link = $this->admin_page->make_link( 
					array( 'id' => '', 'site_id' => $this->admin_page->site_id,'type_id' => $this->admin_page->type_id , 'cur_module' => 'Lister' ) );
			}
			header( 'Location: '.unhtmlentities( $link ) );
			die();
		} // }}}
		function run() // {{{
		{
		} // }}}
	} // }}}
?>
